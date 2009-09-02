<?php

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
 * @package ActiveRecord
 * @subpackage Base
 * @component DbAdapter
 * @author Bermi Ferrer <bermi a.t bermilabs c.om>
 * @author Kaste
 * @author Arno Schneider <arno a.t bermilabs c.om>
 * @copyright Copyright (c) 2002-2009, The Akelos Team http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */


defined('AK_AVAILABLE_DATABASES') ? null : define('AK_AVAILABLE_DATABASES', 'mysql,pgsql,sqlite');

require_once(AK_LIB_DIR.DS.'AkObject.php');

class AkDbAdapter extends AkObject
{

    public $connection;
    public $settings;
    public $dictionary;
    public $debug=false;
    public $logger;

    /**
     * @param array $database_settings
     */
    public function __construct($database_settings, $auto_connect = false)
    {
        $this->settings = $database_settings;
        if ($auto_connect){
            $this->connect();
        }
        if (AK_LOG_EVENTS){
            $this->logger =& Ak::getLogger();
        }
    }

    public function __destruct()
    {
    }

    public function connect($die_on_error=true)
    {
        $dsn = $this->_constructDsn($this->settings);
        require_once(AK_CONTRIB_DIR.DS.'adodb'.DS.'adodb.inc.php');
        $this->connection = AK_DEBUG ? NewADOConnection($dsn) : @NewADOConnection($dsn);

        if (!$this->connection){
            error_reporting(E_ALL);
            if(defined('AK_DATABASE_CONNECTION_FAILURE_CALLBACK') && function_exists(AK_DATABASE_CONNECTION_FAILURE_CALLBACK)){
                $fn = AK_DATABASE_CONNECTION_FAILURE_CALLBACK;
                $fn();
            }
            trigger_error(Ak::t("Connection to the database failed. %dsn",
            array('%dsn'=> AK_DEBUG ? preg_replace('/\/\/(\w+):(.*)@/i','//$1:******@', urldecode($dsn))."\n" : '')),
            ($die_on_error?E_USER_ERROR:E_USER_WARNING));
        } else {
            $this->connection->debug = AK_DEBUG == 2;
            $this->connection->SetFetchMode(ADODB_FETCH_ASSOC);
            defined('AK_DATABASE_CONNECTION_AVAILABLE') ? null : define('AK_DATABASE_CONNECTION_AVAILABLE', true);
        }
    }

    public function connected()
    {
        return !empty($this->connection);
    }


    /**
     * @static
     * @param array $database_settings
     * @return AkDbAdapter
     */
    static public function &getInstance($database_specifications = AK_DEFAULT_DATABASE_PROFILE, $auto_connect = true)
    {
        static $connections;

        $settings_hash = is_string($database_specifications) ? $database_specifications : AkDbAdapter::_hash($database_specifications);

        if (empty($connections[$settings_hash])){
            if (empty($database_specifications)) {
                $settings_hash = AK_ENVIRONMENT;
                $database_specifications = Ak::getSettings('database', false, $settings_hash);
            } else if (is_string($database_specifications)){

                $environment_settings = Ak::getSettings('database', false, $database_specifications);

                if (!empty($environment_settings)){
                    $database_specifications = $environment_settings;
                } elseif(strstr($database_specifications, '://')) {
                    $database_specifications = AkDbAdapter::_getDbSettingsFromDsn($database_specifications);
                    $settings_hash = AK_ENVIRONMENT;
                } else {
                    global $database_settings;
                    if (isset($database_settings) && !file_exists(AK_CONFIG_DIR.DS.'database.yml')) {
                        trigger_error(Ak::t("You are still using the old config/config.php database configuration. Please upgrade to use the config/database.yml configuration."), E_USER_NOTICE);
                    }
                    if (!file_exists(AK_CONFIG_DIR.DS.'database.yml')) {
                        trigger_error(Ak::t("Could not find the database configuration file in %dbconfig.",array('%dbconfig'=>AK_CONFIG_DIR.DS.'database.yml')),E_USER_ERROR);
                    } else {
                        trigger_error(Ak::t("Could not find the database profile '%profile_name' in config/database.yml.",array('%profile_name'=>$database_specifications)),E_USER_ERROR);
                    }


                    $return = false;
                    return $return;
                }
            }elseif (!empty($database_settings[$settings_hash])){
                $database_specifications = $database_settings[$settings_hash];
            }
            $available_adapters = Ak::toArray(AK_AVAILABLE_DATABASES);
            $class_name = 'AkDbAdapter';
            $designated_database = strtolower($database_specifications['type']);
            if (in_array($designated_database, $available_adapters)) {
                $class_name = 'Ak'.ucfirst($designated_database).'DbAdapter';
                require_once(AK_LIB_DIR.DS.'AkActiveRecord'.DS.'AkDbAdapters'.DS.$class_name.'.php');
            }
            $connections[$settings_hash] = new $class_name($database_specifications,$auto_connect);
        }
        return $connections[$settings_hash];
    }

    /**
     * @param array $settings
     * @return string
     */
    public function _hash($settings)
    {
        if(!is_array($settings)){
            return AK_ENVIRONMENT;
        }
        if (isset($settings['password'])){
            unset($settings['password']);
        }
        return join(':',$settings);
    }

    public function &getDictionary()
    {
        if (empty($this->dictionary)){
            if (!$this->connected()){
                $this->connect();
            }
            require_once(AK_CONTRIB_DIR.DS.'adodb'.DS.'adodb.inc.php');
            $this->dictionary =& NewDataDictionary($this->connection);
        }
        return $this->dictionary;
    }

    /**
     * @param array $database_settings
     * @return string
     */
    public function _constructDsn($database_settings)
    {
        if(is_string($database_settings)){
            return $database_settings;
        }
        $dsn  = $database_settings['type'].'://';
        $dsn .= $database_settings['user'].':'.$database_settings['password'];
        $dsn .= !empty($database_settings['host']) ? '@'.$database_settings['host'] : '@localhost';
        $dsn .= !empty($database_settings['port']) ? ':'.$database_settings['port'] : '';
        $dsn .= '/'.$database_settings['database_name'];
        $dsn .= !empty($database_settings['options']) ? $database_settings['options'] : '';
        return $dsn;

    }

    public function _getDbSettingsFromDsn($dsn)
    {
        $settings = $result = parse_url($dsn);
        $result['type'] = $settings['scheme'];
        $result['password'] = $settings['pass'];
        $result['database_name'] = trim($settings['path'],'/');
        return $result;
    }

    public function type()
    {
        return $this->settings['type'];
    }

    public function debug($on = 'switch')
    {
        if ($on == 'switch') {
            $this->debug = !$this->debug;
        }else{
            $this->debug = $on;
        }
        return $this->debug;
    }

    public function _log($message)
    {
        if (!AK_LOG_EVENTS){
            return;
        }
        $this->logger->message($message);
    }

    public function addLimitAndOffset(&$sql,$options)
    {
        if (isset($options['limit']) && $limit = $options['limit']){
            $sql .= " LIMIT $limit";
            if (isset($options['offset']) && $offset = $options['offset']){
                $sql .= " OFFSET $offset";
            }
        }
        return $sql;
    }

    /* DATABASE STATEMENTS - CRUD */

    public function execute($sql, $message = 'SQL')
    {
        if (is_array($sql)) {
            $sql_string = array_shift($sql);
            $bindings = $sql;
        } else $sql_string = $sql;

        $this->_log($message.': '.$sql_string);
        $result = isset($bindings) ? $this->connection->Execute($sql_string, $bindings) : $this->connection->Execute($sql_string);

        if (!$result){
            $error_message = '['.$this->connection->ErrorNo().'] '.$this->connection->ErrorMsg();
            $this->_log('SQL Error: '.$error_message);
            if ($this->debug || AK_DEBUG) trigger_error("Tried '$sql_string'. Got: $error_message", E_USER_NOTICE);
        }
        return $result;
    }

    public function incrementsPrimaryKeyAutomatically()
    {
        return true;
    }

    public function getLastInsertedId($table,$pk)
    {
        return $this->connection->Insert_ID($table,$pk);
    }

    public function getAffectedRows()
    {
        return $this->connection->Affected_Rows();
    }

    public function insert($sql,$id=null,$pk=null,$table=null,$message = '')
    {
        $result = $this->execute($sql,$message);
        if (!$result){
            return false;
        }
        return is_null($id) ? $this->getLastInsertedId($table,$pk) : $id;
    }

    public function update($sql,$message = '')
    {
        $result = $this->execute($sql,$message);
        return ($result) ? $this->getAffectedRows() : false;
    }

    public function delete($sql,$message = '')
    {
        $result = $this->execute($sql,$message);
        return ($result) ? $this->getAffectedRows() : false;
    }

    /**
    * Returns a single value, the first column from the first row, from a record
    */
    public function selectValue($sql)
    {
        $result = $this->selectOne($sql);
        return !is_null($result) ? array_shift($result) : null;
    }

    /**
     * Returns an array of the values of the first column in a select:
     *   sqlSelectValues("SELECT id FROM companies LIMIT 3") => array(1,2,3)
     */
    public function selectValues($sql)
    {
        $values = array();
        if($results = $this->select($sql)){
            foreach ($results as $result){
                $values[] = array_shift($result);
            }
        }
        return $values;
    }

    /**
     * Returns a record array of the first row with the column names as keys and column values
     * as values.
     */
    public function selectOne($sql)
    {
        $result = $this->select($sql);
        return  !is_null($result) ? array_shift($result) : null;
    }

    /**
     * alias for select
     */
    public function selectAll($sql)
    {
        return $this->select($sql);
    }

    /**
    * Returns an array of record hashes with the column names as keys and
    * column values as values.
    */
    public function select($sql, $message = '')
    {
        $result = $this->execute($sql, $message);
        if (!$result){
            return array();
        }

        $records = array();
        while ($record = $result->FetchRow()) {
            $records[] = $record;
        }
        $result->Close();
        return $records;
    }

    /* TRANSACTIONS */

    public function startTransaction()
    {
        return $this->connection->StartTrans();
    }

    public function stopTransaction()
    {
        return $this->connection->CompleteTrans();
    }

    public function failTransaction()
    {
        if(AK_DEBUG && !empty($this->connection->debug)){
            Ak::trace(ak_backtrace(), null, null, null, false);
        }
        return $this->connection->FailTrans();
    }

    public function hasTransactionFailed()
    {
        return $this->connection->HasFailedTrans();
    }

    /* SCHEMA */

    public function renameColumn($table_name,$column_name,$new_name)
    {
        trigger_error(Ak::t('renameColumn is not available for your DbAdapter. Using %db_type.',array('%db_type'=>$this->type())));
    }

    /* META */

    /**
     * caching the meta info
     *
     * @return unknown
     */
    public function availableTables($force_lookup = false)
    {
        $available_tables = array();
        !AK_TEST_MODE && $available_tables = Ak::getStaticVar('available_tables');
        if(!$force_lookup && empty($available_tables)){
            if (($available_tables = AkDbSchemaCache::get('avaliable_tables')) === false) {
                if(empty($available_tables)){
                    $available_tables = $this->connection->MetaTables();
                }
                AkDbSchemaCache::set('avaliable_tables', $available_tables);
                !AK_TEST_MODE && Ak::setStaticVar('available_tables', $available_tables);
            }
        }
        $available_tables = $force_lookup ? $this->connection->MetaTables() : $available_tables;
        $force_lookup && !AK_TEST_MODE && Ak::setStaticVar('available_tables', $available_tables);
        return $available_tables;
    }

    public function tableExists($table_name)
    {
        // First try if cached
        $available_tables = $this->availableTables();
        if(!in_array($table_name,(array)$available_tables)){
            // Force lookup and refresh cache
            $available_tables = $this->availableTables(true);
            return in_array($table_name,(array)$available_tables);
        }
        return true;
    }

    /**
     * caching the meta info
     *
     * @param unknown_type $table_name
     * @return unknown
     */

    public function getColumnDetails($table_name)
    {
        return $this->connection->MetaColumns($table_name);
    }

    /**
     * caching the meta info
     *
     * @param unknown_type $table_name
     * @return unknown
     */
    public function getIndexes($table_name)
    {
        return $this->connection->MetaIndexes($table_name);
    }

    /* QUOTING */

    public function quote_string($value)
    {
        return $this->connection->qstr($value);
    }

    public function quote_datetime($value)
    {
        return $this->connection->DBTimeStamp($value);
    }

    public function quote_date($value)
    {
        return $this->connection->DBDate($value);
    }

    // will be moved to postgre
    public function escape_blob($value)
    {
        return $this->connection->BlobEncode($value);
    }

    // will be moved to postgre
    public function unescape_blob($value)
    {
        return $this->connection->BlobDecode($value);
    }

}

?>