<?php

class AkActiveDocument extends AkLazyObject
{
    private     $_Adapter;
    private     $_internals = array();

    protected   $_primary_key;
    protected   $_attributes = array();

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


    // Attributes

    public function getPrimaryKey(){
        if(empty($this->primary_key)){
            $this->setPrimaryKey($this->getAdapter()->getDefaultPrimaryKey());
        }
        return $this->primary_key;
    }

    public function setPrimaryKey($primary_key){
        $this->primary_key = $primary_key;
    }

    public function setId($id){
        $this->{$this->getPrimaryKey()} = $id;
    }

    public function getId(){
        return isset($this->{$this->getPrimaryKey()}) ? $this->{$this->getPrimaryKey()} : null;
    }


    public function setAttributes($attributes = array()){
        foreach ($attributes as $k => $v){
            $this->setAttribute($k, $v);
        }
    }

    public function setAttribute($name, $value){
        $this->$name = $value;
    }

    public function getAttribute($name){
        return $this->$name;
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
    }

    public function &getAdapter(){
        return $this->_Adapter;
    }


    public function __get($attribute){
        return $this->_attributes[$attribute];
    }

    public function __set($attribute, $value){
        $this->_attributes[$attribute] = $value;
    }

}

class AkActiveDocumentExtenssion
{
    protected $_ActiveDocument;
    public function setExtendedBy(&$ActiveDocument){
        $this->_ActiveDocument = $ActiveDocument;
    }
}

