<?php

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
    $_internationalize = AK_ACTIVE_RECORD_INTERNATIONALIZE_MODELS_BY_DEFAULT,
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
    $_automated_max_length_validator = false,
    $_automated_validators_enabled = false,
    $_automated_not_null_validator = false,
    $_set_default_attribute_values_automatically = true,
    $_activeRecordHasBeenInstantiated = true, // This is needed for enabling support for static active record instantiation under php
    $_defaultErrorMessages = array();

    protected $_options = array(
    );

    private
    $__ActsLikeAttributes = array();

    public function __construct()
    {
        $attributes = (array)func_get_args();
        if(isset($attributes[0]['init']) && $attributes[0]['init'] == false){
            return;
        }
        return $this->init($attributes);
    }

    public function init($attributes = array())
    {
        AK_LOG_EVENTS ? ($this->Logger = Ak::getLogger()) : null;

        $this->_setInternationalizedColumnsStatus();

        @$this->_instantiateDefaultObserver();

        $this->establishConnection();

        $this->_enableLazyLoadingExtenssions();

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

    public function __destruct()
    {

    }


    /**
    * New objects can be instantiated as either empty (pass no construction parameter) or pre-set with attributes but not yet saved
    * (pass an array with key names matching the associated table column names).
    * In both instances, valid attribute keys are determined by the column names of the associated table; hence you can't
    * have attributes that aren't part of the table columns.
    */
    public function &newRecord($attributes)
    {
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
    public function cloneRecord()
    {
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
    public function isNewRecord()
    {
        if(!isset($this->_newRecord) && !isset($this->{$this->getPrimaryKey()})){
            $this->_newRecord = true;
        }
        return $this->_newRecord;
    }



    /**
    * Reloads the attributes of this object from the database.
    */
    public function reload()
    {
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
    public function &create($attributes = array(), $replace_existing = true)
    {
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

    public function createOrUpdate($validate = true)
    {
        if($validate && !$this->isValid()){
            $this->transactionFail();
            return false;
        }
        return $this->isNewRecord() ? $this->_create() : $this->_update();
    }

    public function &findOrCreateBy()
    {
        $args = func_get_args();
        $Item = call_user_func_array(array($this,'findFirstBy'), $args);
        if(!$Item){
            $attributes = array();

            list($sql, $columns) = $this->_getFindBySqlAndColumns(array_shift($args), $args);

            if(!empty($columns)){
                foreach ($columns as $column){
                    $attributes[$column] = array_shift($args);
                }
            }
            $Item = $this->create($attributes);
            $Item->has_been_created = true;
        }else{
            $Item->has_been_created = false;
        }
        $Item->has_been_found = !$Item->has_been_created;
        return $Item;
    }

    /**
    * Creates a new record with values matching those of the instance attributes.
    * Must be called as a result of a call to createOrUpdate.
    */
    private function _create()
    {
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

        $attributes = array_diff($attributes, array(''));


        $sql = 'INSERT INTO '.$table.' '.
        '('.join(', ',array_keys($attributes)).') '.
        'VALUES ('.join(',',array_values($attributes)).')';

        $inserted_id = $this->_db->insert($sql, $id, $pk, $table, 'Create '.$this->getModelName());
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

    protected function _setRecordTimestamps()
    {
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
    public function save($validate = true)
    {
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
    public function countBySql($sql)
    {
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
    public function update($id, $attributes)
    {
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
    public function updateAttribute($name, $value, $should_validate=true)
    {
        $this->setAttribute($name, $value);
        return $this->save($should_validate);
    }


    /**
    * Updates all the attributes in from the passed array and saves the record. If the object is
    * invalid, the saving will fail and false will be returned.
    */
    public function updateAttributes($attributes, $object = null)
    {
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
    public function updateAll($updates, $conditions = null)
    {
        /**
        * @todo sanitize sql conditions
        */
        $sql = 'UPDATE '.$this->getTableName().' SET '.$updates;
        $binds = false;
        if(is_array($conditions)) {
            /*
            * take the first item as the conditions, the following are binds
            *
            */
            $binds = $conditions;
            $conditions=array_shift($binds);

        }
        $this->addConditions($sql, $conditions);
        if($binds) {
            $sql = array_merge(array($sql),$binds);
        }
        return $this->_db->update($sql, $this->getModelName().' Update All');
    }


    /**
    * Updates the associated record with values matching those of the instance attributes.
    * Must be called as a result of a call to createOrUpdate.
    */
    protected function _update()
    {
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

        $quoted_attributes = $this->getAvailableAttributesQuoted();
        $sql = 'UPDATE '.$this->getTableName().' '.
        'SET '.join(', ', $quoted_attributes) .' '.
        'WHERE '.$this->getPrimaryKey().'='.$this->quotedId().$lock_check_sql;

        $affected_rows = $this->_db->update($sql,'Updating '.$this->getModelName());
        if($this->transactionHasFailed()){
            return false;
        }

        if ($this->isLockingEnabled() && $affected_rows != 1){
            $this->setAttribute('lock_version', $previous_value);
            trigger_error(Ak::t('Attempted to update a stale object').Ak::getFileAndNumberTextForError(1), E_USER_NOTICE);
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
    public function delete($id)
    {
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
    public function deleteAll($conditions = null)
    {
        /**
        * @todo sanitize sql conditions
        */
        $sql = 'DELETE FROM '.$this->getTableName();
        $binds = false;
        if(is_array($conditions)) {
            /*
            * take the first item as the conditions, the following are binds
            *
            */
            $binds = $conditions;
            $conditions=array_shift($binds);

        }
        $this->addConditions($sql, $conditions);
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
    public function destroy($id = null)
    {
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

    protected function _destroy()
    {
        if(!$this->beforeDestroy() || !$this->notifyObservers('beforeDestroy')){
            return $this->transactionFail();
        }
        $sql = 'DELETE FROM '.$this->getTableName().' WHERE '.$this->getPrimaryKey().' = '.$this->castAttributeForDatabase($this->getPrimaryKey(), $this->getId());
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
    public function destroyAll($conditions)
    {
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
                          Finding records
    ====================================================================
    */

    /**
    * Returns true if the given id represents the primary key of a record in the database, false otherwise. Example:
    *
    * $Person->exists(5);
    */
    public function exists($id)
    {
        return $this->find('first',array('conditions' => array($this->getPrimaryKey().' = '.$id))) !== false;
    }

    /**
     * Find operates with three different retrieval approaches:
    * * Find by id: This can either be a specific id find(1), a list of ids find(1, 5, 6),
    *   or an array of ids find(array(5, 6, 10)). If no record can be found for all of the listed ids,
    *   then RecordNotFound will be raised.
    * * Find first: This will return the first record matched by the options used. These options
    *   can either be specific conditions or merely an order.
    *   If no record can matched, false is returned.
    * * Find all: This will return all the records matched by the options used. If no records are found, an empty array is returned.
    *
    * All approaches accepts an $option array as their last parameter. The options are:
    *
    * 'conditions' => An SQL fragment like "administrator = 1" or array("user_name = ?" => $username). See conditions in the intro.
    * 'order' => An SQL fragment like "created_at DESC, name".
    * 'limit' => An integer determining the limit on the number of rows that should be returned.
    * 'offset' => An integer determining the offset from where the rows should be fetched. So at 5, it would skip the first 4 rows.
    * 'joins' => An SQL fragment for additional joins like "LEFT JOIN comments ON comments.post_id = $id". (Rarely needed).
    * 'include' => Names associations that should be loaded alongside using LEFT OUTER JOINs. The symbols
    * named refer to already defined associations. See eager loading under Associations.
    *
    * Examples for find by id:
    * <code>
    *   $Person->find(1);       // returns the object for ID = 1
    *   $Person->find(1, 2, 6); // returns an array for objects with IDs in (1, 2, 6), Returns false if any of those IDs is not available
    *   $Person->find(array(7, 17)); // returns an array for objects with IDs in (7, 17)
    *   $Person->find(array(1));     // returns an array for objects the object with ID = 1
    *   $Person->find(1, array('conditions' => "administrator = 1", 'order' => "created_on DESC"));
    * </code>
    *
    * Examples for find first:
    * <code>
    *   $Person->find('first'); // returns the first object fetched by SELECT * FROM people
    *   $Person->find('first', array('conditions' => array("user_name = ':user_name'", ':user_name' => $user_name)));
    *   $Person->find('first', array('order' => "created_on DESC", 'offset' => 5));
    * </code>
    *
    * Examples for find all:
    * <code>
    *   $Person->find('all'); // returns an array of objects for all the rows fetched by SELECT * FROM people
    *   $Person->find(); // Same as $Person->find('all');
    *   $Person->find('all', array('conditions' => array("category IN (categories)", 'categories' => join(','$categories)), 'limit' => 50));
    *   $Person->find('all', array('offset' => 10, 'limit' => 10));
    *   $Person->find('all', array('include' => array('account', 'friends'));
    * </code>
    */
    public function &find()
    {
        $args = func_get_args();
        $options = $this->_extractOptionsFromArgs($args);
        list($fetch, $options) = $this->_extractConditionsFromArgs($args, $options);
        $this->_sanitizeConditionsVariables($options);
        switch ($fetch) {
            case 'first':
                return $this->_findInitial($options);

            case 'all':
                return $this->_findEvery($options);

            default:
                return $this->_findFromIds($args, $options);
        }
        return false;
    }

    public function &_findInitial($options)
    {
        // TODO: virtual_limit is a hack
        // actually we fetch_all and return only the first row
        $options = array_merge($options, array((!empty($options['include']) ?'virtual_limit':'limit')=>1));

        $result = $this->_findEvery($options);

        if(!empty($result) && is_array($result)){
            $_result = $result[0];
        }else{
            $_result = false;
            // if we return an empty array instead of false we need to change this->exists()!
            //$_result = array();
        }
        return  $_result;

    }

    public function &_findEvery($options)
    {
        if((!empty($options['include']) && $this->hasAssociations())){
            $result = $this->findWithAssociations($options);
        }else{
            $sql = $this->constructFinderSql($options);
            if (isset($options['wrap'])) {
                $sql = str_replace('{query}',$sql,$options['wrap']);
            }
            if(!empty($options['bind']) && is_array($options['bind']) && strstr($sql,'?')){
                $sql = array_merge(array($sql),$options['bind']);
            }
            if (!empty($options['returns']) && $options['returns']!='default') {
                $options['returns'] = in_array($options['returns'],array('simulated','default','array'))?$options['returns']:'default';
                $simulation_class = !empty($options['simulation_class']) && class_exists($options['simulation_class'])?$options['simulation_class']:'AkActiveRecordMock';
                $result = $this->findBySql($sql,null,null,null,$options['returns'],$simulation_class);
            } else {
                $result = $this->findBySql($sql);
            }
        }

        if(!empty($result) && is_array($result)){
            $_result = $result;
        }else{
            $_result = false;
        }
        return  $_result;

    }

    public function &_findFromIds($ids, $options)
    {
        $expects_array = is_array($ids[0]);

        $ids = array_map(array($this, 'quotedId'),array_unique($expects_array ? (isset($ids[1]) ? array_merge($ids[0],$ids) : $ids[0]) : $ids));
        $num_ids = count($ids);

        //at this point $options['conditions'] can't be an array
        //$conditions = !empty($options['conditions']) ? ' AND '.$options['conditions'] : '';
        $conditions=!empty($options['conditions'])?$options['conditions']:'';
        switch ($num_ids){
            case 0 :
                trigger_error($this->t('Couldn\'t find %object_name without an ID%conditions',array('%object_name'=>$this->getModelName(),'%conditions'=>$conditions)).Ak::getFileAndNumberTextForError(1), E_USER_ERROR);
                break;

            case 1 :
                $table_name = !empty($options['include']) && $this->hasAssociations() ? '__owner' : $this->getTableName();

                if (!preg_match('/SELECT .* FROM/is', $conditions)) {
                    $options['conditions'] = $table_name.'.'.$this->getPrimaryKey().' = '.$ids[0].(empty($conditions)?'':' AND '.$conditions);
                } else {
                    if (false!==($pos=stripos($conditions,' WHERE '))) {
                        $before_where = substr($conditions,0, $pos);
                        $after_where = substr($conditions, $pos+7);
                        $options['conditions'] = $before_where.' WHERE ('.$table_name.'.'.$this->getPrimaryKey().' = '.$ids[0].') AND ('.$after_where.')';
                    } else {
                        $options['conditions'].=' WHERE '.$table_name.'.'.$this->getPrimaryKey().' = '.$ids[0];
                    }
                }

                $result = $this->_findEvery($options);
                if (!$expects_array && $result !== false){
                    return $result[0];
                }
                return  $result;
                break;

            default:
                $without_conditions = empty($options['conditions']) ? true : false;
                $ids_condition = $this->getPrimaryKey().' IN ('.join(', ', $this->castAttributesForDatabase($this->getPrimaryKey(), $ids)).')';
                if (!preg_match('/SELECT .* FROM/is', $conditions)) {
                    $options['conditions'] = $ids_condition.(empty($conditions)?'':' AND '.$conditions);
                } else {
                    if (false!==($pos=stripos($conditions,' WHERE '))) {
                        $before_where = substr($conditions,0, $pos);
                        $after_where = substr($conditions, $pos+7);
                        $options['conditions'] = $before_where.' WHERE ('.$ids_condition.') AND ('.$after_where.')';
                    } else {
                        $options['conditions'].=' WHERE '.$ids_condition;
                    }
                }

                $result = $this->_findEvery($options);
                if(is_array($result) && ($num_ids==1 && count($result) != $num_ids && $without_conditions)){
                    $result = false;
                }
                return $result;
                break;
        }
    }

    public function quotedId($id = false)
    {
        return $this->castAttributeForDatabase($this->getPrimaryKey(), $id ? $id : $this->getId());
    }

    protected function _extractOptionsFromArgs(&$args)
    {
        $last_arg = count($args)-1;
        return isset($args[$last_arg]) && is_array($args[$last_arg]) && $this->_isOptionsHash($args[$last_arg]) ? array_pop($args) : array();
    }

    protected function _isOptionsHash($options)
    {
        if (isset($options[0])){
            return false;
        }
        $valid_keys = array('simulation_class','returns','load_acts','wrap','conditions', 'include', 'joins', 'limit', 'offset', 'group', 'order', 'sort', 'bind', 'select','select_prefix', 'readonly', 'load_associations', 'load_acts');
        foreach (array_keys($options) as $key){
            if (in_array($key,$valid_keys)){
                return true;
            }
        }
        return false;
    }

    protected function _extractConditionsFromArgs($args, $options)
    {
        if(empty($args)){
            $fetch = 'all';
        } else {
            $fetch = $args[0];
        }
        $num_args = count($args);

        // deprecated: acts like findFirstBySQL
        if ($num_args === 1 && !is_numeric($args[0]) && is_string($args[0]) && $args[0] != 'all' && $args[0] != 'first'){
            //  $Users->find("last_name = 'Williams'");    => find('first',"last_name = 'Williams'");
            Ak::deprecateWarning(array("AR::find('%sql') is ambiguous and therefore deprecated, use AR::find('first',%sql) instead", '%sql'=>$args[0]));
            $options = array('conditions'=> $args[0]);
            return array('first',$options);
        } //end

        // set fetch_mode to 'all' if none is given
        if (!is_numeric($fetch) && !is_array($fetch) && $fetch != 'all' && $fetch != 'first') {
            array_unshift($args, 'all');
            $num_args = count($args);
        }
        if ($num_args > 1) {
            if (is_string($args[1])){
                //  $Users->find(:fetch_mode,"first_name = ?",'Tim');
                $fetch = array_shift($args);
                $options = array_merge($options, array('conditions'=>$args));   //TODO: merge_conditions
            }elseif (is_array($args[1])) {
                //  $Users->find(:fetch_mode,array('first_name = ?,'Tim'));
                $fetch = array_shift($args);
                $options = array_merge($options, array('conditions'=>$args[0]));   //TODO: merge_conditions
            }
        }

        return array($fetch,$options);
    }

    protected function _sanitizeConditionsVariables(&$options)
    {
        if(!empty($options['conditions']) && is_array($options['conditions'])){
            if (isset($options['conditions'][0]) && strstr($options['conditions'][0], '?') && count($options['conditions']) > 1){
                //array('conditions' => array("name=?",$name))
                $pattern = array_shift($options['conditions']);
                $options['bind'] = array_values($options['conditions']);
                $options['conditions'] = $pattern;
            }elseif (isset($options['conditions'][0])){
                //array('conditions' => array("user_name = :user_name", ':user_name' => 'hilario')
                $pattern = array_shift($options['conditions']);
                $options['conditions'] = str_replace(array_keys($options['conditions']), array_values($this->getSanitizedConditionsArray($options['conditions'])),$pattern);
            }else{
                //array('conditions' => array('user_name'=>'Hilario'))
                $options['conditions'] = join(' AND ',(array)$this->getAttributesQuoted($options['conditions']));
            }
        }
        $this->_sanitizeConditionsCollections($options);
    }

    protected function _sanitizeConditionsCollections(&$options)
    {
        if(!empty($options['bind']) && is_array($options['bind']) && preg_match_all('/([a-zA-Z_]+)\s+IN\s+\(?\?\)?/i', $options['conditions'], $matches)){
            $i = 0;
            foreach($options['bind'] as $k => $v){
                if(isset($matches[1][$i]) && is_array($v)){
                    $value = join(', ', $this->castAttributesForDatabase($matches[1][$i], $v));
                    $startpos=strpos($options['conditions'],$matches[0][$i]);
                    $endpos=$startpos+strlen($matches[0][$i]);
                    $options['conditions'] = substr($options['conditions'],0,$startpos).str_replace('?', $value, $matches[0][$i]).substr( $options['conditions'],$endpos);
                    unset($options['bind'][$k]);
                    $i++;
                }
            }
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


    /**
    * Works like find_all, but requires a complete SQL string. Examples:
    * $Post->findBySql("SELECT p.*, c.author FROM posts p, comments c WHERE p.id = c.post_id");
    * $Post->findBySql(array("SELECT * FROM posts WHERE author = ? AND created_on > ?", $author_id, $start_date));
    */
    public function &findBySql($sql, $limit = null, $offset = null, $bindings = null, $returns = 'default', $simulation_class = 'AkActiveRecordMock')
    {
        if ($limit || $offset){
            Ak::deprecateWarning("You're calling AR::findBySql with \$limit or \$offset parameters. This has been deprecated.");
            $this->_db->addLimitAndOffset($sql, array('limit'=>$limit,'offset'=>$offset));
        }
        $objects = array();
        $records = $this->_db->select ($sql,'selecting');
        foreach ($records as $record){
            if ($returns == 'default') {
                $objects[] = $this->instantiate($this->getOnlyAvailableAttributes($record), false);
            } else if ($returns == 'simulated') {
                $objects[] = $this->_castAttributesFromDatabase($this->getOnlyAvailableAttributes($record),$this);
            } else if ($returns == 'array') {

                $objects[] = $this->_castAttributesFromDatabase($this->getOnlyAvailableAttributes($record),$this);
            }
        }
        if ($returns == 'simulated') {
            $false = false;
            $objects = $this->_generateStdClasses($simulation_class,$objects,$this->getType(),$false,$false,array('__owner'=>array('pk'=>$this->getPrimaryKey(),'class'=>$this->getType())));
        }

        return $objects;
    }

    /**
    * This function pretends to emulate RoR finders until AkActiveRecord::addMethod becomes stable on future PHP versions.
    * @todo use PHP5 __call method for handling the magic finder methods like findFirstByUnsenameAndPassword('bermi','pass')
    */
    public function &findFirstBy()
    {
        $args = func_get_args();
        array_unshift($args,'first');
        $result = call_user_func_array(array($this,'findBy'), $args);
        return $result;
    }

    public function &findLastBy()
    {
        $args = func_get_args();
        $options = $this->_extractOptionsFromArgs($args);
        $options['order'] = $this->getPrimaryKey().' DESC';
        array_push($args, $options);
        $result = call_user_func_array(array($this,'findFirstBy'), $args);
        return $result;
    }

    public function &findAllBy()
    {
        $args = func_get_args();
        array_unshift($args,'all');
        $result = call_user_func_array(array($this,'findBy'), $args);
        return $result;
    }

    /**
    * This method allows you to use finders in a more flexible way like:
    *
    *   findBy('username AND password', $username, $password);
    *   findBy('age > ? AND name:contains', 18, 'Joe');
    *   findBy('is_active = true AND session_id', session_id());
    *
    */
    public function &findBy()
    {
        $args = func_get_args();
        $find_by_sql = array_shift($args);
        if($find_by_sql == 'all' || $find_by_sql == 'first'){
            $fetch = $find_by_sql;
            $find_by_sql = array_shift($args);
        }else{
            $fetch = 'all';
        }

        $options = $this->_extractOptionsFromArgs($args);

        $query_values = $args;
        $query_arguments_count = count($query_values);

        list($sql, $requested_args) = $this->_getFindBySqlAndColumns($find_by_sql, $query_values);

        if($query_arguments_count != count($requested_args)){
            trigger_error(Ak::t('Argument list did not match expected set. Requested arguments are:').join(', ',$requested_args), E_USER_ERROR);
            $false = false;
            return $false;
        }

        $true_bool_values = array(true,1,'true','True','TRUE','1','y','Y','yes','Yes','YES','s','Si','SI','V','v','T','t');

        foreach ($requested_args as $k=>$v){
            switch ($this->getColumnType($v)) {
                case 'boolean':
                    $query_values[$k] = in_array($query_values[$k],$true_bool_values) ? true : false;
                    break;

                case 'date':
                case 'datetime':
                    $query_values[$k] = str_replace('/','-', $this->castAttributeForDatabase($k,$query_values[$k],false));
                    break;

                default:
                    break;
            }
        }

        $conditions = array($sql);
        foreach ($query_values as $bind_value){
            $conditions[] = $bind_value;
        }
        /**
        * @todo merge_conditions
        */
        $options['conditions'] = $conditions;

        $result = call_user_func_array(array($this,'find'), array($fetch,$options));
        return $result;
    }


    protected function _getFindBySqlAndColumns($find_by_sql, &$query_values)
    {
        $sql = str_replace(array('(',')','||','|','&&','&','  '),array(' ( ',' ) ',' OR ',' OR ',' AND ',' AND ',' '), $find_by_sql);
        $operators = array('AND','and','(',')','&','&&','NOT','<>','OR','|','||');
        $pieces = explode(' ',$sql);
        $pieces = array_diff($pieces,array(' ',''));
        $params = array_diff($pieces,$operators);
        $operators = array_diff($pieces,$params);

        $new_sql = '';
        $parameter_count = 0;
        $requested_args = array();
        foreach ($pieces as $piece){
            if(in_array($piece,$params) && $this->hasColumn($piece)){
                $new_sql .= $piece.' = ? ';
                $requested_args[$parameter_count] = $piece;
                $parameter_count++;
            }elseif (!in_array($piece,$operators)){

                if(strstr($piece,':')){
                    $_tmp_parts = explode(':',$piece);
                    if($this->hasColumn($_tmp_parts[0])){
                        $query_values[$parameter_count] = isset($query_values[$parameter_count]) ? $query_values[$parameter_count] : $this->get($_tmp_parts[0]);
                        switch (strtolower($_tmp_parts[1])) {
                            case 'like':
                            case '%like%':
                            case 'is':
                            case 'has':
                            case 'contains':
                                $query_values[$parameter_count] = '%'.$query_values[$parameter_count].'%';
                                $new_sql .= $_tmp_parts[0]." LIKE ? ";
                                break;
                            case 'like_left':
                            case 'like%':
                            case 'begins':
                            case 'begins_with':
                            case 'starts':
                            case 'starts_with':
                                $query_values[$parameter_count] = $query_values[$parameter_count].'%';
                                $new_sql .= $_tmp_parts[0]." LIKE ? ";
                                break;
                            case 'like_right':
                            case '%like':
                            case 'ends':
                            case 'ends_with':
                            case 'finishes':
                            case 'finishes_with':
                                $query_values[$parameter_count] = '%'.$query_values[$parameter_count];
                                $new_sql .= $_tmp_parts[0]." LIKE ? ";
                                break;
                            case 'in':
                                $values = join(', ', $this->castAttributesForDatabase($_tmp_parts[0], $query_values[$parameter_count]));
                                if(!empty($values)){
                                    $new_sql .= $_tmp_parts[0].' IN ('.$values.') ';
                                }else{
                                    $new_sql = preg_replace('/(AND|OR) $/','', $new_sql);
                                }
                                unset($query_values[$parameter_count]);
                                break;
                            default:
                                $query_values[$parameter_count] = $query_values[$parameter_count];
                                $new_sql .= $_tmp_parts[0].' '.$_tmp_parts[1].' ? ';
                                break;
                        }
                        $requested_args[$parameter_count] = $_tmp_parts[0];
                        $parameter_count++;
                    }else {
                        $new_sql .= $_tmp_parts[0];
                    }
                }else{
                    $new_sql .= $piece.' ';
                }
            }else{
                $new_sql .= $piece.' ';
            }
        }

        return array($new_sql, $requested_args);
    }


    /**
     *  Given a condition that uses bindings like "user = ?  AND created_at > ?" will return a
     * string replacing the "?" bindings with the column values for current Active Record
     *
     * @return string
     */
    public function getVariableSqlCondition($variable_condition)
    {
        $query_values = array();
        list($sql, $requested_columns) = $this->_getFindBySqlAndColumns($variable_condition, $query_values);
        $replacements = array();
        $sql = preg_replace('/((('.join($requested_columns,'|').') = \?) = \?)/','$2', $sql);
        foreach ($requested_columns as $attribute){
            $replacements[$attribute] = $this->castAttributeForDatabase($attribute, $this->get($attribute));
        }

        return trim(preg_replace('/('.join('|',array_keys($replacements)).')\s+([^\?]+)\s+\?/e', "isset(\$replacements['\\1']) ? '\\1 \\2 '.\$replacements['\\1']:'\\1 \\2 null'", $sql));
    }


    public function constructFinderSql($options, $select_from_prefix = 'default')
    {
        $sql = isset($options['select_prefix']) ? $options['select_prefix'] : ($select_from_prefix == 'default' ? 'SELECT '.(!empty($options['joins'])?$this->getTableName().'.':'') .'* FROM '.$this->getTableName() : $select_from_prefix);
        $sql .= !empty($options['joins']) ? ' '.$options['joins'] : '';

        $this->addConditions($sql, isset($options['conditions']) ? $options['conditions'] : array());

        // Create an alias for order
        if(empty($options['order']) && !empty($options['sort'])){
            $options['order'] = $options['sort'];
        }

        $sql .= !empty($options['group']) ? ' GROUP BY '.$options['group'] : '';
        $sql .= !empty($options['order']) ? ' ORDER BY '.$options['order'] : '';

        $this->_db->addLimitAndOffset($sql,$options);

        return $sql;
    }


    /**
    * Adds a sanitized version of $conditions to the $sql string. Note that the passed $sql string is changed.
    */
    public function addConditions(&$sql, $conditions = null, $table_alias = null)
    {
        if (empty($sql)) {
            $concat = '';
        }
        //if (is_string($conditions) && (stristr($conditions,' WHERE ') || stristr($conditions,'SELECT'))) {
        if (is_string($conditions) && (preg_match('/^SELECT.*?WHERE/is',trim($conditions)))) {// || stristr($conditions,'SELECT'))) {
            $concat = '';
            $sql = $conditions;
            $conditions = '';
        } else {

            $concat = 'WHERE';
        }
        $concat = empty($sql) ? '' : ' WHERE ';
        if (stristr($sql,' WHERE ')) $concat = ' AND ';
        if (empty($conditions) && $this->_getDatabaseType() == 'sqlite') $conditions = '1';  // sqlite HACK

        if($this->getInheritanceColumn() !== false){
            $type_condition = $this->typeCondition($table_alias);
            if (empty($sql)) {
                $sql .= !empty($type_condition) ? $concat.$type_condition : '';
                $concat = ' AND ';
                if (!empty($conditions)) {
                    $conditions = '('.$conditions.')';
                }
            } else {
                if (($wherePos=stripos($sql,'WHERE'))!==false) {
                    if (!empty($type_condition)) {
                        $oldConditions = trim(substr($sql,$wherePos+5));
                        $sql = substr($sql,0,$wherePos).' WHERE '.$type_condition.' AND ('.$oldConditions.')';
                        $concat = ' AND ';
                    }
                    if (!empty($conditions)) {
                        $conditions = '('.$conditions.')';
                    }
                } else {
                    if (!empty($type_condition)) {
                        //$oldConditions = trim(substr($sql,$wherePos+5));
                        $sql = $sql.' WHERE '.$type_condition.'';
                        $concat = ' AND ';
                    }
                    if (!empty($conditions)) {
                        $conditions = '('.$conditions.')';
                    }
                }

            }
        }

        if(!empty($conditions)){

            $sql  .= $concat.$conditions;
            $concat = ' AND ';

        }


        return $sql;
    }

    /**
    * Gets a sanitized version of the input array. Each element will be escaped
    */
    public function getSanitizedConditionsArray($conditions_array)
    {
        $result = array();
        foreach ($conditions_array as $k=>$v){
            $k = str_replace(':','',$k); // Used for Oracle type bindings
            if($this->hasColumn($k)){
                $v = $this->castAttributeForDatabase($k, $v);
                $result[$k] = $v;
            }
        }
        return $result;
    }


    /**
    * This functions is used to get the conditions from an AkRequest object
    */
    public function getConditions($conditions, $prefix = '', $model_name = null)
    {
        $model_name = isset($model_name) ? $model_name : $this->getModelName();
        $model_conditions = !empty($conditions[$model_name]) ? $conditions[$model_name] : $conditions;
        if($this->$model_name instanceof $model_name){
            $model_instance = $this->$model_name;
        }else{
            $model_instance = $this;
        }
        $new_conditions = array();
        if(is_array($model_conditions)){
            foreach ($model_conditions as $col=>$value){
                if($model_instance->hasColumn($col)){
                    $new_conditions[$prefix.$col] = $value;
                }
            }
        }
        return $new_conditions;
    }

    protected function _quoteColumnName($column_name)
    {
        return $this->_db->nameQuote.$column_name.$this->_db->nameQuote;
    }


    /**
    * Finder methods must instantiate through this method to work with the single-table inheritance model and
    * eager loading associations.
    * That makes it possible to create objects of different types from the same table.
    */
    public function &instantiate($record, $set_as_new = true, $call_after_instantiate = true)
    {
        $inheritance_column = $this->getInheritanceColumn();
        if(!empty($record[$inheritance_column])){
            $inheritance_column = $record[$inheritance_column];
            $inheritance_model_name = AkInflector::camelize($inheritance_column);
            @require_once(AkInflector::toModelFilename($inheritance_model_name));
            if(!class_exists($inheritance_model_name)){
                trigger_error($this->t("The single-table inheritance mechanism failed to locate the subclass: '%class_name'. ".
                "This error is raised because the column '%column' is reserved for storing the class in case of inheritance. ".
                "Please rename this column if you didn't intend it to be used for storing the inheritance class ".
                "or overwrite #{self.to_s}.inheritance_column to use another column for that information.",
                array('%class_name'=>$inheritance_model_name, '%column'=>$this->getInheritanceColumn())).Ak::getFileAndNumberTextForError(1),E_USER_ERROR);
            }
        }

        $model_name = isset($inheritance_model_name) ? $inheritance_model_name : $this->getModelName();
        $object = new $model_name(array('init'=>false));
        $object->_newRecord = $set_as_new;
        $object->setConnection($this->getConnection());
        $object->init(array('attributes', $record));

        if ($call_after_instantiate) {
            $object->afterInstantiate();
            $object->notifyObservers('afterInstantiate');
        }
        (AK_CLI && AK_ENVIRONMENT == 'development') ? $object ->toString() : null;

        return $object;
    }

    /*/Finding records*/



    /**
                         Setting Attributes
    ====================================================================
    See also: Getting Attributes, Model Attributes, Toggling Attributes, Counting Attributes.
    */
    public function setAttribute($attribute, $value, $inspect_for_callback_child_method = AK_ACTIVE_RECORD_ENABLE_CALLBACK_SETTERS, $compose_after_set = true)
    {
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
            if ($compose_after_set && $this->isCombinedAttribute($attribute)){
                $this->decomposeCombinedAttribute($attribute);
            }
        }elseif(substr($attribute,-12) == 'confirmation' && $this->hasAttribute(substr($attribute,0,-13))){
            $this->$attribute = $value;
        }

        if($this->_internationalize){
            $this->setInternationalizedAttribute($attribute, $value, $inspect_for_callback_child_method, $compose_after_set);
        }
        return true;
    }

    public function set($attribute, $value = null, $inspect_for_callback_child_method = true, $compose_after_set = true)
    {
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
    public function setAttributes($attributes, $override_attribute_protection = false, $inspect_for_callback_child_method = AK_ACTIVE_RECORD_ENABLE_CALLBACK_GETTERS)
    {
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


    public function setId($value)
    {
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

    public function getAttribute($attribute, $inspect_for_callback_child_method = AK_ACTIVE_RECORD_ENABLE_CALLBACK_GETTERS)
    {
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
        if(isset($this->$attribute) || (!isset($this->$attribute) && $this->isCombinedAttribute($attribute))){
            if($this->hasAttribute($attribute)){
                if (!empty($this->_combinedAttributes) && $this->isCombinedAttribute($attribute)){
                    $this->composeCombinedAttribute($attribute);
                }
                return isset($this->$attribute) ? $this->$attribute : null;
            }elseif($this->_internationalize && $this->isInternationalizeCandidate($attribute)){
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

        if($this->_internationalize){
            return $this->getAttributeByLocale($attribute, is_bool($inspect_for_callback_child_method) ? $this->getCurrentLocale() : $inspect_for_callback_child_method);
        }
        return null;
    }

    public function get($attribute = null, $inspect_for_callback_child_method = true)
    {
        return !isset($attribute) ? $this->getAttributes($inspect_for_callback_child_method) : $this->getAttribute($attribute, $inspect_for_callback_child_method);
    }

    /**
    * Returns an array of all the attributes with their names as keys and clones of their objects as values in case they are objects.
    */
    public function getAttributes($inspect_for_callback_child_method = AK_ACTIVE_RECORD_ENABLE_CALLBACK_GETTERS)
    {
        $attributes = array();
        $available_attributes = $this->getAvailableAttributes();
        foreach ($available_attributes as $available_attribute){
            $attribute = $this->getAttribute($available_attribute['name'], $inspect_for_callback_child_method);
            $attributes[$available_attribute['name']] = is_object($attribute) ? clone($attribute) : $attribute;
        }

        if($this->_internationalize){
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
    public function getId()
    {
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
    public function toggleAttribute($attribute)
    {
        $value = $this->getAttribute($attribute);
        $new_value = $value ? false : true;
        $this->setAttribute($attribute, $new_value);
        return $new_value;
    }


    /**
    * Toggles the attribute and saves the record.
    */
    public function toggleAttributeAndSave($attribute)
    {
        $value = $this->toggleAttribute($attribute);
        if($this->updateAttribute($attribute, $value)){
            return $value;
        }
        return null;
    }

    /*/Toggling Attributes*/


    /**
                         Counting Attributes
    ====================================================================
    See also: Counting Records, Setting Attributes, Getting Attributes.
    */

    /**
    * Increments the specified counter by one. So $DiscussionBoard->incrementCounter("post_count",
    * $discussion_board_id); would increment the "post_count" counter on the board responding to
    * $discussion_board_id. This is used for caching aggregate values, so that they doesn't need to
    * be computed every time. Especially important for looping over a collection where each element
    * require a number of aggregate values. Like the $DiscussionBoard that needs to list both the number of posts and comments.
    */
    public function incrementCounter($counter_name, $id, $difference = 1)
    {
        return $this->updateAll("$counter_name = $counter_name + $difference", $this->getPrimaryKey().' = '.$this->castAttributeForDatabase($this->getPrimaryKey(), $id)) === 1;
    }

    /**
    * Works like AkActiveRecord::incrementCounter, but decrements instead.
    */
    public function decrementCounter($counter_name, $id, $difference = 1)
    {
        return $this->updateAll("$counter_name = $counter_name - $difference", $this->getPrimaryKey().' = '.$this->castAttributeForDatabase($this->getPrimaryKey(), $id)) === 1;
    }

    /**
    * Initializes the attribute to zero if null and subtracts one. Only makes sense for number-based attributes. Returns attribute value.
    */
    public function decrementAttribute($attribute)
    {
        if(!isset($this->$attribute)){
            $this->$attribute = 0;
        }
        return $this->$attribute -= 1;
    }

    /**
    * Decrements the attribute and saves the record.
    */
    public function decrementAndSaveAttribute($attribute)
    {
        return $this->updateAttribute($attribute,$this->decrementAttribute($attribute));
    }


    /**
    * Initializes the attribute to zero if null and adds one. Only makes sense for number-based attributes. Returns attribute value.
    */
    public function incrementAttribute($attribute)
    {
        if(!isset($this->$attribute)){
            $this->$attribute = 0;
        }
        return $this->$attribute += 1;
    }

    /**
    * Increments the attribute and saves the record.
    */
    public function incrementAndSaveAttribute($attribute)
    {
        return $this->updateAttribute($attribute,$this->incrementAttribute($attribute));
    }

    /*/Counting Attributes*/

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
    public function setAccessibleAttributes()
    {
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
    public function setProtectedAttributes()
    {
        $args = func_get_args();
        $this->_protectedAttributes = array_unique(array_merge((array)$this->_protectedAttributes, $args));
    }

    public function removeAttributesProtectedFromMassAssignment($attributes)
    {
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

    public function getAvailableAttributes()
    {
        return array_merge($this->getColumns(), $this->getAvailableCombinedAttributes());
    }

    public function getAttributeCaption($attribute)
    {
        return $this->t(AkInflector::humanize($attribute));
    }

    /**
     * This function is useful in case you need to know if attributes have been assigned to an object.
     */
    public function hasAttributesDefined()
    {
        $attributes = join('',$this->getAttributes());
        return empty($attributes);
    }


    /**
    * Returns the primary key field.
    */
    public function getPrimaryKey()
    {
        if(!isset($this->_primaryKey)){
            $this->setPrimaryKey();
        }
        return $this->_primaryKey;
    }

    public function getColumnNames()
    {
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
    public function getContentColumns()
    {
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
    public function getAttributeNames()
    {
        $attributes = array_keys($this->getAvailableAttributes());
        $names = array_combine($attributes,array_map(array($this,'getAttributeCaption'), $attributes));
        natsort($names);
        return $names;
    }


    /**
    * Returns true if the specified attribute has been set by the user or by a database load and is neither null nor empty?
    */
    public function isAttributePresent($attribute)
    {
        $value = $this->getAttribute($attribute);
        return !empty($value);
    }

    /**
    * Returns true if given attribute exists for this Model.
    *
    * @param string $attribute
    * @return boolean
    */
    public function hasAttribute ($attribute)
    {
        empty($this->_columns) ? $this->getColumns() : $this->_columns; // HINT: only used by HasAndBelongsToMany joinObjects, if the table is not present yet!
        return isset($this->_columns[$attribute]) || (!empty($this->_combinedAttributes) && $this->isCombinedAttribute($attribute));
    }

    /*/Model Attributes*/


    /**
                          Combined attributes
    ====================================================================
    *
    * The Akelos Framework has a handy way to represent combined fields.
    * You can add a new attribute to your models using a printf patter to glue
    * multiple parameters in a single one.
    *
    * For example, If we set...
    * $this->addCombinedAttributeConfiguration('name', "%s %s", 'first_name', 'last_name');
    * $this->addCombinedAttributeConfiguration('date', "%04d-%02d-%02d", 'year', 'month', 'day');
    * $this->setAttributes('first_name=>','John','last_name=>','Smith','year=>',2005,'month=>',9,'day=>',27);
    *
    * $this->name // will have "John Smith" as value and
    * $this->date // will be 2005-09-27
    *
    * On the other hand if you do
    *
    *   $this->setAttribute('date', '2008-11-30');
    *
    *   All the 'year', 'month' and 'day' getters will be fired (if they exist) the following attributes will be set
    *
    *    $this->year // will be 2008
    *    $this->month // will be 11 and
    *    $this->day // will be 27
    *
    * Sometimes you might need a pattern for composing and another for decomposing attributes. In this case you can specify
    * an array as the pattern values, where first element will be the composing pattern and second element will be used
    * for decomposing.
    *
    * You can also specify a callback method from this object function instead of a pattern. You can also assign a callback
    * for composing and another for decomposing by passing their names as an array like on the patterns.
    *
    *    <?php
    *    class User extends ActiveRecord
    *    {
    *        public function User()
    *        {
    *            // You can use a multiple patterns array where "%s, %s" will be used for combining fields and "%[^,], %s" will be used
    *            // for decomposing fields. (as you can see you can also use regular expressions on your patterns)
    *            $User->addCombinedAttributeConfiguration('name', array("%s, %s","%[^,], %s"), 'last_name', 'first_name');
    *
    *            //Here we set email_link so compose_email_link() will be triggered for building up the field and parse_email_link will
    *            // be used for getting the fields out
    *            $User->addCombinedAttributeConfiguration('email_link', array("compose_email_link","parse_email_link"), 'email', 'name');
    *
    *            // We need to tell the ActiveRecord to load it's magic (see the example below for a simpler solution)
    *            $attributes = (array)func_get_args();
    *            return $this->init($attributes);
    *
    *        }
    *        public function compose_email_link()
    *        {
    *            $args = func_get_arg(0);
    *            return "<a href=\'mailto:{$args[\'email\']}\'>{$args[\'name\']}</a>";
    *        }
    *        public function parse_email_link($email_link)
    *        {
    *            $results = sscanf($email_link, "<a href=\'mailto:%[^\']\'>%[^<]</a>");
    *            return array(\'email\'=>$results[0],\'name\'=>$results[1]);
    *        }
    *
    *    }
    *   ?>
    *
    * You can also simplify your live by declaring the combined attributes as a class variable like:
    *    <?php
    *    class User extends ActiveRecord
    *    {
    *       public $combined_attributes array(
    *       array('name', array("%s, %s","%[^,], %s"), 'last_name', 'first_name')
    *       array('email_link', array("compose_email_link","parse_email_link"), 'email', 'name')
    *       );
    *
    *       // ....
    *    }
    *   ?>
    *
    */

    /**
    * Returns true if given attribute is a combined attribute for this Model.
    *
    * @param string $attribute
    * @return boolean
    */
    public function isCombinedAttribute ($attribute)
    {
        return !empty($this->_combinedAttributes) && isset($this->_combinedAttributes[$attribute]);
    }

    public function addCombinedAttributeConfiguration($attribute)
    {
        $args = is_array($attribute) ? $attribute : func_get_args();
        $columns = array_slice($args,2);
        $invalid_columns = array();
        foreach ($columns as $colum){
            if(!$this->hasAttribute($colum)){
                $invalid_columns[] = $colum;
            }
        }
        if(!empty($invalid_columns)){
            trigger_error(Ak::t('There was an error while setting the composed field "%field_name", the following mapping column/s "%columns" do not exist',
            array('%field_name'=>$args[0],'%columns'=>join(', ',$invalid_columns))).Ak::getFileAndNumberTextForError(1), E_USER_ERROR);
        }else{
            $attribute = array_shift($args);
            $this->_combinedAttributes[$attribute] = $args;
            $this->composeCombinedAttribute($attribute);
        }
    }

    public function composeCombinedAttributes()
    {

        if(!empty($this->_combinedAttributes)){
            $attributes = array_keys($this->_combinedAttributes);
            foreach ($attributes as $attribute){
                $this->composeCombinedAttribute($attribute);
            }
        }
    }

    public function composeCombinedAttribute($combined_attribute)
    {
        if($this->isCombinedAttribute($combined_attribute)){
            $config = $this->_combinedAttributes[$combined_attribute];
            $pattern = array_shift($config);

            $pattern = is_array($pattern) ? $pattern[0] : $pattern;
            $got = array();

            foreach ($config as $attribute){
                if(isset($this->$attribute)){
                    $got[$attribute] = $this->getAttribute($attribute);
                }
            }
            if(count($got) === count($config)){
                $this->$combined_attribute = method_exists($this, $pattern) ? $this->{$pattern}($got) : vsprintf($pattern, $got);
            }
        }
    }

    public function getCombinedAttributesWhereThisAttributeIsUsed($attribute)
    {
        $result = array();
        foreach ($this->_combinedAttributes as $combined_attribute=>$settings){
            if(in_array($attribute,$settings)){
                $result[] = $combined_attribute;
            }
        }
        return $result;
    }


    public function requiredForCombination($attribute)
    {
        foreach ($this->_combinedAttributes as $settings){
            if(in_array($attribute,$settings)){
                return true;
            }
        }
        return false;
    }

    public function hasCombinedAttributes()
    {
        return count($this->getCombinedSubattributes()) === 0 ? false :true;
    }

    public function getCombinedSubattributes($attribute)
    {
        $result = array();
        if(is_array($this->_combinedAttributes[$attribute])){
            $attributes = $this->_combinedAttributes[$attribute];
            array_shift($attributes);
            foreach ($attributes as $attribute_to_check){
                if(isset($this->_combinedAttributes[$attribute_to_check])){
                    $result[] = $attribute_to_check;
                }
            }
        }
        return $result;
    }

    public function decomposeCombinedAttributes()
    {
        if(!empty($this->_combinedAttributes)){
            $attributes = array_keys($this->_combinedAttributes);
            foreach ($attributes as $attribute){
                $this->decomposeCombinedAttribute($attribute);
            }
        }
    }

    public function decomposeCombinedAttribute($combined_attribute, $used_on_combined_fields = false)
    {
        if(isset($this->$combined_attribute) && $this->isCombinedAttribute($combined_attribute)){
            $config = $this->_combinedAttributes[$combined_attribute];
            $pattern = array_shift($config);
            $pattern = is_array($pattern) ? $pattern[1] : $pattern;

            if(method_exists($this, $pattern)){
                $pieces = $this->{$pattern}($this->$combined_attribute);
                if(is_array($pieces)){
                    foreach ($pieces as $k=>$v){
                        $is_combined = $this->isCombinedAttribute($k);
                        if($is_combined){
                            $this->decomposeCombinedAttribute($k);
                        }
                        $this->setAttribute($k, $v, true, !$is_combined);
                    }
                    if($is_combined && !$used_on_combined_fields){
                        $combined_attributes_contained_on_this_attribute = $this->getCombinedSubattributes($combined_attribute);
                        if(count($combined_attributes_contained_on_this_attribute)){
                            $this->decomposeCombinedAttribute($combined_attribute, true);
                        }
                    }
                }
            }else{
                $got = sscanf($this->$combined_attribute, $pattern);
                for ($x=0; $x<count($got); $x++){
                    $attribute = $config[$x];
                    $is_combined = $this->isCombinedAttribute($attribute);
                    if($is_combined){
                        $this->decomposeCombinedAttribute($attribute);
                    }
                    $this->setAttribute($attribute, $got[$x], true, !$is_combined);
                }
            }
        }
    }

    public function getAvailableCombinedAttributes()
    {
        $combined_attributes = array();
        foreach ($this->_combinedAttributes as $attribute=>$details){
            $combined_attributes[$attribute] = array('name'=>$attribute, 'type'=>'string', 'path' => array_shift($details), 'uses'=>$details);
        }
        return !empty($this->_combinedAttributes) && is_array($this->_combinedAttributes) ? $combined_attributes : array();
    }

    /*/Combined attributes*/




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
    public function &establishConnection($specification_or_profile = AK_DEFAULT_DATABASE_PROFILE, $force = false)
    {
        if($force || !$this->isConnected()){
            $adapter = AkDbAdapter::getInstance($specification_or_profile);
            return $this->setConnection($adapter);
        }
        return $this->_db;
    }


    /**
    * Returns true if a connection that's accessible to this class have already been opened.
    */
    public function isConnected()
    {
        return isset($this->_db);
    }

    /**
    * Returns the connection currently associated with the class. This can also be used to
    * "borrow" the connection to do database work unrelated to any of the specific Active Records.
    */
    public function &getConnection()
    {
        return $this->_db;
    }

    /**
    * Sets the connection for the class.
    */
    public function &setConnection($db_adapter = null)
    {
        if (is_null($db_adapter)){
            $db_adapter = AkDbAdapter::getInstance();
        }
        $this->_db = $db_adapter;
        return $db_adapter;
    }

    protected function _getDatabaseType()
    {
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
    public function setPrimaryKey($primary_key = 'id')
    {
        if(!$this->hasColumn($primary_key)){
            trigger_error($this->t('Opps! We could not find primary key column %primary_key on the table %table, for the model %model',array('%primary_key'=>$primary_key,'%table'=>$this->getTableName(), '%model'=>$this->getModelName())).' '.Ak::getFileAndNumberTextForError(1),E_USER_ERROR);
        }else {
            $this->_primaryKey = $primary_key;
        }
    }


    public function getTableName($modify_for_associations = true)
    {
        if(!isset($this->_tableName)){
            // We check if we are on a inheritance Table Model
            $this->getClassForDatabaseTableMapping();
            if(!isset($this->_tableName)){
                $this->setTableName();
            }
        }

        if($modify_for_associations && isset($this->_associationTablePrefixes[$this->_tableName])){
            return $this->_associationTablePrefixes[$this->_tableName];
        }

        return $this->_tableName;
    }

    public function setTableName($table_name = null, $check_for_existence = AK_ACTIVE_RECORD_VALIDATE_TABLE_NAMES, $check_mode = false)
    {
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
                    ' avoid table name validation',array('%table_name'=>$table_name,'%model'=>$this->getModelName())).Ak::getFileAndNumberTextForError(1),E_USER_WARNING);
                }
                return false;
            }
        }
        $this->_tableName = $table_name;
        return true;
    }


    public function getOnlyAvailableAttributes($attributes)
    {
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

    public function getColumnsForAttributes($attributes)
    {
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
    public function hasColumn($column)
    {
        empty($this->_columns) ? $this->getColumns() : $this->_columns;
        return isset($this->_columns[$column]);
    }


    /*/Table Settings*/

    /**
                           Database Reflection
    ====================================================================
    See also: Table Settings, Type Casting.
    */


    /**
    * Initializes the attributes array with keys matching the columns from the linked table and
    * the values matching the corresponding default value of that column, so
    * that a new instance, or one populated from a passed-in array, still has all the attributes
    * that instances loaded from the database would.
    */
    public function attributesFromColumnDefinition()
    {
        $attributes = array();

        foreach ((array)$this->getColumns() as $column_name=>$column_settings){
            if (!isset($column_settings['primaryKey']) && isset($column_settings['hasDefault'])) {
                $attributes[$column_name] = $this->_extractValueFromDefault($column_settings['defaultValue']);
            } else {
                $attributes[$column_name] = null;
            }
        }
        return $attributes;
    }

    /**
     * Gets information from the database engine about a single table
     */
    protected function _databaseTableInternals($table)
    {
        if (!$cache = AkDbSchemaCache::get('table_internals_for_'.$table)) {
            $cache = $this->_db->getColumnDetails($table);
            AkDbSchemaCache::set('table_internals_for_'.$table, $cache);
        }
        return $cache;
    }
    public function getColumnsWithRegexBoundariesAndAlias($alias)
    {
        $columns = array_keys($this->getColumns());
        foreach ($columns as $k=>$column){
            $columns[$k] = '/([^_])\b('.$alias.')\.('.$column.')\b/';
        }
        return $columns;
    }
    public function getColumnsWithRegexBoundaries()
    {
        $columns = array_keys($this->getColumns());
        foreach ($columns as $k=>$column){
            $columns[$k] = '/([^\.])\b('.$column.')\b/';
        }
        return $columns;
    }


    /**
    * If is the first time we use a model this function will run the installer for the model if it exists
    */
    protected function _runCurrentModelInstallerIfExists(&$column_objects)
    {
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
    * Returns an array of column objects for the table associated with this class.
    */
    public function getColumns($force_reload = false)
    {
        if(empty($this->_columns) || $force_reload){
            $this->_columns = $this->getColumnSettings($force_reload);
        }

        return (array)$this->_columns;
    }

    public function getColumnSettings($force_reload = false)
    {
        if(empty($this->_columnsSettings) || $force_reload){
            $this->loadColumnsSettings($force_reload);
            $this->initiateColumnsToNull();
        }
        return isset($this->_columnsSettings) ? $this->_columnsSettings : array();
    }

    public function loadColumnsSettings($force_reload = false)
    {
        if(is_null($this->_db)){
            $this->establishConnection();
        }
        $this->_columnsSettings = ($force_reload ? null : $this->_getPersistedTableColumnSettings());
        if(empty($this->_columnsSettings)){
            if(empty($this->_dataDictionary)){
                $this->_dataDictionary = $this->_db->getDictionary();
            }

            $column_objects = $this->_databaseTableInternals($this->getTableName());

            if( !isset($this->_avoidTableNameValidation) &&
            !is_array($column_objects) &&
            !$this->_runCurrentModelInstallerIfExists($column_objects)){
                // akelos_migrations is the first active record to be installed, therefore the table will be created after the first run.
                if($this->getTableName() != 'akelos_migrations'){
                    trigger_error(Ak::t('Ooops! Could not fetch details for the table %table_name.', array('%table_name'=>$this->getTableName())).Ak::getFileAndNumberTextForError(4), E_USER_NOTICE);
                }
                return false;
            }elseif (empty($column_objects)){
                $this->_runCurrentModelInstallerIfExists($column_objects);
            }
            if(is_array($column_objects)){
                foreach (array_keys($column_objects) as $k){
                    $this->setColumnSettings($column_objects[$k]->name, $column_objects[$k]);
                }
            }
            if(!empty($this->_columnsSettings)){
                $this->_persistTableColumnSettings();
            }
        }
        return isset($this->_columnsSettings) ? $this->_columnsSettings : array();
    }



    public function setColumnSettings($column_name, $column_object)
    {
        $this->_columnsSettings[$column_name] = array();
        $this->_columnsSettings[$column_name]['name'] = $column_object->name;

        if($this->_internationalize && $this->isInternationalizeCandidate($column_object->name)){
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
    * Resets all the cached information about columns, which will cause they to be reloaded on the next request.
    */
    public function resetColumnInformation()
    {
        $this->_clearPersitedColumnSettings();
        $this->_columnNames = $this->_columns = $this->_columnsSettings = $this->_contentColumns = array();
    }

    protected function _getModelColumnSettings()
    {
        return AkDbSchemaCache::get($this->getModelName().'_column_settings');
    }

    protected function _persistTableColumnSettings()
    {
        AkDbSchemaCache::set($this->getModelName().'_column_settings', $this->_columnsSettings);
    }

    protected function _getPersistedTableColumnSettings()
    {
        return AkDbSchemaCache::get($this->getModelName().'_column_settings');
    }

    protected function _clearPersitedColumnSettings()
    {
        AkDbSchemaCache::clear($this->getModelName());
    }



    public function initiateAttributeToNull($attribute)
    {
        if(!isset($this->$attribute)){
            $this->$attribute = null;
        }
    }

    public function initiateColumnsToNull()
    {
        if(isset($this->_columnsSettings) && is_array($this->_columnsSettings)){
            array_map(array($this,'initiateAttributeToNull'),array_keys($this->_columnsSettings));
        }
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
    public function getAkelosDataType(&$adodb_column_object)
    {
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
        'C'=>'string', // Varchar, capped to 255 characters.
        'X' => 'text', // Larger varchar, capped to 4000 characters (to be compatible with Oracle).
        'XL' => 'text', // For Oracle, returns CLOB, otherwise the largest varchar size.

        'C2' => 'string', // Multibyte varchar
        'X2' => 'string', // Multibyte varchar (largest size)

        'B' => 'binary', // BLOB (binary large object)

        'D' => array('date'), //  Date
        'T' =>  array('datetime', 'timestamp'), //Datetime or Timestamp
        'L' => 'boolean', // Integer field suitable for storing booleans (0 or 1)
        'R' => 'serial', // Serial Integer
        'I' => 'integer', // Integer (mapped to I4)
        'I1' => 'integer', // 1-byte integer
        'I2' => 'integer', // 2-byte integer
        'I4' => 'integer', // 4-byte integer
        'I8' => 'integer', // 8-byte integer
        'F' => 'float', // Floating point number
        'N' => 'decimal' //  Numeric or decimal number
        );

        $result = !isset($adodb_data_types[$meta_type]) ?
        'string' :
        (is_array($adodb_data_types[$meta_type]) ? $adodb_data_types[$meta_type][0] : $adodb_data_types[$meta_type]);

        if($result == 'text'){
            if((strstr($adodb_column_object->type, 'CHAR')) || (isset($adodb_column_object->max_length) && $adodb_column_object->max_length > 0 && $adodb_column_object->max_length < 256 )){
                return 'string';
            }
        }

        if($this->_getDatabaseType() == 'mysql'){
            if($result == 'integer' && stristr($adodb_column_object->type, 'TINYINT')){
                return 'boolean';
            }
        }elseif($this->_getDatabaseType() == 'postgre'){
            if($adodb_column_object->type == 'timestamp' || $result == 'datetime'){
                $adodb_column_object->max_length = 19;
            }
        }elseif($this->_getDatabaseType() == 'sqlite'){
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


    /**
     * This method retrieves current class name that will be used to map
     * your database to this object.
     */
    public function getClassForDatabaseTableMapping()
    {
        $class_name = get_class($this);
        if(is_subclass_of($this,'akactiverecord') || is_subclass_of($this,'AkActiveRecord')){
            $parent_class = get_parent_class($this);
            while (substr(strtolower($parent_class),-12) != 'activerecord'){
                $class_name = $parent_class;
                $parent_class = get_parent_class($parent_class);
            }
        }

        $class_name = $this->_getModelName($class_name);
        // This is an Active Record Inheritance so we set current table to parent table.
        if(!empty($class_name) && strtolower($class_name) != 'activerecord'){
            $this->_inheritanceClassName = $class_name;
            @$this->setTableName(AkInflector::tableize($class_name), false);
        }

        return $class_name;
    }

    public function getDisplayField()
    {
        return  empty($this->displayField) && $this->hasAttribute('name') ? 'name' : (isset($this->displayField) && $this->hasAttribute($this->displayField) ? $this->displayField : $this->getPrimaryKey());
    }

    public function setDisplayField($attribute_name)
    {
        if($this->hasAttribute($attribute_name)){
            $this->displayField = $attribute_name;
            return true;
        }else {
            return false;
        }
    }


    /*/Database Reflection*/



    /**
                             Type Casting
    ====================================================================
    See also: Database Reflection.
    */

    public function getAttributesBeforeTypeCast()
    {
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


    public function getAttributeBeforeTypeCast($attribute)
    {
        if(isset($this->{$attribute.'_before_type_cast'})){
            return $this->{$attribute.'_before_type_cast'};
        }
        return null;
    }

    public function getAvailableAttributesQuoted($inspect_for_callback_child_method = AK_ACTIVE_RECORD_ENABLE_CALLBACK_GETTERS)
    {
        return $this->getAttributesQuoted($this->getAttributes($inspect_for_callback_child_method));
    }


    public function getAttributesQuoted($attributes_array)
    {
        $set = array();
        $attributes_array = $this->getSanitizedConditionsArray($attributes_array);
        foreach (array_diff($attributes_array,array('')) as $k=>$v){
            $set[$k] = $k.'='.$v;
        }

        return $set;
    }

    public function getColumnType($column_name)
    {
        empty($this->_columns) ? $this->getColumns() : null;
        return empty($this->_columns[$column_name]['type']) ? false : $this->_columns[$column_name]['type'];
    }

    public function getColumnScale($column_name)
    {
        empty($this->_columns) ? $this->getColumns() : null;
        return empty($this->_columns[$column_name]['scale']) ? false : $this->_columns[$column_name]['scale'];
    }

    public function castAttributeForDatabase($column_name, $value, $add_quotes = true)
    {
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
                if($this->_getDatabaseType() == 'postgre'){
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
                $result = !empty($this->_columns[$column_name]['notNull']) && $result == 'null' && $this->_getDatabaseType() == 'sqlite' ? '0' : $result;
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
    public function castAttributesForDatabase($column_name, $values, $add_quotes = true)
    {
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

    public function castAttributeFromDatabase($column_name, $value)
    {
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
                    if ($this->_getDatabaseType()=='postgre'){
                        return $value=='t' ? true : false;
                    }
                    return (integer)$value === 1 ? true : false;
                }elseif(!empty($value) && 'date' == $column_type && strstr(trim($value),' ')){
                    return substr($value,0,10) == '0000-00-00' ? null : str_replace(substr($value,strpos($value,' ')), '', $value);
                }elseif (!empty($value) && 'datetime' == $column_type && substr($value,0,10) == '0000-00-00'){
                    return null;
                }elseif ('binary' == $column_type && $this->_getDatabaseType() == 'postgre'){
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
    protected function _castDateParametersFromDateHelper(&$params)
    {
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

    protected function _addBlobQueryStack($column_name, $blob_value)
    {
        $this->_BlobQueryStack[$column_name] = $blob_value;
    }


    protected function _shouldCompressColumn($column_name)
    {
        if(empty($this->compress)){
            return false;
        }elseif(!is_array($this->compress)){
            $this->compress = Ak::toArray($this->compress);
        }
        $return = isset($this->compress[$column_name]) || in_array($column_name, $this->compress);
        return $return;
    }

    protected function _shouldSerializeColumn($column_name)
    {
        if(empty($this->serialize)){
            return false;
        }elseif(!is_array($this->serialize)){
            $this->serialize = Ak::toArray($this->serialize);
        }
        $return=isset($this->serialize[$column_name]) || in_array($column_name, $this->serialize);
        return $return;
    }

    protected function _ensureClassExistsForSerializedColumnBeforeUnserializing($column_name)
    {
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

    protected function _updateBlobFields($condition)
    {
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
    public function isLockingEnabled()
    {
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

    public function transactionStart()
    {
        return $this->_db->startTransaction();
    }

    public function transactionComplete()
    {
        return $this->_db->stopTransaction();
    }

    public function transactionFail()
    {
        $this->_db->failTransaction();
        return false;
    }

    public function transactionHasFailed()
    {
        return $this->_db->hasTransactionFailed();
    }

    /*/Transactions*/





    /**
                                  Observers
    ====================================================================
    See also: Callbacks.
    */

    /**
    * $state store the state of this observable object
    */
    protected $_observable_state;

    protected function _instantiateDefaultObserver()
    {
        $default_observer_name = ucfirst($this->getModelName().'Observer');
        if(class_exists($default_observer_name)){
            //$Observer = new $default_observer_name($this);
            Ak::singleton($default_observer_name,  $this);
        }
    }

    /**
    * Calls the $method using the reference to each
    * registered observer.
    * @return true (this is used internally for triggering observers on default callbacks)
    */
    public function notifyObservers ($method = null)
    {
        $observers = $this->getObservers();
        $observer_count = count($observers);

        if(!empty($method)){
            $this->setObservableState($method);
        }

        $model_name = $this->getModelName();
        for ($i=0; $i<$observer_count; $i++) {
            if(in_array($model_name, $observers[$i]->_observing)){
                if(method_exists($observers[$i], $method)){
                    $observers[$i]->$method($this);
                }else{
                    $observers[$i]->update($this->getObservableState(), $this);
                }
            }else{
                $observers[$i]->update($this->getObservableState(), $this);
            }
        }
        $this->setObservableState('');

        return true;
    }


    public function setObservableState($state_message)
    {
        $this->_observable_state = $state_message;
    }

    public function getObservableState()
    {
        return $this->_observable_state;
    }

    /**
    * Register the reference to an object object
    *
    *
    * @param $observer AkObserver
    * @param $options array of options for the observer
    * @return void
    */
    public function addObserver(&$observer)
    {
        $staticVarNs='AkActiveRecord::observers::' . $this->_modelName;
        $observer_class_name = get_class($observer);
        /**
         * get the statically stored observers for the namespace
         */
        $observers = Ak::getStaticVar($staticVarNs);
        if (!is_array($observers)) {
            $observers = array('classes'=>array(),'objects'=>array());
        }
        /**
         * if not already registered, the observerclass will
         * be registered now
         */
        if (!in_array($observer_class_name,$observers['classes'])) {
            $observers['classes'][] = $observer_class_name;
            $observers['objects'][] = $observer;
            Ak::setStaticVar($staticVarNs, $observers);

        }
    }
    /**
    * Register the reference to an object object
    * @return void
    */
    public function &getObservers()
    {
        $staticVarNs='AkActiveRecord::observers::' . $this->_modelName;
        $key = 'objects';

        $array = array();
        $observers_arr = Ak::getStaticVar($staticVarNs);
        if (isset($observers_arr[$key])) {
            $observers = $observers_arr[$key];
        } else {
            $observers = $array;
        }

        return $observers;
    }

    /*/Observers*/






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
    public function actsAs($behaviour, $options = array())
    {
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

    protected function _getActAsClassName($behaviour)
    {
        $class_name = AkInflector::camelize($behaviour);
        return file_exists(AK_ACTIVE_RECORD_DIR.DS.'behaviours'.DS.'acts_as_'.AkInflector::underscore($class_name).'.php') && !class_exists('ActsAs'.$class_name) ?
        'AkActsAs'.$class_name : 'ActsAs'.$class_name;
    }

    public function &_getActAsInstance($class_name, $options)
    {
        if(!class_exists($class_name)){
            if(substr($class_name,0,2) == 'Ak'){
                include_once(AK_ACTIVE_RECORD_DIR.DS.'behaviours'.DS.AkInflector::underscore(substr($class_name, 2)).'.php');
            }else{
                include_once(AK_APP_PLUGINS_DIR.DS.AkInflector::underscore($class_name).DS.'lib'.DS.$class_name.'.php');
            }
        }
        if(!class_exists($class_name)){
            trigger_error(Ak::t('The class %class used for handling an "act_as %class" does not exist',array('%class'=>$class_name)).Ak::getFileAndNumberTextForError(1), E_USER_ERROR);
            $false = false;
            return $false;
        }else{
            $ActAsInstance = new $class_name($this, $options);
            return $ActAsInstance;
        }
    }

    protected function _loadActAsBehaviours()
    {
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
    public function actsLike()
    {
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
                            Debugging
    ====================================================================
    */


    public function dbug()
    {
        if(!$this->isConnected()){
            $this->establishConnection();
        }
        $this->_db->connection->debug = $this->_db->connection->debug ? false : true;
        $this->db_debug = $this->_db->connection->debug;
    }

    public function toString($print = false)
    {
        $result = '';
        if(!AK_CLI || (AK_ENVIRONMENT == 'testing' && !AK_CLI)){
            $result = "<h2>Details for ".AkInflector::humanize(AkInflector::underscore($this->getModelName()))." with ".$this->getPrimaryKey()." ".$this->getId()."</h2>\n<dl>\n";
            foreach ($this->getColumnNames() as $column=>$caption){
                $result .= "<dt>$caption</dt>\n<dd>".$this->getAttribute($column)."</dd>\n";
            }
            $result .= "</dl>\n<hr />";
            if($print){
                echo $result;
            }
        }elseif(AK_DEV_MODE){
            $result =   "\n".
            str_replace("\n"," ",var_export($this->getAttributes(),true));
            $result .= "\n";
            echo $result;
            return '';
        }elseif (AK_CLI){
            $result = "\n-------\n Details for ".AkInflector::humanize(AkInflector::underscore($this->getModelName()))." with ".$this->getPrimaryKey()." ".$this->getId()." ==\n\n/==\n";
            foreach ($this->getColumnNames() as $column=>$caption){
                $result .= "\t * $caption: ".$this->getAttribute($column)."\n";
            }
            $result .= "\n\n-------\n";
            if($print){
                echo $result;
            }
        }
        return $result;
    }

    public function dbugging($trace_this_on_debug_mode = null)
    {
        if(!empty($this->_db->debug) && !empty($trace_this_on_debug_mode)){
            $message = !is_scalar($trace_this_on_debug_mode) ? var_export($trace_this_on_debug_mode, true) : (string)$trace_this_on_debug_mode;
            Ak::trace($message);
        }
        return !empty($this->_db->debug);
    }



    public function debug ($data = 'active_record_class', $_functions=0)
    {
        if(!AK_DEBUG && !AK_DEV_MODE){
            return;
        }

        $data = $data == 'active_record_class' ?  clone($this) : $data;

        if($_functions!=0) {
            $sf=1;
        } else {
            $sf=0 ;
        }

        if (isset ($data)) {
            if (is_array($data) || is_object($data)) {

                if (count ($data)) {
                    echo AK_CLI ? "/--\n" : "<ol>\n";
                    while (list ($key,$value) = each ($data)) {
                        if($key{0} == '_'){
                            continue;
                        }
                        $type=gettype($value);
                        if ($type=="array") {
                            AK_CLI ? printf ("\t* (%s) %s:\n",$type, $key) :
                            printf ("<li>(%s) <b>%s</b>:\n",$type, $key);
                            ob_start();
                            Ak::debug ($value,$sf);
                            $lines = explode("\n",ob_get_clean()."\n");
                            foreach ($lines as $line){
                                echo "\t".$line."\n";
                            }
                        }elseif($type == "object"){
                            if(method_exists($value,'hasColumn') && $value->hasColumn($key)){
                                $value->toString(true);
                                AK_CLI ? printf ("\t* (%s) %s:\n",$type, $key) :
                                printf ("<li>(%s) <b>%s</b>:\n",$type, $key);
                                ob_start();
                                Ak::debug ($value,$sf);
                                $lines = explode("\n",ob_get_clean()."\n");
                                foreach ($lines as $line){
                                    echo "\t".$line."\n";
                                }
                            }
                        }elseif (stristr($type, "function")) {
                            if ($sf) {
                                AK_CLI ? printf ("\t* (%s) %s:\n",$type, $key, $value) :
                                printf ("<li>(%s) <b>%s</b> </li>\n",$type, $key, $value);
                            }
                        } else {
                            if (!$value) {
                                $value="(none)";
                            }
                            AK_CLI ? printf ("\t* (%s) %s = %s\n",$type, $key, $value) :
                            printf ("<li>(%s) <b>%s</b> = %s</li>\n",$type, $key, $value);
                        }
                    }
                    echo AK_CLI ? "\n--/\n" : "</ol>fin.\n";
                } else {
                    echo "(empty)";
                }
            }
        }
    }

    /*/Debugging*/



    /**
                        Utilities
    ====================================================================
    */
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
    public function select(&$source_array)
    {
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
    public function collect($source_array, $key_index, $value_index)
    {
        $resulting_array = array();
        if(!empty($source_array) && is_array($source_array)) {
            foreach ($source_array as $source_item){
                $resulting_array[$source_item->get($key_index)] = $source_item->get($value_index);
            }
        }
        return $resulting_array;
    }

    /**
     * Generate a json representation of the model record.
     *
     * parameters:
     *
     * @param array $options
     *
     *              option parameters:
     *             array(
     *              'collection' => array($Person1,$Person), // array of ActiveRecords
     *              'include' => array('association1','association2'), // include the associations when exporting
     *              'exclude' => array('id','name'), // exclude the attribtues
     *              'only' => array('email','last_name') // only export these attributes
     *              )
     * @return string in Json Format
     */
    public function toJson($options = array())
    {
        if (is_array($options) && isset($options[0]) && ($options[0] instanceof AkActiveRecord)) {
            $options = array('collection'=>$options);
        }
        if (isset($options['collection']) && is_array($options['collection']) && $options['collection'][0]->_modelName == $this->_modelName) {
            $json = '';

            $collection = $options['collection'];
            unset($options['collection']);
            $jsonVals = array();
            foreach ($collection as $element) {
                $jsonVals[]= $element->toJson($options);
            }
            $json = '['.implode(',',$jsonVals).']';
            return $json;
        }
        /**
         * see if we need to include associations
         */
        $associatedIds = array();
        if (isset($options['include']) && !empty($options['include'])) {
            $options['include'] = is_array($options['include'])?$options['include']:preg_split('/,\s*/',$options['include']);
            foreach ($this->_associations as $key => $obj) {
                if (in_array($key,$options['include'])) {
                    $associatedIds[$obj->getAssociationId() . '_id'] = array('name'=>$key,'type'=>$obj->getType());
                }
            }
        }
        if (isset($options['only'])) {
            $options['only'] = is_array($options['only'])?$options['only']:preg_split('/,\s*/',$options['only']);
        }
        if (isset($options['except'])) {
            $options['except'] = is_array($options['except'])?$options['except']:preg_split('/,\s*/',$options['except']);
        }
        foreach ($this->_columns as $key => $def) {

            if (isset($options['except']) && in_array($key, $options['except'])) {
                continue;
            } else if (isset($options['only']) && !in_array($key, $options['only'])) {
                continue;
            } else {
                $val = $this->$key;
                $type = $this->getColumnType($key);
                if (($type == 'serial' || $type=='integer') && $val!==null) $val = intval($val);
                if ($type == 'float' && $val!==null) $val = floatval($val);
                if ($type == 'boolean') $val = $val?1:0;
                $data[$key] = $val;
            }
        }
        if (isset($options['include'])) {
            foreach($this->_associationIds as $key=>$val) {
                if ((in_array($key,$options['include']) || in_array($val,$options['include']))) {
                    $this->$key->load();
                    $associationElement = $key;
                    $associationElement = $this->_convertColumnToXmlElement($associationElement);
                    if (is_array($this->$key)) {
                        $data[$associationElement] = array();
                        foreach ($this->$key as $el) {
                            if ($el instanceof AkActiveRecord) {
                                $attributes = $el->getAttributes();
                                foreach($attributes as $ak=>$av) {
                                    $type = $el->getColumnType($ak);
                                    if (($type == 'serial' || $type=='integer') && $av!==null) $av = intval($av);
                                    if ($type == 'float' && $av!==null) $av = floatval($av);
                                    if ($type == 'boolean') $av = $av?1:0;
                                    $attributes[$ak]=$av;
                                }
                                $data[$associationElement][] = $attributes;
                            }
                        }
                    } else {
                        $el = $this->$key->load();
                        if ($el instanceof AkActiveRecord) {
                            $attributes = $el->getAttributes();
                            foreach($attributes as $ak=>$av) {
                                $type = $el->getColumnType($ak);
                                if (($type == 'serial' || $type=='integer') && $av!==null) $av = intval($av);
                                if ($type == 'float' && $av!==null) $av = floatval($av);
                                if ($type == 'boolean') $av = $av?1:0;
                                $attributes[$ak]=$av;
                            }
                            $data[$associationElement] = $attributes;
                        }
                    }
                }
            }
        }
        return Ak::toJson($data);
    }

    protected function _convertColumnToXmlElement($col)
    {
        return str_replace('_','-',$col);
    }

    protected function _convertColumnFromXmlElement($col)
    {
        return str_replace('-','_',$col);
    }

    protected function _parseXmlAttributes($attributes)
    {
        $new = array();
        foreach($attributes as $key=>$value)
        {
            $new[$this->_convertColumnFromXmlElement($key)] = $value;
        }
        return $new;
    }

    public function &_generateModelFromArray($modelName,$attributes)
    {
        if (isset($attributes[0]) && is_array($attributes[0])) {
            $attributes = $attributes[0];
        }
        $record = new $modelName('attributes', $this->_parseXmlAttributes($attributes));
        $record->_newRecord = !empty($attributes['id']);

        $associatedIds = array();
        foreach ($record->getAssociatedIds() as $key) {
            if (isset($attributes[$key]) && is_array($attributes[$key])) {
                $class = $record->$key->_AssociationHandler->getOption($key,'class_name');
                $related = $this->_generateModelFromArray($class,$attributes[$key]);
                $record->$key->build($related->getAttributes(),false);
                $related = $record->$key->load();
                $record->$key = $related;
            }
        }
        return $record;
    }

    protected function _fromArray($array)
    {
        $data  = $array;
        $modelName = $this->getModelName();
        $values = array();
        if (!isset($data[0])) {
            $data = array($data);
        }
        foreach ($data as $key => $value) {
            if (is_array($value)){
                $values[] = $this->_generateModelFromArray($modelName, $value);
            }
        }
        return count($values)==1?$values[0]:$values;
    }

    /**
     * Reads Xml in the following format:
     *
     *
     * <?xml version="1.0" encoding="UTF-8"?>
     * <person>
     *    <id>1</id>
     *    <first-name>Hansi</first-name>
     *    <last-name>Müller</last-name>
     *    <email>hans@mueller.com</email>
     *    <created-at type="datetime">2008-01-01 13:01:23</created-at>
     * </person>
     *
     * and returns an ActiveRecord Object
     *
     * @param string $xml
     * @return AkActiveRecord
     */
    public function fromXml($xml)
    {
        $array = Ak::convert('xml','array', $xml);
        $array = $this->_fromXmlCleanup($array);
        return $this->_fromArray($array);
    }

    protected function _fromXmlCleanup($array)
    {
        $result = array();
        $key = key($array);
        while(is_string($key) && is_array($array[$key]) && count($array[$key])==1) {
            $array = $array[$key][0];
            $key = key($array);
        }
        if (is_string($key) && is_array($array[$key])) {
            $array = $array[$key];
        }
        return $array;
    }
    /**
     * Reads Json string in the following format:
     *
     * {"id":1,"first_name":"Hansi","last_name":"M\u00fcller",
     *  "email":"hans@mueller.com","created_at":"2008-01-01 13:01:23"}
     *
     * and returns an ActiveRecord Object
     *
     * @param string $json
     * @return AkActiveRecord
     */
    public function fromJson($json)
    {
        $json = Ak::fromJson($json);
        $array = Ak::convert('Object','Array',$json);
        return $this->_fromArray($array);
    }

    /**
     * Generate a xml representation of the model record.
     *
     * Example result:
     *
     * <?xml version="1.0" encoding="UTF-8"?>
     * <person>
     *    <id>1</id>
     *    <first-name>Hansi</first-name>
     *    <last-name>Müller</last-name>
     *    <email>hans@mueller.com</email>
     *    <created-at type="datetime">2008-01-01 13:01:23</created-at>
     * </person>
     *
     * parameters:
     *
     * @param array $options
     *
     *              option parameters:
     *             array(
     *              'collection' => array($Person1,$Person), // array of ActiveRecords
     *              'include' => array('association1','association2'), // include the associations when exporting
     *              'exclude' => array('id','name'), // exclude the attribtues
     *              'only' => array('email','last_name') // only export these attributes
     *              )
     * @return string in Xml Format
     */
    public function toXml($options = array())
    {
        if (is_array($options) && isset($options[0]) && ($options[0] instanceof AkActiveRecord)) {
            $options = array('collection'=>$options);
        }
        if (isset($options['collection']) && is_array($options['collection']) && $options['collection'][0]->_modelName == $this->_modelName) {
            $root = strtolower(AkInflector::pluralize($this->_modelName));
            $root = $this->_convertColumnToXmlElement($root);
            $xml = '';
            if (!(isset($options['skip_instruct']) && $options['skip_instruct'] == true)) {
                $xml .= '<?xml version="1.0" encoding="UTF-8"?>';
            }
            $xml .= '<' . $root . '>';
            $collection = $options['collection'];
            unset($options['collection']);
            $options['skip_instruct'] = true;
            foreach ($collection as $element) {
                $xml .= $element->toXml($options);
            }
            $xml .= '</' . $root .'>';
            return $xml;
        }
        /**
         * see if we need to include associations
         */
        $associatedIds = array();
        if (isset($options['include']) && !empty($options['include'])) {
            $options['include'] = is_array($options['include'])?$options['include']:preg_split('/,\s*/',$options['include']);
            foreach ($this->_associations as $key => $obj) {
                if (in_array($key,$options['include'])) {
                    if ($obj->getType()!='hasAndBelongsToMany') {
                        $associatedIds[$obj->getAssociationId() . '_id'] = array('name'=>$key,'type'=>$obj->getType());
                    } else {
                        $associatedIds[$key] = array('name'=>$key,'type'=>$obj->getType());
                    }
                }
            }
        }
        if (isset($options['only'])) {
            $options['only'] = is_array($options['only'])?$options['only']:preg_split('/,\s*/',$options['only']);
        }
        if (isset($options['except'])) {
            $options['except'] = is_array($options['except'])?$options['except']:preg_split('/,\s*/',$options['except']);
        }
        $xml = '';
        if (!(isset($options['skip_instruct']) && $options['skip_instruct'] == true)) {
            $xml .= '<?xml version="1.0" encoding="UTF-8"?>';
        }
        $root = $this->_convertColumnToXmlElement(strtolower($this->_modelName));

        $xml .= '<' . $root . '>';
        $xml .= "\n";
        foreach ($this->_columns as $key => $def) {

            if (isset($options['except']) && in_array($key, $options['except'])) {
                continue;
            } else if (isset($options['only']) && !in_array($key, $options['only'])) {
                continue;
            } else {
                $columnType = $def['type'];
                $elementName = $this->_convertColumnToXmlElement($key);
                $xml .= '<' . $elementName;
                $val = $this->$key;
                if (!in_array($columnType,array('string','text','serial'))) {
                    $xml .= ' type="' . $columnType . '"';
                    if ($columnType=='boolean') $val = $val?1:0;
                }
                $xml .= '>' . Ak::utf8($val) . '</' . $elementName . '>';
                $xml .= "\n";
            }
        }
        if (isset($options['include'])) {
            foreach($this->_associationIds as $key=>$val) {
                if ((in_array($key,$options['include']) || in_array($val,$options['include']))) {
                    if (is_array($this->$key)) {

                        $associationElement = $key;
                        $associationElement = AkInflector::pluralize($associationElement);
                        $associationElement = $this->_convertColumnToXmlElement($associationElement);
                        $xml .= '<'.$associationElement.'>';
                        foreach ($this->$key as $el) {
                            if ($el instanceof AkActiveRecord) {
                                $xml .= $el->toXml(array('skip_instruct'=>true));
                            }
                        }
                        $xml .= '</' . $associationElement .'>';
                    } else {
                        $el = $this->$key->load();
                        if ($el instanceof AkActiveRecord) {
                            $xml.=$el->toXml(array('skip_instruct'=>true));
                        }
                    }
                }
            }
        }
        $xml .= '</' . $root . '>';
        return $xml;
    }
    /**
     * converts to yaml-strings
     *
     * examples:
     * User::toYaml($users->find('all'));
     * $Bermi->toYaml();
     *
     * @param array of ActiveRecords[optional] $data
     */
    public function toYaml($data = null)
    {
        return Ak::convert('active_record', 'yaml', empty($data) ? $this : $data);
    }


    /*/Utilities*/


    public function getAttributeCondition($argument)
    {
        if(is_array($argument)){
            return 'IN (?)';
        }elseif (is_null($argument)){
            return 'IS ?';
        }else{
            return '= ?';
        }
    }



    public function t($string, $array = null,$model=null)
    {
        return Ak::t($string, $array, empty($model) ? AkInflector::underscore($this->getModelName()): $model);
    }


    public function hasBeenModified()
    {
        return Ak::objectHasBeenModified($this);
    }

    /**
    * Just freeze the attributes hash, such that associations are still accessible even on destroyed records.
    *
    * @todo implement freeze correctly for its intended use
    */
    public function freeze()
    {
        return $this->_freeze = true;
    }

    public function isFrozen()
    {
        return !empty($this->_freeze);
    }

    /**
    * Alias for getModelName()
    */
    public function getType()
    {
        return $this->getModelName();
    }

    public function &objectCache()
    {
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


    /**
                        Connection adapters
    ====================================================================
    Right now Akelos uses phpAdodb for bd abstraction. These are functionalities not
    provided in phpAdodb and that will move to a separated driver for each db
    engine in a future
    */
    protected function _extractValueFromDefault($default)
    {
        if($this->_getDatabaseType() == 'postgre'){
            if(preg_match("/^'(.*)'::/", $default, $match)){
                return $match[1];
            }
            // a postgre HACK; we dont know the column-type here
            if ($default=='true') {
                return true;
            }
            if ($default=='false') {
                return false;
            }
        }
        return $default;
    }


    /**
     * LAZY LOCADING FUNCTIONALITY
     */
    protected function _enableLazyLoadingExtenssions($options = array())
    {
        empty($options['skip_calculations'])        && $this->_enableCalculations();
        empty($options['skip_table_inheritance'])   && $this->_enableTableInheritance();
        empty($options['skip_localization'])        && $this->_enableLocalization();
        empty($options['skip_errors'])              && $this->_enableErrors();
        empty($options['skip_validations'])         && $this->_enableValidations();
    }

    protected function _enableCalculations()
    {
        $this->extendClassLazily('AkActiveRecordCalculations',
        array(
        'methods' => array('count', 'average', 'minimum', 'maximum', 'sum', 'calculate'),
        'autoload_path' => AK_ACTIVE_RECORD_DIR.DS.'calculations.php'
        ));
    }

    protected function _enableTableInheritance()
    {
        $this->extendClassLazily('AkActiveRecordTableInheritance',
        array(
        'methods' => array('getInheritanceColumn','setInheritanceColumn','typeCondition'),
        'autoload_path' => AK_ACTIVE_RECORD_DIR.DS.'table_inheritance.php'
        ));
    }

    private function _setInternationalizedColumnsStatus($force_enable = false)
    {
        if($force_enable){
            $this->_internationalize = true;
        }else{
            $this->_internationalize = !empty($this->_internationalize) ? count($this->getAvailableLocales()) > 1 : true;
        }
    }

    protected function _enableLocalization()
    {
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

    protected function _enableErrors()
    {
        $this->extendClassLazily('AkActiveRecordErrors',
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
            'autoload_path' => AK_ACTIVE_RECORD_DIR.DS.'errors.php'
        ));
    }


    protected function _enableValidations()
    {
        $this->extendClassLazily('AkActiveRecordValidations',
        array(
        'methods' => array(
            'isBlank',
            'isValid',
            'validate',
            'validateOnCreate',
            'validateOnUpdate',
            'validatesAcceptanceOf',
            'validatesAssociated',
            'validatesConfirmationOf',
            'validatesExclusionOf',
            'validatesFormatOf',
            'validatesInclusionOf',
            'validatesLengthOf',
            'validatesNumericalityOf',
            'validatesPresenceOf',
            'validatesSizeOf',
            'validatesUniquenessOf',
            ),
            'autoload_path' => AK_ACTIVE_RECORD_DIR.DS.'validations.php'
        ));
    }

}



class AkActiveRecordExtenssion
{
    protected $_ActiveRecord;
    public function setExtendedBy(&$ActiveRecord)
    {
        $this->_ActiveRecord = $ActiveRecord;
    }
}
