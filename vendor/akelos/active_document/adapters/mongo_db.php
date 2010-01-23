<?php

defined('AK_MONGO_DEFAULT_HOST')        || define('AK_MONGO_DEFAULT_HOST', 'localhost');
defined('AK_MONGO_DEFAULT_DATABASE')    || define('AK_MONGO_DEFAULT_DATABASE', AkInflector::underscore(AK_APP_NAME));

class AkOdbMongoDbAdapter
{
    private $_is_connected = false;
    private $_Mongo;
    private $_MongoDatabases;
    private $_connetion_signature = 'default';
    private $_options = array();

    public function __construct($options = array()) {
        $this->setOptions($options);
    }

    public function connect($options = null) {
        $this->setOptions($options);
        if($this->_meetsDependencies()){
            $port = $this->getOption('port');
            if(!$Connection = Ak::getStaticVar(__CLASS__.'_'.$this->_connetion_signature)){
                try{
                    $Connection = new Mongo($this->getOption('host').(empty($port)?'':':'.$port));
                }catch(MongoConnectionException $e){
                    $Connection = false;
                }
                Ak::setStaticVar(__CLASS__.'_'.$this->_connetion_signature, $Connection);
            }
            $this->_Mongo[$this->_connetion_signature] = $Connection;
            if($Connection && !$this->isConnected()){
                $Connection->connect();
            }
        }
        return $this->isConnected();
    }

    public function disconnect() {
        if($this->isConnected()){
            $this->getConnection()->close();
            unset($this->_Mongo[$this->_connetion_signature]);
            unset($this->_MongoDatabases[$this->_connetion_signature]);
        }
        return !$this->isConnected();
    }

    public function dropDatabase($database_name = null) {
        $database_name = empty($database_name) ? $this->getOption('database') : $database_name;
        $this->getConnection()->dropDB($database_name);
    }

    public function isConnected() {
        try{
            $this->getConnection();
            return true;
        }catch (AkDatabaseConnectionException $e){
            return false;
        }catch (Exception $e){
            throw $e;
        }
    }

    public function getOption($name, $default = null) {
        return isset($this->_options[$name]) ? $this->_options[$name] : $default;
    }

    public function setOptions($options = array()) {
        if(is_null($options)) return;
        $default_options = array(
        'host'      => AK_MONGO_DEFAULT_HOST,
        'user'      => '',
        'password'  => '',
        'database'  => AK_MONGO_DEFAULT_DATABASE,
        );
        $this->_options = array_merge($default_options, $options);
        $this->_updateSignature();
    }

    public function getType() {
        return 'mongo_db';
    }

    public function &getDatabase($database_name = null) {
        $database_name = AkInflector::underscore(empty($database_name) ? $this->getOption('database') : $database_name);
        if(isset($this->_MongoDatabases[$this->_connetion_signature][$database_name])){
            return $this->_MongoDatabases[$this->_connetion_signature][$database_name];
        }
        $Database = $this->getConnection()->selectDB($database_name);
        $this->_authenticateDatabase($Database);
        $this->_MongoDatabases[$this->_connetion_signature][$database_name] = $Database;
        return $Database;
    }

    public function &getConnection() {
        if(empty($this->_Mongo[$this->_connetion_signature])){
            throw new AkDatabaseConnectionException('Could not get MogoDb connection.');
        }
        return $this->_Mongo[$this->_connetion_signature];
    }

    public function getDefaultPrimaryKey(){
        return '_id';
    }


    // CRUD

    public function &createRecord($collection_name, $attributes = array()){
        $this->getDatabase()->selectCollection($collection_name)->insert($attributes);
        $attributes[$this->getDefaultPrimaryKey()] = (string)$attributes[$this->getDefaultPrimaryKey()];
        return $attributes;
    }

    public function &updateRecord($collection_name, $attributes = array()){
        $this->getDatabase()->selectCollection($collection_name)->save($attributes);
        return $attributes;
    }

    public function &find($collection_name, $options = array()){
        $false = false;
        if(!empty($options['conditions']) && empty($options['attributes'])){
            $options['attributes'] = $options['conditions'];
            unset($options['conditions']);
        }
        if(empty($options['attributes'])) return $false;
        $Cursor = $this->getDatabase()->selectCollection($collection_name)->find($this->_castAttributesForFinder($options['attributes']));
        isset($options['limit'])    &&  $Cursor->limit($options['limit']);
        isset($options['sort'])     &&  $Cursor->sort(array($options['sort'] => 1));
        if($Cursor->count() == 0) {
            return $false;
        }
        return $Cursor;
    }

    public function delete($collection_name, $id){
        return $this->getDatabase()->selectCollection($collection_name)->remove($this->_castAttributesForFinder(array($this->getDefaultPrimaryKey() => $id)));
    }

    private function _castAttributesForFinder($attributes = array()){
        $pk = $this->getDefaultPrimaryKey();
        foreach ($attributes as $k => $v){
            if($k == $pk){
                $attributes[$k] =  new MongoId("$v");
            }
        }
        return $attributes;
    }

    private function _authenticateDatabase(&$Database) {
        $user = $this->getOption('user');
        if(!empty($user)){
            $password = $this->getOption('password');
            $Database->authenticate($user, $password);
        }
    }

    private function _updateSignature() {
        $this->_connetion_signature = md5(serialize($this->_options));
    }

    private function _meetsDependencies() {
        if(!class_exists('Mongo')){
            trigger_error('Mongo extenssion is not enabled. You can enable it by running "sudo pecl install mongo"', E_USER_WARNING);
            return false;
        }
        return true;
    }
}