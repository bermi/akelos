<?php

class AkActiveDocument extends AkBaseModel
{
    public $default_error_messages  = array();
    public $skip_attributes         = array();

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
        $this->_enableLazyLoadingExtenssions();
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
    * Deletes the record with the given id without instantiating an object first. If an array of
    * ids is provided, all of them are deleted.
    */
    public function delete($id)
    {
        $ids = is_array($id) ? $id : array($id);
        foreach ($ids as $id){
            $this->getAdapter()->delete($this->getCollectionName(), $id);
        }
    }

    public function destroy($id = null)
    {
        if(!$this->_callback('beforeDestroy')) return false;
        $this->delete(empty($id) ? $this->getId() : $id);
        unset($this->_internals['existing_record']);
        if(!$this->_callback('afterDestroy')) return false;
        return true;
    }

    /**
    * - No record exists: Creates a new record with values matching those of the object attributes.
    * - A record does exist: Updates the record with values matching those of the object attributes.
    */
    public function save($validate = true){
        return $this->_createOrUpdate($validate);
    }

    /**
    * Reloads the attributes of this object from the database.
    */
    public function reload()
    {
        return $this->_loadFromDatabase($this->getId());
    }

    public function isValid(){
        $this->clearErrors();
        $new_record = $this->isNewRecord();
        return
        (
        !($this->_callback('beforeValidation')) ||
        !($new_record ? $this->_callback('beforeValidationOnCreate') : $this->_callback('beforeValidationOnUpdate')) ||
        !($this->validate() !== false && !$this->hasErrors()) ||
        !(($new_record ? $this->validateOnCreate() !== false : $this->validateOnUpdate() !== false) && !$this->hasErrors()) ||
        !($this->_callback('afterValidation')) ||
        !($new_record ? $this->_callback('afterValidationOnCreate') : $this->_callback('afterValidationOnUpdate'))
        ) ? false : true;
    }

    public function isNewRecord(){
        return empty($this->_internals['existing_record']);
    }


    private function _createOrUpdate($validate = true){
        if($validate && $this->needsValidation() && !$this->isValid()){
            return false;
        }
        $this->_setRecordTimestamps();
        return
        (
        !($this->_callback('beforeSave')) ||
        !($this->isNewRecord() ? $this->_create() : $this->_update()) ||
        !($this->_callback('afterSave'))
        ) ? false : true;
    }

    /**
    * Creates a new record with values matching those of the instance attributes.
    * Must be called as a result of a call to _createOrUpdate.
    */
    private function _create(){
        if(!$this->_callback('beforeCreate')) return false;
        if($record = $this->getAdapter()->createRecord($this->getCollectionName(), $this->_getAttributesCastedForDatabase())){
            $this->_matchCurrentModelWithDatabaseRecord($record);
            if(!$this->_callback('afterCreate')) return false;
            return true;
        }
        return false;
    }

    /**
    * Updates the associated record with values matching those of the instance attributes.
    * Must be called as a result of a call to _createOrUpdate.
    */
    private function _update(){
        if(!$this->_callback('beforeUpdate')) return false;
        if($record = $this->getAdapter()->updateRecord($this->getCollectionName(), $this->_getAttributesCastedForDatabase())){
            $this->_matchCurrentModelWithDatabaseRecord($record);
            if(!$this->_callback('afterUpdate')) return false;
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


    public function &findFirst()
    {
        $args = func_get_args();
        $result = call_user_func_array(array($this,'find'), array_merge(array('first'),$args));
        return $result;
    }

    public function &findAll()
    {
        $args = func_get_args();
        $result = call_user_func_array(array($this,'find'), array_merge(array('all'),$args));
        return $result;
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
        if(!empty($result)){
            $result = new AkActiveDocumentIterator($result, $this);
        }
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
        $fetch = 'all';
        $num_args = count($args);
        if(!empty($args) && count($args) == 1 && array_key_exists(0, $args)){
            $fetch = 'first';
            if($args[0] != 'first'){
                $options['attributes'][$this->getPrimaryKey()] = $args[0];
            }
        }
        if ($num_args > 1 && is_array($args[1])) {
            $fetch = array_shift($args);
            $options = array_merge($options, array('conditions'=>$args[0]));
        }
        return array($fetch, $options);
    }


    // Attributes

    public function hasColumn($column_name){
        return !in_array($column_name, $this->skip_attributes);
    }

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


    public function setAttributesFromDatabase($attributes){
        $this->_internals['existing_record'] = true;
        $this->setAttributes($attributes);
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
            $this->setAttributesFromDatabase($record->getAttributes());
            return $this->_callback('afterInstantiate');
        }
        return false;
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





    /**
                                    Callbacks
    ====================================================================
    See also: Observers.
    *
    * Callbacks are hooks into the life-cycle of an Active Document object that allows you to trigger logic
    * before or after an alteration of the object state. This can be used to make sure that associated and
    * dependent objects are deleted when destroy is called (by overwriting beforeDestroy) or to massage attributes
    * before they're validated (by overwriting beforeValidation). As an example of the callbacks initiated, consider
    * the AkActiveDocument->save() call:
    *
    * - (-) save()
    * - (-) needsValidation()
    * - (1) beforeValidation()
    * - (2) beforeValidationOnCreate() / beforeValidationOnUpdate()
    * - (-) validate()
    * - (-) validateOnCreate()
    * - (4) afterValidation()
    * - (5) afterValidationOnCreate() / afterValidationOnUpdate()
    * - (6) beforeSave()
    * - (7) beforeCreate() / beforeUpdate()
    * - (-) create()
    * - (8) afterCreate() / afterUpdate()
    * - (9) afterSave()
    * - (10) afterDestroy()
    * - (11) beforeDestroy()
    *
    *
    * That's a total of 15 callbacks, which gives you immense power to react and prepare for each state in the
    * Active Document lifecycle.
    *
    * Examples:
    *   class CreditCard extends ActiveDocument
    *   {
    *       // Strip everything but digits, so the user can specify "555 234 34" or
    *       // "5552-3434" or both will mean "55523434"
    *       private function _beforeValidationOnCreate
    *       {
    *           if(!empty($this->number)){
    *               $this->number = preg_replace('/([^0-9]*)/','',$this->number);
    *           }
    *       }
    *   }
    *
    *   class Subscription extends ActiveDocument
    *   {
    *       // Note: This is not implemented yet
    *       public $beforeCreate  = 'recordSignup';
    *
    *       public function recordSignup()
    *       {
    *         $this->signed_up_on = date("Y-m-d");
    *       }
    *   }
    *
    *   class Firm extends ActiveDocument
    *   {
    *       //Destroys the associated clients and people when the firm is destroyed
    *       // Note: This is not implemented yet
    *       public $beforeDestroy = array('destroyAssociatedPeople', 'destroyAssociatedClients');
    *
    *       public function destroyAssociatedPeople()
    *       {
    *           $Person = new Person();
    *           $Person->destroyAll("firm_id=>", $this->id);
    *       }
    *
    *       public function destroyAssociatedClients()
    *       {
    *           $Client = new Client();
    *           $Client->destroyAll("client_of=>", $this->id);
    *       }
    *   }
    *
    *
    * == Canceling callbacks ==
    *
    * If a before* callback returns false, all the later callbacks and the associated action are cancelled. If an after* callback returns
    * false, all the later callbacks are cancelled. Callbacks are generally run in the order they are defined, with the exception of callbacks
    * defined as methods on the model, which are called last.
    *
    * Override this methods to hook Active Documents
    */
    private function _callback($type){
        $result = true;
        if(method_exists($this, $type)){
            $result = $this->$type();
        }
        return $result ? $this->notifyObservers($type) : false;
    }

    /*/Callbacks*/



    // Lazy loading
    protected function _enableLazyLoadingExtenssions($options = array())
    {
        empty($options['skip_observers'])   && $this->_enableObservers();
        empty($options['skip_errors'])      && $this->_enableErrors();
        empty($options['skip_validations']) && $this->_enableValidations();
    }

    protected function _enableObservers()
    {
        $this->extendClassLazily('AkModelObserver',
        array(
        'methods' => array (
        'notifyObservers',
        'setObservableState',
        'getObservableState',
        'addObserver',
        'getObservers',
        ),
        'autoload_path' => AK_ACTIVE_SUPPORT_DIR.DS.'models'.DS.'observer.php'
        ));
    }



    protected function _enableErrors()
    {
        $this->extendClassLazily('AkModelErrors',
        array(
        'methods' => array(
        'addError',
        'addErrorOnBlank',
        'addErrorOnBoundaryBreaking',
        'addErrorOnBoundryBreaking',
        'addErrorOnEmpty',
        'addErrorToBase',
        'clearErrors',
        'countErrors',
        'errorsToString',
        'getBaseErrors',
        'getDefaultErrorMessageFor',
        'getErrors',
        'getErrorsOn',
        'getFullErrorMessages',
        'hasErrors',
        'isInvalid',
        'yieldEachError',
        'yieldEachFullError',
        'yieldError',
        ),
        'autoload_path' => AK_ACTIVE_SUPPORT_DIR.DS.'models'.DS.'errors.php'
        ));
    }


    protected function _enableValidations()
    {
        $this->extendClassLazily('AkModelValidations',
        array(
        'methods' => array(
        'validate',
        'validateOnCreate',
        'validateOnUpdate',
        'needsValidation',
        'isBlank',
        'validatesPresenceOf',
        'validatesUniquenessOf',
        'validatesLengthOf',
        'validatesInclusionOf',
        'validatesExclusionOf',
        'validatesNumericalityOf',
        'validatesFormatOf',
        'validatesAcceptanceOf',
        'validatesConfirmationOf',
        'validatesSizeOf',
        ),
        'autoload_path' => AK_ACTIVE_SUPPORT_DIR.DS.'models'.DS.'validations.php'
        ));
    }

    // Magic callback methods


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
    private $_Model;
    private $_ModelInstance;

    public function __construct(&$AdapterIterator, &$Model){
        $this->_AdapterIterator = $AdapterIterator;
        $this->_Model           = $Model;
        $class_name = $Model->getModelName();
        $this->_ModelInstance   = new $class_name;
    }

    public function rewind() {
        $this->_AdapterIterator->rewind();
    }

    public function current() {
        $Model = clone($this->_ModelInstance);
        $Model->setAttributesFromDatabase($this->_AdapterIterator->current());
        return $Model;
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

