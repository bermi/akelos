<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2006, Akelos Media, S.L.  & Bermi Ferrer Martinez |
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
 * @package ActiveSupport
 * @subpackage Installer
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

require_once(AK_LIB_DIR.DS.'Ak.php');
require_once(AK_LIB_DIR.DS.'AkObject.php');
require_once(AK_LIB_DIR.DS.'AkActiveRecord.php');
file_exists(AK_APP_DIR.DS.'shared_model.php') ? require_once(AK_APP_DIR.DS.'shared_model.php') : null;
defined('AK_APP_INSTALLERS_DIR') ? null : define('AK_APP_INSTALLERS_DIR', AK_APP_DIR.DS.'installers');

defined('AK_VERBOSE_INSTALLER') ? null : define('AK_VERBOSE_INSTALLER', AK_DEV_MODE);

// Install scripts might use more RAM than normal requests.
@ini_set('memory_limit', -1);

/**
 *
 * == Column Types ==
 *
 * Akelos natively supports the following column data types.
 *
 * integer|int, float, decimal,
 * string, text,
 * datetime|timestamp, date,
 * binary,
 * boolean
 *
 * Caution: Because boolean is virtual tinyint on mysql, you can't use tinyint for other things!
 *
 *
 * == Default settings for columns ==
 *
 * AkInstaller suggests some default values for the column-details.
 *
 * So
 * <code>
 *     $this->createTable('Post','title,body,created_at,is_draft');
 * </code>
 *
 * will actually create something like this:
 *
 *     title => string(255), body => text, created_at => datetime, is_draft => boolean not null default 0 index
 *
 *
 * column_name                    | default setting
 * -------------------------------+--------------------------------------------
 * id                             | integer not null auto_increment primary_key
 * *_id,*_by                      | integer index
 * description,content,body       | text
 * position                       | integer index
 * *_count                        | integer default 0
 * lock_version                   | integer default 1
 * *_at                           | datetime
 * *_on                           | date
 * is_*,has_*,do_*,does_*,are_*   | boolean not null default 0 index
 * *somename                      | multilingual column => en_somename, es_somename
 * default                        | string
 *
 */
class AkInstaller extends AkObject
{
    var $db;
    var $data_dictionary;
    var $available_tables = array();
    var $vervose = AK_VERBOSE_INSTALLER;
    var $module;
    var $warn_if_same_version = true;
    var $use_transactions = true;

    function AkInstaller($db_connection = null)
    {
        if(empty($db_connection)){
            $this->db =& AkDbAdapter::getInstance();
        }else {
            $this->db =& $db_connection;
        }
        AkDbSchemaCache::clearAll();
        $this->data_dictionary =& $this->db->getDictionary();
        $this->available_tables = $this->getAvailableTables();
    }

    function install($version = null, $options = array())
    {
        $version = (is_null($version)) ? max($this->getAvailableVersions()) : $version;
        return $this->_upgradeOrDowngrade('up',  $version , $options);
    }

    function up($version = null, $options = array())
    {
        return $this->_upgradeOrDowngrade('up', $version, $options);
    }

    function uninstall($version = null, $options = array())
    {
        $version = (is_null($version)) ? 0 : $version;
        return $this->_upgradeOrDowngrade('down', $version, $options);
    }

    function down($version = null, $options = array())
    {
        return $this->_upgradeOrDowngrade('down', $version, $options);
    }

    function _upgradeOrDowngrade($action, $version = null, $options = array())
    {
        AkDbSchemaCache::clearAll();
        if(in_array('quiet',$options) && AK_DEV_MODE){
            $this->vervose = false;
        }elseif(!empty($this->vervose) && AK_DEV_MODE){
            $this->debug(true);
        }

        $current_version = $this->getInstalledVersion($options);
        $available_versions = $this->getAvailableVersions();

        $action = stristr($action,'down') ? 'down' : 'up';

        if($action == 'up'){
            $newest_version = max($available_versions);
            $version = isset($version) && is_numeric($version) ? $version :
            (isset($version[0]) && is_numeric($version[0]) ? $version[0] : $newest_version);

            $versions = range($current_version+1,$version);

            if($current_version > $version){
                echo Ak::t("You can't upgrade to version %version on the installer %installer_name, when you are currently on version %current_version", array('%version'=>$version,'%current_version'=>$current_version, '%installer_name' => $this->getInstallerName()))."\n";
                return false;
            }
        }else{
            $version = isset($version) && is_numeric($version) ? $version :
            (isset($version[0]) && is_numeric($version[0]) ? $version[0] : 0);

            $versions = range($current_version, empty($version) ? 1 : $version+1);

            if($current_version == 0){
                return true;
            }elseif($current_version < $version){
                echo Ak::t("You can't downgrade to version %version on the installer %installer_name, when you just have installed version %current_version", array('%version'=>$version,'%current_version'=>$current_version, '%installer_name' => $this->getInstallerName()))."\n";
                return false;
            }
        }

        if($this->warn_if_same_version && $current_version == $version && AK_ENVIRONMENT != 'setup'){
            echo Ak::t("Can't go %action to version %version on the installer %installer_name, you're already on version %version", array('%version'=>$version, '%installer_name' => $this->getInstallerName(), '%action' => $action))."\n";
            return false;
        }

        if(AK_CLI && !empty($this->vervose) && (AK_DEV_MODE || AK_TEST_MODE)){
            echo "\n".Ak::t('[%installer_name] '.ucfirst($action).'grading to version %version', array('%version'=>$version, '%installer_name'=>$this->getInstallerName()));
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
            return $method_prefix == 'down';
        }

        $version_number = empty($version_number) ? ($method_prefix=='down' ? $version-1 : $version) : $version_number;

        $this->transactionStart();

        if($this->$method_name($options) === false){
            $this->log($this->getInstallerName().': returned false');
            $this->transactionFail();
        }
        $success = !$this->transactionHasFailed();
        $this->transactionComplete();
        if($success){
            $this->setInstalledVersion($version_number, $options);
        }
        return $success;
    }

    function getInstallerName()
    {
        return str_replace('installer','',strtolower(get_class($this)));
    }

    function _versionPath($options = array())
    {
        $mode = empty($options['mode']) ? AK_ENVIRONMENT : $options['mode'];
        return AK_TMP_DIR.DS.'installer_versions'.DS.(empty($this->module)?'':$this->module.DS).$mode.'_'.$this->getInstallerName().'_version.txt';
    }

    // DEPRECATED
    function _versionPath_Deprecated($options = array())
    {
        $mode = empty($options['mode']) ? AK_ENVIRONMENT : $options['mode'];
        return AK_APP_INSTALLERS_DIR.DS.(empty($this->module)?'':$this->module.DS).'versions'.DS.$mode.'_'.$this->getInstallerName().'_version.txt';
    }

    // DEPRECATED
    function _moveOldVersionsFileToNewLocation($options)
    {
        $old_filename = $this->_versionPath_Deprecated($options);
        if (is_file($old_filename)){
            $this->setInstalledVersion(Ak::file_get_contents($old_filename),$options);
            Ak::file_delete($old_filename);
            Ak::file_put_contents(AK_APP_INSTALLERS_DIR.DS.'versions'.DS.'NOTE',"Version information is now stored in the temp folder. \n\rYou can safely move this files here over there to tmp/installer_versions/* or delete this directory if empty.");
        }
    }

    function getInstalledVersion($options = array())
    {
        $this->_createMigrationsTable();
        $version = $this->db->selectValue(array('SELECT version FROM akelos_migrations WHERE name=?',$this->getInstallerName()));

        if($version==NULL) {

            $version_file = $this->_versionPath($options);

            $this->_moveOldVersionsFileToNewLocation($options);

            if(!is_file($version_file)){
                $version = 0;
                $this->setInstalledVersion($version, $options);
            } else {
                $oldfile=$this->_versionPath($options);
                $version = Ak::file_get_contents($oldfile);
                //if(!$tableExists) {
                //    $this->_createMigrationsTable();
                //}
                copy($oldfile,$oldfile.'.backup');
                unlink($oldfile);
                $this->log('message','got old version from file:'.$oldfile.'='.$version.' moved to backup-file:'.$oldfile.'.backup');
                $this->setInstalledVersion($version, $options);
                //$this->db->execute(array('INSERT INTO akelos_migrations (name,version,created_at) VALUES (?,?,?)',$this->getInstallerName(),$version,Ak::getDate()));
            }

        }
        $this->log('Installed version of '.$this->getInstallerName().':'.$version);
        return $version;
    }

    function _createMigrationsTable()
    {
        if(!$this->tableExists('akelos_migrations')) {
            AkDbSchemaCache::clearAll();
            $this->data_dictionary =& $this->db->getDictionary();
            $this->available_tables = $this->getAvailableTables();
            $this->createTable('akelos_migrations','id, name, version int');
            $this->addIndex('akelos_migrations','UNIQUE name','unq_name');
        }
    }

    function setInstalledVersion($version, $options = array())
    {
        //if(!$this->tableExists('akelos_migrations')) {
            $this->_createMigrationsTable();
        //}
        /**
         * this will produce an error if the unique index on name is violated, then we update
         */
        $this->log('Setting version of '.$this->getInstallerName().' to '.$version);
        $old_version=$this->db->selectValue(array('SELECT version from akelos_migrations WHERE name = ?', $this->getInstallerName()));
        if($old_version !== null || !@$this->db->execute(array('INSERT INTO akelos_migrations (version,created_at,name) VALUES (?,?,?)',$version,Ak::getDate(),$this->getInstallerName()))) {
            return @$this->db->execute(array('UPDATE akelos_migrations SET version=?, updated_at=? WHERE name=?',$version,Ak::getDate(),$this->getInstallerName()));
        }

        return true;

    }


    function getAvailableVersions()
    {
        $versions = array();
        foreach(get_class_methods($this) as $method_name){
            if(preg_match('/^up_([0-9]*)$/',$method_name, $match)){
                $versions[] = intval($match[1]);
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
        $this->clearSchemaCacheForTable($table_name);
        $this->timestamps = false;
        $column_details = $this->_getColumnsAsAdodbDataDictionaryString($column_details);
        return $this->data_dictionary->ExecuteSQLArray($this->data_dictionary->AddColumnSQL($table_name, $column_details));
    }

    function changeColumn($table_name, $column_details)
    {
        $this->clearSchemaCacheForTable($table_name);
        $this->timestamps = false;
        $column_details = $this->_getColumnsAsAdodbDataDictionaryString($column_details);
        return $this->data_dictionary->ExecuteSQLArray($this->data_dictionary->AlterColumnSQL($table_name, $column_details));
    }

    function removeColumn($table_name, $column_name)
    {
        $this->clearSchemaCacheForTable($table_name);
        return $this->data_dictionary->ExecuteSQLArray($this->data_dictionary->DropColumnSQL($table_name, $column_name));
    }

    function renameColumn($table_name, $old_column_name, $new_column_name)
    {
        $this->clearSchemaCacheForTable($table_name);
        return $this->db->renameColumn($table_name, $old_column_name, $new_column_name);
    }


    function createTable($table_name, $column_options = null, $table_options = array())
    {
        if($this->tableExists($table_name)){
            trigger_error(Ak::t('Table %table_name already exists on the database', array('%table_name'=>$table_name)), E_USER_NOTICE);
            return false;
        }
        $this->timestamps = (!isset($table_options['timestamp']) || (isset($table_options['timestamp']) && $table_options['timestamp'])) &&
        (!strstr($column_options, 'created') && !strstr($column_options, 'updated'));
        return $this->_createOrModifyTable($table_name, $column_options, $table_options);
    }

    function clearSchemaCacheForTable($table_name)
    {
        require_once(AK_LIB_DIR.DS.'AkActiveRecord'.DS.'AkDbSchemaCache.php');
        AkDbSchemaCache::clear($table_name);
    }

    function _createOrModifyTable($table_name, $column_options = null, $table_options = array())
    {
        $this->clearSchemaCacheForTable($table_name);
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

        $create_or_alter_table_sql = $this->data_dictionary->ChangeTableSQL($table_name, str_replace(array(' UNIQUE', ' INDEX', ' FULLTEXT', ' HASH'), '', $column_string), $table_options);
        $result = $this->data_dictionary->ExecuteSQLArray($create_or_alter_table_sql, false);

        if($result){
            $this->available_tables[] = $table_name;
        }else{
            trigger_error(Ak::t("Could not create or alter table %name using the SQL \n--------\n%sql\n--------\n", array('%name'=>$table_name, '%sql'=>$create_or_alter_table_sql[0])), E_USER_ERROR);
        }

        $columns_to_index = $this->_getColumnsToIndex($column_string);

        foreach ($columns_to_index as $column_to_index => $index_type){
            $this->addIndex($table_name, $column_to_index.($index_type != 'INDEX' ? ' '.$index_type : ''));
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
        require_once(AK_LIB_DIR.DS.'AkActiveRecord'.DS.'AkDbSchemaCache.php');
        //AkDbSchemaCache::clear(AkInflector::classify($table_name));
        AkDbSchemaCache::clear($table_name);

        $result = $this->tableExists($table_name) ? $this->db->execute('DROP TABLE '.$table_name) : 1;
        if($result){
            unset($this->available_tables[array_search($table_name, $this->available_tables)]);
            if(!empty($options['sequence'])){
                $this->dropSequence($table_name);
            }

        }
    }

    function dropTables()
    {
        $args = func_get_args();
        if(!empty($args)){
            $num_args = count($args);
            $options = $num_args > 1 && is_array($args[$num_args-1]) ? array_shift($args) : array();
            $tables = count($args) > 1 ? $args : (is_array($args[0]) ? $args[0] : Ak::toArray($args[0]));
            foreach ($tables as $table){
                $this->dropTable($table, $options);
            }
        }
    }

    function addIndex($table_name, $columns, $index_name = '')
    {
        $index_name = ($index_name == '') ? 'idx_'.$table_name.'_'.$columns : $index_name;
        $index_options = array();
        if(preg_match('/(UNIQUE|FULLTEXT|HASH)/',$columns,$match)){
            $columns = trim(str_replace($match[1],'',$columns),' ');
            $index_options[] = $match[1];
        }
        return $this->tableExists($table_name) ? $this->data_dictionary->ExecuteSQLArray($this->data_dictionary->CreateIndexSQL($index_name, $table_name, $columns, $index_options)) : false;
    }

    function removeIndex($table_name, $columns_or_index_name)
    {
        require_once(AK_LIB_DIR.DS.'AkActiveRecord'.DS.'AkDbSchemaCache.php');
        AkDbSchemaCache::clear($table_name);
        if(!$this->tableExists($table_name)){
            return false;
        }
        $available_indexes = $this->db->getIndexes($table_name);
        $index_name = isset($available_indexes[$columns_or_index_name]) ? $columns_or_index_name : 'idx_'.$table_name.'_'.$columns_or_index_name;
        if(!isset($available_indexes[$index_name])){
            trigger_error(Ak::t('Index %index_name does not exist.', array('%index_name'=>$index_name)), E_USER_NOTICE);
            return false;
        }
        return $this->data_dictionary->ExecuteSQLArray($this->data_dictionary->DropIndexSQL($index_name, $table_name));
    }

    function dropIndex($table_name, $columns_or_index_name)
    {
        $this->clearSchemaCacheForTable($table_name);
        return $this->removeIndex($table_name,$columns_or_index_name);
    }

    function createSequence($table_name)
    {
        $this->clearSchemaCacheForTable($table_name);
        $result = $this->tableExists('seq_'.$table_name) ? false : $this->db->connection->CreateSequence('seq_'.$table_name);
        $this->available_tables[] = 'seq_'.$table_name;
        return $result;
    }

    function dropSequence($table_name)
    {
        $this->clearSchemaCacheForTable($table_name);
        $result = $this->tableExists('seq_'.$table_name) ? $this->db->connection->DropSequence('seq_'.$table_name) : true;
        if($result){
            unset($this->available_tables[array_search('seq_'.$table_name, $this->available_tables)]);

        }
        return $result;
    }

    function getAvailableTables()
    {
        return $this->available_tables = $this->db->availableTables(true);
    }

    function tableExists($table_name)
    {
        return in_array($table_name,$this->getAvailableTables());
    }

    function _getColumnsAsAdodbDataDictionaryString($columns)
    {
        $columns = $this->_setColumnDefaults($columns);
        $this->_ensureColumnNameCompatibility($columns);

        $equivalences = array(
        '/ ((limit|max|length) ?= ?)([0-9]+)([ \n\r,]+)/'=> ' (\3) ',
        '/([ \n\r,]+)default([ =]+)([^\'^,^\n]+)/i'=> ' DEFAULT \'\3\'',
        '/([ \n\r,]+)(integer|int)([( \n\r,]+)/'=> '\1 I \3',
        '/([ \n\r,]+)float([( \n\r,]+)/'=> '\1 F \2',
        '/([ \n\r,]+)decimal([( \n\r,]+)/'=> '\1 N \2',
        '/([ \n\r,]+)datetime([( \n\r,]+)/'=> '\1 T \2',
        '/([ \n\r,]+)date([( \n\r,]+)/'=> '\1 D \2',
        '/([ \n\r,]+)timestamp([( \n\r,]+)/'=> '\1 T \2',
        '/([ \n\r,]+)time([( \n\r,]+)/'=> '\1 T \2',
        '/([ \n\r,]+)text([( \n\r,]+)/'=> '\1 XL \2',
        '/([ \n\r,]+)string([( \n\r,]+)/'=> '\1 C \2',
        '/([ \n\r,]+)binary([( \n\r,]+)/'=> '\1 B \2',
        '/([ \n\r,]+)boolean([( \n\r,]+)/'=> '\1 L'.($this->db->type()=='mysql'?'(1)':'').' \2',
        '/ NOT( |_)?NULL/i'=> ' NOTNULL',
        '/ AUTO( |_)?INCREMENT/i'=> ' AUTO ',
        '/ +/'=> ' ',
        '/ ([\(,]+)/'=> '\1',
        '/ INDEX| IDX/i'=> ' INDEX ',
        '/ UNIQUE/i'=> ' UNIQUE ',
        '/ HASH/i'=> ' HASH ',
        '/ FULL_?TEXT/i'=> ' FULLTEXT ',
        '/ ((PRIMARY( |_)?)?KEY|pk)/i'=> ' KEY',
        );

        return trim(preg_replace(array_keys($equivalences),array_values($equivalences), ' '.$columns.' '), ' ');
    }

    function _setColumnDefaults($columns)
    {
        $columns = Ak::toArray($columns);
        foreach ((array)$columns as $column){
            $column = trim($column, "\n\t\r, ");
            if(!empty($column)){
                $single_columns[$column] = $this->_setColumnDefault($column);
            }
        }
        if(!empty($this->timestamps) && !isset($single_columns['created_at']) &&  !isset($single_columns['updated_at'])){
            $single_columns['updated_at'] = $this->_setColumnDefault('updated_at');
            $single_columns['created_at'] = $this->_setColumnDefault('created_at');
        }
        return join(",\n", $single_columns);
    }

    function _setColumnDefault($column)
    {
        return $this->_needsDefaultAttributes($column) ? $this->_setDefaultAttributes($column) : $column;
    }

    function _needsDefaultAttributes($column)
    {
        return preg_match('/^(([A-Z0-9_\(\)]+)|(.+ string[^\(.]*)|(\*.*))$/i',$column);
    }

    function _setDefaultAttributes($column)
    {
        $rules = $this->getDefaultColumnAttributesRules();
        foreach ($rules as $regex=>$replacement){
            if(is_string($replacement)){
                $column = preg_replace($regex,$replacement,$column);
            }elseif(preg_match($regex,$column,$match)){
                $column = call_user_func_array($replacement,$match);
            }
        }
        return $column;
    }

    /**
     * Returns a key => value pair of regular expressions that will trigger methods
     * to cast database columns to their respective default values or a replacement expression.
     */
    function getDefaultColumnAttributesRules()
    {
        return array(
        '/^\*(.*)$/i' => array(&$this,'_castToMultilingualColumn'),
        '/^(description|content|body|details)$/i' => '\1 text',
        '/^(lock_version)$/i' => '\1 integer default \'1\'',
        '/^(.+_count)$/i' => '\1 integer default \'0\'',
        '/^(id)$/i' => 'id integer not null auto_increment primary_key',
        '/^(.+)_(id|by)$/i' => '\1_\2 integer index',
        '/^(position)$/i' => '\1 integer index',
        '/^(.+_at)$/i' => '\1 datetime',
        '/^(.+_on)$/i' => '\1 date',
        '/^(is_|has_|do_|does_|are_)([A-Z0-9_]+)$/i' => '\1\2 boolean not null default \'0\' index', //
        '/^([A-Z0-9_]+) *(\([0-9]+\))?$/i' => '\1 string\2', // Everything else will default to string
        '/^((.+ )string([^\(.]*))$/i' => '\2string(255)\3', // If we don't set the string lenght it will fail, so if not present will set it to 255
        );
    }

    function _castToMultilingualColumn($found, $column)
    {
        $columns = array();
        foreach (Ak::langs() as $lang){
            $columns[] = $lang.'_'.ltrim($column);
        }
        return $this->_setColumnDefaults($columns);
    }

    function _getColumnsToIndex($column_string)
    {
        $columns_to_index = array();
        foreach (explode(',',$column_string.',') as $column){
            if(preg_match('/([A-Za-z0-9_]+) (.*) (INDEX|UNIQUE|FULLTEXT|HASH) ?(.*)$/i',$column,$match)){
                $columns_to_index[$match[1]] = $match[3];
            }
        }
        return $columns_to_index;
    }

    function _getUniqueValueColumns($column_string)
    {
        $unique_columns = array();
        foreach (explode(',',$column_string.',') as $column){
            if(preg_match('/([A-Za-z0-9_]+) (.*) UNIQUE ?(.*)$/',$column,$match)){
                $unique_columns[] = $match[1];
            }
        }
        return $unique_columns;
    }

    function _requiresSequenceTable($column_string)
    {
        if(in_array($this->db->type(),array('mysql','postgre'))){
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
        return ($this->use_transactions ? $this->db->startTransaction() : true);
    }

    function transactionComplete()
    {
        return ($this->use_transactions ? $this->db->stopTransaction() : true);
    }

    function transactionFail()
    {
        return ($this->use_transactions ? $this->db->failTransaction() : true);
    }

    function transactionHasFailed()
    {
        return ($this->use_transactions ? $this->db->hasTransactionFailed() : false);
    }


    /**
     * Promts for a variable on console scripts
     */
    function promptUserVar($message, $options = array())
    {
        $f = fopen("php://stdin","r");
        $default_options = array(
        'default' => null,
        'optional' => false,
        );

        $options = array_merge($default_options, $options);

        echo "\n".$message.(empty($options['default'])?'': ' ['.$options['default'].']').': ';
        $user_input = fgets($f, 25600);
        $value = trim($user_input,"\n\r\t ");
        $value = empty($value) ? $options['default'] : $value;
        if(empty($value) && empty($options['optional'])){
            echo "\n\nThis setting is not optional.";
            fclose($f);
            return AkInstaller::promptUserVar($message, $options);
        }
        fclose($f);
        return empty($value) ? $options['default'] : $value;
    }

    //DEPRECATED
    function promtUserVar($message, $options = array())
    {
        trigger_error(Ak::t('AkInstaller::promtUserVar is deprecated and will be removed on future versions. Please use AkInstaller::promptUserVar instead.'), E_USER_NOTICE);
        return $this->promptUserVar($message, $options);
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
        $path = AK_APP_INSTALLERS_DIR.DS.$this->getInstallerName().'.xml';
        return file_exists($path) ? $path : false;
    }

    function _ensureColumnNameCompatibility($columns)
    {
        $columns = explode(',',$columns.',');
        foreach ($columns as $column){
            $column = trim($column);
            $column = substr($column, 0, strpos($column.' ',' '));
            $this->_canUseColumn($column);
        }
    }

    function _canUseColumn($column_name)
    {
        $invalid_columns = $this->_getInvalidColumnNames();
        if(in_array($column_name, $invalid_columns)){

            $method_name_part = AkInflector::camelize($column_name);
            require_once(AK_LIB_DIR.DS.'AkActiveRecord.php');
            $method_name = (method_exists(new AkActiveRecord(), 'set'.$method_name_part)?'set':'get').$method_name_part;

            trigger_error(Ak::t('A method named %method_name exists in the AkActiveRecord class'.
            ' which will cause a recusion problem if you use the column %column_name in your database. '.
            'You can disable automatic %type by setting the constant %constant to false '.
            'in your configuration file.', array(
            '%method_name'=> $method_name,
            '%column_name' => $column_name,
            '%type' => Ak::t($method_name[0] == 's' ? 'setters' : 'getters'),
            '%constant' => Ak::t($method_name[0] == 's' ? 'AK_ACTIVE_RECORD_ENABLE_CALLBACK_SETTERS' : 'AK_ACTIVE_RECORD_ENABLE_CALLBACK_GETTERS'),
            ''
            )), E_USER_ERROR);
        }
    }

    function _getInvalidColumnNames()
    {
        return defined('AK_INVALID_ACTIVE_RECORD_COLUMNS') ? explode(',',AK_INVALID_ACTIVE_RECORD_COLUMNS) : array('sanitized_conditions_array','conditions','inheritance_column','inheritance_column',
        'subclasses','attribute','attributes','attribute','attributes','accessible_attributes','protected_attributes',
        'serialized_attributes','available_attributes','attribute_caption','primary_key','column_names','content_columns',
        'attribute_names','combined_subattributes','available_combined_attributes','connection','connection','primary_key',
        'table_name','table_name','only_available_atrributes','columns_for_atrributes','columns_with_regex_boundaries','columns',
        'column_settings','column_settings','akelos_data_type','class_for_database_table_mapping','display_field','display_field',
        'internationalized_columns','available_locales','current_locale','attribute_by_locale','attribute_locales',
        'attribute_by_locale','attribute_locales','attributes_before_type_cast','attribute_before_type_cast','serialize_attribute',
        'available_attributes_quoted','attributes_quoted','column_type','value_for_date_column','observable_state',
        'observable_state','observers','errors','base_errors','errors_on','full_error_messages','array_from_ak_string',
        'attribute_condition','association_handler','associated','associated_finder_sql_options','association_option',
        'association_option','association_id','associated_ids','associated_handler_name','associated_type','association_type',
        'collection_handler_name','model_name','model_name','parent_model_name','parent_model_name');
    }

    function execute($sql)
    {
        return $this->db->execute($sql);
    }

    function debug($toggle = null)
    {
        $this->db->connection->debug = $toggle === null ? !$this->db->connection->debug : $toggle;
    }

    function usage()
    {
        echo Ak::t("Description:
    Database migrations is a sort of SCM like subversion, but for database settings.

    The migration command takes the name of an installer located on your
    /app/installers folder and runs one of the following commands:

    - \"install\" + (options version number): Will update to the provided version
    number or to the latest one in no version is given.

    - \"uninstall\" + (options version number): Will downgrade to the provided
    version number or to the lowest one in no version is given.

    Current version number will be sorted at app/installers/installer_name_version.txt.

Example:
    >> migrate framework install

    Will run the default database schema for the framework.
    This generates the tables for handling database driven sessions and cache.

");
    }


}


?>
