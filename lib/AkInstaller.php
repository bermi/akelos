<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2006, Akelos Media, S.L.  & Bermi Ferrer Martinez |
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
 * @package AkelosFramework
 * @subpackage Administration
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

require_once(AK_LIB_DIR.DS.'Ak.php');
require_once(AK_LIB_DIR.DS.'AkActiveRecord.php');
file_exists(AK_APP_DIR.DS.'shared_model.php') ? require_once(AK_APP_DIR.DS.'shared_model.php') : null;

class AkInstaller
{
    var $db;
    var $data_dictionary;
    var $debug = false;
    var $available_tables = array();
    var $vervose = true;

    function AkInstaller($db_connection = null)
    {
        if(empty($db_connection)){
            $this->db =& Ak::db();
        }else {
            $this->db =& $db_connection;
        }
        
        $this->available_tables = $this->getAvailableTables();
        
        $this->db->debug =& $this->debug;

        $this->data_dictionary = NewDataDictionary($this->db);
    }
    
    function install($version = null, $options = array())
    {
        return $this->_upgradeOrDowngrade('up', $version, $options);
    }
    
    
    function uninstall($version = null, $options = array())
    {
        return $this->_upgradeOrDowngrade('down', $version, $options);
    }
    

    function _upgradeOrDowngrade($action, $version = null, $options = array())
    {
        if(in_array('quiet',$options) && AK_ENVIRONMENT == 'development'){
            $this->vervose = false;
        }elseif(!empty($this->vervose) && AK_ENVIRONMENT == 'development'){
            $this->db->debug = true;
        }
        
        $current_version = $this->getInstalledVersion();
        $available_versions = $this->getAvailableVersions();
        
        $action = stristr($action,'down') ? 'down' : 'up';
        
        if($action == 'up'){
            $newest_version = max($available_versions);
            $version = is_numeric($version) ? $version : $newest_version;
            
            if($version <= $newest_version && $version > $current_version){
                $versions = array_slice($available_versions, array_shift(array_keys($available_versions,$current_version))+($current_version ? 1 : 0));
            }
            
        }else{
            $version = is_numeric($version) ? $version : 1;
            $installed_versions = array_slice(array_reverse($available_versions), array_shift(array_keys(array_reverse($available_versions),$current_version)));
            $versions = array_slice($installed_versions, array_shift(array_keys($installed_versions,$current_version)));
            if(!in_array($version, $versions)){
                return false;
            }
        }
        
        if(AK_CLI && !empty($this->vervose) && AK_ENVIRONMENT == 'development'){
            echo Ak::t(ucfirst($action).'grading');
        }
        
        if(!empty($versions) && is_array($versions)){
            foreach ($versions as $version){
                if(!$this->_runInstallerMethod($action, $version, $options)){
                    return false;
                }
            }
        }else{
            return false;
        }

        return true;
    }
    
    
    function installVersion($version, $options = array())
    {
        return $this->_runInstallerMethod('up', $version, $options);
    }
    
    function uninstallVersion($version, $options = array())
    {
        return $this->_runInstallerMethod('down', $version, $options);
    }
    
    /**
     * Runs a a dow_1, up_3 method and wraps it into a transaction.
     */
    function _runInstallerMethod($method_prefix, $version, $options = array(), $version_number = null)
    {
        $method_name = $method_prefix.'_'.$version;
        if(!method_exists($this, $method_name)){
            return false;
        }
        
        $version_number = empty($version_number) ? ($method_prefix=='down' ? $version-1 : $version) : $version_number;
        
        $this->transactionStart();
        if($this->$method_name($options) === false){
            $this->transactionFail();
        }
        $success = !$this->transactionHasFailed();
        $this->transactionComplete();
        if($success){
            $this->setInstalledVersion($version_number);
        }
        return $success;
    }
    
    function getInstallerName()
    {
        return str_replace('installer','',strtolower(get_class($this)));
    }

    
    function _versionPath()
    {
        return AK_APP_DIR.DS.'installers'.DS.$this->getInstallerName().'_version.txt';
    }

    
    function getInstalledVersion()
    {
        $version_file = $this->_versionPath();
        if(!is_file($version_file)){
            $this->setInstalledVersion(0);
        }
        return Ak::file_get_contents($this->_versionPath());
    }

    
    function setInstalledVersion($version)
    {
        return Ak::file_put_contents($this->_versionPath(), $version);
    }

    
    function getAvailableVersions()
    {
        $versions = array();
        foreach(get_class_methods($this) as $method_name){
            if(preg_match('/^up_([0-9]*)$/',$method_name, $match)){
                $versions[] = $match[1];
            }
        }
        sort($versions);
        return $versions;
    }
    

    function modifyTable($table_name, $column_options = null, $table_options = array())
    {
        return $this->_createOrModifyTable($table_name, $column_options, $table_options);
    }
    
    /**
     * Adds a new column to the table called $table_name 
     */
    function addColumn($table_name, $column_details)
    {
        $column_details = $this->_getColumnsAsAdodbDataDictionaryString($column_details);
        return $this->data_dictionary->ExecuteSQLArray($this->data_dictionary->AddColumnSQL($table_name, $column_details));
    }
    
    function changeColumn($table_name, $column_details)
    {
        $column_details = $this->_getColumnsAsAdodbDataDictionaryString($column_details);
        return $this->data_dictionary->ExecuteSQLArray($this->data_dictionary->AlterColumnSQL($table_name, $column_details));
    }
    
    function removeColumn($table_name, $column_name)
    {
        return $this->data_dictionary->ExecuteSQLArray($this->data_dictionary->DropColumnSQL($table_name, $column_details));
    }
    
    function renameColumn($table_name, $old_column_name, $new_column_name)
    {
        if(!strstr($this->db->databaseType,'mysql')){
            trigger_error(Ak::t('Column renaming is only supported when using MySQL databases'), E_USER_ERROR);
        }
        return $this->data_dictionary->ExecuteSQLArray($this->data_dictionary->RenameColumnSQL($table_name, $old_column_name, $new_column_name));
    }
    

    function createTable($table_name, $column_options = null, $table_options = array())
    {
        if($this->tableExists($table_name)){
            trigger_error(Ak::t('Table %table_name already exists on the database', array('%table_name'=>$table_name)), E_USER_NOTICE);
            return false;
        }
        return $this->_createOrModifyTable($table_name, $column_options, $table_options);
    }
    
    function _createOrModifyTable($table_name, $column_options = null, $table_options = array())
    {
        if(empty($column_options) && $this->_loadDbDesignerDbSchema()){
            $column_options = $this->db_designer_schema[$table_name];
        }elseif(empty($column_options)){
            trigger_error(Ak::t('You must supply details for the table you are creating.'), E_USER_ERROR);
            return false;
        }
        
        $column_options = is_string($column_options) ? array('columns'=>$column_options) : $column_options;

        $default_column_options = array(
        'sequence_table' => false
        );
        $column_options = array_merge($default_column_options, $column_options);

        $default_table_options = array(
        'mysql' => 'TYPE=InnoDB',
        //'REPLACE'
        );
        $table_options = array_merge($default_table_options, $table_options);

        $column_string = $this->_getColumnsAsAdodbDataDictionaryString($column_options['columns']);

        $result = $this->data_dictionary->ExecuteSQLArray($this->data_dictionary->ChangeTableSQL($table_name, str_replace(array(' UNIQUE ', ' INDEX '), '', $column_string), $table_options));
        
        if($result){
            $this->available_tables[] = $table_name;
        }
        
        $columns_to_index = $this->_getColumnsToIndex($column_string);
        $unique_value_columns = $this->_getUniqueValueColumns($column_string);

        foreach ($columns_to_index as $column_to_index){
            $this->addIndex($table_name, $column_to_index.(in_array($column_to_index, $unique_value_columns) ? ' UNIQUE' : ''));
        }

        if(isset($column_options['index_columns'])){
            $this->addIndex($table_name, $column_options['index_columns']);

        }
        if($column_options['sequence_table'] || $this->_requiresSequenceTable($column_string)){
            $this->createSequence($table_name);
        }

        return $result;
    }

    function dropTable($table_name, $options = array())
    {
        $result = $this->tableExists($table_name) ? $this->db->Execute('DROP TABLE '.$table_name) : true;
        if($result){
            unset($this->available_tables[array_search($table_name, $this->available_tables)]);
            if(!empty($options['sequence'])){
                $this->dropSequence($table_name);
            }
        }
    }

    function addIndex($table_name, $columns)
    {
        return $this->tableExists($table_name) ? $this->data_dictionary->ExecuteSQLArray($this->data_dictionary->CreateIndexSQL('idx_'.$table_name.'_'.$columns, $table_name, $columns)) : false;
    }
    
    function removeIndex($table_name, $columns)
    {
        return $this->tableExists($table_name) ? $this->data_dictionary->ExecuteSQLArray($this->data_dictionary->DropIndexSQL('idx_'.$table_name.'_'.$columns, $table_name)) : false;
    }

    function createSequence($table_name)
    {
        $result = $this->tableExists('seq_'.$table_name) ? false : $this->db->CreateSequence('seq_'.$table_name);
        $this->available_tables[] = 'seq_'.$table_name;
        return $result;
    }

    function dropSequence($table_name)
    {
        $result = $this->tableExists('seq_'.$table_name) ? $this->db->DropSequence('seq_'.$table_name) : true;
        if($result){
            unset($this->available_tables[array_search('seq_'.$table_name, $this->available_tables)]);

        }
        return $result;
    }

    function getAvailableTables()
    {
        if(empty($this->available_tables)){
            $this->available_tables = array_diff((array)$this->db->MetaTables(), array(''));
        }        
        return $this->available_tables;
    }
    
    function tableExists($table_name)
    {
        return in_array($table_name,$this->getAvailableTables());
    }

    function _getColumnsAsAdodbDataDictionaryString($columns)
    {
        $columns_string = is_array($columns) ? join(', ',$columns) : $columns;
        $equivalences = array(
        '/ ((limit|max|length) ?= ?)([0-9]+)([ \n\r,]+)/'=> ' (\3) ',
        '/([ \n\r,]+)default([ =]+)([^\'^,^\n]+)/i'=> ' DEFAULT \'\3\'',
        '/([ \n\r,]+)(integer|int)([( \n\r,]+)/'=> '\1 I \3',
        '/([ \n\r,]+)foat([( \n\r,]+)/'=> '\1 F \2',
        '/([ \n\r,]+)datetime([( \n\r,]+)/'=> '\1 T \2',
        '/([ \n\r,]+)date([( \n\r,]+)/'=> '\1 D \2',
        '/([ \n\r,]+)timestamp([( \n\r,]+)/'=> '\1 T \2',
        '/([ \n\r,]+)time([( \n\r,]+)/'=> '\1 T \2',
        '/([ \n\r,]+)text([( \n\r,]+)/'=> '\1 XL \2',
        '/([ \n\r,]+)string([( \n\r,]+)/'=> '\1 C \2',
        '/([ \n\r,]+)binary([( \n\r,]+)/'=> '\1 B \2',
        '/([ \n\r,]+)boolean([( \n\r,]+)/'=> '\1 I1(1) \2',
        '/ NOT( |_)?NULL/i'=> ' NOTNULL',
        '/ AUTO( |_)?INCREMENT/i'=> ' AUTO ',
        '/ +/'=> ' ',
        '/ ([\(,]+)/'=> '\1',
        '/ INDEX| IDX/i'=> ' INDEX ',
        '/ ((PRIMARY( |_)?)?KEY|pk)/i'=> ' KEY',
        );
        
        return trim(preg_replace(array_keys($equivalences),array_values($equivalences), ' '.$columns.' '), ' ');
    }

    function _getColumnsToIndex($column_string)
    {
        $columns_to_index = array();
        foreach (explode(',',$column_string.',') as $column){
            if(preg_match('/([A-Za-z0-9_]+) (.*) INDEX (.*)$/',$column,$match)){
                $columns_to_index[] = $match[1];
            }
        }
        return $columns_to_index;
    }

    function _getUniqueValueColumns($column_string)
    {
        $unique_columns = array();
        foreach (explode(',',$column_string.',') as $column){
            if(preg_match('/([A-Za-z0-9_]+) (.*) UNIQUE (.*)$/',$column,$match)){
                $unique_columns[] = $match[1];
            }
        }
        return $unique_columns;
    }

    function _requiresSequenceTable($column_string)
    {
        if(strstr($this->db->databaseType,'mysql')){
            return false;
        }
        foreach (explode(',',$column_string.',') as $column){
            if(preg_match('/([A-Za-z0-9_]+) (.*) AUTO (.*)$/',$column)){
                return true;
            }
            if(preg_match('/^id /',$column)){
                return true;
            }
        }
        return false;
    }
    
    
    /**
    * Transaction support for database operations
    *
    * Transactions are enabled automatically for Intaller objects, But you can nest transactions within models.
    * This transactions are nested, and only the othermost will be executed
    *
    *   $UserInstalller->transactionStart();
    *   $UserInstalller->addTable('id, name');
    *
    *    if(!isCompatible()){ 
    *       $User->transactionFail();
    *    }
    *
    *   $User->transactionComplete();
    */
    function transactionStart()
    {
        return $this->db->StartTrans();
    }

    function transactionComplete()
    {
        return $this->db->CompleteTrans();
    }

    function transactionFail()
    {
        return $this->db->FailTrans();
    }

    function transactionHasFailed()
    {
        return $this->db->HasFailedTrans();
    }
    
    
    function _loadDbDesignerDbSchema()
    {
        if($path = $this->_getDbDesignerFilePath()){
            $this->db_designer_schema = Ak::convert('DBDesigner','AkelosDatabaseDesign', Ak::file_get_contents($path));
            return !empty($this->db_designer_schema);
        }
        return false;
    }
    
    function _getDbDesignerFilePath()
    {
        $path = AK_APP_DIR.DS.'installers'.DS.$this->getInstallerName().'.xml';
        return file_exists($path) ? $path : false;
    }
    
    function execute($sql)
    {
        return $this->db->Execute($sql);
    }
    
    
    function usage()
    {
        return Ak::t("Description:
    Database migrations is a sort of SCM like subversion, but for database settings.

    The migration command takes the name of an installer located on your 
    /app/intallers folder and runs one of the folowing commands:
    
    - \"install\" + (options version number): Will update to the provided version 
    number or to the latest one in no version is given.
    
    - \"uninstall\" + (options version number): Will downgrade to the provided 
    version number or to the lowest one in no version is given.

    Current version number will be sored at app/installers/installer_name_version.txt.

Example:
    >> migrate framework install

    Will run the default database schema for the framework. 
    This generates the tables for handling database driven sessions and cache.

");
    }


}


?>
