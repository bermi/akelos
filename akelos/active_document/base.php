<?php

class AkActiveDocument extends AkLazyObject
{
    protected   $_primary_key;
    protected   $_record_timestamps = true;

    private     $_Adapter;
    private     $_internals = array();
    private     $_attributes = array();

    public function __construct(){
        $attributes = (array)func_get_args();
        if(isset($attributes[0]['init']) && $attributes[0]['init'] == false){
            return;
        }
        return $this->init($attributes);
    }

    public function init($attributes = array()){
        if(isset($attributes[0]) && count($attributes) === 1 && array_key_exists(0, $attributes) && !is_array($attributes[0])){
            return $this->_loadFromDatabase($attributes[0]);
        }elseif(!empty($attributes[0]) && is_array($attributes[0])){
            $this->setAttributes($attributes[0]);
        }
    }

    /**
    * Creates an object, instantly saves it as a record (if the validation permits it), and returns it.
    * If the save fail under validations, the unsaved object is still returned.
    */
    public function &create($attributes = array()){
        $model = $this->getModelName();
        $object = new $model();
        if(!empty($this->_Adapter)){
            $object->setAdapter($this->getAdapter());
        }
        $object->setAttributes($attributes);
        $object->save();
        return $object;
    }


    /**
    * - No record exists: Creates a new record with values matching those of the object attributes.
    * - A record does exist: Updates the record with values matching those of the object attributes.
    */
    public function save($validate = true){
        return $this->createOrUpdate($validate);
    }

    public function createOrUpdate($validate = true){
        if($validate && !$this->isValid()){
            return false;
        }
        $this->_setRecordTimestamps();
        return $this->isNewRecord() ? $this->_create() : $this->_update();
    }

    public function isValid(){
        return true;
    }

    public function isNewRecord(){
        return empty($this->_internals['existing_record']);
    }


    /**
    * Creates a new record with values matching those of the instance attributes.
    * Must be called as a result of a call to createOrUpdate.
    */
    private function _create(){
        if($record = $this->getAdapter()->createRecord($this->getCollectionName(), $this->_getAttributesCastedForDatabase())){
            $this->_matchCurrentModelWithDatabaseRecord($record);
            return true;
        }
        return false;
    }


    /**
    * Updates the associated record with values matching those of the instance attributes.
    * Must be called as a result of a call to createOrUpdate.
    */
    private function _update(){
        if($record = $this->getAdapter()->updateRecord($this->getCollectionName(), $this->_getAttributesCastedForDatabase())){
            $this->_matchCurrentModelWithDatabaseRecord($record);
            return true;
        }
        return false;
    }


    // Finder

    public function &find()
    {
        $args = func_get_args();
        $options = $this->_extractOptionsFromArgs($args);
        list($fetch, $options) = $this->_extractConditionsFromArgs($args, $options);
        switch ($fetch) {
            case 'first':
                return $this->_findInitial($options);
            default:
                return $this->_findEvery($options);
        }
    }

    private function &_findInitial($options){
        $options['limit'] = 1;
        $result = $this->_findEvery($options);
        if(!empty($result)){
            $result->rewind();
            return $result->current();
        }else{
            $result = false;
            return  $result;
        }
    }

    private function &_findEvery($options){
        $result = $this->getAdapter()->find($this->getCollectionName(), $options);
        return  $result;
    }

    private function _extractOptionsFromArgs(&$args) {
        $last_arg = count($args)-1;
        return isset($args[$last_arg]) && is_array($args[$last_arg]) && $this->_isOptionsHash($args[$last_arg]) ? array_pop($args) : array();
    }

    private function _isOptionsHash($options) {
        if (isset($options[0])) return false;
        $valid_keys = array('conditions', 'limit', 'offset', 'order', 'sort');
        return count($options) != count(array_diff(array_keys($options), $valid_keys));
    }

    private function _extractConditionsFromArgs($args, $options){
        if(!empty($args) && count($args) == 1 && array_key_exists(0, $args)){
            $fetch = 'first';
            $options['attributes'][$this->getPrimaryKey()] = $args[0];
        }
        return array($fetch, $options);
    }


    // Attributes

    public function getPrimaryKey(){
        if(empty($this->_primary_key)){
            $this->setPrimaryKey($this->getAdapter()->getDefaultPrimaryKey());
        }
        return $this->_primary_key;
    }

    public function setPrimaryKey($primary_key){
        $this->_primary_key = $primary_key;
    }

    public function setId($id){
        $this->{$this->getPrimaryKey()} = $id;
    }

    public function getId(){
        $pk = $this->getPrimaryKey();
        return isset($this->_attributes[$pk]) ? $this->$pk : null;
    }


    public function setAttributes($attributes = array()){
        foreach ($attributes as $k => $v){
            $this->setAttribute($k, $v);
        }
    }

    public function setAttribute($name, $value){
        $this->$name = $value;
    }
    public function set($name, $value){
        $this->setAttribute($name, $value);
    }

    public function getAttribute($name){
        return $this->$name;
    }
    public function get($name){
        return $this->getAttribute($name);
    }

    public function getAttributes(){
        return $this->_attributes;
    }

    public function castAttributeForDatabase($attribute, $value){
        return $value;
        if(preg_match('/^(is_|has_|do_|does_|are_)/', $attribute, $matches)){
            return (bool)$value;
        }
        if(preg_match('/.+_(at|on)$/', $attribute, $matches)){
            //return (bool)$matches[0];
        }
    }

    public function castAttributeFromDatabase($attribute, $value){
        return $value;
    }

    protected function _setRecordTimestamps(){
        if (!$this->_record_timestamps) return;
        $this->setAttribute($this->isNewRecord() ? 'created_at' : 'updated_at', Ak::getDate());
    }

    private function _getAttributesCastedForDatabase(){
        $attributes = array();
        foreach ($this->getAttributes() as $k => $v){
            $attributes[$k] = $this->castAttributeForDatabase($k ,$v);
        }
        return array_diff($attributes, array(''));
    }

    private function _matchCurrentModelWithDatabaseRecord($record){
        $this->setId($record[$this->getPrimaryKey()]);
        $this->_internals['existing_record'] = true;
    }

    private function _loadFromDatabase($id){
        if($record = $this->find($id)){
            $this->_setAttributesFromDatabase((array)$record);
            return true;
        }
        return false;
    }

    private function _setAttributesFromDatabase($attributes){
        $this->_internals['existing_record'] = true;
        $this->setAttributes($attributes);
    }


    // Database adapter

    public function getModelName(){
        return get_class($this);
    }

    public function getCollectionName(){
        if(empty($this->_internals['collection_name'])){
            $this->_internals['collection_name'] = AkInflector::tableize($this->getModelName());
        }
        return $this->_internals['collection_name'];
    }

    public function getTableName(){
        return $this->getCollectionName();
    }


    public function setAdapter(&$Adapter){
        $this->_Adapter = $Adapter;
        Ak::setStaticVar($this->getModelName().'_last_used_Adapter', $Adapter);
        Ak::setStaticVar('AkActiveDocument_last_used_Adapter', $Adapter);
    }

    public function &getAdapter(){
        if(empty($this->_Adapter)){
            if(!$this->_Adapter = Ak::getStaticVar($this->getModelName().'_last_used_Adapter')){
                $this->_Adapter = Ak::getStaticVar('AkActiveDocument_last_used_Adapter');
            }
        }
        return $this->_Adapter;
    }

    public function __get($attribute){
        return $this->_attributes[$attribute];
    }

    public function __set($attribute, $value){
        $this->_attributes[$attribute] = $value;
    }
}



class AkActiveDocumentIterator implements Iterator
{
    private $_AdapterIterator;

    public function __construct($AdapterIterator){
        $this->_AdapterIterator = $AdapterIterator;
    }

    public function rewind() {
        $this->_AdapterIterator->rewind();
    }

    public function current() {
        return $this->_AdapterIterator->current();
    }

    public function key() {
        return $this->_AdapterIterator->key();
    }

    public function next() {
        return $this->_AdapterIterator->next();
    }

    public function valid() {
        return $this->_AdapterIterator->valid();
    }
}


class AkActiveDocumentExtenssion
{
    protected $_ActiveDocument;
    public function setExtendedBy(&$ActiveDocument){
        $this->_ActiveDocument = $ActiveDocument;
    }
}

