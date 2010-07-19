<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

/**
* Active Record objects doesn't specify their attributes directly, but rather infer them from the table definition with
* which they're linked. Adding, removing, and changing attributes and their type is done directly in the database. Any change
* is instantly reflected in the Active Record objects. The mapping that binds a given Active Record class to a certain
* database table will happen automatically in most common cases, but can be overwritten for the uncommon ones.
*
* See the mapping rules in table_name and the full example in README.txt for more insight.
*
* == Creation ==
*
* Active Records accepts constructor parameters either in an array or as a list of parameters in a specific format. The array method is especially useful when
* you're receiving the data from somewhere else, like a HTTP request. It works like this:
*
* <code>
*   $user = new User(array('name' => 'David', 'occupation' => 'Code Artist'));
*   echo $user->name; // Will print "David"
* </code>
*
* You can also use a parameter list initialization.:
*
*   $user = new User('name->', 'David', 'occupation->', 'Code Artist');
*
* And of course you can just create a bare object and specify the attributes after the fact:
*
* <code>
*   $user = new User();
*   $user->name = 'David';
*   $user->occupation = 'Code Artist';
* </code>
*
* == Conditions ==
*
* Conditions can either be specified as a string or an array representing the WHERE-part of an SQL statement.
* The array form is to be used when the condition input is tainted and requires sanitization. The string form can
* be used for statements that doesn't involve tainted data. Examples:
*
* <code>
*   class User extends ActiveRecord
*   {
*     public function authenticateUnsafely($user_name, $password)
*     {
*          return findFirst("user_name = '$user_name' AND password = '$password'");
*     }
*
*     public function authenticateSafely($user_name, $password)
*     {
*          return findFirst("user_name = ? AND password = ?", $user_name, $password);
*     }
*    }
* </code>
*
* The <tt>authenticateUnsafely</tt> method inserts the parameters directly into the query and is thus susceptible to SQL-injection
* attacks if the <tt>$user_name</tt> and <tt>$password</tt> parameters come directly from a HTTP request. The <tt>authenticateSafely</tt> method,
* on the other hand, will sanitize the <tt>$user_name</tt> and <tt>$password</tt> before inserting them in the query, which will ensure that
* an attacker can't escape the query and fake the login (or worse).
*
* When using multiple parameters in the conditions, it can easily become hard to read exactly what the fourth or fifth
* question mark is supposed to represent. In those cases, you can resort to named bind variables instead. That's done by replacing
* the question marks with symbols and supplying a hash with values for the matching symbol keys:
*
* <code>
*   $Company->findFirst(
*              "id = :id AND name = :name AND division = :division AND created_at > :accounting_date",
*               array(':id' => 3, ':name' => "37signals", ':division' => "First", ':accounting_date' => '2005-01-01')
*             );
* </code>
*
* == Accessing attributes before they have been type casted ==
*
* Some times you want to be able to read the raw attribute data without having the column-determined type cast run its course first.
* That can be done by using the <attribute>_before_type_cast accessors that all attributes have. For example, if your Account model
* has a balance attribute, you can call $Account->balance_before_type_cast or $Account->id_before_type_cast.
*
* This is especially useful in validation situations where the user might supply a string for an integer field and you want to display
* the original string back in an error message. Accessing the attribute normally would type cast the string to 0, which isn't what you
* want.
*
* == Saving arrays, hashes, and other non-mappable objects in text columns ==
*
* Active Record can serialize any object in text columns. To do so, you must specify this with by setting the attribute serialize with
* an array where the Keys is the column name and the value should be either true or the class name of the object being serialized.
*
* This makes it possible to store arrays, hashes, and other non-mappeable objects without doing any additional work. Example:
*
* <code>
*   class User extends ActiveRecord
*   {
*      public $serialize = array('preferences');
*   }
*
*   $User = new User(array('preferences'=>array("background" => "black", "display" => 'large')));
*   $User->find($user_id);
*   $User->preferences // array("background" => "black", "display" => 'large')
* </code>
*
* == Connection to multiple databases in different models ==
*
* Connections are usually created through AkActiveRecord->establishConnection and retrieved by AkActiveRecord->connection.
* All classes inheriting from AkActiveRecord will use this connection. But you can also set a class-specific connection.
* For example, if $Course is a AkActiveRecord, but resides in a different database you can just say $Course->establishConnection
* and $Course and all its subclasses will use this connection instead.
*
* Active Records will automatically record creation and/or update timestamps of database objects
* if fields of the names created_at/created_on or updated_at/updated_on are present.
* Date only: created_on, updated_on
* Date and time: created_at, updated_at
*
* This behavior can be turned off by setting <tt>$this->_recordTimestamps = false</tt>.
*/
class AkActiveRecord extends AkAssociatedActiveRecord
{

    public
    $_db;

    public
    $_tableName,
    $_newRecord,
    $_freeze,
    $_dataDictionary,
    $_primaryKey,
    $_inheritanceColumn,
    $internationalize = AK_ACTIVE_RECORD_INTERNATIONALIZE_MODELS_BY_DEFAULT,
    $_attributes = array(),
    $_protectedAttributes = array(),
    $_accessibleAttributes = array(),
    $_association_handler_copies = array(),
    $_recordTimestamps = true,
    $_columnNames = array(), // Column description
    $_columns = array(), // Array of column objects for the table associated with this class.
    $_contentColumns = array(), // Columns that can be edited/viewed
    $_combinedAttributes = array(),
    $_BlobQueryStack = null,
    //$automated_max_length_validator = false,
    //$automated_validators_enabled = false,
    //$automated_not_null_validator = false,
    $set_default_attribute_values_automatically = true,
    $_activeRecordHasBeenInstantiated = true, // This is needed for enabling support for static active record instantiation under php

    $default_error_messages = array();

    protected $_options = array(
    );

    private
    $__ActsLikeAttributes = array();

    public function __construct() {
        $attributes = (array)func_get_args();
        if(isset($attributes[0]['init']) && $attributes[0]['init'] == false){
            return;
        }
        return $this->init($attributes);
    }

    public function init($attributes = array()) {
        AK_LOG_EVENTS ? ($this->Logger = Ak::getLogger()) : null;

        $this->establishConnection();
        $this->_instantiateDefaultObserver();

        if(!empty($this->table_name)){
            $this->setTableName($this->table_name);
        }
        $load_acts = isset($attributes[1]['load_acts']) ? $attributes[1]['load_acts'] : (isset($attributes[0]['load_acts']) ? $attributes[0]['load_acts'] : true);
        $this->act_as = !empty($this->acts_as) ? $this->acts_as : (empty($this->act_as) ? false : $this->act_as);
        if (!empty($this->act_as) && $load_acts) {;
        $this->_loadActAsBehaviours();
        }

        if(!empty($this->combined_attributes)){
            foreach ($this->combined_attributes as $combined_attribute){
                $this->addCombinedAttributeConfiguration($combined_attribute);
            }
        }

        if(isset($attributes[0]) && is_array($attributes[0]) && count($attributes) === 1){
            $attributes = $attributes[0];
            $this->_newRecord = true;
        }

        // new AkActiveRecord(23); //Returns object with primary key 23
        if(isset($attributes[0]) && count($attributes) === 1 && $attributes[0] > 0){
            $record = $this->find($attributes[0]);
            if(!$record){
                return false;
            }else {
                $this->setAttributes($record->getAttributes(), true);
            }
            // This option is only used internally for loading found objects
        }elseif(isset($attributes[0]) && isset($attributes[1]) && $attributes[0] == 'attributes' && is_array($attributes[1])){
            foreach(array_keys($attributes[1]) as $k){
                $attributes[1][$k] = $this->castAttributeFromDatabase($k, $attributes[1][$k]);
            }

            $avoid_loading_associations = isset($attributes[1]['load_associations']) ? false : !empty($this->disableAutomatedAssociationLoading);
            $this->setAttributes($attributes[1], true);
        }else{
            $this->newRecord($attributes);
        }

        empty($avoid_loading_associations) ? $this->loadAssociations() : null;

    }

    public function __destruct() {

    }

    public function __toString(){
        return $this->toString();
    }


    /**
    * New objects can be instantiated as either empty (pass no construction parameter) or pre-set with attributes but not yet saved
    * (pass an array with key names matching the associated table column names).
    * In both instances, valid attribute keys are determined by the column names of the associated table; hence you can't
    * have attributes that aren't part of the table columns.
    */
    public function &newRecord($attributes) {
        $this->_newRecord = true;

        if(AK_ACTIVE_RECORD_SKIP_SETTING_ACTIVE_RECORD_DEFAULTS && empty($attributes)){
            return;
        }

        if(isset($attributes) && !is_array($attributes)){
            $attributes = func_get_args();
        }
        $this->setAttributes($this->attributesFromColumnDefinition(),true);
        $this->setAttributes($attributes);
        return $this;
    }


    /**
    * Returns a clone of the record that hasn't been assigned an id yet and is treated as a new record.
    */
    public function cloneRecord() {
        $model_name = $this->getModelName();
        $attributes = $this->getAttributesBeforeTypeCast();
        if(isset($attributes[$this->getPrimaryKey()])){
            unset($attributes[$this->getPrimaryKey()]);
        }
        return new $model_name($attributes);
    }


    /**
    * Returns true if this object hasn't been saved yet that is, a record for the object doesn't exist yet.
    */
    public function isNewRecord() {
        if(!isset($this->_newRecord) && !isset($this->{$this->getPrimaryKey()})){
            $this->_newRecord = true;
        }
        return $this->_newRecord;
    }



    /**
    * Reloads the attributes of this object from the database.
    */
    public function reload() {
        /**
        * @todo clear cache
        */
        if($object = $this->find($this->getId())){
            $this->setAttributes($object->getAttributes(), true);
            return true;
        }else {
            return false;
        }
    }



    /**
                         Creating records
    ====================================================================
    */
    /**
    * Creates an object, instantly saves it as a record (if the validation permits it), and returns it.
    * If the save fail under validations, the unsaved object is still returned.
    */
    public function &create($attributes = array(), $replace_existing = true) {
        if(func_num_args() > 1){
            $attributes = func_get_args();
        }
        $model = $this->getModelName();

        $object = new $model(array('init'=>false));
        if(!empty($this->_db)){
            $object->setConnection($this->getConnection());
        }
        $object->init();
        $object->setAttributes($attributes);
        $object->save();
        return $object;
    }

    public function createOrUpdate($validate = true) {
        if($validate && !$this->isValid()){
            $this->transactionFail();
            return false;
        }
        return $this->isNewRecord() ? $this->_create() : $this->_update();
    }


    /**
    * Creates a new record with values matching those of the instance attributes.
    * Must be called as a result of a call to createOrUpdate.
    */
    private function _create() {
        if (!$this->beforeCreate() || !$this->notifyObservers('beforeCreate')){
            return $this->transactionFail();
        }

        $this->_setRecordTimestamps();

        // deprecated section
        if($this->isLockingEnabled() && is_null($this->get('lock_version'))){
            Ak::deprecateWarning(array("Column %lock_version_column should have a default setting. Assumed '1'.",'%lock_version_column'=>'lock_version'));
            $this->setAttribute('lock_version',1);
        } // end

        $attributes = $this->getColumnsForAttributes($this->getAttributes());
        foreach ($attributes as $column=>$value){
            $attributes[$column] = $this->castAttributeForDatabase($column,$value);
        }

        $pk = $this->getPrimaryKey();
        $table = $this->getTableName();

        $id = $this->_db->incrementsPrimaryKeyAutomatically() ? null : $this->_db->getNextSequenceValueFor($table);
        $attributes[$pk] = $id;

        $inserted_id = $this->_db->insertWithAttributes($table, $attributes, $pk, 'Create '.$this->getModelName());

        if ($this->transactionHasFailed()){
            return false;
        }
        $this->setId($inserted_id);

        $this->_newRecord = false;

        if (!$this->afterCreate() || !$this->notifyObservers('afterCreate')){
            return $this->transactionFail();
        }

        return true;
    }

    protected function _setRecordTimestamps() {
        if (!$this->_recordTimestamps){
            return;
        }
        if ($this->_newRecord){
            if ($this->hasColumn('created_at')){
                $this->setAttribute('created_at', Ak::getDate());
            }
            if ($this->hasColumn('created_on')){
                $this->setAttribute('created_on', Ak::getDate(null, 'Y-m-d'));
            }
        }else{
            if ($this->hasColumn('updated_at')){
                $this->setAttribute('updated_at', Ak::getDate());
            }
            if ($this->hasColumn('updated_on')){
                $this->setAttribute('updated_on', Ak::getDate(null, 'Y-m-d'));
            }
        }

        if($this->_newRecord && isset($this->expires_on)){
            if(isset($this->expires_at) && $this->hasColumn('expires_at')){
                $this->setAttribute('expires_at',Ak::getDate(strtotime($this->expires_at) + (defined('AK_TIME_DIFFERENCE') ? AK_TIME_DIFFERENCE*60 : 0)));
            }elseif(isset($this->expires_on) && $this->hasColumn('expires_on')){
                $this->setAttribute('expires_on',Ak::getDate(strtotime($this->expires_on) + (defined('AK_TIME_DIFFERENCE') ? AK_TIME_DIFFERENCE*60 : 0), 'Y-m-d'));
            }
        }

    }

    /*/Creating records*/


    /**
                         Saving records
    ====================================================================
    */
    /**
    * - No record exists: Creates a new record with values matching those of the object attributes.
    * - A record does exist: Updates the record with values matching those of the object attributes.
    */
    public function save($validate = true) {
        if($this->isFrozen()){
            return false;
        }
        $result = false;
        $this->transactionStart();
        if($this->beforeSave() && $this->notifyObservers('beforeSave')){
            $result = $this->createOrUpdate($validate);
            if(!$this->transactionHasFailed()){
                if(!$this->afterSave()){
                    $this->transactionFail();
                }else{
                    if(!$this->notifyObservers('afterSave')){
                        $this->transactionFail();
                    }
                }
            }
        }else{
            $this->transactionFail();
        }

        $result = $this->transactionHasFailed() ? false : $result;
        $this->transactionComplete();

        return $result;
    }

    /*/Saving records*/

    /**
                            Counting Records
    ====================================================================
    See also: Counting Attributes.
    */

    /**
      * Returns the result of an SQL statement that should only include a COUNT(*) in the SELECT part.
      *
      *   $Product->countBySql("SELECT COUNT(*) FROM sales s, customers c WHERE s.customer_id = c.id");
      */
    public function countBySql($sql) {
        if(!stristr($sql, 'COUNT') && stristr($sql, ' FROM ')){
            $sql = 'SELECT COUNT(*) '.substr($sql,strpos(str_replace(' from ',' FROM ', $sql),' FROM '));
        }
        if(!$this->isConnected()){
            $this->establishConnection();
        }

        return (integer)$this->_db->selectValue($sql);
    }
    /*/Counting Records*/

    /**
                          Updating records
    ====================================================================
    See also: Callbacks.
    */

    /**
    * Finds the record from the passed id, instantly saves it with the passed attributes (if the validation permits it),
    * and returns it. If the save fail under validations, the unsaved object is still returned.
    */
    public function update($id, $attributes) {
        if(is_array($id)){
            $results = array();
            foreach ($id as $idx=>$single_id){
                $results[] = $this->update($single_id, isset($attributes[$idx]) ? $attributes[$idx] : $attributes);
            }
            return $results;
        }else{
            $object = $this->find($id);
            $object->updateAttributes($attributes);
            return $object;
        }
    }

    /**
    * Updates a single attribute and saves the record. This is especially useful for boolean flags on existing records.
    */
    public function updateAttribute($name, $value, $should_validate=true) {
        $this->setAttribute($name, $value);
        return $this->save($should_validate);
    }


    /**
    * Updates all the attributes in from the passed array and saves the record. If the object is
    * invalid, the saving will fail and false will be returned.
    */
    public function updateAttributes($attributes, $object = null) {
        isset($object) ? $object->setAttributes($attributes) : $this->setAttributes($attributes);

        return isset($object) ? $object->save() : $this->save();
    }

    /**
    * Updates all records with the SET-part of an SQL update statement in updates and returns an
    * integer with the number of rows updates. A subset of the records can be selected by specifying conditions. Example:
    * <code>$Billing->updateAll("category = 'authorized', approved = 1", "author = 'David'");</code>
    *
    * Or using binds, the safer way:
    * <code>$Billing->updateAll("category = 'authorized', approved = 1", array("author = ?","David"));</code>
    *
    * Important note: Conditions are not sanitized yet so beware of accepting
    * variable conditions when using this function
    */
    public function updateAll($updates, $conditions = null) {
        /**
        * @todo sanitize sql conditions
        */
        $sql = 'UPDATE '.$this->_db->quoteTableName($this->getTableName()).' SET '.$updates;
        $binds = false;
        if(is_array($conditions)) {
            /*
            * take the first item as the conditions, the following are binds
            *
            */
            $binds = $conditions;
            $conditions=array_shift($binds);

        }
        $sql = $this->sanitizeConditions($sql, $conditions);
        if($binds) {
            $sql = array_merge(array($sql),$binds);
        }
        return $this->_db->update($sql, $this->getModelName().' Update All');
    }


    /**
    * Updates the associated record with values matching those of the instance attributes.
    * Must be called as a result of a call to createOrUpdate.
    */
    protected function _update() {
        if(!$this->beforeUpdate() || !$this->notifyObservers('beforeUpdate')){
            return $this->transactionFail();
        }

        $this->_setRecordTimestamps();

        $lock_check_sql = '';
        if ($this->isLockingEnabled()){
            $previous_value = $this->lock_version;
            $this->setAttribute('lock_version', $previous_value + 1);
            $lock_check_sql = ' AND lock_version = '.$previous_value;
        }

        $quoted_attributes = $this->getAvailableAttributesQuotedWithColumnsEscaped();
        $sql = 'UPDATE '.$this->_db->quoteTableName($this->getTableName()).' '.
        'SET '.join(', ', $quoted_attributes) .' '.
        'WHERE '.$this->_db->quoteColumnName($this->getPrimaryKey()).'='.$this->quotedId().$lock_check_sql;

        $affected_rows = $this->_db->update($sql,'Updating '.$this->getModelName());
        if($this->transactionHasFailed()){
            return false;
        }

        if ($this->isLockingEnabled() && $affected_rows != 1){
            $this->setAttribute('lock_version', $previous_value);
            trigger_error(Ak::t('Attempted to update a stale object').AkDebug::getFileAndNumberTextForError(1), E_USER_NOTICE);
            return $this->transactionFail();
        }

        if(!$this->afterUpdate() || !$this->notifyObservers('afterUpdate')){
            return $this->transactionFail();
        }

        return true;
    }

    /*/Updating records*/



    /**
                          Deleting records
    ====================================================================
    See also: Callbacks.
    */

    /**
    * Deletes the record with the given id without instantiating an object first. If an array of
    * ids is provided, all of them are deleted.
    */
    public function delete($id) {
        $id = func_num_args() > 1 ? func_get_args() : $id;
        return $this->deleteAll($this->getPrimaryKey().' IN ('.join(', ', $this->castAttributesForDatabase($this->getPrimaryKey(), Ak::toArray($id))).')');
    }


    /**
    * Deletes all the records that matches the condition without instantiating the objects first
    * (and hence not calling the destroy method). Example:
    *
    * <code>$Post->destroyAll("person_id = 5 AND (category = 'Something' OR category = 'Else')");</code>
    *
    * Or using binds, the safer way:
    *
    * <code>$Post->destroyAll(array("person_id = ? AND (category = ? OR category = ?)",5,"Something","Else"));</code>
    *
    * Important note: Conditions are not sanitized yet so beware of accepting
    * variable conditions when using this function
    */
    public function deleteAll($conditions = null) {
        /**
        * @todo sanitize sql conditions
        */
        $sql = 'DELETE FROM '.$this->_db->quoteTableName($this->getTableName());
        $binds = false;
        if(is_array($conditions)) {
            /*
            * take the first item as the conditions, the following are binds
            *
            */
            $binds = $conditions;
            $conditions=array_shift($binds);

        }
        $sql = $this->sanitizeConditions($sql, $conditions);
        if($binds) {
            $sql = array_merge(array($sql),$binds);
        }
        return $this->_db->delete($sql,$this->getModelName().' Delete All');
    }


    /**
    * Destroys the record with the given id by instantiating the object and calling destroy
    * (all the callbacks are the triggered). If an array of ids is provided, all of them are destroyed.
    * Deletes the record in the database and freezes this instance to reflect that no changes should be
    * made (since they can't be persisted).
    */
    public function destroy($id = null) {
        $id = func_num_args() > 1 ? func_get_args() : $id;

        if(isset($id)){
            $this->transactionStart();
            $id_arr = is_array($id) ? $id : array($id);
            if($objects = $this->find($id_arr)){
                $results = count($objects);
                $no_problems = true;
                for ($i=0; $results > $i; $i++){
                    if(!$objects[$i]->destroy()){
                        $no_problems = false;
                    }
                }
                $this->transactionComplete();
                return $no_problems;
            }else {
                $this->transactionComplete();
                return false;
            }
        }else{
            if(!$this->isNewRecord()){
                $this->transactionStart();
                $return = $this->_destroy() && $this->freeze();
                $this->transactionComplete();
                return $return;
            }
        }
    }

    protected function _destroy() {
        if(!$this->beforeDestroy() || !$this->notifyObservers('beforeDestroy')){
            return $this->transactionFail();
        }
        $sql = 'DELETE FROM '.$this->_db->quoteTableName($this->getTableName()).' WHERE '.$this->getPrimaryKey().' = '.$this->castAttributeForDatabase($this->getPrimaryKey(), $this->getId());
        if ($this->_db->delete($sql,$this->getModelName().' Destroy') !== 1){
            return $this->transactionFail();
        }

        if (!$this->afterDestroy() || !$this->notifyObservers('afterDestroy')){
            return $this->transactionFail();
        }
        return true;
    }

    /**
    * Destroys the objects for all the records that matches the condition by instantiating
    * each object and calling the destroy method.
    *
    * Example:
    *
    *   $Person->destroyAll("last_login < '2004-04-04'");
    */
    public function destroyAll($conditions) {
        if($objects = $this->find('all',array('conditions'=>$conditions))){
            $results = count($objects);
            $no_problems = true;
            for ($i=0; $results > $i; $i++){
                if(!$objects[$i]->destroy()){
                    $no_problems = false;
                }
            }
            return $no_problems;
        }else {
            return false;
        }
    }

    /*/Deleting records*/





    /**
                         Setting Attributes
    ====================================================================
    See also: Getting Attributes, Model Attributes, Toggling Attributes, Counting Attributes.
    */
    public function setAttribute($attribute, $value, $inspect_for_callback_child_method = AK_ACTIVE_RECORD_ENABLE_CALLBACK_SETTERS, $compose_after_set = true) {
        if($attribute[0] == '_'){
            return false;
        }
        if($this->isFrozen()){
            return false;
        }
        if($inspect_for_callback_child_method === true){
            $_setter_name = 'set'.AkInflector::camelize($attribute);
            if(method_exists($this, $_setter_name)){
                $this->{$attribute.'_before_type_cast'} = $value;
                return $this->$_setter_name($value);

            }
        }

        if($this->hasAttribute($attribute)){
            $this->{$attribute.'_before_type_cast'} = $value;
            $this->$attribute = $value;
            if($compose_after_set && !empty($this->_combinedAttributes) && !$this->requiredForCombination($attribute)){
                $combined_attributes = $this->getCombinedAttributesWhereThisAttributeIsUsed($attribute);
                foreach ($combined_attributes as $combined_attribute){
                    $this->composeCombinedAttribute($combined_attribute);
                }
            }
            if ($compose_after_set && !empty($this->_combinedAttributes) && $this->isCombinedAttribute($attribute)){
                $this->decomposeCombinedAttribute($attribute);
            }
        }elseif(substr($attribute,-12) == 'confirmation' && $this->hasAttribute(substr($attribute,0,-13))){
            $this->$attribute = $value;
        }

        if($this->internationalize){
            $this->setInternationalizedAttribute($attribute, $value, $inspect_for_callback_child_method, $compose_after_set);
        }
        return true;
    }

    public function set($attribute, $value = null, $inspect_for_callback_child_method = true, $compose_after_set = true) {
        if(is_array($attribute)){
            return $this->setAttributes($attribute);
        }
        return $this->setAttribute($attribute, $value, $inspect_for_callback_child_method, $compose_after_set);
    }

    /**
    * Allows you to set all the attributes at once by passing in an array with
    * keys matching the attribute names (which again matches the column names).
    * Sensitive attributes can be protected from this form of mass-assignment by
    * using the $this->setProtectedAttributes method. Or you can alternatively
    * specify which attributes can be accessed in with the $this->setAccessibleAttributes method.
    * Then all the attributes not included in that won?t be allowed to be mass-assigned.
    */
    public function setAttributes($attributes, $override_attribute_protection = false, $inspect_for_callback_child_method = AK_ACTIVE_RECORD_ENABLE_CALLBACK_GETTERS) {
        $this->_castDateParametersFromDateHelper($attributes);
        if(!$override_attribute_protection){
            $attributes = $this->removeAttributesProtectedFromMassAssignment($attributes);
        }
        if(!empty($attributes) && is_array($attributes)){
            foreach ($attributes as $k=>$v){
                $this->setAttribute($k, $v, $inspect_for_callback_child_method);
            }
        }
    }


    public function setId($value) {
        if($this->isFrozen()){
            return false;
        }
        $pk = $this->getPrimaryKey();
        $this->$pk = $value;
        return true;
    }


    /*/Setting Attributes*/

    /**
                         Getting Attributes
    ====================================================================
    See also: Setting Attributes, Model Attributes, Toggling Attributes, Counting Attributes.
    */

    public function getAttribute($attribute, $inspect_for_callback_child_method = AK_ACTIVE_RECORD_ENABLE_CALLBACK_GETTERS) {
        if($attribute[0] == '_'){
            return false;
        }
        if($attribute == $this->getInheritanceColumn()){
            return AkInflector::humanize(AkInflector::underscore($this->getType()));
        }
        if($inspect_for_callback_child_method === true){
            $_getter_method = 'get'.AkInflector::camelize($attribute);
            if(method_exists($this, $_getter_method)){
                return $this->$_getter_method();
            }
        }
        if(isset($this->$attribute) || (!isset($this->$attribute) && !empty($this->_combinedAttributes) && $this->isCombinedAttribute($attribute))){
            if($this->hasAttribute($attribute)){
                if (!empty($this->_combinedAttributes) && $this->isCombinedAttribute($attribute)){
                    $this->composeCombinedAttribute($attribute);
                }
                return isset($this->$attribute) ? $this->$attribute : null;
            }elseif($this->internationalize && $this->isInternationalizeCandidate($attribute)){
                if(!empty($this->$attribute) && is_string($this->$attribute)){
                    return $this->$attribute;
                }
                $current_locale = $this->getCurrentLocale();
                if(!empty($this->$attribute[$current_locale]) && is_array($this->$attribute)){
                    return $this->$attribute[$current_locale];
                }
                return $this->getAttribute($current_locale.'_'.$attribute);
            }
        }

        if($this->internationalize){
            return $this->getAttributeByLocale($attribute, is_bool($inspect_for_callback_child_method) ? $this->getCurrentLocale() : $inspect_for_callback_child_method);
        }
        return null;
    }

    public function get($attribute = null, $inspect_for_callback_child_method = true) {
        return !isset($attribute) ? $this->getAttributes($inspect_for_callback_child_method) : $this->getAttribute($attribute, $inspect_for_callback_child_method);
    }

    /**
    * Returns an array of all the attributes with their names as keys and clones of their objects as values in case they are objects.
    */
    public function getAttributes($inspect_for_callback_child_method = AK_ACTIVE_RECORD_ENABLE_CALLBACK_GETTERS) {
        $attributes = array();
        $available_attributes = $this->getAvailableAttributes();
        foreach ($available_attributes as $available_attribute){
            $attribute = $this->getAttribute($available_attribute['name'], $inspect_for_callback_child_method);
            $attributes[$available_attribute['name']] = is_object($attribute) ? clone($attribute) : $attribute;
        }

        if($this->internationalize){
            $current_locale = $this->getCurrentLocale();
            foreach ($this->getInternationalizedColumns() as $column=>$languages){
                if(empty($attributes[$column]) && isset($attributes[$current_locale.'_'.$column]) && in_array($current_locale,$languages)){
                    $attributes[$column] = $attributes[$current_locale.'_'.$column];
                }
            }
        }

        return $attributes;
    }


    /**
    * Every Active Record class must use "id" as their primary ID. This getter overwrites the native id method, which isn't being used in this context.
    */
    public function getId() {
        $pk=$this->getPrimaryKey();
        if(empty($pk)) {
            debug_print_backtrace();
        }
        return $this->{$pk};
    }

    /*/Getting Attributes*/



    /**
                         Toggling Attributes
    ====================================================================
    See also: Setting Attributes, Getting Attributes.
    */
    /**
    * Turns an attribute that's currently true into false and vice versa. Returns attribute value.
    */
    public function toggleAttribute($attribute) {
        $value = $this->getAttribute($attribute);
        $new_value = $value ? false : true;
        $this->setAttribute($attribute, $new_value);
        return $new_value;
    }


    /**
    * Toggles the attribute and saves the record.
    */
    public function toggleAttributeAndSave($attribute) {
        $value = $this->toggleAttribute($attribute);
        if($this->updateAttribute($attribute, $value)){
            return $value;
        }
        return null;
    }

    /*/Toggling Attributes*/




    /**
                         Protecting attributes
    ====================================================================
    */

    /**
    * If this macro is used, only those attributed named in it will be accessible
    * for mass-assignment, such as new ModelName($attributes) and $this->attributes($attributes).
    * This is the more conservative choice for mass-assignment protection.
    * If you'd rather start from an all-open default and restrict attributes as needed,
    * have a look at AkActiveRecord::setProtectedAttributes().
    */
    public function setAccessibleAttributes() {
        $args = func_get_args();
        $this->_accessibleAttributes = array_unique(array_merge((array)$this->_accessibleAttributes, $args));
    }

    /**
     * Attributes named in this macro are protected from mass-assignment, such as
     * new ModelName($attributes) and $this->attributes(attributes). Their assignment
     * will simply be ignored. Instead, you can use the direct writer methods to do assignment.
     * This is meant to protect sensitive attributes to be overwritten by URL/form hackers.
     *
     * Example:
     * <code>
     *   class Customer extends ActiveRecord
     *    {
     *      public function Customer()
     *      {
     *          $this->setProtectedAttributes('credit_rating');
     *      }
     *    }
     *
     *    $Customer = new Customer('name' => 'David', 'credit_rating' => 'Excellent');
     *    $Customer->credit_rating // => null
     *    $Customer->attributes(array('description' => 'Jolly fellow', 'credit_rating' => 'Superb'));
     *    $Customer->credit_rating // => null
     *
     *    $Customer->credit_rating = 'Average'
     *    $Customer->credit_rating // => 'Average'
     *  </code>
     */
    public function setProtectedAttributes() {
        $args = func_get_args();
        $this->_protectedAttributes = array_unique(array_merge((array)$this->_protectedAttributes, $args));
    }

    public function removeAttributesProtectedFromMassAssignment($attributes) {
        if(!empty($this->_accessibleAttributes) && is_array($this->_accessibleAttributes) &&  is_array($attributes)){
            foreach (array_keys($attributes) as $k){
                if(!in_array($k,$this->_accessibleAttributes)){
                    unset($attributes[$k]);
                }
            }
        }elseif (!empty($this->_protectedAttributes) && is_array($this->_protectedAttributes) &&  is_array($attributes)){
            foreach (array_keys($attributes) as $k){
                if(in_array($k,$this->_protectedAttributes)){
                    unset($attributes[$k]);
                }
            }
        }
        return $attributes;
    }

    /*/Protecting attributes*/


    /**
                          Model Attributes
     ====================================================================
     See also: Getting Attributes, Setting Attributes.
     */

    public function getAvailableAttributes() {
        return empty($this->_combinedAttributes) ? $this->getColumns() : array_merge($this->getColumns(), $this->getAvailableCombinedAttributes());
    }

    public function getAttributeCaption($attribute) {
        return $this->t(AkInflector::humanize($attribute));
    }

    /**
     * This function is useful in case you need to know if attributes have been assigned to an object.
     */
    public function hasAttributesDefined() {
        $attributes = join('',$this->getAttributes());
        return empty($attributes);
    }


    /**
    * Returns the primary key field.
    */
    public function getPrimaryKey() {
        if(!isset($this->_primaryKey)){
            $this->setPrimaryKey();
        }
        return $this->_primaryKey;
    }

    public function getColumnNames() {
        if(empty($this->_columnNames)){
            $columns = $this->getColumns();
            foreach ($columns as $column_name=>$details){
                $this->_columnNames[$column_name] = isset($details->columnName) ? $this->t($details->columnName) : $this->getAttributeCaption($column_name);
            }
        }
        return $this->_columnNames;
    }


    /**
    * Returns an array of columns objects where the primary id, all columns ending in "_id" or "_count",
    * and columns used for single table inheritance has been removed.
    */
    public function getContentColumns() {
        $inheritance_column = $this->getInheritanceColumn();
        $columns = $this->getColumns();

        foreach ($columns as $name => $details){
            if(
            (substr($name,-3) == '_id' || substr($name,-6) == '_count') ||
            !empty($details['primaryKey']) ||
            ($inheritance_column !== false && $inheritance_column == $name)
            ){
                unset($columns[$name]);
            }
        }
        return $columns;
    }

    /**
    * Returns an array of names for the attributes available on this object sorted alphabetically.
    */
    public function getAttributeNames() {
        $attributes = array_keys($this->getAvailableAttributes());
        $names = array_combine($attributes,array_map(array($this,'getAttributeCaption'), $attributes));
        natsort($names);
        return $names;
    }


    /**
    * Returns true if the specified attribute has been set by the user or by a database load and is neither null nor empty?
    */
    public function isAttributePresent($attribute) {
        $value = $this->getAttribute($attribute);
        return !empty($value);
    }

    /**
    * Returns true if given attribute exists for this Model.
    *
    * @param string $attribute
    * @return boolean
    */
    public function hasAttribute ($attribute) {
        empty($this->_columns) ? $this->getColumns() : $this->_columns; // HINT: only used by HasAndBelongsToMany joinObjects, if the table is not present yet!
        return isset($this->_columns[$attribute]) || (!empty($this->_combinedAttributes) && $this->isCombinedAttribute($attribute));
    }

    /*/Model Attributes*/





    /**
                         Database connection
    ====================================================================
    */
    /**
    * Establishes the connection to the database. Accepts either a profile name specified in config/config.php or
    * an array as input where the 'type' key must be specified with the name of a database adapter (in lower-case)
    * example for regular databases (MySQL, Postgresql, etc):
    *
    *   $AkActiveRecord->establishConnection('development');
    *   $AkActiveRecord->establishConnection('super_user');
    *
    *   $AkActiveRecord->establishConnection(
    *       array(
    *       'type'  => "mysql",
    *       'host'     => "localhost",
    *       'username' => "myuser",
    *       'password' => "mypass",
    *       'database' => "somedatabase"
    *       ));
    *
    *    Example for SQLite database:
    *
    *     $AkActiveRecord->establishConnection(
    *       array(
    *       'type' => "sqlite",
    *       'dbfile'  => "path/to/dbfile"
    *       )
    *     )
    */
    public function &establishConnection($specification_or_profile = AK_DEFAULT_DATABASE_PROFILE, $force = false) {
        if($force || !$this->isConnected()){
            $adapter = AkDbAdapter::getInstance($specification_or_profile);
            return $this->setConnection($adapter);
        }
        return $this->_db;
    }


    /**
    * Returns true if a connection that's accessible to this class have already been opened.
    */
    public function isConnected() {
        return isset($this->_db);
    }

    /**
    * Returns the connection currently associated with the class. This can also be used to
    * "borrow" the connection to do database work unrelated to any of the specific Active Records.
    */
    public function &getConnection() {
        return $this->_db;
    }
    public function &getAdapter() {
        return $this->_db;
    }

    /**
    * Sets the connection for the class.
    */
    public function &setConnection($db_adapter = null) {
        if (is_null($db_adapter)){
            $db_adapter = AkDbAdapter::getInstance();
        }
        $this->_db = $db_adapter;
        return $db_adapter;
    }

    public function getDatabaseType() {
        return $this->_db->type();
    }



    /*/Database connection*/


    /**
                           Table Settings
    ====================================================================
    See also: Database Reflection.
    */

    /**
    * Defines the primary key field ? can be overridden in subclasses.
    */
    public function setPrimaryKey($primary_key = 'id') {
        if(!$this->hasColumn($primary_key)){
            trigger_error($this->t('Opps! We could not find primary key column %primary_key on the table %table, for the model %model',array('%primary_key'=>$primary_key,'%table'=>$this->getTableName(), '%model'=>$this->getModelName())).' '.AkDebug::getFileAndNumberTextForError(1),E_USER_ERROR);
        }else {
            $this->_primaryKey = $primary_key;
        }
    }


    public function getTableName($modify_for_associations = true) {
        if(!isset($this->_tableName)){
            // We check if we are on a inheritance Table Model
            $this->_getClassForDatabaseTableMapping();
            if(!isset($this->_tableName)){
                $this->setTableName();
            }
        }

        if($modify_for_associations && isset($this->_associationTablePrefixes[$this->_tableName])){
            return $this->_associationTablePrefixes[$this->_tableName];
        }

        return $this->_tableName;
    }

    public function setTableName($table_name = null, $check_for_existence = AK_ACTIVE_RECORD_VALIDATE_TABLE_NAMES, $check_mode = false) {
        if(empty($table_name)){
            $table_name = AkInflector::tableize($this->getModelName());
        }
        if($check_for_existence){
            !isset($this->_db) && $this->establishConnection();
            if(!$this->_db->tableExists($table_name, true)){
                if(!$check_mode){
                    trigger_error(Ak::t('Unable to set "%table_name" table for the model "%model".'.
                    '  There is no "%table_name" available into current database layout.'.
                    ' Set AK_ACTIVE_RECORD_VALIDATE_TABLE_NAMES constant to false in order to'.
                    ' avoid table name validation',array('%table_name'=>$table_name,'%model'=>$this->getModelName())).AkDebug::getFileAndNumberTextForError(1),E_USER_WARNING);
                }
                return false;
            }
        }
        $this->_tableName = $table_name;
        return true;
    }


    public function getOnlyAvailableAttributes($attributes) {
        $table_name = $this->getTableName();
        $ret_attributes = array();
        if(!empty($attributes) && is_array($attributes)){
            $available_attributes = $this->getAvailableAttributes();

            $keys = array_keys($attributes);
            $size = sizeOf($keys);
            for ($i=0; $i < $size; $i++){
                $k = str_replace($table_name.'.','',$keys[$i]);
                if(isset($available_attributes[$k]['name'][$k])){
                    $ret_attributes[$k] = $attributes[$keys[$i]];
                }
            }
        }
        return $ret_attributes;
    }

    public function getColumnsForAttributes($attributes) {
        $ret_attributes = array();
        $table_name = $this->getTableName();
        if(!empty($attributes) && is_array($attributes)){
            $columns = $this->getColumns();
            foreach ($attributes as $k=>$v){
                $k = str_replace($table_name.'.','',$k);
                if(isset($columns[$k]['name'][$k])){
                    $ret_attributes[$k] = $v;
                }
            }
        }
        return $ret_attributes;
    }

    /**
    * Returns true if given attribute exists for this Model.
    *
    * @param string $name Name of table to look in
    * @return boolean
    */
    public function hasColumn($column) {
        empty($this->_columns) ? $this->getColumns() : $this->_columns;
        return isset($this->_columns[$column]);
    }


    /**
     * This method retrieves current class name that will be used to map
     * your database to this object.
     */
    protected function _getClassForDatabaseTableMapping() {
        $class_name = get_class($this);
        if($this instanceof AkActiveRecord){
            $parent_class = get_parent_class($this);
            while (substr($parent_class,-12) != 'ActiveRecord'){
                $class_name = $parent_class;
                $parent_class = get_parent_class($parent_class);
            }
        }

        // This is an Active Record Inheritance so we set current table to parent table.
        if(!empty($class_name) && $class_name != 'ActiveRecord'){
            $this->_inheritanceClassName = $class_name;
            @$this->setTableName(AkInflector::tableize($class_name), false);
        }
        return $class_name;
    }


    /**
    * Initializes the attributes array with keys matching the columns from the linked table and
    * the values matching the corresponding default value of that column, so
    * that a new instance, or one populated from a passed-in array, still has all the attributes
    * that instances loaded from the database would.
    */
    public function attributesFromColumnDefinition() {
        if(!$attributes = $this->_getCacheForModelMethod(__METHOD__)){
            $attributes = array();
            foreach ((array)$this->getColumns() as $column_name=>$column_settings){
                if (!isset($column_settings['primaryKey']) && isset($column_settings['hasDefault'])) {
                    $attributes[$column_name] = $this->_db->extractValueFromDefault($column_settings['defaultValue']);
                } else {
                    $attributes[$column_name] = null;
                }
            }
            $this->_setCacheForModelMethod(__METHOD__, $attributes);
        }
        return $attributes;
    }

    /**
    * Returns an array of column objects for the table associated with this class.
    */
    public function getColumns($force_reload = false) {
        if(empty($this->_columns) || $force_reload){
            $this->_columns = $this->getColumnSettings($force_reload);
        }

        return (array)$this->_columns;
    }

    public function getColumnSettings($force_reload = false) {
        if(empty($this->_columnsSettings) || $force_reload){
            $this->loadColumnsSettings($force_reload);
            $this->initiateColumnsToNull();
        }
        return isset($this->_columnsSettings) ? $this->_columnsSettings : array();
    }

    public function loadColumnsSettings($force_reload = false) {
        if(is_null($this->_db)){
            $this->establishConnection();
        }
        $this->_columnsSettings = ($force_reload ? null : $this->_getPersistedTableColumnSettings());
        if(empty($this->_columnsSettings)){
            $this->readColumnSettings();
        }
        return isset($this->_columnsSettings) ? $this->_columnsSettings : array();
    }

    public function initiateAttributeToNull($attribute) {
        if(!isset($this->$attribute)){
            $this->$attribute = null;
        }
    }

    public function initiateColumnsToNull() {
        if(isset($this->_columnsSettings) && is_array($this->_columnsSettings)){
            array_map(array($this,'initiateAttributeToNull'), array_keys($this->_columnsSettings));
        }
    }


    public function readColumnSettings() {
        if(empty($this->_dataDictionary)){
            $this->_dataDictionary = $this->_db->getDictionary();
        }

        $column_objects = $this->_databaseTableInternals($this->getTableName());

        if( !isset($this->_avoidTableNameValidation) &&
        !is_array($column_objects) &&
        !$this->_runCurrentModelInstallerIfExists($column_objects)){
            // akelos_migrations is the first active record to be installed, therefore the table will be created after the first run.
            if($this->getTableName() != 'akelos_migrations'){
                trigger_error(Ak::t('Ooops! Could not fetch details for the table %table_name.', array('%table_name'=>$this->getTableName())).AkDebug::getFileAndNumberTextForError(4), E_USER_NOTICE);
            }
            return false;
        }elseif (empty($column_objects)){
            $this->_runCurrentModelInstallerIfExists($column_objects);
        }
        if(is_array($column_objects)){
            // sorting columns alphabetically will help on writting consinting tests in the long run
            ksort($column_objects);
            foreach (array_keys($column_objects) as $k){
                $this->_setColumnSettings($column_objects[$k]->name, $column_objects[$k]);
            }
        }
        if(!empty($this->_columnsSettings)){
            $this->_persistTableColumnSettings();
        }
    }


    /**
    * Resets all the cached information about columns, which will cause they to be reloaded on the next request.
    */
    public function resetColumnInformation() {
        $this->_clearPersitedColumnSettings();
        $this->_columnNames = $this->_columns = $this->_columnsSettings = $this->_contentColumns = array();
    }

    protected function _getModelColumnSettings() {
        return AkDbSchemaCache::get($this->getModelName().'_column_settings');
    }

    protected function _persistTableColumnSettings() {
        AkDbSchemaCache::set($this->getModelName().'_column_settings', $this->_columnsSettings);
    }

    protected function _getPersistedTableColumnSettings() {
        return AkDbSchemaCache::get($this->getModelName().'_column_settings');
    }

    protected function _clearPersitedColumnSettings() {
        AkDbSchemaCache::clear($this->getModelName());
    }






    /**
    * Akelos data types are mapped to phpAdodb data types
    *
    * Returns the Akelos data type for an Adodb Column Object
    *
    * 'C'=>'string', // Varchar, capped to 255 characters.
    * 'X' => 'text' // Larger varchar, capped to 4000 characters (to be compatible with Oracle).
    * 'XL' => 'text' // For Oracle, returns CLOB, otherwise the largest varchar size.
    *
    * 'C2' => 'string', // Multibyte varchar
    * 'X2' => 'string', // Multibyte varchar (largest size)
    *
    * 'B' => 'binary', // BLOB (binary large object)
    *
    * 'D' => array('date', 'datetime'), //  Date (some databases do not support this, and we return a datetime type)
    * 'T' =>  array('datetime', 'timestamp'), //Datetime or Timestamp
    * 'L' => 'boolean', // Integer field suitable for storing booleans (0 or 1)
    * 'I' => // Integer (mapped to I4)
    * 'I1' => 'integer', // 1-byte integer
    * 'I2' => 'integer', // 2-byte integer
    * 'I4' => 'integer', // 4-byte integer
    * 'I8' => 'integer', // 8-byte integer
    * 'F' => 'float', // Floating point number
    * 'N' => 'integer' //  Numeric or decimal number
    *
    * @return string One of this 'string','text','integer','float','datetime','timestamp',
    * 'time', 'name','date', 'binary', 'boolean'
    */
    public function getAkelosDataType(&$adodb_column_object) {
        $config_var_name = AkInflector::variablize($adodb_column_object->name.'_data_type');
        if(!empty($this->{$config_var_name})){
            return $this->{$config_var_name};
        }
        if(stristr($adodb_column_object->type, 'BLOB')){
            return 'binary';
        }
        if(!empty($adodb_column_object->auto_increment)) {
            return 'serial';
        }
        $meta_type = $this->_dataDictionary->MetaType($adodb_column_object);
        $adodb_data_types = array(
        'C'     =>  'string',   // Varchar, capped to 255 characters.
        'X'     =>  'text',     // Larger varchar, capped to 4000 characters (to be compatible with Oracle).
        'XL'    =>  'text',     // For Oracle, returns CLOB, otherwise the largest varchar size.

        'C2'    =>  'string',   // Multibyte varchar
        'X2'    =>  'string',   // Multibyte varchar (largest size)

        'B'     =>  'binary',   // BLOB (binary large object)

        'D'     =>  array('date'),  //  Date
        'T'     =>  array('datetime', 'timestamp'), //Datetime or Timestamp
        'L'     =>  'boolean',  // Integer field suitable for storing booleans (0 or 1)
        'R'     =>  'serial',   // Serial Integer
        'I'     =>  'integer',  // Integer (mapped to I4)
        'I1'    =>  'integer',  // 1-byte integer
        'I2'    =>  'integer',  // 2-byte integer
        'I4'    =>  'integer',  // 4-byte integer
        'I8'    =>  'integer',  // 8-byte integer
        'F'     =>  'float',    // Floating point number
        'N'     =>  'decimal'   //  Numeric or decimal number
        );

        $result = !isset($adodb_data_types[$meta_type]) ?
        'string' :
        (is_array($adodb_data_types[$meta_type]) ? $adodb_data_types[$meta_type][0] : $adodb_data_types[$meta_type]);

        if($result == 'text'){
            if((strstr($adodb_column_object->type, 'CHAR')) || (isset($adodb_column_object->max_length) && $adodb_column_object->max_length > 0 && $adodb_column_object->max_length < 256 )){
                return 'string';
            }
        }

        if($this->getDatabaseType() == 'mysql'){
            if($result == 'integer' && stristr($adodb_column_object->type, 'TINYINT')){
                return 'boolean';
            }
        }elseif($this->getDatabaseType() == 'postgre'){
            if($adodb_column_object->type == 'timestamp' || $result == 'datetime'){
                $adodb_column_object->max_length = 19;
            }
        }elseif($this->getDatabaseType() == 'sqlite'){
            if($result == 'integer' && (int)$adodb_column_object->max_length === 1 && stristr($adodb_column_object->type, 'TINYINT')){
                return 'boolean';
            }elseif($result == 'integer' && stristr($adodb_column_object->type, 'DOUBLE')){
                return 'float';
            }
        }

        if($result == 'datetime' && substr($adodb_column_object->name,-3) == '_on'){
            $result = 'date';
        }

        return $result;
    }


    private function _setColumnSettings($column_name, $column_object) {
        $this->_columnsSettings[$column_name] = array();
        $this->_columnsSettings[$column_name]['name'] = $column_object->name;

        if($this->internationalize && $this->isInternationalizeCandidate($column_object->name)){
            $this->addInternationalizedColumn($column_object->name);
        }

        $this->_columnsSettings[$column_name]['type'] = $this->getAkelosDataType($column_object);

        if(!empty($column_object->primary_key)){
            $this->_primaryKey = empty($this->_primaryKey) ? $column_object->name : $this->_primaryKey;
            $this->_columnsSettings[$column_name]['primaryKey'] = true;
        }
        if(!empty($column_object->auto_increment)){
            $this->_columnsSettings[$column_name]['autoIncrement'] = true;
        }
        if(!empty($column_object->has_default)){
            $this->_columnsSettings[$column_name]['hasDefault'] = true;
        }
        if(!empty($column_object->not_null)){
            $this->_columnsSettings[$column_name]['notNull'] = true;
        }
        if(!empty($column_object->max_length) && $column_object->max_length > 0){
            $this->_columnsSettings[$column_name]['maxLength'] = $column_object->max_length;
        }
        if(!empty($column_object->scale) && $column_object->scale > 0){
            $this->_columnsSettings[$column_name]['scale'] = $column_object->scale;
        }
        if(isset($column_object->default_value)){
            $this->_columnsSettings[$column_name]['defaultValue'] = $column_object->default_value;
        }
    }

    /**
    * If is the first time we use a model this function will run the installer for the model if it exists
    */
    private function _runCurrentModelInstallerIfExists(&$column_objects) {
        static $installed_models = array();
        if(!defined('AK_AVOID_AUTOMATIC_ACTIVE_RECORD_INSTALLERS') && !in_array($this->getModelName(), $installed_models)){
            $installed_models[] = $this->getModelName();
            $installer_name = $this->getModelName().'Installer';
            $installer_file = AkConfig::getDir('app_installers').DS.AkInflector::underscore($installer_name).'.php';
            if(file_exists($installer_file)){
                require_once($installer_file);
                if(class_exists($installer_name)){
                    $Installer = new $installer_name($this->getConnection());
                    if(method_exists($Installer,'install')){
                        $Installer->install();
                        $column_objects = $this->_databaseTableInternals($this->getTableName());
                        return !empty($column_objects);
                    }
                }
            }
        }
        return false;
    }

    /**
     * Gets information from the database engine about a single table
     */
    private function _databaseTableInternals($table) {
        if (!$cache = AkDbSchemaCache::get('table_internals_for_'.$table)) {
            $cache = $this->_db->getColumnDetails($table);
            AkDbSchemaCache::set('table_internals_for_'.$table, $cache);
        }
        return $cache;
    }

    /*/Table Settings*/





    /**
                             Type Casting
    ====================================================================
    See also: Database Reflection.
    */

    public function getAttributesBeforeTypeCast() {
        $attributes_array = array();
        $available_attributes = $this->getAvailableAttributes();
        foreach ($available_attributes as $attribute){
            $attribute_value = $this->getAttributeBeforeTypeCast($attribute['name']);
            if(!empty($attribute_value)){
                $attributes_array[$attribute['name']] = $attribute_value;
            }
        }
        return $attributes_array;
    }


    public function getAttributeBeforeTypeCast($attribute) {
        if(isset($this->{$attribute.'_before_type_cast'})){
            return $this->{$attribute.'_before_type_cast'};
        }
        return null;
    }

    public function getAvailableAttributesQuoted($inspect_for_callback_child_method = AK_ACTIVE_RECORD_ENABLE_CALLBACK_GETTERS) {
        return $this->getAttributesQuoted($this->getAttributes($inspect_for_callback_child_method));
    }

    public function getAvailableAttributesQuotedWithColumnsEscaped($inspect_for_callback_child_method = AK_ACTIVE_RECORD_ENABLE_CALLBACK_GETTERS) {
        return $this->getAttributesQuoted($this->getAttributes($inspect_for_callback_child_method), array('escape_columns' => true));
    }


    public function getAttributesQuoted($attributes_array, $options = array()) {
        $set = array();
        $escape_columns = !empty($options['escape_columns']);
        $attributes_array = $this->getSanitizedConditionsArray($attributes_array);
        foreach (array_diff($attributes_array,array('')) as $k=>$v){
            if($escape_columns){
                $set[$k] = $this->_db->quoteColumnName($k).'='.$v;
            }else{
                $set[$k] = $k.'='.$v;
            }
        }
        return $set;
    }

    public function getColumnType($column_name) {
        empty($this->_columns) ? $this->getColumns() : null;
        return empty($this->_columns[$column_name]['type']) ? false : $this->_columns[$column_name]['type'];
    }

    public function getColumnScale($column_name) {
        empty($this->_columns) ? $this->getColumns() : null;
        return empty($this->_columns[$column_name]['scale']) ? false : $this->_columns[$column_name]['scale'];
    }

    public function quotedId($id = false) {
        return $this->castAttributeForDatabase($this->getPrimaryKey(), $id ? $id : $this->getId());
    }


    public function castAttributeForDatabase($column_name, $value, $add_quotes = true) {
        $result = '';
        switch ($this->getColumnType($column_name)) {
            case 'datetime':
                if(!empty($value)){
                    $date_time = $this->_db->quote_datetime(Ak::getTimestamp($value));
                    $result = $add_quotes ? $date_time : trim($date_time ,"'");
                }else{
                    $result = 'null';
                }
                break;

            case 'date':
                if(!empty($value)){
                    $date = $this->_db->quote_date(Ak::getTimestamp($value));
                    $result = $add_quotes ? $date : trim($date, "'");
                }else{
                    $result = 'null';
                }
                break;

            case 'boolean':
                $result = is_null($value) ? 'null' : (!empty($value) ? "'1'" : "'0'");
                break;

            case 'binary':
                if(!empty($value) && $this->_shouldCompressColumn($column_name)){
                    $value = Ak::compress($value);
                }
                if($this->getDatabaseType() == 'postgre'){
                    $result =  is_null($value) ? 'null::bytea ' : " '".$this->_db->escape_blob($value)."'::bytea ";
                }else{
                    $result = is_null($value) ? 'null' : ($add_quotes ? $this->_db->quote_string($value) : $value);
                }
                break;

            case 'decimal':
                if(is_null($value)){
                    $result = 'null';
                }else{
                    if($scale = $this->getColumnScale($column_name)){
                        $value = number_format($value, $scale, '.', '');
                    }
                    $result = $add_quotes ? $this->_db->quote_string($value) : $value;
                }
                break;

            case 'serial':
            case 'integer':
                $result = (is_null($value) || $value==='') ? 'null' : (integer)$value;
                break;

            case 'float':
                $result = (empty($value) && $value !== 0) ? 'null' : (is_numeric($value) ? $value : $this->_db->quote_string($value));
                $result = !empty($this->_columns[$column_name]['notNull']) && $result == 'null' && $this->getDatabaseType() == 'sqlite' ? '0' : $result;
                break;

            default:
                if($this->_shouldSerializeColumn($column_name)){
                    $value = serialize($value);
                }
                $result = is_null($value) ? 'null' : ($add_quotes ? $this->_db->quote_string($value) : $value);
                break;
        }

        //  !! nullable vs. not nullable !!
        return empty($this->_columns[$column_name]['notNull']) ? ($result === '' ? "''" : $result) : ($result === 'null' ? '' : $result);
    }

    /**
    * You can use this method for casting multiple attributes of the same time at once.
    *
    * You can pass an array of values or an array of Active Records that might be the response of a finder.
    */
    public function castAttributesForDatabase($column_name, $values, $add_quotes = true) {
        $casted_values = array();
        $values = !empty($values[0]) && is_object($values[0]) && method_exists($values[0], 'collect') && method_exists($values[0], 'getPrimaryKey') ?
        $values[0]->collect($values, $values[0]->getPrimaryKey(), $column_name)
        : Ak::toArray($values);
        if(!empty($values)){
            $casted_values = array();
            foreach ($values as $value){
                $casted_values[] = $this->castAttributeForDatabase($column_name, $value, $add_quotes);
            }
        }
        return $casted_values;
    }

    public function castAttributesFromDatabase($attributes = array()) {
        foreach($attributes as $key => $value) {
            $attributes[$key] = $this->castAttributeFromDatabase($key, $value);
        }
        return $attributes;
    }

    public function castAttributeFromDatabase($column_name, $value) {
        if($this->hasColumn($column_name)){
            $column_type = $this->getColumnType($column_name);

            if($column_type){
                if('integer' == $column_type){
                    return is_null($value) ? null : (integer)$value;
                    //return is_null($value) ? null : $value;    // maybe for bigint we can do this
                }elseif('boolean' == $column_type){
                    if (is_null($value)) {
                        return null;
                    }
                    if ($this->getDatabaseType()=='postgre'){
                        return $value=='t' ? true : false;
                    }
                    return (integer)$value === 1 ? true : false;
                }elseif(!empty($value) && 'date' == $column_type && strstr(trim($value),' ')){
                    return substr($value,0,10) == '0000-00-00' ? null : str_replace(substr($value,strpos($value,' ')), '', $value);
                }elseif (!empty($value) && 'datetime' == $column_type && substr($value,0,10) == '0000-00-00'){
                    return null;
                }elseif ('binary' == $column_type && $this->getDatabaseType() == 'postgre'){
                    $value = $this->_db->unescape_blob($value);
                    $value = empty($value) || trim($value) == 'null' ? null : $value;
                    if(!empty($value) && $this->_shouldCompressColumn($column_name)){
                        $value = Ak::uncompress($value);
                    }
                }elseif($this->_shouldSerializeColumn($column_name) && is_string($value)){
                    $this->_ensureClassExistsForSerializedColumnBeforeUnserializing($column_name);
                    $value = @unserialize($value);
                }
            }
        }
        return $value;
    }


    /**
    * Joins date arguments into a single attribute. Like the array generated by the date_helper, so
    * array('published_on(1i)' => 2002, 'published_on(2i)' => 'January', 'published_on(3i)' => 24)
    * Will be converted to array('published_on'=>'2002-01-24')
    */
    protected function _castDateParametersFromDateHelper(&$params) {
        if(empty($params)){
            return;
        }
        $date_attributes = array();
        foreach ($params as $k=>$v) {
            if(preg_match('/^([A-Za-z0-9_]+)\(([1-5]{1})i\)$/',$k,$match)){
                $date_attributes[$match[1]][$match[2]] = $v;
                $this->$k = $v;
                unset($params[$k]);
            }
        }
        foreach ($date_attributes as $attribute=>$date){
            $params[$attribute] = trim(@$date[1].'-'.@$date[2].'-'.@$date[3].' '.@$date[4].':'.@$date[5].':'.@$date[6],' :-');
        }
    }

    protected function _addBlobQueryStack($column_name, $blob_value) {
        $this->_BlobQueryStack[$column_name] = $blob_value;
    }


    protected function _shouldCompressColumn($column_name) {
        if(empty($this->compress)){
            return false;
        }elseif(!is_array($this->compress)){
            $this->compress = Ak::toArray($this->compress);
        }
        $return = isset($this->compress[$column_name]) || in_array($column_name, $this->compress);
        return $return;
    }

    protected function _shouldSerializeColumn($column_name) {
        if(empty($this->serialize)){
            return false;
        }elseif(!is_array($this->serialize)){
            $this->serialize = Ak::toArray($this->serialize);
        }
        $return=isset($this->serialize[$column_name]) || in_array($column_name, $this->serialize);
        return $return;
    }

    protected function _ensureClassExistsForSerializedColumnBeforeUnserializing($column_name) {
        static $imported_cache = array();
        if(empty($imported_cache[$column_name])){
            $class_name = isset($this->serialize[$column_name])  ?
            (is_string($this->serialize[$column_name]) ? $this->serialize[$column_name] : $column_name) : false;
            if($class_name) {
                Ak::import($class_name);
            }
            $imported_cache[$column_name] = true;
        }
    }

    protected function _updateBlobFields($condition) {
        if(!empty($this->_BlobQueryStack) && is_array($this->_BlobQueryStack)){
            foreach ($this->_BlobQueryStack as $column=>$value){
                $this->_db->UpdateBlob($this->getTableName(), $column, $value, $condition);
            }
            $this->_BlobQueryStack = null;
        }
    }


    /*/Type Casting*/

    /**
                             Optimistic Locking
    ====================================================================
    *
    * Active Records support optimistic locking if the field <tt>lock_version</tt> is present.  Each update to the
    * record increments the lock_version column and the locking facilities ensure that records instantiated twice
    * will let the last one saved return false on save() if the first was also updated. Example:
    *
    *   $p1 = new Person(1);
    *   $p2 = new Person(1);
    *
    *   $p1->first_name = "Michael";
    *   $p1->save();
    *
    *   $p2->first_name = "should fail";
    *   $p2->save(); // Returns false
    *
    * You're then responsible for dealing with the conflict by checking the return value of save(); and either rolling back, merging,
    * or otherwise apply the business logic needed to resolve the conflict.
    *
    * You must ensure that your database schema defaults the lock_version column to 0.
    *
    * This behavior can be turned off by setting <tt>AkActiveRecord::lock_optimistically = false</tt>.
    */
    public function isLockingEnabled() {
        return (!isset($this->lock_optimistically) || $this->lock_optimistically !== false) && $this->hasColumn('lock_version');
    }
    /*/Optimistic Locking*/


    /**
                                    Callbacks
    ====================================================================
    See also: Observers.
    *
    * Callbacks are hooks into the life-cycle of an Active Record object that allows you to trigger logic
    * before or after an alteration of the object state. This can be used to make sure that associated and
    * dependent objects are deleted when destroy is called (by overwriting beforeDestroy) or to massage attributes
    * before they're validated (by overwriting beforeValidation). As an example of the callbacks initiated, consider
    * the AkActiveRecord->save() call:
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
    * Active Record lifecycle.
    *
    * Examples:
    *   class CreditCard extends ActiveRecord
    *   {
    *       // Strip everything but digits, so the user can specify "555 234 34" or
    *       // "5552-3434" or both will mean "55523434"
    *       public function beforeValidationOnCreate
    *       {
    *           if(!empty($this->number)){
    *               $this->number = preg_replace('/([^0-9]*)/','',$this->number);
    *           }
    *       }
    *   }
    *
    *   class Subscription extends ActiveRecord
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
    *   class Firm extends ActiveRecord
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
    * Override this methods to hook Active Records
    */

    public function beforeCreate(){return true;}
    public function beforeValidation(){return true;}
    public function beforeValidationOnCreate(){return true;}
    public function beforeValidationOnUpdate(){return true;}
    public function beforeSave(){return true;}
    public function beforeUpdate(){return true;}
    public function afterUpdate(){return true;}
    public function afterValidation(){return true;}
    public function afterValidationOnCreate(){return true;}
    public function afterValidationOnUpdate(){return true;}
    public function beforeInstantiate(&$attributes = array()){return true;}
    public function afterInstantiate(){return true;}
    public function afterCreate(){return true;}
    public function afterDestroy(){return true;}
    public function beforeDestroy(){return true;}
    public function afterSave(){return true;}

    /*/Callbacks*/


    /**
                                    Transactions
    ====================================================================
    *
    * Transaction support for database operations
    *
    * Transactions are enabled automatically for Active record objects, But you can nest transactions within models.
    * This transactions are nested, and only the outermost will be executed
    *
    *   $User->transactionStart();
    *   $User->create('username'=>'Bermi');
    *   $Members->create('username'=>'Bermi');
    *
    *    if(!checkSomething()){
    *       $User->transactionFail();
    *    }
    *
    *   $User->transactionComplete();
    */

    public function transactionStart() {
        return $this->_db->startTransaction();
    }

    public function transactionComplete() {
        return $this->_db->stopTransaction();
    }

    public function transactionFail() {
        $this->_db->failTransaction();
        return false;
    }

    public function transactionHasFailed() {
        return $this->_db->hasTransactionFailed();
    }

    /*/Transactions*/





    /**
                            Act as Behaviours
    ====================================================================
    See also: Acts as List, Acts as Tree, Acts as Nested Set.
    */

    /**
     * actAs provides a method for extending Active Record models.
     *
     * Example:
     * $this->actsAs('list', array('scope' => 'todo_list'));
     */
    public function actsAs($behaviour, $options = array()) {
        $class_name = $this->_getActAsClassName($behaviour);
        $underscored_place_holder = AkInflector::underscore($behaviour);
        $camelized_place_holder = AkInflector::camelize($underscored_place_holder);

        if($this->$underscored_place_holder = $this->_getActAsInstance($class_name, $options)){
            $this->$camelized_place_holder = $this->$underscored_place_holder;
            if($this->$underscored_place_holder->init($options)){
                $this->__ActsLikeAttributes[$underscored_place_holder] = $underscored_place_holder;
            }
        }
    }

    protected function _getActAsClassName($behaviour) {
        $class_name = AkInflector::camelize($behaviour);
        return file_exists(AK_ACTIVE_RECORD_DIR.DS.'behaviours'.DS.'acts_as_'.AkInflector::underscore($class_name).'.php') && !class_exists('ActsAs'.$class_name) ?
        'AkActsAs'.$class_name : 'ActsAs'.$class_name;
    }

    public function &_getActAsInstance($class_name, $options) {
        if(!class_exists($class_name)){
            if(substr($class_name,0,2) == 'Ak'){
                include_once(AK_ACTIVE_RECORD_DIR.DS.'behaviours'.DS.AkInflector::underscore(substr($class_name, 2)).'.php');
            }else{
                include_once(AK_APP_PLUGINS_DIR.DS.AkInflector::underscore($class_name).DS.'lib'.DS.$class_name.'.php');
            }
        }
        if(!class_exists($class_name)){
            trigger_error(Ak::t('The class %class used for handling an "act_as %class" does not exist',array('%class'=>$class_name)).AkDebug::getFileAndNumberTextForError(1), E_USER_ERROR);
            $false = false;
            return $false;
        }else{
            $ActAsInstance = new $class_name($this, $options);
            return $ActAsInstance;
        }
    }

    protected function _loadActAsBehaviours() {
        //$this->act_as = !empty($this->acts_as) ? $this->acts_as : (empty($this->act_as) ? false : $this->act_as);
        if(!empty($this->act_as)){
            if(is_string($this->act_as)){
                $this->act_as = array_unique(array_diff(array_map('trim',explode(',',$this->act_as.',')), array('')));
                foreach ($this->act_as as $type){
                    $this->actsAs($type);
                }
            }elseif (is_array($this->act_as)){
                foreach ($this->act_as as $type=>$options){
                    if(is_numeric($type)){
                        $this->actsAs($options, array());
                    }else{
                        $this->actsAs($type, $options);
                    }
                }
            }
        }
    }

    /**
    * Returns a comma separated list of possible acts like (active record, nested set, list)....
    */
    public function actsLike() {
        $result = 'active record';
        foreach ($this->__ActsLikeAttributes as $type){
            if(!empty($this->$type) && is_object($this->$type) && method_exists($this->{$type}, 'getType')){
                $result .= ','.$this->{$type}->getType();
            }
        }
        return $result;
    }

    /*/Act as Behaviours*/


    /**
     * Selects and filters a search result to include only specified columns
     *
     *    $people_for_select = $People->select($People->find(),'name','email');
     *
     *    Now $people_for_select will hold an array with
     *    array (
     *        array ('name' => 'Jose','email' => 'jose@example.com'),
     *        array ('name' => 'Alicia','email' => 'alicia@example.com'),
     *        array ('name' => 'Hilario','email' => 'hilario@example.com'),
     *        array ('name' => 'Bermi','email' => 'bermi@example.com')
     *    );
     */
    public function select(&$source_array) {
        $resulting_array = array();
        if(!empty($source_array) && is_array($source_array) && func_num_args() > 1) {
        (array)$args = array_filter(array_slice(func_get_args(),1),array($this,'hasColumn'));
        foreach ($source_array as $source_item){
            $item_fields = array();
            foreach ($args as $arg){
                $item_fields[$arg] = $source_item->get($arg);
            }
            $resulting_array[] = $item_fields;
        }
        }
        return $resulting_array;
    }

    /**
     * Collect is a function for selecting items from double depth array
     * like the ones returned by the AkActiveRecord. This comes useful when you just need some
     * fields for generating tables, select lists with only desired fields.
     *
     *    $people_for_select = Ak::select($People->find(),'id','email');
     *
     *    Returns something like:
     *    array (
     *        array ('10' => 'jose@example.com'),
     *        array ('15' => 'alicia@example.com'),
     *        array ('16' => 'hilario@example.com'),
     *        array ('18' => 'bermi@example.com')
     *    );
     */
    public function collect($source_array, $key_index, $value_index) {
        $resulting_array = array();
        if(!empty($source_array) && is_array($source_array)) {
            foreach ($source_array as $source_item){
                $resulting_array[$source_item->get($key_index)] = $source_item->get($value_index);
            }
        }
        return $resulting_array;
    }



    public function getAttributeCondition($argument) {
        return is_array($argument) ? 'IN (?)' : (is_null($argument) ? 'IS ?' : '= ?');
    }



    public function hasBeenModified() {
        return Ak::objectHasBeenModified($this);
    }

    /**
    * Just freeze the attributes hash, such that associations are still accessible even on destroyed records.
    *
    * @todo implement freeze correctly for its intended use
    */
    public function freeze() {
        return $this->_freeze = true;
    }

    public function isFrozen() {
        return !empty($this->_freeze);
    }

    /**
    * Alias for getModelName()
    */
    public function getType() {
        return $this->getModelName();
    }

    public function &objectCache() {
        static $cache;
        $false = false;
        $args = func_get_args();
        if(count($args) == 2){
            if(!isset($cache[$args[0]])){
                $cache[$args[0]] = $args[1];
            }
        }elseif(!isset($cache[$args[0]])){
            return $false;
        }
        return $cache[$args[0]];
    }



    public function getDisplayField() {
        return  empty($this->displayField) && $this->hasAttribute('name') ? 'name' : (isset($this->displayField) && $this->hasAttribute($this->displayField) ? $this->displayField : $this->getPrimaryKey());
    }

    public function setDisplayField($attribute_name) {
        if($this->hasAttribute($attribute_name)){
            $this->displayField = $attribute_name;
            return true;
        }else {
            return false;
        }
    }

    public function getColumnsWithRegexBoundaries() {
        $columns = array_keys($this->getColumns());
        foreach ($columns as $k=>$column){
            $columns[$k] = '/([^\.])\b('.$column.')\b/';
        }
        return $columns;
    }

    /**
     * LAZY LOADING FUNCTIONALITY
     */
    protected function _enableLazyLoadingExtenssions($options = array()) {
        empty($options['skip_observers'])           && $this->_enableObservers();
        empty($options['skip_finders'])             && $this->_enableFinders();
        empty($options['skip_validations'])         && $this->_enableValidations();
        empty($options['skip_errors'])              && $this->_enableErrors();
        empty($options['skip_table_inheritance'])   && $this->_enableTableInheritance();
        empty($options['skip_localization'])        && $this->_enableLocalization();
        empty($options['skip_calculations'])        && $this->_enableCalculations();
        empty($options['skip_counter'])             && $this->_enableCounter();
        empty($options['skip_combined_attributes']) && $this->_enableCombinedAttributes();
        empty($options['skip_utilities'])           && $this->_enableUtilities();
        empty($options['skip_debug'])               && $this->_enableDebug();
    }

    protected function _enableCalculations() {
        $this->extendClassLazily('AkActiveRecordCalculations',
        array(
        'methods' => array('count', 'average', 'minimum', 'maximum', 'sum', 'calculate'),
        'autoload_path' => AK_ACTIVE_RECORD_DIR.DS.'calculations.php'
        ));
    }

    /**
     * Gets the column name for use with single table inheritance. Can be overridden in subclasses.
    */
    public function getInheritanceColumn() {
        return empty($this->_inheritanceColumn) ? ($this->hasColumn('type') ? 'type' : false ) : $this->_inheritanceColumn;
    }

    protected function _enableTableInheritance() {
        $this->extendClassLazily('AkActiveRecordTableInheritance',
        array(
        'methods' => array('setInheritanceColumn','typeCondition'),
        'autoload_path' => AK_ACTIVE_RECORD_DIR.DS.'table_inheritance.php'
        ));
    }

    private function _setInternationalizedColumnsStatus($force_enable = false) {
        return;
        if($force_enable){
            $this->internationalize = true;
        }else{
            $this->internationalize = !empty($this->internationalize) ? count($this->getAvailableLocales()) > 1 : true;
        }
    }

    protected function _enableLocalization() {
        $this->extendClassLazily('AkActiveRecordLocalization',
        array(
        'methods' => array(
        'getInternationalizedColumns',
        'getAvailableLocales',
        'getCurrentLocale',
        'getAttributeByLocale',
        'getAttributeLocales',
        'setAttributeByLocale',
        'setAttributeLocales',
        'isInternationalizeCandidate',
        'setInternationalizedAttribute',
        'addInternationalizedColumn',
        'delocalizeAttribute',
        ),
        'autoload_path' => AK_ACTIVE_RECORD_DIR.DS.'localization.php'
        ));
    }



    protected function _enableFinders() {
        $this->extendClassLazily('AkActiveRecordFinders',
        array(
        'methods' => array(
        'all',
        'exists',
        'getVariableSqlCondition',
        'constructFinderSql',
        'constructFinderSqlWithAssociations',
        'sanitizeConditions',
        'getSanitizedConditionsArray',
        'getConditions',
        'instantiate',
        ),
        'autoload_path' => AK_ACTIVE_RECORD_DIR.DS.'finders.php',
        'methods_match' => '/^find.*/',
        'accept_all_matches' => true // required for running __call on dynamic finders
        ));
    }


    protected function _enableCounter() {
        $this->extendClassLazily('AkActiveRecordCounter',
        array(
        'methods' => array(
        'incrementCounter',
        'decrementCounter',
        'decrementAttribute',
        'decrementAndSaveAttribute',
        'incrementAttribute',
        'incrementAndSaveAttribute',
        ),
        'autoload_path' => AK_ACTIVE_RECORD_DIR.DS.'counter.php'
        ));
    }


    protected function _enableCombinedAttributes() {
        $this->extendClassLazily('AkActiveRecordCombinedAttributes',
        array(
        'methods' => array(
        'isCombinedAttribute',
        'addCombinedAttributeConfiguration',
        'composeCombinedAttributes',
        'composeCombinedAttribute',
        'getCombinedAttributesWhereThisAttributeIsUsed',
        'requiredForCombination',
        'hasCombinedAttributes',
        'getCombinedSubattributes',
        'decomposeCombinedAttributes',
        'decomposeCombinedAttribute',
        'getAvailableCombinedAttributes',
        ),
        'autoload_path' => AK_ACTIVE_RECORD_DIR.DS.'combined_attributes.php'
        ));
    }

    protected function _instantiateDefaultObserver() {
        $default_observer_name = $this->getModelName().'Observer';
        if(class_exists($default_observer_name)){
            Ak::singleton($default_observer_name,  $this);
        }
    }


    /**
     * Caching of expensive model scoped methods (like table structure, default values...)
     */
    protected function &_getCacheForModelMethod($method) {
        if(AK_TEST_MODE){
            $false = false;
            return $false;
        }
        return Ak::getStaticVar('AR_'.$this->getModelName().$method);
    }

    protected function _setCacheForModelMethod($method, &$value) {
        return AK_TEST_MODE || Ak::setStaticVar('AR_'.$this->getModelName().$method, $value);
    }
}


class AkActiveRecordIterator implements Iterator, ArrayAccess, Countable
{
    private $_ResultSet;
    private $_records = array();
    private $_options = array();
    private $_returnCurrentHandler;
    private $_FinderInstance;
    private $_ActiveRecord;

    public function __construct(&$ResultSet, &$options = array()){
        $this->_ResultSet = $ResultSet;
        $this->_options = $options;
        $this->_returnCurrentHandler = '_returns'.ucfirst($options['returns']);
        $this->_FinderInstance  = $options['Finder'];
        $this->_ActiveRecord   = $options['ActiveRecord'];
    }
    
    public function __destruct(){
        $this->_ResultSet->Close();
    }

    public function rewind() {
        $this->_ResultSet->MoveFirst();
    }

    public function valid() {        
        return !$this->_ResultSet->EOF;
    }

    public function key() {
        return $this->_ResultSet->_currentRow;
    }

    public function current() {
        return $this->{$this->_returnCurrentHandler}($this->_ResultSet->fields);
    }

    public function next() {
        $this->_ResultSet->MoveNext();
    }

    public function hasMore() {
        return !$this->_ResultSet->EOF;
    }

    private function _enableArrayAccess(){
        if(empty($this->_records)){
            $this->rewind();
            $i = 0;
            foreach ($this as $Record){
                $this->_records[$i] = $Record;
                $i++;
            }
        }
    }

    public function offsetSet($offset, $value) {
        $this->_enableArrayAccess();
        $this->_records[$offset] = $value;
    }

    public function offsetExists($offset) {
        $this->_enableArrayAccess();
        return isset($this->_records[$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->_records[$offset]);
    }

    public function offsetGet($offset) {
        $this->_enableArrayAccess();
        return isset($this->_records[$offset]) ? $this->_records[$offset] : null;
    }
    

    private function _returnsDefault($attributes){
        return $this->_FinderInstance->instantiate($this->_ActiveRecord->getOnlyAvailableAttributes($attributes), false);
    }
    
    private function _returnsSimulated($attributes){
        $false = false;
        return $this->_FinderInstance->generateStdClasses($this->_options['simulation_class'], $attributes, $this->_ActiveRecord->getType(), $false, $false, array('__owner'=>array('pk'=>$this->_ActiveRecord->getPrimaryKey(),'class'=>$this->_ActiveRecord->getType())));
    }
    
    private function _returnsArray($attributes){
        return $this->_ActiveRecord->castAttributesFromDatabase($this->_ActiveRecord->getOnlyAvailableAttributes($attributes));
    }
    

    public function toXml() {
        return $this[0]->toXml(array('collection' => $this));
    }

    public function toJson() {
        return $this[0]->toJson(array('collection' => $this));
    }
    
    public function count() {
        $this->_enableArrayAccess();
        return count($this->_records);
    }
    
    public function first() {
        $this->rewind();
        return $this->current();
    }
}



class AkActiveRecordExtenssion
{
    protected $_ActiveRecord;
    public function setExtendedBy(&$ActiveRecord) {
        $this->_ActiveRecord = $ActiveRecord;
    }
}
