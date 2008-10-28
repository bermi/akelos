<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2006, Akelos Media, S.L.  & Bermi Ferrer Martinez |
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
 * @package ActiveRecord
 * @subpackage Base
 * @component Active Record
 * @author Bermi Ferrer <bermi a.t akelos c.om> 2004 - 2007
 * @author Kaste 2007
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

require_once(AK_LIB_DIR.DS.'AkActiveRecord'.DS.'AkAssociatedActiveRecord.php');
require_once(AK_LIB_DIR.DS.'AkActiveRecord'.DS.'AkDbAdapter.php');
require_once(AK_LIB_DIR.DS.'AkActiveRecord'.DS.'AkDbSchemaCache.php');
/**#@+
* Constants
*/
// Akelos args is a short way to call functions that is only intended for fast prototyping
defined('AK_ENABLE_AKELOS_ARGS') ? null : define('AK_ENABLE_AKELOS_ARGS', false);
// Use setColumnName if available when using set('column_name', $value);
defined('AK_ACTIVE_RECORD_INTERNATIONALIZE_MODELS_BY_DEFAULT') ? null : define('AK_ACTIVE_RECORD_INTERNATIONALIZE_MODELS_BY_DEFAULT', true);
defined('AK_ACTIVE_RECORD_ENABLE_AUTOMATIC_SETTERS_AND_GETTERS') ? null : define('AK_ACTIVE_RECORD_ENABLE_AUTOMATIC_SETTERS_AND_GETTERS', false);
defined('AK_ACTIVE_RECORD_ENABLE_CALLBACK_SETTERS') ? null : define('AK_ACTIVE_RECORD_ENABLE_CALLBACK_SETTERS', AK_ACTIVE_RECORD_ENABLE_AUTOMATIC_SETTERS_AND_GETTERS);
defined('AK_ACTIVE_RECORD_ENABLE_CALLBACK_GETTERS') ? null : define('AK_ACTIVE_RECORD_ENABLE_CALLBACK_GETTERS', AK_ACTIVE_RECORD_ENABLE_AUTOMATIC_SETTERS_AND_GETTERS);

// Forces loading database schema on every call
if(AK_DEV_MODE) {
    AkDbSchemaCache::doRefresh(true);
} else if (AK_ENVIRONMENT == 'testing') {
    AkDbSchemaCache::doRefresh(true);
    define('AK_ACTIVE_RECORD_CACHE_DATABASE_SCHEMA',true);
}

defined('AK_ACTIVE_RECORD_ENABLE_PERSISTENCE') ? null : define('AK_ACTIVE_RECORD_ENABLE_PERSISTENCE', AK_ENVIRONMENT != 'testing');
defined('AK_ACTIVE_RECORD_CACHE_DATABASE_SCHEMA') ? null : define('AK_ACTIVE_RECORD_CACHE_DATABASE_SCHEMA', AK_ACTIVE_RECORD_ENABLE_PERSISTENCE && AK_ENVIRONMENT != 'development');
defined('AK_ACTIVE_RECORD_CACHE_DATABASE_SCHEMA_LIFE') ? null : define('AK_ACTIVE_RECORD_CACHE_DATABASE_SCHEMA_LIFE', 300);
defined('AK_ACTIVE_RECORD_VALIDATE_TABLE_NAMES') ? null : define('AK_ACTIVE_RECORD_VALIDATE_TABLE_NAMES', true);
defined('AK_ACTIVE_RECORD_SKIP_SETTING_ACTIVE_RECORD_DEFAULTS') ? null : define('AK_ACTIVE_RECORD_SKIP_SETTING_ACTIVE_RECORD_DEFAULTS', false);
defined('AK_NOT_EMPTY_REGULAR_EXPRESSION') ? null : define('AK_NOT_EMPTY_REGULAR_EXPRESSION','/.+/');
defined('AK_EMAIL_REGULAR_EXPRESSION') ? null : define('AK_EMAIL_REGULAR_EXPRESSION',"/^([a-z0-9_\-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([a-z0-9\-]+\.)+))([a-z]{2,4}|[0-9]{1,3})(\]?)$/i");
defined('AK_NUMBER_REGULAR_EXPRESSION') ? null : define('AK_NUMBER_REGULAR_EXPRESSION',"/^[0-9]+$/");
defined('AK_PHONE_REGULAR_EXPRESSION') ? null : define('AK_PHONE_REGULAR_EXPRESSION',"/^([\+]?[(]?[\+]?[ ]?[0-9]{2,3}[)]?[ ]?)?[0-9 ()\-]{4,25}$/");
defined('AK_DATE_REGULAR_EXPRESSION') ? null : define('AK_DATE_REGULAR_EXPRESSION',"/^(([0-9]{1,2}(\-|\/|\.| )[0-9]{1,2}(\-|\/|\.| )[0-9]{2,4})|([0-9]{2,4}(\-|\/|\.| )[0-9]{1,2}(\-|\/|\.| )[0-9]{1,2})){1}$/");
defined('AK_IP4_REGULAR_EXPRESSION') ? null : define('AK_IP4_REGULAR_EXPRESSION',"/^((25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])\.){3}(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])$/");
defined('AK_POST_CODE_REGULAR_EXPRESSION') ? null : define('AK_POST_CODE_REGULAR_EXPRESSION',"/^[0-9A-Za-z  -]{2,9}$/");
/**#@-*/



ak_compat('array_combine');

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
*     function authenticateUnsafely($user_name, $password)
*     {
*          return findFirst("user_name = '$user_name' AND password = '$password'");
*     }
*     
*     function authenticateSafely($user_name, $password)
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
* a comma separated list of columns or an array. 
* This makes it possible to store arrays, hashes, and other non-mappeable objects without doing any additional work. Example:
*
* <code> 
*   class User extends ActiveRecord
*   {
*      var $serialize = 'preferences';
*   }
*
*   $User = new User(array('preferences'=>array("background" => "black", "display" => 'large')));
*   $User->find($user_id);
*   $User->preferences // array("background" => "black", "display" => 'large')
* </code>
* 
* == Single table inheritance ==
* 
* Active Record allows inheritance by storing the name of the class in a column that by default is called "type" (can be changed 
* by overwriting <tt>AkActiveRecord->_inheritanceColumn</tt>). This means that an inheritance looking like this:
* 
* <code>
*   class Company extends ActiveRecord{}
*   class Firm extends Company{}
*   class Client extends Company{}
*   class PriorityClient extends Client{}
* </code>
*
* When you do $Firm->create('name =>', "akelos"), this record will be saved in the companies table with type = "Firm". You can then
* fetch this row again using $Company->find('first', "name = '37signals'") and it will return a Firm object.
* 
* If you don't have a type column defined in your table, single-table inheritance won't be triggered. In that case, it'll work just
* like normal subclasses with no special magic for differentiating between them or reloading the right type with find.
* 
* Note, all the attributes for all the cases are kept in the same table. Read more:
* http://www.martinfowler.com/eaaCatalog/singleTableInheritance.html
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
    /**#@+
    * @access private
    */
    //var $disableAutomatedAssociationLoading = true;
    var $_tableName;
    var $_db;
    var $_newRecord;
    var $_freeze;
    var $_dataDictionary;
    var $_primaryKey;
    var $_inheritanceColumn;

    var $_associations;

    var $_internationalize;

    var $_errors = array();

    var $_attributes = array();

    var $_protectedAttributes = array();
    var $_accessibleAttributes = array();

    var $_recordTimestamps = true;

    // Column description
    var $_columnNames = array();
    // Array of column objects for the table associated with this class.
    var $_columns = array();
    // Columns that can be edited/viewed
    var $_contentColumns = array();
    // Methods that will be dinamically loaded for the model (EXPERIMENTAL) This pretends to generate something similar to Ruby on Rails finders.
    // If you set array('findOneByUsernameAndPassword', 'findByCompany', 'findAllByExipringDate')
    // You'll get $User->findOneByUsernameAndPassword('admin', 'pass');
    var $_dynamicMethods = false;
    var $_combinedAttributes = array();

    var $_BlobQueryStack = null;

    var $_automated_max_length_validator = false;
    var $_automated_validators_enabled = false;
    var $_automated_not_null_validator = false;
    var $_set_default_attribute_values_automatically = true;

    // This is needed for enabling support for static active record instantation under php
    var $_activeRecordHasBeenInstantiated = true;

    var $__ActsLikeAttributes = array();

    /**
    * Holds a hash with all the default error messages, such that they can be replaced by your own copy or localizations.
    */
    var $_defaultErrorMessages = array(
    'inclusion' =>  "is not included in the list",
    'exclusion' => "is reserved",
    'invalid' => "is invalid",
    'confirmation' => "doesn't match confirmation",
    'accepted' => "must be accepted",
    'empty' => "can't be empty",
    'blank' => "can't be blank",
    'too_long' => "is too long (max is %d characters)",
    'too_short' => "is too short (min is %d characters)",
    'wrong_length' => "is the wrong length (should be %d characters)",
    'taken' => "has already been taken",
    'not_a_number' => "is not a number"
    );

    var $__activeRecordObject = true;

    /**#@-*/

    function __construct()
    {
        $attributes = (array)func_get_args();
        return $this->init($attributes);
    }

    function init($attributes = array())
    {
        AK_LOG_EVENTS ? ($this->Logger =& Ak::getLogger()) : null;
        $this->_internationalize = is_null($this->_internationalize) && AK_ACTIVE_RECORD_INTERNATIONALIZE_MODELS_BY_DEFAULT ? count($this->getAvailableLocales()) > 1 : $this->_internationalize;

        @$this->_instantiateDefaultObserver();

        $this->setConnection();

        if(!empty($this->table_name)){
            $this->setTableName($this->table_name);
        }
        $this->act_as = !empty($this->acts_as) ? $this->acts_as : (empty($this->act_as) ? false : $this->act_as);
        if (!empty($this->act_as)) {
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

        if($this->_dynamicMethods){
            $this->_buildFinders();
        }
        empty($avoid_loading_associations) ? $this->loadAssociations() : null;
    }

    function __destruct()
    {

    }

    /**
    * New objects can be instantiated as either empty (pass no construction parameter) or pre-set with attributes but not yet saved
    * (pass an array with key names matching the associated table column names). 
    * In both instances, valid attribute keys are determined by the column names of the associated table; hence you can't 
    * have attributes that aren't part of the table columns.
    */
    function newRecord($attributes)
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
    }


    /**
    * Returns a clone of the record that hasn't been assigned an id yet and is treated as a new record.
    */
    function cloneRecord()
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
    function isNewRecord()
    {
        if(!isset($this->_newRecord) && !isset($this->{$this->getPrimaryKey()})){
            $this->_newRecord = true;
        }
        return $this->_newRecord;
    }



    /**
    * Reloads the attributes of this object from the database.
    */   
    function reload()
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
    function &create($attributes = null)
    {
        if(!isset($this->_activeRecordHasBeenInstantiated)){
            return Ak::handleStaticCall();
        }

        if(func_num_args() > 1){
            $attributes = func_get_args();
        }
        $model = $this->getModelName();

        $object =& new $model();
        $object->setAttributes($attributes);
        $object->save();
        return $object;
    }

    function createOrUpdate($validate = true)
    {
        if($validate && !$this->isValid()){
            $this->transactionFail();
            return false;
        }
        return $this->isNewRecord() ? $this->_create() : $this->_update();
    }

    function &findOrCreateBy()
    {
        $args = func_get_args();
        $Item =& Ak::call_user_func_array(array(&$this,'findFirstBy'), $args);
        if(!$Item){
            $attributes = array();

            list($sql, $columns) = $this->_getFindBySqlAndColumns(array_shift($args), $args);

            if(!empty($columns)){
                foreach ($columns as $column){
                    $attributes[$column] = array_shift($args);
                }
            }
            $Item =& $this->create($attributes);
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
    *
    * @access private
    */
    function _create()
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

    function _setRecordTimestamps()
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
    function save($validate = true)
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
    function countBySql($sql)
    {
        if(!isset($this->_activeRecordHasBeenInstantiated)){
            return Ak::handleStaticCall();
        }
        if(!stristr($sql, 'COUNT') && stristr($sql, ' FROM ')){
            $sql = 'SELECT COUNT(*) '.substr($sql,strpos(str_replace(' from ',' FROM ', $sql),' FROM '));
        }
        if(!$this->isConnected()){
            $this->setConnection();
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
    function update($id, $attributes)
    {
        if(!isset($this->_activeRecordHasBeenInstantiated)){
            return Ak::handleStaticCall();
        }
        if(is_array($id)){
            $results = array();
            foreach ($id as $idx=>$single_id){
                $results[] = $this->update($single_id, isset($attributes[$idx]) ? $attributes[$idx] : $attributes);
            }
            return $results;
        }else{
            $object =& $this->find($id);
            $object->updateAttributes($attributes);
            return $object;
        }
    }

    /**
    * Updates a single attribute and saves the record. This is especially useful for boolean flags on existing records. 
    */
    function updateAttribute($name, $value, $should_validate=true)
    {
        $this->setAttribute($name, $value);
        return $this->save($should_validate);
    }


    /**
    * Updates all the attributes in from the passed array and saves the record. If the object is 
    * invalid, the saving will fail and false will be returned.
    */
    function updateAttributes($attributes, $object = null)
    {
        isset($object) ? $object->setAttributes($attributes) : $this->setAttributes($attributes);

        return isset($object) ? $object->save() : $this->save();
    }

    /**
    * Updates all records with the SET-part of an SQL update statement in updates and returns an 
    * integer with the number of rows updates. A subset of the records can be selected by specifying conditions. Example:
    * <code>$Billing->updateAll("category = 'authorized', approved = 1", "author = 'David'");</code>
    * 
    * Important note: Conditions are not sanitized yet so beware of accepting 
    * variable conditions when using this function
    */
    function updateAll($updates, $conditions = null)
    {
        if(!isset($this->_activeRecordHasBeenInstantiated)){
            return Ak::handleStaticCall();
        }
        /**
        * @todo sanitize sql conditions
        */
        $sql = 'UPDATE '.$this->getTableName().' SET '.$updates;
        $this->addConditions($sql, $conditions);
        return $this->_db->update($sql, $this->getModelName().' Update All');
    }


    /**
    * Updates the associated record with values matching those of the instance attributes.
    * Must be called as a result of a call to createOrUpdate.
    *
    * @access private
    */
    function _update()
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
            trigger_error(Ak::t('Attempted to update a stale object'), E_USER_NOTICE);
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
    function delete($id)
    {
        if(!isset($this->_activeRecordHasBeenInstantiated)){
            return Ak::handleStaticCall();
        }
        $id = func_num_args() > 1 ? func_get_args() : $id;
        return $this->deleteAll($this->getPrimaryKey().' IN ('.(is_array($id) ? join(', ',$id) : $id).')');
    }


    /**
    * Deletes all the records that matches the condition without instantiating the objects first 
    * (and hence not calling the destroy method). Example:
    * 
    * <code>$Post->destroyAll("person_id = 5 AND (category = 'Something' OR category = 'Else')");</code>
    * 
    * Important note: Conditions are not sanitized yet so beware of accepting 
    * variable conditions when using this function
    */
    function deleteAll($conditions = null)
    {
        if(!isset($this->_activeRecordHasBeenInstantiated)){
            return Ak::handleStaticCall();
        }
        /**
        * @todo sanitize sql conditions
        */
        $sql = 'DELETE FROM '.$this->getTableName();
        $this->addConditions($sql,$conditions);
        return $this->_db->delete($sql,$this->getModelName().' Delete All');
    }


    /**
    * Destroys the record with the given id by instantiating the object and calling destroy 
    * (all the callbacks are the triggered). If an array of ids is provided, all of them are destroyed.
    * Deletes the record in the database and freezes this instance to reflect that no changes should be 
    * made (since they can't be persisted).
    */
    function destroy($id = null)
    {
        if(!isset($this->_activeRecordHasBeenInstantiated)){
            return Ak::handleStaticCall();
        }

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

    function _destroy()
    {
        if(!$this->beforeDestroy() || !$this->notifyObservers('beforeDestroy')){
            return $this->transactionFail();
        }

        $sql = 'DELETE FROM '.$this->getTableName().' WHERE '.$this->getPrimaryKey().' = '.$this->_db->quote_string($this->getId());
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
    function destroyAll($conditions)
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
    function exists($id)
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
    function &find()
    {
        if(!isset($this->_activeRecordHasBeenInstantiated)){
            return Ak::handleStaticCall();
        }

        $args = func_get_args();

        $options = $this->_extractOptionsFromArgs($args);
        list($fetch,$options) = $this->_extractConditionsFromArgs($args,$options);

        $this->_sanitizeConditionsVariables($options);

        switch ($fetch) {
            case 'first':
                // HACK: php4 pass by ref
                $result =& $this->_findInitial($options);
                return $result;
                break;

            case 'all':
                // HACK: php4 pass by ref
                $result =& $this->_findEvery($options);
                return $result;
                break;

            default:
                // HACK: php4 pass by ref
                $result =& $this->_findFromIds($args, $options);
                return $result;
                break;
        }
        $result = false;
        return $result;
    }

    function &_findInitial($options)
    {
        // TODO: virtual_limit is a hack
        // actually we fetch_all and return only the first row
        $options = array_merge($options, array((!empty($options['include']) ?'virtual_limit':'limit')=>1));
        $result =& $this->_findEvery($options);

        if(!empty($result) && is_array($result)){
            $_result =& $result[0];
        }else{
            $_result = false;
            // if we return an empty array instead of false we need to change this->exists()!
            //$_result = array();
        }
        return  $_result;

    }

    function &_findEvery($options)
    {
        if((!empty($options['include']) && $this->hasAssociations())){
            $result =& $this->findWithAssociations($options);
        }else{
            $sql = $this->constructFinderSql($options);
            if(!empty($options['bind']) && is_array($options['bind']) && strstr($sql,'?')){
                $sql = array_merge(array($sql),$options['bind']);
            }
            
            $result =& $this->findBySql($sql);
        }

        if(!empty($result) && is_array($result)){
            $_result =& $result;
        }else{
            $_result = false;
        }
        return  $_result;

    }

    function &_findFromIds($ids, $options)
    {
        $expects_array = is_array($ids[0]);
        $ids = array_unique($expects_array ? (isset($ids[1]) ? array_merge($ids[0],$ids) : $ids[0]) : $ids);

        $num_ids = count($ids);

        //at this point $options['conditions'] can't be an array
        $conditions = !empty($options['conditions']) ? ' AND '.$options['conditions'] : '';

        switch ($num_ids){
            case 0 :
                trigger_error($this->t('Couldn\'t find %object_name without an ID%conditions',array('%object_name'=>$this->getModelName(),'%conditions'=>$conditions)), E_USER_ERROR);
                break;

            case 1 :
                $table_name = !empty($options['include']) && $this->hasAssociations() ? '__owner' : $this->getTableName();
                $options['conditions'] = $table_name.'.'.$this->getPrimaryKey().' = '.$ids[0].$conditions;
                $result =& $this->_findEvery($options);
                if (!$expects_array && $result !== false){
                    return $result[0];
                }
                return  $result;
                break;

            default:
                $without_conditions = empty($options['conditions']) ? true : false;
                $ids_condition = $this->getPrimaryKey().' IN ('.join(', ',$ids).')';
                $options['conditions'] = $ids_condition.$conditions;

                $result =& $this->_findEvery($options);
                if(is_array($result) && (count($result) != $num_ids && $without_conditions)){
                    $result = false;
                }
                return $result;
                break;
        }

    }

    function _extractOptionsFromArgs(&$args)
    {
        $last_arg = count($args)-1;
        return isset($args[$last_arg]) && is_array($args[$last_arg]) && $this->_isOptionsHash($args[$last_arg]) ? array_pop($args) : array();
    }

    function _isOptionsHash($options)
    {
        if (isset($options[0])){
            return false;
        }
        $valid_keys = array('conditions', 'include', 'joins', 'limit', 'offset', 'group', 'order', 'sort', 'bind', 'select','select_prefix', 'readonly');
        foreach (array_keys($options) as $key){
            if (!in_array($key,$valid_keys)){
                return false;
            }
        }
        return true;
    }

    function _extractConditionsFromArgs($args, $options)
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

    function _sanitizeConditionsVariables(&$options)
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
        
    }

    function &findFirst()
    {
        if(!isset($this->_activeRecordHasBeenInstantiated)){
            return Ak::handleStaticCall();
        }
        $args = func_get_args();
        $result =& Ak::call_user_func_array(array(&$this,'find'), array_merge(array('first'),$args));
        return $result;
    }

    function &findAll()
    {
        if(!isset($this->_activeRecordHasBeenInstantiated)){
            return Ak::handleStaticCall();
        }
        $args = func_get_args();
        $result =& Ak::call_user_func_array(array(&$this,'find'), array_merge(array('all'),$args));
        return $result;
    }


    /**
    * Works like find_all, but requires a complete SQL string. Examples:
    * $Post->findBySql("SELECT p.*, c.author FROM posts p, comments c WHERE p.id = c.post_id");
    * $Post->findBySql(array("SELECT * FROM posts WHERE author = ? AND created_on > ?", $author_id, $start_date));
    */
    function &findBySql($sql, $limit = null, $offset = null, $bindings = null)
    {
        if ($limit || $offset){
            Ak::deprecateWarning("You're calling AR::findBySql with \$limit or \$offset parameters. This has been deprecated.");
            $this->_db->addLimitAndOffset($sql, array('limit'=>$limit,'offset'=>$offset));
        }
        if(!isset($this->_activeRecordHasBeenInstantiated)){
            return Ak::handleStaticCall();
        }
        $objects = array();
        $records = $this->_db->select ($sql,'selecting');
        foreach ($records as $record){
            $objects[] =& $this->instantiate($this->getOnlyAvailableAttributes($record), false);
        }
        return $objects;
    }

    /**
    * This function pretends to emulate RoR finders until AkActiveRecord::addMethod becomes stable on future PHP versions.
    * @todo use PHP5 __call method for handling the magic finder methods like findFirstByUnsenameAndPassword('bermi','pass')
    */
    function &findFirstBy()
    {
        if(!isset($this->_activeRecordHasBeenInstantiated)){
            return Ak::handleStaticCall();
        }
        $args = func_get_args();
        array_unshift($args,'first');
        $result =& Ak::call_user_func_array(array(&$this,'findBy'), $args);
        return $result;
    }

    function &findLastBy()
    {
        if(!isset($this->_activeRecordHasBeenInstantiated)){
            return Ak::handleStaticCall();
        }
        $args = func_get_args();
        $options = $this->_extractOptionsFromArgs($args);
        $options['order'] = $this->getPrimaryKey().' DESC';
        array_push($args, $options);
        $result =& Ak::call_user_func_array(array(&$this,'findFirstBy'), $args);
        return $result;
    }

    function &findAllBy()
    {
        if(!isset($this->_activeRecordHasBeenInstantiated)){
            return Ak::handleStaticCall();
        }
        $args = func_get_args();
        array_unshift($args,'all');
        $result =& Ak::call_user_func_array(array(&$this,'findBy'), $args);
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
    function &findBy()
    {
        if(!isset($this->_activeRecordHasBeenInstantiated)){
            return Ak::handleStaticCall();
        }
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
            trigger_error(Ak::t('Argument list did not match expected set. Requested arguments are:').join(', ',$requested_args),E_USER_ERROR);
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

        $result =& Ak::call_user_func_array(array(&$this,'find'), array($fetch,$options));
        return $result;
    }


    function _getFindBySqlAndColumns($find_by_sql, &$query_values)
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
    function _getVariableSqlCondition($variable_condition)
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


    function constructFinderSql($options, $select_from_prefix = 'default')
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
    function addConditions(&$sql, $conditions = null, $table_alias = null)
    {
        $concat = empty($sql) ? '' : ' WHERE ';
        if (empty($conditions) && $this->_getDatabaseType() == 'sqlite') $conditions = '1';  // sqlite HACK
        if(!empty($conditions)){
            $sql  .= $concat.$conditions;
            $concat = ' AND ';
        }

        if($this->getInheritanceColumn() !== false && $this->descendsFromActiveRecord($this)){
            $type_condition = $this->typeCondition($table_alias);
            $sql .= !empty($type_condition) ? $concat.$type_condition : '';
        }
        return $sql;
    }

    /**
    * Gets a sanitized version of the input array. Each element will be escaped
    */
    function getSanitizedConditionsArray($conditions_array)
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
    function getConditions($conditions, $prefix = '', $model_name = null)
    {
        $model_name = isset($model_name) ? $model_name : $this->getModelName();
        $model_conditions = !empty($conditions[$model_name]) ? $conditions[$model_name] : $conditions;
        if(is_a($this->$model_name)){
            $model_instance =& $this->$model_name;
        }else{
            $model_instance =& $this;
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

    /**
    *
    * @access private
    */
    function _quoteColumnName($column_name)
    {
        return $this->_db->nameQuote.$column_name.$this->_db->nameQuote;
    }




    /**
    * EXPERIMENTAL: Will allow to create finders when PHP includes aggregate_methods as a stable feature on PHP4, for PHP5 we might use __call
    *
    * @access private
    */
    function _buildFinders($finderFunctions = array('find','findFirst'))
    {
        if(!$this->_dynamicMethods){
            return;
        }
        $columns = !is_array($this->_dynamicMethods) ? array_keys($this->getColumns()) : $this->_dynamicMethods;
        $class_name = 'ak_'.md5(serialize($columns));
        if(!class_exists($class_name)){
            $permutations = Ak::permute($columns);
            $implementations = '';
            foreach ($finderFunctions as $finderFunction){
                foreach ($permutations as $permutation){
                    $permutation = array_map(array('AkInflector','camelize'),$permutation);
                    foreach ($permutation as $k=>$v){
                        $method_name = $finderFunction.'By'.join($permutation,'And');
                        $implementation = 'function &'.$method_name.'(';
                        $first_param = '';
                        $params = '';
                        $i = 1;
                        foreach ($permutation as $column){
                            $column = AkInflector::underscore($column);
                            $params .= "$$column, ";
                            $first_param .= "$column ";
                            $i++;
                        }
                        $implementation .= trim($params,' ,')."){\n";
                        $implementation .= '$options = func_num_args() == '.$i.' ? func_get_arg('.($i-1).') : array();'."\n";
                        $implementation .= 'return $this->'.$finderFunction.'By(\''.$first_param.'\', '.trim($params,' ,').", \$options);\n }\n";
                        $implementations[$method_name] = $implementation;
                        array_shift($permutation);
                    }
                }
            }
            eval('class '.$class_name.' { '.join("\n",$implementations).' } ');
        }

        aggregate_methods(&$this, $class_name);
    }


    /**
    * Finder methods must instantiate through this method to work with the single-table inheritance model and
    * eager loading associations.
    * that makes it possible to create objects of different types from the same table.
    */
    function &instantiate($record, $set_as_new = true)
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
                array('%class_name'=>$inheritance_model_name, '%column'=>$this->getInheritanceColumn())),E_USER_ERROR);
            }
        }

        $model_name = isset($inheritance_model_name) ? $inheritance_model_name : $this->getModelName();
        $object =& new $model_name('attributes', $record);

        $object->_newRecord = $set_as_new;
        
        $object->afterInstantiate();
        $object->notifyObservers('afterInstantiate');
        
        (AK_CLI && AK_ENVIRONMENT == 'development') ? $object ->toString() : null;

        return $object;
    }

    /*/Finding records*/



    /**
                           Table inheritance
     ====================================================================
     */
    function descendsFromActiveRecord(&$object)
    {
        if(substr(strtolower(get_parent_class($object)),-12) == 'activerecord'){
            return true;
        }
        if(!method_exists($object, 'getInheritanceColumn')){
            return false;
        }
        $inheritance_column = $object->getInheritanceColumn();
        return !empty($inheritance_column);
    }

    /**
     * Gets the column name for use with single table inheritance. Can be overridden in subclasses.
    */
    function getInheritanceColumn()
    {
        return empty($this->_inheritanceColumn) ? ($this->hasColumn('type') ? 'type' : false ) : $this->_inheritanceColumn;
    }

    /**
     * Defines the column name for use with single table inheritance. Can be overridden in subclasses.
     */
    function setInheritanceColumn($column_name)
    {
        if(!$this->hasColumn($column_name)){
            trigger_error(Ak::t('Could not set "%column_name" as the inheritance column as this column is not available on the database.',array('%column_name'=>$column_name)), E_USER_NOTICE);
            return false;
        }elseif($this->getColumnType($column_name) != 'string'){
            trigger_error(Ak::t('Could not set %column_name as the inheritance column as this column type is "%column_type" instead of "string".',array('%column_name'=>$column_name,'%column_type'=>$this->getColumnType($column_name))), E_USER_NOTICE);
            return false;
        }else{
            $this->_inheritanceColumn = $column_name;
            return true;
        }
    }


    function getSubclasses()
    {
        $current_class = get_class($this);
        $subclasses = array();
        $classes = get_declared_classes();

        while ($class = array_shift($classes)) {
            $parent_class = get_parent_class($class);
            if($parent_class == $current_class || in_array($parent_class,$subclasses)){
                $subclasses[] = $class;
            }elseif(!empty($parent_class)){
                $classes[] = $parent_class;
            }
        }
        $subclasses = array_unique(array_map(array(&$this,'_getModelName'),$subclasses));
        return $subclasses;
    }


    function typeCondition($table_alias = null)
    {
        $inheritance_column = $this->getInheritanceColumn();
        $type_condition = array();
        $table_name = $this->getTableName();
        $available_types = array_merge(array($this->getModelName()),$this->getSubclasses());
        foreach ($available_types as $subclass){
            $type_condition[] = ' '.($table_alias != null ? $table_alias : $table_name).'.'.$inheritance_column.' = \''.AkInflector::humanize(AkInflector::underscore($subclass)).'\' ';
        }
        return empty($type_condition) ? '' : '('.join('OR',$type_condition).') ';
    }

    /*/Table inheritance*/



    /**
                         Setting Attributes
    ====================================================================
    See also: Getting Attributes, Model Attributes, Toggling Attributes, Counting Attributes.
    */
    function setAttribute($attribute, $value, $inspect_for_callback_child_method = AK_ACTIVE_RECORD_ENABLE_CALLBACK_SETTERS, $compose_after_set = true)
    {
        if($attribute[0] == '_'){
            return false;
        }

        if($this->isFrozen()){
            return false;
        }
        if($inspect_for_callback_child_method === true && method_exists($this,'set'.AkInflector::camelize($attribute))){
            static $watchdog;
            $watchdog[$attribute] = @$watchdog[$attribute]+1;
            if($watchdog[$attribute] == 5000){
                if((!defined('AK_ACTIVE_RECORD_PROTECT_SET_RECURSION')) || defined('AK_ACTIVE_RECORD_PROTECT_SET_RECURSION') && AK_ACTIVE_RECORD_PROTECT_SET_RECURSION){
                    trigger_error(Ak::t('You are calling recursively AkActiveRecord::setAttribute by placing parent::setAttribute() or  parent::set() on your model "%method" method. In order to avoid this, set the 3rd paramenter of parent::setAttribute to FALSE. If this was the behaviour you expected, please define the constant AK_ACTIVE_RECORD_PROTECT_SET_RECURSION and set it to false',array('%method'=>'set'.AkInflector::camelize($attribute))),E_USER_ERROR);
                    return false;
                }
            }
            $this->{$attribute.'_before_type_cast'} = $value;
            return $this->{'set'.AkInflector::camelize($attribute)}($value);
        }
        if($this->hasAttribute($attribute)){
            $this->{$attribute.'_before_type_cast'} = $value;
            $this->$attribute = $value;
            if($compose_after_set && !empty($this->_combinedAttributes) && !$this->requiredForCombination($attribute)){
                $combined_attributes = $this->_getCombinedAttributesWhereThisAttributeIsUsed($attribute);
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
            if(is_array($value)){
                $this->setAttributeLocales($attribute, $value);
            }elseif(is_string($inspect_for_callback_child_method)){
                $this->setAttributeByLocale($attribute, $value, $inspect_for_callback_child_method);
            }else{
                $this->_groupInternationalizedAttribute($attribute, $value);
            }
        }
        return true;
    }

    function set($attribute, $value = null, $inspect_for_callback_child_method = true, $compose_after_set = true)
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
    function setAttributes($attributes, $override_attribute_protection = false)
    {
        $this->parseAkelosArgs($attributes);
        if(!$override_attribute_protection){
            $attributes = $this->removeAttributesProtectedFromMassAssignment($attributes);
        }
        if(!empty($attributes) && is_array($attributes)){
            foreach ($attributes as $k=>$v){
                $this->setAttribute($k, $v);
            }
        }
    }


    function setId($value)
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

    function getAttribute($attribute, $inspect_for_callback_child_method = AK_ACTIVE_RECORD_ENABLE_CALLBACK_GETTERS)
    {
        if($attribute[0] == '_'){
            return false;
        }

        if($inspect_for_callback_child_method === true && method_exists($this,'get'.AkInflector::camelize($attribute))){
            static $watchdog;
            $watchdog[@$attribute] = @$watchdog[$attribute]+1;
            if($watchdog[$attribute] == 5000){
                if((!defined('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION')) || defined('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION') && AK_ACTIVE_RECORD_PROTECT_GET_RECURSION){
                    trigger_error(Ak::t('You are calling recursivelly AkActiveRecord::getAttribute by placing parent::getAttribute() or  parent::get() on your model "%method" method. In order to avoid this, set the 2nd paramenter of parent::getAttribute to FALSE. If this was the behaviour you expected, please define the constant AK_ACTIVE_RECORD_PROTECT_GET_RECURSION and set it to false',array('%method'=>'get'.AkInflector::camelize($attribute))),E_USER_ERROR);
                    return false;
                }
            }
            $value = $this->{'get'.AkInflector::camelize($attribute)}();
            return $this->getInheritanceColumn() === $attribute ? AkInflector::humanize(AkInflector::underscore($value)) : $value;
        }
        if(isset($this->$attribute) || (!isset($this->$attribute) && $this->isCombinedAttribute($attribute))){
            if($this->hasAttribute($attribute)){
                if (!empty($this->_combinedAttributes) && $this->isCombinedAttribute($attribute)){
                    $this->composeCombinedAttribute($attribute);
                }
                return isset($this->$attribute) ? $this->$attribute : null;
            }elseif($this->_internationalize && $this->_isInternationalizeCandidate($attribute)){
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

    function get($attribute = null, $inspect_for_callback_child_method = true)
    {
        return !isset($attribute) ? $this->getAttributes($inspect_for_callback_child_method) : $this->getAttribute($attribute, $inspect_for_callback_child_method);
    }

    /**
    * Returns an array of all the attributes with their names as keys and clones of their objects as values in case they are objects.
    */
    function getAttributes()
    {
        $attributes = array();
        $available_attributes = $this->getAvailableAttributes();
        foreach ($available_attributes as $available_attribute){
            $attribute = $this->getAttribute($available_attribute['name']);
            $attributes[$available_attribute['name']] = AK_PHP5 && is_object($attribute) ? clone($attribute) : $attribute;
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
    function getId()
    {
        return $this->{$this->getPrimaryKey()};
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
    function toggleAttribute($attribute)
    {
        $value = $this->getAttribute($attribute);
        $new_value = $value ? false : true;
        $this->setAttribute($attribute, $new_value);
        return $new_value;
    }


    /**
    * Toggles the attribute and saves the record.
    */
    function toggleAttributeAndSave($attribute)
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
    function incrementCounter($counter_name, $id, $difference = 1)
    {
        return $this->updateAll("$counter_name = $counter_name + $difference", $this->getPrimaryKey().' = '.$this->castAttributeForDatabase($this->getPrimaryKey(), $id)) === 1;
    }

    /**
    * Works like AkActiveRecord::incrementCounter, but decrements instead.
    */
    function decrementCounter($counter_name, $id, $difference = 1)
    {
        return $this->updateAll("$counter_name = $counter_name - $difference", $this->getPrimaryKey().' = '.$this->castAttributeForDatabase($this->getPrimaryKey(), $id)) === 1;
    }

    /**
    * Initializes the attribute to zero if null and subtracts one. Only makes sense for number-based attributes. Returns attribute value.
    */
    function decrementAttribute($attribute)
    {
        if(!isset($this->$attribute)){
            $this->$attribute = 0;
        }
        return $this->$attribute -= 1;
    }

    /**
    * Decrements the attribute and saves the record.
    */
    function decrementAndSaveAttribute($attribute)
    {
        return $this->updateAttribute($attribute,$this->decrementAttribute($attribute));
    }


    /**
    * Initializes the attribute to zero if null and adds one. Only makes sense for number-based attributes. Returns attribute value.
    */
    function incrementAttribute($attribute)
    {
        if(!isset($this->$attribute)){
            $this->$attribute = 0;
        }
        return $this->$attribute += 1;
    }

    /**
    * Increments the attribute and saves the record.
    */
    function incrementAndSaveAttribute($attribute)
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
    function setAccessibleAttributes()
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
     *      function Customer()
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
    function setProtectedAttributes()
    {
        $args = func_get_args();
        $this->_protectedAttributes = array_unique(array_merge((array)$this->_protectedAttributes, $args));
    }

    function removeAttributesProtectedFromMassAssignment($attributes)
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
    /**
    * Returns an array of all the attributes that have been specified for serialization as keys and the objects as values.
    */
    function getSerializedAttributes()
    {
        return isset($this->_serializedAttributes) ? $this->_serializedAttributes : array();
    }

    function getAvailableAttributes()
    {
        return array_merge($this->getColumns(), $this->getAvailableCombinedAttributes());
    }

    function getAttributeCaption($attribute)
    {
        return $this->t(AkInflector::humanize($attribute));
    }

    /**
     * This function is useful in case you need to know if attributes have been assigned to an object.
     */
    function hasAttributesDefined()
    {
        $attributes = join('',$this->getAttributes());
        return empty($attributes);
    }


    /**
    * Returns the primary key field.
    */
    function getPrimaryKey()
    {
        if(!isset($this->_primaryKey)){
            $this->setPrimaryKey();
        }
        return $this->_primaryKey;
    }

    function getColumnNames()
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
    function getContentColumns()
    {
        $inheritance_column = $this->getInheritanceColumn();
        $columns = $this->getColumns();
        foreach ($columns as $name=>$details){
            if((substr($name,-3) == '_id' || substr($name,-6) == '_count') ||
            !empty($details['primaryKey']) || ($inheritance_column !== false && $inheritance_column == $name)){
                unset($columns[$name]);
            }
        }
        return $columns;
    }

    /**
    * Returns an array of names for the attributes available on this object sorted alphabetically.
    */
    function getAttributeNames()
    {
        if(!isset($this->_activeRecordHasBeenInstantiated)){
            return Ak::handleStaticCall();
        }
        $attributes = array_keys($this->getAvailableAttributes());
        $names = array_combine($attributes,array_map(array(&$this,'getAttributeCaption'), $attributes));
        natsort($names);
        return $names;
    }


    /**
    * Returns true if the specified attribute has been set by the user or by a database load and is neither null nor empty?
    */
    function isAttributePresent($attribute)
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
    function hasAttribute ($attribute)
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
    *        function User()
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
    *        function compose_email_link()
    *        {
    *            $args = func_get_arg(0); 
    *            return "<a href=\'mailto:{$args[\'email\']}\'>{$args[\'name\']}</a>"; 
    *        } 
    *        function parse_email_link($email_link) 
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
    *       var $combined_attributes array(
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
    function isCombinedAttribute ($attribute)
    {
        return !empty($this->_combinedAttributes) && isset($this->_combinedAttributes[$attribute]);
    }

    function addCombinedAttributeConfiguration($attribute)
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
            array('%field_name'=>$args[0],'%columns'=>join(', ',$invalid_columns))), E_USER_ERROR);
        }else{
            $attribute = array_shift($args);
            $this->_combinedAttributes[$attribute] = $args;
            $this->composeCombinedAttribute($attribute);
        }
    }

    function composeCombinedAttributes()
    {

        if(!empty($this->_combinedAttributes)){
            $attributes = array_keys($this->_combinedAttributes);
            foreach ($attributes as $attribute){
                $this->composeCombinedAttribute($attribute);
            }
        }
    }

    function composeCombinedAttribute($combined_attribute)
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

    /**
    * @access private
    */
    function _getCombinedAttributesWhereThisAttributeIsUsed($attribute)
    {
        $result = array();
        foreach ($this->_combinedAttributes as $combined_attribute=>$settings){
            if(in_array($attribute,$settings)){
                $result[] = $combined_attribute;
            }
        }
        return $result;
    }


    function requiredForCombination($attribute)
    {
        foreach ($this->_combinedAttributes as $settings){
            if(in_array($attribute,$settings)){
                return true;
            }
        }
        return false;
    }

    function hasCombinedAttributes()
    {
        return count($this->getCombinedSubattributes()) === 0 ? false :true;
    }

    function getCombinedSubattributes($attribute)
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

    function decomposeCombinedAttributes()
    {
        if(!empty($this->_combinedAttributes)){
            $attributes = array_keys($this->_combinedAttributes);
            foreach ($attributes as $attribute){
                $this->decomposeCombinedAttribute($attribute);
            }
        }
    }

    function decomposeCombinedAttribute($combined_attribute, $used_on_combined_fields = false)
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

    function getAvailableCombinedAttributes()
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
    function &establishConnection($specification_or_profile = AK_DEFAULT_DATABASE_PROFILE)
    {
        $adapter =& AkDbAdapter::getInstance($specification_or_profile);
        return $this->setConnection(&$adapter);
    }


    /**
    * Returns true if a connection that's accessible to this class have already been opened.
    */ 
    function isConnected()
    {
        return isset($this->_db);
    }

    /**
    * Returns the connection currently associated with the class. This can also be used to 
    * "borrow" the connection to do database work unrelated to any of the specific Active Records.
    */
    function &getConnection()
    {
        return $this->_db;
    }

    /**
    * Sets the connection for the class.
    */
    function &setConnection($db_adapter = null)
    {
        if (is_null($db_adapter)){
            $db_adapter =& AkDbAdapter::getInstance();
        }
        return $this->_db =& $db_adapter;
    }

    /**
    * @access private
    */
    function _getDatabaseType()
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
    function setPrimaryKey($primary_key = 'id')
    {
        if(!$this->hasColumn($primary_key)){
            trigger_error($this->t('Opps! We could not find primary key column %primary_key on the table %table, for the model %model',array('%primary_key'=>$primary_key,'%table'=>$this->getTableName(), '%model'=>$this->getModelName())),E_USER_ERROR);
        }else {
            $this->_primaryKey = $primary_key;
        }
    }


    function getTableName($modify_for_associations = true)
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

    function setTableName($table_name = null, $check_for_existence = AK_ACTIVE_RECORD_VALIDATE_TABLE_NAMES, $check_mode = false)
    {
        !AK_TEST_MODE && $static_cached_tables = Ak::getStaticVar('available_tables');
        
        if(empty($table_name)){
            $table_name = AkInflector::tableize($this->getModelName());
        }
        if($check_for_existence){
            if(!isset($available_tables) || $check_mode){
                if(!isset($this->_db)){
                    $this->setConnection();
                }
                if (!AK_ACTIVE_RECORD_CACHE_DATABASE_SCHEMA || 
                    ($available_tables = AkDbSchemaCache::getAvailableTables()) === false) {
                    if(!empty($static_cached_tables)){
                        $available_tables = $static_cached_tables;
                    }else{
                        $available_tables = $this->_db->availableTables();
                    }
                    if (AK_ACTIVE_RECORD_CACHE_DATABASE_SCHEMA) {
                        AkDbSchemaCache::setAvailableTables($available_tables);
                    }
                    !AK_TEST_MODE && Ak::setStaticVar('available_tables', $available_tables);
                }
            }
            if(!in_array($table_name,(array)$available_tables)){
                if(!$check_mode){
                    trigger_error(Ak::t('Unable to set "%table_name" table for the model "%model".'.
                    '  There is no "%table_name" available into current database layout.'.
                    ' Set AK_ACTIVE_RECORD_VALIDATE_TABLE_NAMES constant to false in order to'.
                    ' avoid table name validation',array('%table_name'=>$table_name,'%model'=>$this->getModelName())),E_USER_WARNING);
                }
                return false;
            }
        }
        $this->_tableName = $table_name;
        return true;
    }


    function getOnlyAvailableAttributes($attributes)
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
                    $ret_attributes[$k] =& $attributes[$keys[$i]];
                }
            }
        }
        return $ret_attributes;
    }

    function getColumnsForAttributes($attributes)
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
    function hasColumn($column)
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
    function attributesFromColumnDefinition()
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
     *
     * @access private
     */
    function _databaseTableInternals($table)
    {
        if (!AK_ACTIVE_RECORD_CACHE_DATABASE_SCHEMA || ($cache = AkDbSchemaCache::getDbTableInternals($table))===false) {
            $cache = $this->_db->getColumnDetails($table);
            if (AK_ACTIVE_RECORD_CACHE_DATABASE_SCHEMA) {
                AkDbSchemaCache::setDbTableInternals($table,$cache);
            }
        }
        return $cache;
    }

    function getColumnsWithRegexBoundaries()
    {
        $columns = array_keys($this->getColumns());
        foreach ($columns as $k=>$column){
            $columns[$k] = '/([^\.])\b('.$column.')\b/';
        }
        return $columns;
    }


    /**
    * If is the first time we use a model this function will run the installer for the model if it exists
    *
    * @access private
    */
    function _runCurrentModelInstallerIfExists(&$column_objects)
    {
        static $installed_models = array();
        if(!defined('AK_AVOID_AUTOMATIC_ACTIVE_RECORD_INSTALLERS') && !in_array($this->getModelName(), $installed_models)){
            $installed_models[] = $this->getModelName();
            require_once(AK_LIB_DIR.DS.'AkInstaller.php');
            $installer_name = $this->getModelName().'Installer';
            $installer_file = AK_APP_DIR.DS.'installers'.DS.AkInflector::underscore($installer_name).'.php';
            if(file_exists($installer_file)){
                require_once($installer_file);
                if(class_exists($installer_name)){
                    $Installer = new $installer_name();
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
    function getColumns($force_reload = false)
    {
        if(empty($this->_columns) || $force_reload){
            $this->_columns = $this->getColumnSettings($force_reload);
        }

        return (array)$this->_columns;
    }

    function getColumnSettings($force_reload = false)
    {
        if(empty($this->_columnsSettings) || $force_reload){
            $this->loadColumnsSettings($force_reload);
            $this->initiateColumnsToNull();
        }
        return isset($this->_columnsSettings) ? $this->_columnsSettings : array();
    }

    function loadColumnsSettings($force_reload = false)
    {
        if(is_null($this->_db)){
            $this->setConnection();
        }
        $this->_columnsSettings = $force_reload ? null : $this->_getPersistedTableColumnSettings();

        if(empty($this->_columnsSettings) || !AK_ACTIVE_RECORD_ENABLE_PERSISTENCE){
            if(empty($this->_dataDictionary)){
                $this->_dataDictionary =& $this->_db->getDictionary();
            }

            $column_objects = $this->_databaseTableInternals($this->getTableName());

            if( !isset($this->_avoidTableNameValidation) &&
            !is_array($column_objects) &&
            !$this->_runCurrentModelInstallerIfExists($column_objects)){
                trigger_error(Ak::t('Ooops! Could not fetch details for the table %table_name.', array('%table_name'=>$this->getTableName())), E_USER_ERROR);
                return false;
            }elseif (empty($column_objects)){
                $this->_runCurrentModelInstallerIfExists($column_objects);
            }
            if(is_array($column_objects)){
                foreach (array_keys($column_objects) as $k){
                    $this->setColumnSettings($column_objects[$k]->name, $column_objects[$k]);
                }
            }
            if(!empty($this->_columnsSettings) && AK_ACTIVE_RECORD_CACHE_DATABASE_SCHEMA){
                $this->_persistTableColumnSettings();
            }
        }
        return isset($this->_columnsSettings) ? $this->_columnsSettings : array();
    }



    function setColumnSettings($column_name, $column_object)
    {
        $this->_columnsSettings[$column_name] = array();
        $this->_columnsSettings[$column_name]['name'] = $column_object->name;

        if($this->_internationalize && $this->_isInternationalizeCandidate($column_object->name)){
            $this->_addInternationalizedColumn($column_object->name);
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
    function resetColumnInformation()
    {
        $this->_clearPersitedColumnSettings();
        $this->_columnNames = $this->_columns = $this->_columnsSettings = $this->_contentColumns = array();
    }

    /**
    * @access private
    */
    function _getColumnsSettings()
    {
        return AkDbSchemaCache::getColumnsSettings();
    }

    /**
    * @access private
    */
    function _getModelColumnSettings()
    {
        return AkDbSchemaCache::getModelColumnSettings($this->getModelName());
        
    }

    /**
    * @access private
    */
    function _persistTableColumnSettings()
    {
        AkDbSchemaCache::setModelColumnSettings($this->getModelName(), $this->_columnsSettings);
    }

    /**
    * @access private
    */
    function _getPersistedTableColumnSettings()
    {
        return AkDbSchemaCache::getModelColumnSettings($this->getModelName());
        
    }

    /**
    * @access private
    */
    function _clearPersitedColumnSettings()
    {
        AkDbSchemaCache::clear($this->getModelName());
    }



    function initiateAttributeToNull($attribute)
    {
        if(!isset($this->$attribute)){
            $this->$attribute = null;
        }
    }

    function initiateColumnsToNull()
    {
        if(isset($this->_columnsSettings) && is_array($this->_columnsSettings)){
            array_map(array(&$this,'initiateAttributeToNull'),array_keys($this->_columnsSettings));
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
    function getAkelosDataType(&$adodb_column_object)
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
            if(stristr($adodb_column_object->type, 'CHAR') | (isset($adodb_column_object->max_length) && $adodb_column_object->max_length > 0 &&$adodb_column_object->max_length < 256 )){
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
    function getClassForDatabaseTableMapping()
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

    function getDisplayField()
    {
        return  empty($this->displayField) && $this->hasAttribute('name') ? 'name' : (isset($this->displayField) && $this->hasAttribute($this->displayField) ? $this->displayField : $this->getPrimaryKey());
    }

    function setDisplayField($attribute_name)
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
                               Localization
    ====================================================================
    */

    function t($string, $array = null)
    {
        return Ak::t($string, $array, AkInflector::underscore($this->getModelName()));
    }

    function getInternationalizedColumns()
    {
        static $cache;
        $model = $this->getModelName();
        $available_locales = $this->getAvailableLocales();
        if(empty($cache[$model])){
            $cache[$model] = array();
            foreach ($this->getColumnSettings() as $column_name=>$details){
                if(!empty($details['i18n'])){
                    $_tmp_pos = strpos($column_name,'_');
                    $column = substr($column_name,$_tmp_pos+1);
                    $lang = substr($column_name,0,$_tmp_pos);
                    if(in_array($lang, $available_locales)){
                        $cache[$model][$column] = empty($cache[$model][$column]) ? array($lang) :
                        array_merge($cache[$model][$column] ,array($lang));
                    }
                }
            }
        }

        return $cache[$model];
    }

    function getAvailableLocales()
    {
        static $available_locales;
        if(empty($available_locales)){
            if(defined('AK_ACTIVE_RECORD_DEFAULT_LOCALES')){
                $available_locales = Ak::stringToArray(AK_ACTIVE_RECORD_DEFAULT_LOCALES);
            }else{
                $available_locales =  Ak::langs();
            }
        }
        return $available_locales;
    }

    function getCurrentLocale()
    {
        static $current_locale;
        if(empty($current_locale)){
            $current_locale = Ak::lang();
            $available_locales = $this->getAvailableLocales();
            if(!in_array($current_locale, $available_locales)){
                $current_locale = array_shift($available_locales);
            }
        }
        return $current_locale;
    }


    function getAttributeByLocale($attribute, $locale)
    {
        $internationalizable_columns = $this->getInternationalizedColumns();
        if(!empty($internationalizable_columns[$attribute]) && is_array($internationalizable_columns[$attribute]) && in_array($locale, $internationalizable_columns[$attribute])){
            return $this->getAttribute($locale.'_'.$attribute);
        }
    }

    function getAttributeLocales($attribute)
    {
        $attribute_locales = array();
        foreach ($this->getAvailableLocales() as $locale){
            if($this->hasColumn($locale.'_'.$attribute)){
                $attribute_locales[$locale] = $this->getAttributeByLocale($attribute, $locale);
            }
        }
        return $attribute_locales;
    }

    function setAttributeByLocale($attribute, $value, $locale)
    {
        $internationalizable_columns = $this->getInternationalizedColumns();

        if($this->_isInternationalizeCandidate($locale.'_'.$attribute) && !empty($internationalizable_columns[$attribute]) && is_array($internationalizable_columns[$attribute]) && in_array($locale, $internationalizable_columns[$attribute])){
            $this->setAttribute($locale.'_'.$attribute, $value);
        }

    }

    function setAttributeLocales($attribute, $values = array())
    {
        foreach ($values as $locale=>$value){
            $this->setAttributeByLocale($attribute, $value, $locale);
        }
    }

    /**
    * @access private
    */
    function _delocalizeAttribute($attribute)
    {
        return $this->_isInternationalizeCandidate($attribute) ? substr($attribute,3) : $attribute;
    }

    /**
    * @access private
    */
    function _isInternationalizeCandidate($column_name)
    {
        $pos = strpos($column_name,'_');
        return $pos === 2 && in_array(substr($column_name,0,$pos),$this->getAvailableLocales());
    }

    /**
    * @access private
    */
    function _addInternationalizedColumn($column_name)
    {
        $this->_columnsSettings[$column_name]['i18n'] = true;
    }


    /**
     * Adds an internationalized attribute to an array containing other locales for the same column name
     * 
     * Example:
     *  es_title and en_title will be available user title = array('es'=>'...', 'en' => '...')
     *
     * @access private
     */
    function _groupInternationalizedAttribute($attribute, $value)
    {
        if($this->_internationalize && $this->_isInternationalizeCandidate($attribute)){
            if(!empty($this->$attribute)){
                $_tmp_pos = strpos($attribute,'_');
                $column = substr($attribute,$_tmp_pos+1);
                $lang = substr($attribute,0,$_tmp_pos);
                $this->$column = empty($this->$column) ? array() : $this->$column;
                if(empty($this->$column) || (!empty($this->$column) && is_array($this->$column))){
                    $this->$column = empty($this->$column) ? array($lang=>$value) : array_merge($this->$column,array($lang=>$value));
                }
            }
        }
    }

    /*/Localization*/




    /**
                             Type Casting
    ====================================================================
    See also: Database Reflection.
    */

    function getAttributesBeforeTypeCast()
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


    function getAttributeBeforeTypeCast($attribute)
    {
        if(isset($this->{$attribute.'_before_type_cast'})){
            return $this->{$attribute.'_before_type_cast'};
        }
        return null;
    }

    function quotedId()
    {
        return $this->castAttributeForDatabase($this->getPrimaryKey(), $this->getId());
    }

    /**
    * Specifies that the attribute by the name of attr_name should be serialized before saving to the database and unserialized after loading from the database. If class_name is specified, the serialized object must be of that class on retrieval, as a new instance of the object will be loaded with serialized values.
    */
    function setSerializeAttribute($attr_name, $class_name = null)
    {
        if($this->hasColumn($attr_name)){
            $this->_serializedAttributes[$attr_name] = $class_name;
        }
    }

    function getAvailableAttributesQuoted()
    {
        return $this->getAttributesQuoted($this->getAttributes());
    }


    function getAttributesQuoted($attributes_array)
    {
        $set = array();
        $attributes_array = $this->getSanitizedConditionsArray($attributes_array);
        foreach (array_diff($attributes_array,array('')) as $k=>$v){
            $set[$k] = $k.'='.$v;
        }

        return $set;
    }

    function getColumnType($column_name)
    {
        empty($this->_columns) ? $this->getColumns() : null;
        return empty($this->_columns[$column_name]['type']) ? false : $this->_columns[$column_name]['type'];
    }

    function getColumnScale($column_name)
    {
        empty($this->_columns) ? $this->getColumns() : null;
        return empty($this->_columns[$column_name]['scale']) ? false : $this->_columns[$column_name]['scale'];
    }

    function castAttributeForDatabase($column_name, $value, $add_quotes = true)
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
                $result = is_null($value) ? 'null' : ($add_quotes ? $this->_db->quote_string($value) : $value);
                break;
        }

        //  !! nullable vs. not nullable !!
        return empty($this->_columns[$column_name]['notNull']) ? ($result === '' ? "''" : $result) : ($result === 'null' ? '' : $result);
    }

    function castAttributeFromDatabase($column_name, $value)
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
                }
            }
        }
        return $value;
    }


    /**
    * Joins date arguments into a single attribute. Like the array generated by the date_helper, so
    * array('published_on(1i)' => 2002, 'published_on(2i)' => 'January', 'published_on(3i)' => 24)
    * Will be converted to array('published_on'=>'2002-01-24')
    *
    * @access private
    */
    function _castDateParametersFromDateHelper_(&$params)
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

    /**
    * @access private
    */
    function _addBlobQueryStack($column_name, $blob_value)
    {
        $this->_BlobQueryStack[$column_name] = $blob_value;
    }

    /**
    * @access private
    */
    function _updateBlobFields($condition)
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
    function isLockingEnabled()
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
    *       function beforeValidationOnCreate
    *       {
    *           if(!empty($this->number)){
    *               $this->number = ereg_replace('[^0-9]*','',$this->number);
    *           }
    *       }
    *   }
    *
    *   class Subscription extends ActiveRecord
    *   {
    *       // Note: This is not implemented yet
    *       var $beforeCreate  = 'recordSignup';
    *
    *       function recordSignup()
    *       {
    *         $this->signed_up_on = date("Y-m-d");
    *       }
    *   }
    *
    *   class Firm extends ActiveRecord
    *   {
    *       //Destroys the associated clients and people when the firm is destroyed
    *       // Note: This is not implemented yet
    *       var $beforeDestroy = array('destroyAssociatedPeople', 'destroyAssociatedClients');
    *
    *       function destroyAssociatedPeople()
    *       {
    *           $Person = new Person();
    *           $Person->destroyAll("firm_id=>", $this->id);
    *       }
    *
    *       function destroyAssociatedClients()
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
    *
    * @access public
    */

    function beforeCreate(){return true;}
    function beforeValidation(){return true;}
    function beforeValidationOnCreate(){return true;}
    function beforeValidationOnUpdate(){return true;}
    function beforeSave(){return true;}
    function beforeUpdate(){return true;}
    function afterUpdate(){return true;}
    function afterValidation(){return true;}
    function afterValidationOnCreate(){return true;}
    function afterValidationOnUpdate(){return true;}
    function afterInstantiate(){return true;}
    function afterCreate(){return true;}
    function afterDestroy(){return true;}
    function beforeDestroy(){return true;}
    function afterSave(){return true;}

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

    function transactionStart()
    {
        return $this->_db->startTransaction();
    }

    function transactionComplete()
    {
        return $this->_db->stopTransaction();
    }

    function transactionFail()
    {
        $this->_db->failTransaction();
        return false;
    }

    function transactionHasFailed()
    {
        return $this->_db->hasTransactionFailed();
    }

    /*/Transactions*/




    /**
                                    Validators
    ====================================================================
    See also: Error Handling.
    *
    * Active Records implement validation by overwriting AkActiveRecord::validate (or the variations, validateOnCreate and
    * validateOnUpdate). Each of these methods can inspect the state of the object, which usually means ensuring
    * that a number of attributes have a certain value (such as not empty, within a given range, matching a certain regular expression).
    * 
    * Example:
    * 
    *   class Person extends ActiveRecord
    *   {
    *       function validate()
    *       {
    *           $this->addErrorOnEmpty(array('first_name', 'last_name'));
    *           if(!preg_match('/[0-9]{4,12}/', $this->phone_number)){
    *               $this->addError("phone_number", "has invalid format");
    *           }
    *       }
    * 
    *       function validateOnCreate() // is only run the first time a new object is saved
    *       {
    *           if(!isValidDiscount($this->membership_discount)){
    *               $this->addError("membership_discount", "has expired");
    *           }
    *       }
    * 
    *       function validateOnUpdate()
    *       {
    *           if($this->countChangedAttributes() == 0){
    *               $this->addErrorToBase("No changes have occurred");
    *           }
    *       }
    *   }
    * 
    *   $Person = new Person(array("first_name" => "David", "phone_number" => "what?"));
    *   $Person->save();                    // => false (and doesn't do the save);
    *   $Person->hasErrors();         // => false
    *   $Person->countErrors();          // => 2
    *   $Person->getErrorsOn("last_name");       // => "can't be empty"
    *   $Person->getErrorsOn("phone_number");    // => "has invalid format"
    *   $Person->yieldEachFullError();        // => "Last name can't be empty \n Phone number has invalid format"
    * 
    *   $Person->setAttributes(array("last_name" => "Heinemeier", "phone_number" => "555-555"));
    *   $Person->save(); // => true (and person is now saved in the database)
    * 
    * An "_errors" array is available for every Active Record.
    * 
    */

    /**
      * Encapsulates the pattern of wanting to validate a password or email address field with a confirmation. Example:
      * 
      *  Model:
      *     class Person extends ActiveRecord
      *     {
      *         function validate()
      *         {
      *             $this->validatesConfirmationOf('password');
      *             $this->validatesConfirmationOf('email_address', "should match confirmation");
      *         }
      *    }
      * 
      *  View:
      *    <?=$form_helper->password_field("person", "password"); ?>
      *    <?=$form_helper->password_field("person", "password_confirmation"); ?>
      * 
      * The person has to already have a password attribute (a column in the people table), but the password_confirmation is virtual.
      * It exists only as an in-memory variable for validating the password. This check is performed only if password_confirmation
      * is not null.
      * 
      */
    function validatesConfirmationOf($attribute_names, $message = 'confirmation')
    {
        $message = isset($this->_defaultErrorMessages[$message]) ? $this->t($this->_defaultErrorMessages[$message]) : $message;
        $attribute_names = Ak::toArray($attribute_names);
        foreach ($attribute_names as $attribute_name){
            $attribute_accessor = $attribute_name.'_confirmation';
            if(isset($this->$attribute_accessor) && @$this->$attribute_accessor != @$this->$attribute_name){
                $this->addError($attribute_name, $message);
            }
        }
    }

    /**
      * Encapsulates the pattern of wanting to validate the acceptance of a terms of service check box (or similar agreement). Example:
      * 
      * class Person extends ActiveRecord
      * {
      *     function validateOnCreate()
      *     {
      *         $this->validatesAcceptanceOf('terms_of_service');
      *         $this->validatesAcceptanceOf('eula', "must be abided");
      *     }
      * }
      * 
      * The terms_of_service attribute is entirely virtual. No database column is needed. This check is performed only if
      * terms_of_service is not null.
      * 
      * 
      * @param accept 1 
      * Specifies value that is considered accepted.  The default value is a string "1", which makes it easy to relate to an HTML checkbox.
      */
    function validatesAcceptanceOf($attribute_names, $message = 'accepted', $accept = 1)
    {
        $message = isset($this->_defaultErrorMessages[$message]) ? $this->t($this->_defaultErrorMessages[$message]) : $message;

        $attribute_names = Ak::toArray($attribute_names);
        foreach ($attribute_names as $attribute_name){
            if(@$this->$attribute_name != $accept){
                $this->addError($attribute_name, $message);
            }
        }
    }

    /**
    * Validates whether the associated object or objects are all valid themselves. Works with any kind of association.
    *
    *   class Book extends ActiveRecord
    *   {
    *       var $has_many = 'pages';
    *       var $belongs_to = 'library';
    * 
    *       function validate(){
    *           $this->validatesAssociated(array('pages', 'library'));
    *       }
    *   }
    * 
    *
    * Warning: If, after the above definition, you then wrote:
    *
    *   class Page extends ActiveRecord
    *   {
    *       var $belongs_to = 'book';
    *       function validate(){
    *           $this->validatesAssociated('book');
    *       }
    *   }
    *
    * ...this would specify a circular dependency and cause infinite recursion.
    *
    * NOTE: This validation will not fail if the association hasn't been assigned. If you want to ensure that the association
    * is both present and guaranteed to be valid, you also need to use validatesPresenceOf.
    */
    function validatesAssociated($attribute_names, $message = 'invalid')
    {
        $message = isset($this->_defaultErrorMessages[$message]) ? $this->t($this->_defaultErrorMessages[$message]) : $message;
        $attribute_names = Ak::toArray($attribute_names);
        foreach ($attribute_names as $attribute_name){
            if(!empty($this->$attribute_name)){
                if(is_array($this->$attribute_name)){
                    foreach(array_keys($this->$attribute_name) as $k){
                        if(method_exists($this->{$attribute_name}[$k],'isValid') && !$this->{$attribute_name}[$k]->isValid()){
                            $this->addError($attribute_name, $message);
                        }
                    }
                }elseif (method_exists($this->$attribute_name,'isValid') && !$this->$attribute_name->isValid()){
                    $this->addError($attribute_name, $message);
                }
            }
        }
    }

    function isBlank($value = null)
    {
        return trim((string)$value) == '';
    }

    /**
      * Validates that the specified attributes are not blank (as defined by AkActiveRecord::isBlank()).
      */
    function validatesPresenceOf($attribute_names, $message = 'blank')
    {
        $message = isset($this->_defaultErrorMessages[$message]) ? $this->t($this->_defaultErrorMessages[$message]) : $message;

        $attribute_names = Ak::toArray($attribute_names);
        foreach ($attribute_names as $attribute_name){
            $this->addErrorOnBlank($attribute_name, $message);
        }
    }

    /**
      * Validates that the specified attribute matches the length restrictions supplied. Only one option can be used at a time:
      * 
      * class Person extends ActiveRecord
      * {
      *     function validate()
      *     {
      *         $this->validatesLengthOf('first_name', array('maximum'=>30));
      *         $this->validatesLengthOf('last_name', array('maximum'=>30,'message'=> "less than %d if you don't mind"));
      *         $this->validatesLengthOf('last_name', array('within'=>array(7, 32)));
      *         $this->validatesLengthOf('last_name', array('in'=>array(6, 20), 'too_long' => "pick a shorter name", 'too_short' => "pick a longer name"));
      *         $this->validatesLengthOf('fav_bra_size', array('minimum'=>1, 'too_short'=>"please enter at least %d character"));
      *         $this->validatesLengthOf('smurf_leader', array('is'=>4, 'message'=>"papa is spelled with %d characters... don't play me."));
      *     }
      * }
      *  
      * NOTE: Be aware that $this->validatesLengthOf('field', array('is'=>5)); Will match a string containing 5 characters (Ie. "Spain"), an integer 5, and an array with 5 elements. You must supply additional checking to check for appropriate types.
      *
      * Configuration options:
      * <tt>minimum</tt> - The minimum size of the attribute
      * <tt>maximum</tt> - The maximum size of the attribute
      * <tt>is</tt> - The exact size of the attribute
      * <tt>within</tt> - A range specifying the minimum and maximum size of the attribute
      * <tt>in</tt> - A synonym(or alias) for :within
      * <tt>allow_null</tt> - Attribute may be null; skip validation.
      * 
      * <tt>too_long</tt> - The error message if the attribute goes over the maximum (default "is" "is too long (max is %d characters)")
      * <tt>too_short</tt> - The error message if the attribute goes under the minimum (default "is" "is too short (min is %d characters)")
      * <tt>wrong_length</tt> - The error message if using the "is" method and the attribute is the wrong size (default "is" "is the wrong length (should be %d characters)")
      * <tt>message</tt> - The error message to use for a "minimum", "maximum", or "is" violation.  An alias of the appropriate too_long/too_short/wrong_length message
      */
    function validatesLengthOf($attribute_names, $options = array())
    {
        // Merge given options with defaults.
        $default_options = array(
        'too_long'     => $this->_defaultErrorMessages['too_long'],
        'too_short'     => $this->_defaultErrorMessages['too_short'],
        'wrong_length'     => $this->_defaultErrorMessages['wrong_length'],
        'allow_null' => false
        );

        $range_options = array();
        foreach ($options as $k=>$v){
            if(in_array($k,array('minimum','maximum','is','in','within'))){
                $range_options[$k] = $v;
                $option = $k;
                $option_value = $v;
            }
        }

        // Ensure that one and only one range option is specified.
        switch (count($range_options)) {
            case 0:
                trigger_error(Ak::t('Range unspecified.  Specify the "within", "maximum", "minimum, or "is" option.'), E_USER_ERROR);
                return false;
                break;
            case 1:
                $options = array_merge($default_options, $options);
                break;
            default:
                trigger_error(Ak::t('Too many range options specified.  Choose only one.'), E_USER_ERROR);
                return false;
                break;
        }


        switch ($option) {
            case 'within':
            case 'in':
                if(empty($option_value) || !is_array($option_value) || count($option_value) != 2 || !is_numeric($option_value[0]) || !is_numeric($option_value[1])){
                    trigger_error(Ak::t('%option must be a Range (array(min, max))',array('%option',$option)), E_USER_ERROR);
                    return false;
                }
                $attribute_names = Ak::toArray($attribute_names);
                
                foreach ($attribute_names as $attribute_name){
                    if((!empty($option['allow_null']) && !isset($this->$attribute_name)) || (Ak::size($this->$attribute_name)) < $option_value[0]){
                        $this->addError($attribute_name, sprintf($options['too_short'], $option_value[0]));
                    }elseif((!empty($option['allow_null']) && !isset($this->$attribute_name)) || (Ak::size($this->$attribute_name)) > $option_value[1]){
                        $this->addError($attribute_name, sprintf($options['too_long'], $option_value[1]));
                    }
                }
                break;

            case 'is':
            case 'minimum':
            case 'maximum':

                if(empty($option_value) || !is_numeric($option_value) || $option_value <= 0){
                    trigger_error(Ak::t('%option must be a nonnegative Integer',array('%option',$option_value)), E_USER_ERROR);
                    return false;
                }

                // Declare different validations per option.
                $validity_checks = array('is' => "==", 'minimum' => ">=", 'maximum' => "<=");
                $message_options = array('is' => 'wrong_length', 'minimum' => 'too_short', 'maximum' => 'too_long');

                $message = sprintf(!empty($options['message']) ? $options['message'] : $options[$message_options[$option]],$option_value);

                $attribute_names = Ak::toArray($attribute_names);
                foreach ($attribute_names as $attribute_name){
                    if((!$options['allow_null'] && !isset($this->$attribute_name)) ||
                    eval("return !(".Ak::size(@$this->$attribute_name)." {$validity_checks[$option]} $option_value);")){
                        $this->addError($attribute_name, $message);
                    }
                }
                break;
            default:
                break;
        }

        return true;
    }

    function validatesSizeOf($attribute_names, $options = array())
    {
        return validatesLengthOf($attribute_names, $options);
    }

    /**
    * Validates whether the value of the specified attributes are unique across the system. Useful for making sure that only one user
    * can be named "davidhh".
    *
    *  class Person extends ActiveRecord
    *   {
    *       function validate()
    *       {
    *           $this->validatesUniquenessOf('passport_number');
    *           $this->validatesUniquenessOf('user_name', array('scope' => "account_id"));
    *       }
    *   }
    *
    * It can also validate whether the value of the specified attributes are unique based on multiple scope parameters.  For example,
    * making sure that a teacher can only be on the schedule once per semester for a particular class. 
    *
    *   class TeacherSchedule extends ActiveRecord
    *   {
    *       function validate()
    *       {
    *           $this->validatesUniquenessOf('passport_number');
    *           $this->validatesUniquenessOf('teacher_id', array('scope' => array("semester_id", "class_id"));
    *       }
    *   }
    * 
    * 
    * When the record is created, a check is performed to make sure that no record exist in the database with the given value for the specified
    * attribute (that maps to a column). When the record is updated, the same check is made but disregarding the record itself.
    *
    * Configuration options:
    * <tt>message</tt> - Specifies a custom error message (default is: "has already been taken")
    * <tt>scope</tt> - Ensures that the uniqueness is restricted to a condition of "scope = record.scope"
    * <tt>case_sensitive</tt> - Looks for an exact match.  Ignored by non-text columns (true by default).
    * <tt>if</tt> - Specifies a method to call or a string to evaluate to determine if the validation should
    * occur (e.g. 'if' => 'allowValidation', or 'if' => '$this->signup_step > 2').  The
    * method, or string should return or evaluate to a true or false value.
    */
    function validatesUniquenessOf($attribute_names, $options = array())
    {
        $default_options = array('case_sensitive'=>true, 'message'=>'taken');
        $options = array_merge($default_options, $options);

        if(!empty($options['if'])){
            if(method_exists($this,$options['if'])){
                if($this->{$options['if']}() === false){
                    return true;
                }
            }else {
                eval('$__eval_result = ('.rtrim($options['if'],';').');');
                if(empty($__eval_result)){
                    return true;
                }
            }
        }

        $message = isset($this->_defaultErrorMessages[$options['message']]) ? $this->t($this->_defaultErrorMessages[$options['message']]) : $options['message'];
        unset($options['message']);

        foreach ((array)$attribute_names as $attribute_name){
            $value = isset($this->$attribute_name) ? $this->$attribute_name : null;

            if($value === null || ($options['case_sensitive'] || !$this->hasColumn($attribute_name))){
                $condition_sql = $this->getTableName().'.'.$attribute_name.' '.$this->getAttributeCondition($value);
                $condition_params = array($value);
            }else{
                $condition_sql = 'LOWER('.$this->getTableName().'.'.$attribute_name.') '.$this->getAttributeCondition($value);
                $condition_params = array(is_array($value) ? array_map('utf8_strtolower',$value) : utf8_strtolower($value));
            }

            if(!empty($options['scope'])){
                foreach ((array)$options['scope'] as $scope_item){
                    $scope_value = $this->get($scope_item);
                    $condition_sql .= ' AND '.$this->getTableName().'.'.$scope_item.' '.$this->getAttributeCondition($scope_value);
                    $condition_params[] = $scope_value;
                }
            }

            if(!$this->isNewRecord()){
                $condition_sql .= ' AND '.$this->getTableName().'.'.$this->getPrimaryKey().' <> ?';
                $condition_params[] = $this->getId();
            }
            array_unshift($condition_params,$condition_sql);
            if ($this->find('first', array('conditions' => $condition_params))){
                $this->addError($attribute_name, $message);
            }
        }
    }



    /**
    * Validates whether the value of the specified attribute is of the correct form by matching it against the regular expression
    * provided.
    *
    * <code>
    *   class Person extends ActiveRecord
    *   {
    *       function validate()
    *       {
    *           $this->validatesFormatOf('email', "/^([^@\s]+)@((?:[-a-z0-9]+\.)+[a-z]{2,})$/");
    *       }
    *   }
    * </code>
    *
    * A regular expression must be provided or else an exception will be raised.
    *
    * There are some regular expressions bundled with the Akelos Framework. 
    * You can override them by defining them as PHP constants (Ie. define('AK_EMAIL_REGULAR_EXPRESSION', '/^My custom email regex$/');). This must be done on your main configuration file.
    * This are predefined perl-like regular extensions.
    *
    * * AK_NOT_EMPTY_REGULAR_EXPRESSION ---> /.+/
    * * AK_EMAIL_REGULAR_EXPRESSION ---> /^([a-z0-9_\-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([a-z0-9\-]+\.)+))([a-z]{2,4}|[0-9]{1,3})(\]?)$/i
    * * AK_NUMBER_REGULAR_EXPRESSION ---> /^[0-9]+$/
    * * AK_PHONE_REGULAR_EXPRESSION ---> /^([\+]?[(]?[\+]?[ ]?[0-9]{2,3}[)]?[ ]?)?[0-9 ()\-]{4,25}$/
    * * AK_DATE_REGULAR_EXPRESSION ---> /^(([0-9]{1,2}(\-|\/|\.| )[0-9]{1,2}(\-|\/|\.| )[0-9]{2,4})|([0-9]{2,4}(\-|\/|\.| )[0-9]{1,2}(\-|\/|\.| )[0-9]{1,2})){1}$/
    * * AK_IP4_REGULAR_EXPRESSION ---> /^((25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])\.){3}(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])$/
    * * AK_POST_CODE_REGULAR_EXPRESSION ---> /^[0-9A-Za-z  -]{2,7}$/
    *
    * IMPORTANT: Predefined regular expressions may change in newer versions of the Framework, so is highly recommended to hardcode you own on regex on your validators.
    * 
    * Params:
    * <tt>$message</tt> - A custom error message (default is: "is invalid")
    * <tt>$regular_expression</tt> - The regular expression used to validate the format with (note: must be supplied!)
    */
    function validatesFormatOf($attribute_names, $regular_expression, $message = 'invalid', $regex_function = 'preg_match')
    {
        $message = isset($this->_defaultErrorMessages[$message]) ? $this->t($this->_defaultErrorMessages[$message]) : $message;

        $attribute_names = Ak::toArray($attribute_names);
        foreach ($attribute_names as $attribute_name){
            if(!isset($this->$attribute_name) || !$regex_function($regular_expression, $this->$attribute_name)){
                $this->addError($attribute_name, $message);
            }
        }
    }

    /**
    * Validates whether the value of the specified attribute is available in a particular array of elements.
    *
    * class Person extends ActiveRecord
    * {
    *   function validate()
    *   {
    *       $this->validatesInclusionOf('gender', array('male', 'female'), "woah! what are you then!??!!");
    *       $this->validatesInclusionOf('age', range(0, 99));
    *   }
    *
    * Parameters:
    * <tt>$array_of_ possibilities</tt> - An array of available items
    * <tt>$message</tt> - Specifies a customer error message (default is: "is not included in the list")
    * <tt>$allow_null</tt> - If set to true, skips this validation if the attribute is null (default is: false)
    */
    function validatesInclusionOf($attribute_names, $array_of_possibilities, $message = 'inclusion', $allow_null = false)
    {
        $message = isset($this->_defaultErrorMessages[$message]) ? $this->t($this->_defaultErrorMessages[$message]) : $message;

        $attribute_names = Ak::toArray($attribute_names);
        foreach ($attribute_names as $attribute_name){
            if($allow_null ? (@$this->$attribute_name != '' ? (!in_array($this->$attribute_name,$array_of_possibilities)) : @$this->$attribute_name === 0 ) : (isset($this->$attribute_name) ? !in_array(@$this->$attribute_name,$array_of_possibilities) : true )){
                $this->addError($attribute_name, $message);
            }
        }
    }

    /**
    * Validates that the value of the specified attribute is not in a particular array of elements.
    *
    *   class Person extends ActiveRecord
    *   {
    *       function validate()
    *       {
    *           $this->validatesExclusionOf('username', array('admin', 'superuser'), "You don't belong here");
    *           $this->validatesExclusionOf('age', range(30,60), "This site is only for under 30 and over 60");
    *       }
    *   }
    * 
    * Parameters:
    * <tt>$array_of_possibilities</tt> - An array of items that the value shouldn't be part of
    * <tt>$message</tt> - Specifies a customer error message (default is: "is reserved")
    * <tt>$allow_null</tt> - If set to true, skips this validation if the attribute is null (default is: false)
    */
    function validatesExclusionOf($attribute_names, $array_of_possibilities, $message = 'exclusion', $allow_null = false)
    {
        $message = isset($this->_defaultErrorMessages[$message]) ? $this->t($this->_defaultErrorMessages[$message]) : $message;

        $attribute_names = Ak::toArray($attribute_names);
        foreach ($attribute_names as $attribute_name){

            if($allow_null ? (!empty($this->$attribute_name) ? (in_array(@$this->$attribute_name,$array_of_possibilities)) : false ) : (isset($this->$attribute_name) ? in_array(@$this->$attribute_name,$array_of_possibilities) : true )){
                $this->addError($attribute_name, $message);
            }
        }
    }




    /**
    * Validates whether the value of the specified attribute is numeric.
    * 
    *   class Person extends ActiveRecord
    *   {
    *       function validate()
    *       {
    *           $this->validatesNumericalityOf('value');
    *       }
    *   }
    *
    * Parameters:
    * <tt>$message</tt> - A custom error message (default is: "is not a number")
    * <tt>$only_integer</tt> Specifies whether the value has to be an integer, e.g. an integral value (default is false)
    * <tt>$allow_null</tt> Skip validation if attribute is null (default is false).
    */
    function validatesNumericalityOf($attribute_names, $message = 'not_a_number', $only_integer = false, $allow_null = false)
    {
        $message = isset($this->_defaultErrorMessages[$message]) ? $this->t($this->_defaultErrorMessages[$message]) : $message;

        $attribute_names = Ak::toArray($attribute_names);
        foreach ($attribute_names as $attribute_name){
            if (isset($this->$attribute_name)){
                $value = $this->$attribute_name;
                if ($only_integer){
                    $is_int = is_numeric($value) && (int)$value == $value;
                    $has_error = !$is_int;
                }else{
                    $has_error = !is_numeric($value);
                }
            }else{
                $has_error = $allow_null ? false : true;
            }

            if ($has_error){
                $this->addError($attribute_name, $message);
            }
        }
    }



    /**
    * Returns true if no errors were added otherwise false.
    */
    function isValid()
    {
        $this->clearErrors();
        if($this->beforeValidation() && $this->notifyObservers('beforeValidation')){


            if($this->_set_default_attribute_values_automatically){
                $this->_setDefaultAttributeValuesAutomatically();
            }

            $this->validate();

            if($this->_automated_validators_enabled){
                $this->_runAutomatedValidators();
            }

            $this->afterValidation();
            $this->notifyObservers('afterValidation');

            if ($this->isNewRecord()){
                if($this->beforeValidationOnCreate()){
                    $this->notifyObservers('beforeValidationOnCreate');
                    $this->validateOnCreate();
                    $this->afterValidationOnCreate();
                    $this->notifyObservers('afterValidationOnCreate');
                }
            }else{
                if($this->beforeValidationOnUpdate()){
                    $this->notifyObservers('beforeValidationOnUpdate');
                    $this->validateOnUpdate();
                    $this->afterValidationOnUpdate();
                    $this->notifyObservers('afterValidationOnUpdate');
                }
            }
        }

        return !$this->hasErrors();
    }

    /**
    * By default the Active Record will validate for the maximum length for database columns. You can
    * disable the automated validators by setting $this->_automated_validators_enabled to false.
    * Specific validators are (for now):
    * $this->_automated_max_length_validator = false; // false by default, but you can set it to true on your model
    * $this->_automated_not_null_validator = false; // disabled by default
    *
    * @access private
    */
    function _runAutomatedValidators()
    {
        foreach ($this->_columns as $column_name=>$column_settings){
            if($this->_automated_max_length_validator &&
            empty($column_settings['primaryKey']) &&
            !empty($this->$column_name) &&
            !empty($column_settings['maxLength']) && $column_settings['maxLength'] > 0 &&
            strlen($this->$column_name) > $column_settings['maxLength']){
                $this->addError($column_name, sprintf($this->_defaultErrorMessages['too_long'], $column_settings['maxLength']));
            }elseif($this->_automated_not_null_validator && empty($column_settings['primaryKey']) && !empty($column_settings['notNull']) && (!isset($this->$column_name) || is_null($this->$column_name))){
                $this->addError($column_name,'empty');
            }
        }
    }

    /**
    * $this->_set_default_attribute_values_automatically = true; // This enables automated attribute setting from database definition
    *
    * @access private
    */
    function _setDefaultAttributeValuesAutomatically()
    {
        foreach ($this->_columns as $column_name=>$column_settings){
            if(empty($column_settings['primaryKey']) && isset($column_settings['hasDefault']) && $column_settings['hasDefault'] && (!isset($this->$column_name) || is_null($this->$column_name))){
                if(empty($column_settings['defaultValue'])){
                    if($column_settings['type'] == 'integer' && empty($column_settings['notNull'])){
                        $this->$column_name = 0;
                    }elseif(($column_settings['type'] == 'string' || $column_settings['type'] == 'text') && empty($column_settings['notNull'])){
                        $this->$column_name = '';
                    }
                }else {
                    $this->$column_name = $column_settings['defaultValue'];
                }
            }
        }
    }

    /**
    * Overwrite this method for validation checks on all saves and use addError($field, $message); for invalid attributes.
    */
    function validate()
    {
    }

    /**
    * Overwrite this method for validation checks used only on creation.
    */
    function validateOnCreate()
    {
    }

    /**
    * Overwrite this method for validation checks used only on updates.
    */
    function validateOnUpdate()
    {
    }

    /*/Validators*/


    /**
                                  Observers
    ====================================================================
    See also: Callbacks.
    */

    /**
    * $state store the state of this observable object
    *
    * @access private
    */
    var $_observable_state;

    /**
    * @access private
    */
    function _instantiateDefaultObserver()
    {
        $default_observer_name = ucfirst($this->getModelName().'Observer');
        if(class_exists($default_observer_name)){
            //$Observer =& new $default_observer_name($this);
            Ak::singleton($default_observer_name,  $this);
        }
    }

    /**
    * Calls the $method using the reference to each
    * registered observer.
    * @return true (this is used internally for triggering observers on default callbacks)
    */ 
    function notifyObservers ($method = null)
    {
        $observers =& $this->getObservers();
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
                    $observers[$i]->update($this->getObservableState(), &$this);
                }
            }else{
                $observers[$i]->update($this->getObservableState(), &$this);
            }
        }
        $this->setObservableState('');

        return true;
    }


    function setObservableState($state_message)
    {
        $this->_observable_state = $state_message;
    }

    function getObservableState()
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
    function addObserver(&$observer)
    {
        $staticVarNs='AkActiveRecord::observers::' . $this->_modelName;
        $observer_class_name = get_class($observer);
        /**
         * get the statically stored observers for the namespace
         */
        $observers = &Ak::getStaticVar($staticVarNs);
        if (!is_array($observers)) {
            $observers = array('classes'=>array(),'objects'=>array());
        }
        /**
         * if not already registered, the observerclass will 
         * be registered now
         */
        if (!in_array($observer_class_name,$observers['classes'])) {
            $observers['classes'][] = $observer_class_name;
            $observers['objects'][] = &$observer;
            Ak::setStaticVar($staticVarNs, $observers);
            
        }
    }
    /**
    * Register the reference to an object object
    * @return void
    */ 
    function &getObservers()
    {
        $staticVarNs='AkActiveRecord::observers::' . $this->_modelName;
        $key = 'objects';

        $array = array();
        $observers_arr =& Ak::getStaticVar($staticVarNs);
        if (isset($observers_arr[$key])) {
            $observers = &$observers_arr[$key];
        } else {
            $observers = &$array;
        }

        return $observers;
    }

    /*/Observers*/




    /**
                                    Error Handling
    ====================================================================
    See also: Validators.
    */


    /**
    * Returns the Errors array that holds all information about attribute error messages.
    */
    function getErrors()
    {
        return $this->_errors;
    }

    /**
    * Adds an error to the base object instead of any particular attribute. This is used
    * to report errors that doesn't tie to any specific attribute, but rather to the object
    * as a whole. These error messages doesn't get prepended with any field name when iterating
    * with yieldEachFullError, so they should be complete sentences.
    */
    function addErrorToBase($message)
    {
        $this->addError($this->getModelName(), $message);
    }

    /**
    * Returns errors assigned to base object through addToBase according to the normal rules of getErrorsOn($attribute).
    */
    function getBaseErrors()
    {
        $errors = $this->getErrors();
        return (array)@$errors[$this->getModelName()];
    }


    /**
    * Adds an error message ($message) to the ($attribute), which will be returned on a call to <tt>getErrorsOn($attribute)</tt>
    * for the same attribute and ensure that this error object returns false when asked if <tt>hasErrors</tt>. More than one
    * error can be added to the same $attribute in which case an array will be returned on a call to <tt>getErrorsOn($attribute)</tt>.
    * If no $message is supplied, "invalid" is assumed.
    */
    function addError($attribute, $message = 'invalid')
    {
        $message = isset($this->_defaultErrorMessages[$message]) ? $this->t($this->_defaultErrorMessages[$message]) : $message;
        $this->_errors[$attribute][] = $message;
    }

    /**
    * Will add an error message to each of the attributes in $attributes that is empty.
    */
    function addErrorOnEmpty($attribute_names, $message = 'empty')
    {
        $message = isset($this->_defaultErrorMessages[$message]) ? $this->t($this->_defaultErrorMessages[$message]) : $message;
        $attribute_names = Ak::toArray($attribute_names);
        foreach ($attribute_names as $attribute){
            if(empty($this->$attribute)){
                $this->addError($attribute, $message);
            }
        }
    }

    /**
    * Will add an error message to each of the attributes in $attributes that is blank (using $this->isBlank).
    */
    function addErrorOnBlank($attribute_names, $message = 'blank')
    {
        $message = isset($this->_defaultErrorMessages[$message]) ? $this->t($this->_defaultErrorMessages[$message]) : $message;
        $attribute_names = Ak::toArray($attribute_names);
        foreach ($attribute_names as $attribute){
            if($this->isBlank(@$this->$attribute)){
                $this->addError($attribute, $message);
            }
        }
    }

    /**
    * Will add an error message to each of the attributes in $attributes that has a length outside of the passed boundary $range.
    * If the length is above the boundary, the too_long_message message will be used. If below, the too_short_message.
    */
    function addErrorOnBoundaryBreaking($attribute_names, $range_begin, $range_end, $too_long_message = 'too_long', $too_short_message = 'too_short')
    {
        $too_long_message = isset($this->_defaultErrorMessages[$too_long_message]) ? $this->_defaultErrorMessages[$too_long_message] : $too_long_message;
        $too_short_message = isset($this->_defaultErrorMessages[$too_short_message]) ? $this->_defaultErrorMessages[$too_short_message] : $too_short_message;

        $attribute_names = Ak::toArray($attribute_names);
        foreach ($attribute_names as $attribute){
            if(@$this->$attribute < $range_begin){
                $this->addError($attribute, $too_short_message);
            }
            if(@$this->$attribute > $range_end){
                $this->addError($attribute, $too_long_message);
            }
        }

    }

    function addErrorOnBoundryBreaking ($attributes, $range_begin, $range_end, $too_long_message = 'too_long', $too_short_message = 'too_short')
    {
        $this->addErrorOnBoundaryBreaking($attributes, $range_begin, $range_end, $too_long_message, $too_short_message);
    }

    /**
    * Returns true if the specified $attribute has errors associated with it.
    */
    function isInvalid($attribute)
    {
        return $this->getErrorsOn($attribute);
    }

    /**
    * Returns false, if no errors are associated with the specified $attribute.
    * Returns the error message, if one error is associated with the specified $attribute.
    * Returns an array of error messages, if more than one error is associated with the specified $attribute.
    */
    function getErrorsOn($attribute)
    {
        if (empty($this->_errors[$attribute])){
            return false;
        }elseif (count($this->_errors[$attribute]) == 1){
            $k = array_keys($this->_errors[$attribute]);
            return $this->_errors[$attribute][$k[0]];
        }else{
            return $this->_errors[$attribute];
        }
    }


    /**
    * Yields each attribute and associated message per error added.
    */
    function yieldEachError()
    {
        foreach ($this->_errors as $errors){
            foreach ($errors as $error){
                $this->yieldError($error);
            }
        }
    }

    function yieldError($message)
    {
        $messages = is_array($message) ? $message : array($message);
        foreach ($messages as $message){
            echo "<div class='error'><p>$message</p></div>\n";
        }

    }

    /**
    * Yields each full error message added. So Person->addError("first_name", "can't be empty") will be returned
    * through iteration as "First name can't be empty".
    */
    function yieldEachFullError()
    {
        $full_messages = $this->getFullErrorMessages();
        foreach ($full_messages as $full_message){
            $this->yieldError($full_message);
        }
    }


    /**
    * Returns all the full error messages in an array.
    */
    function getFullErrorMessages()
    {
        $full_messages = array();

        foreach ($this->_errors as $attribute=>$errors){
            $full_messages[$attribute] = array();
            foreach ($errors as $error){
                $full_messages[$attribute][] = $this->t('%attribute_name %error', array(
                '%attribute_name'=>AkInflector::humanize($this->_delocalizeAttribute($attribute)),
                '%error'=>$error
                ));
            }
        }
        return $full_messages;
    }

    /**
    * Returns true if no errors have been added.
    */
    function hasErrors()
    {
        return !empty($this->_errors);
    }

    /**
    * Removes all the errors that have been added.
    */
    function clearErrors()
    {
        $this->_errors = array();
    }

    /**
    * Returns the total number of errors added. Two errors added to the same attribute will be counted as such
    * with this as well.
    */
    function countErrors()
    {
        $error_count = 0;
        foreach ($this->_errors as $errors){
            $error_count = count($errors)+$error_count;
        }

        return $error_count;
    }


    function errorsToString($print = false)
    {
        $result = "\n<div id='errors'>\n<ul class='error'>\n";
        foreach ($this->getFullErrorMessages() as $error){
            $result .= is_array($error) ? "<li class='error'>".join('</li><li class=\'error\'>',$error)."</li>\n" : "<li class='error'>$error</li>\n";
        }
        $result .= "</ul>\n</div>\n";

        if($print){
            echo $result;
        }
        return $result;
    }

    /*/Error Handling*/



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
    function actsAs($behaviour, $options = array())
    {
        $class_name = $this->_getActAsClassName($behaviour);
        $underscored_place_holder = AkInflector::underscore($behaviour);
        $camelized_place_holder = AkInflector::camelize($underscored_place_holder);

        if($this->$underscored_place_holder =& $this->_getActAsInstance($class_name, $options)){
            $this->$camelized_place_holder =& $this->$underscored_place_holder;
            if($this->$underscored_place_holder->init($options)){
                $this->__ActsLikeAttributes[$underscored_place_holder] = $underscored_place_holder;
            }
        }
    }

    /**
    * @access private
    */
    function _getActAsClassName($behaviour)
    {
        $class_name = AkInflector::camelize($behaviour);
        return file_exists(AK_LIB_DIR.DS.'AkActiveRecord'.DS.'AkActsAsBehaviours'.DS.'AkActsAs'.$class_name.'.php') && !class_exists('ActsAs'.$class_name) ?
        'AkActsAs'.$class_name : 'ActsAs'.$class_name;
    }

    /**
    * @access private
    */
    function &_getActAsInstance($class_name, $options)
    {
        if(!class_exists($class_name)){
            if(substr($class_name,0,2) == 'Ak'){
                include_once(AK_LIB_DIR.DS.'AkActiveRecord'.DS.'AkActsAsBehaviours'.DS.$class_name.'.php');
            }else{
                include_once(AK_APP_PLUGINS_DIR.DS.AkInflector::underscore($class_name).DS.'lib'.DS.$class_name.'.php');
            }
        }
        if(!class_exists($class_name)){
            trigger_error(Ak::t('The class %class used for handling an "act_as %class" does not exist',array('%class'=>$class_name)), E_USER_ERROR);
            $false = false;
            return $false;
        }else{
            $ActAsInstance =& new $class_name($this, $options);
            return $ActAsInstance;
        }
    }

    /**
    * @access private
    */
    function _loadActAsBehaviours()
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
                    $this->actsAs($type, $options);
                }
            }
        }
    }

    /**
    * Returns a comma separated list of possible acts like (active record, nested set, list)....
    */
    function actsLike()
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

    
    function dbug()
    {
        if(!$this->isConnected()){
            $this->setConnection();
        }
        $this->_db->connection->debug = $this->_db->connection->debug ? false : true;
        $this->db_debug =& $this->_db->connection->debug;
    }

    function toString($print = false)
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

    function dbugging($trace_this_on_debug_mode = null)
    {
        if(!empty($this->_db->debug) && !empty($trace_this_on_debug_mode)){
            $message = !is_scalar($trace_this_on_debug_mode) ? var_export($trace_this_on_debug_mode, true) : (string)$trace_this_on_debug_mode;
            Ak::trace($message);
        }
        return !empty($this->_db->debug);
    }



    function debug ($data = 'active_record_class', $_functions=0)
    {
        if(!AK_DEBUG && !AK_DEV_MODE){
            return;
        }

        $data = $data == 'active_record_class' ?  (AK_PHP5 ? clone($this) : $this) : $data;

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
                        }elseif (eregi ("function", $type)) {
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
    function select(&$source_array)
    {
        $resulting_array = array();
        if(!empty($source_array) && is_array($source_array) && func_num_args() > 1) {
        (array)$args = array_filter(array_slice(func_get_args(),1),array($this,'hasColumn'));
        foreach ($source_array as $source_item){
            $item_fields = array();
            foreach ($args as $arg){
                $item_fields[$arg] =& $source_item->get($arg);
            }
            $resulting_array[] =& $item_fields;
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
    function collect(&$source_array, $key_index, $value_index)
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
    function toJson($options = array())
    {
        if (is_array($options) && isset($options[0]) && is_a($options[0], 'AkActiveRecord')) {
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
                    $associationElement = $this->_convert_column_to_xml_element($associationElement);
                    if (is_array($this->$key)) {
                        $data[$associationElement] = array();
                        foreach ($this->$key as $el) {
                            if (is_a($el,'AkActiveRecord')) {
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
                        $el = &$this->$key->load();
                        if (is_a($el,'AkActiveRecord')) {
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
    function _convert_column_to_xml_element($col)
    {
        return str_replace('_','-',$col);
    }
    function _convert_column_from_xml_element($col)
    {
        return str_replace('-','_',$col);
    }
    
    function _parseXmlAttributes($attributes)
    {
        $new = array();
        foreach($attributes as $key=>$value)
        {
            $new[$this->_convert_column_from_xml_element($key)] = $value;
        }
        return $new;
    }
    
    function &_generateModelFromArray($modelName,$attributes)
    {
        if (isset($attributes[0]) && is_array($attributes[0])) {
            $attributes = $attributes[0];
        }
        $record = new $modelName('attributes',$this->_parseXmlAttributes($attributes));
        $record->_newRecord = !empty($attributes['id']);

        $associatedIds = array();
        foreach ($record->getAssociatedIds() as $key) {
            if (isset($attributes[$key]) && is_array($attributes[$key])) {
                $class = $record->$key->_AssociationHandler->getOption($key,'class_name');
                $related = $this->_generateModelFromArray($class,$attributes[$key]);
                $record->$key->build($related->getAttributes(),false);
                $related = &$record->$key->load();
                $record->$key = &$related;
            }
        }
        return $record;
    }
    
    function _fromArray($array)
    {
        $data  = $array;
        $modelName = $this->getModelName();
        $values = array();
        if (!isset($data[0])) {
            $data = array($data);
        }
        foreach ($data as $key => $value) {
            if (is_array($value)){
                $values[] = &$this->_generateModelFromArray($modelName,$value);
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
    function fromXml($xml)
    {
        $array = Ak::xml_to_array($xml);
        $array = $this->_fromXmlCleanup($array);
         return $this->_fromArray($array);
    }
    
    function _fromXmlCleanup($array)
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
    function fromJson($json)
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
    function toXml($options = array())
    {
        if (is_array($options) && isset($options[0]) && is_a($options[0], 'AkActiveRecord')) {
            $options = array('collection'=>$options);
        }
        if (isset($options['collection']) && is_array($options['collection']) && $options['collection'][0]->_modelName == $this->_modelName) {
            $root = strtolower(AkInflector::pluralize($this->_modelName));
            $root = $this->_convert_column_to_xml_element($root);
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
        $root = $this->_convert_column_to_xml_element(strtolower($this->_modelName));
        
        $xml .= '<' . $root . '>';
        $xml .= "\n";
        foreach ($this->_columns as $key => $def) {
            
            if (isset($options['except']) && in_array($key, $options['except'])) {
                continue;
            } else if (isset($options['only']) && !in_array($key, $options['only'])) {
                continue;
            } else {
                $columnType = $def['type'];
                $elementName = $this->_convert_column_to_xml_element($key);
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
                        $associationElement = $this->_convert_column_to_xml_element($associationElement);
                        $xml .= '<'.$associationElement.'>';
                        foreach ($this->$key as $el) {
                            if (is_a($el,'AkActiveRecord')) {
                                $xml .= $el->toXml(array('skip_instruct'=>true));
                            }
                        }
                        $xml .= '</' . $associationElement .'>';
                    } else {
                        $el = &$this->$key->load();
                        if (is_a($el,'AkActiveRecord')) {
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
    function toYaml($data = null)
    {
        return Ak::convert('active_record', 'yaml', empty($data) ? $this : $data);
    }


    /**
    * Parses an special formated array as a list of keys and values
    * 
    * This function generates an array with values and keys from an array with numeric keys.
    * 
    * This allows to parse an array to a function in the following manner.
    * create('first_name->', 'Bermi', 'last_name->', 'Ferrer');
    * //Previous code will be the same that
    * create(array('first_name'=>'Bermi', 'last_name'=> 'Ferrer'));
    *
    * Use this syntax only for quick testings, not for production environments. If the number of arguments varies, the result might be unpredictable.
    *
    * This function syntax is disabled by default. You need to define('AK_ENABLE_AKELOS_ARGS', true)
    * if you need this functionality.
    *
    * @deprecated
    */
    function parseAkelosArgs(&$args)
    {
        if(!AK_ENABLE_AKELOS_ARGS){
            $this->_castDateParametersFromDateHelper_($args);
            return ;
        }
        $k = array_keys($args);
        if(isset($k[1]) && substr($args[$k[0]],-1) == '>'){
            $size = sizeOf($k);
            $params = array();
            for($i = 0; $i < $size; $i++ ) {
                $v = $args[$k[$i]];
                if(!isset($key) && is_string($args[$k[$i]]) && substr($v,-1) == '>'){
                    $key = rtrim($v, '=-> ');
                }elseif(isset($key)) {
                    $params[$key] = $v;
                    unset($key);
                }else{
                    $params[$k[$i]] = $v;
                }
            }
            if(!empty($params)){
                $args = $params;
            }
        }
        $this->_castDateParametersFromDateHelper_($args);
    }
    /**
    * Gets an array from a string.
    *
    * Acts like Php explode() function but uses any of this as valid separators ' AND ',' and ',' + ',' ',',',';'
    */
    function getArrayFromAkString($string)
    {
        if(is_array($string)){
            return $string;
        }
        $string = str_replace(array(' AND ',' and ',' + ',' ',',',';'),array('|','|','|','','|','|'),trim($string));
        return strstr($string,'|') ? explode('|', $string) : array($string);
    }
    /*/Utilities*/


    function getAttributeCondition($argument)
    {
        if(is_array($argument)){
            return 'IN (?)';
        }elseif (is_null($argument)){
            return 'IS ?';
        }else{
            return '= ?';
        }
    }


    /**
                     Calculations
 ====================================================================
 */

    /**
    * @access private
    */
    var $_calculation_options = array('conditions', 'joins', 'order', 'select', 'group', 'having', 'distinct', 'limit', 'offset');

    /**
      * Count operates using three different approaches.
      *
      * * Count all: By not passing any parameters to count, it will return a count of all the rows for the model.
      * * Count by conditions or joins
      * * Count using options will find the row count matched by the options used.
      *
      * The last approach, count using options, accepts an option hash as the only parameter. The options are:
      *
      * * <tt>'conditions'</tt>: An SQL fragment like "administrator = 1" or array("user_name = ?", $username ). See conditions in the intro.
      * * <tt>'joins'</tt>: An SQL fragment for additional joins like "LEFT JOIN comments ON comments.post_id = id". (Rarely needed).
      * * <tt>'order'</tt>: An SQL fragment like "created_at DESC, name" (really only used with GROUP BY calculations).
      * * <tt>'group'</tt>: An attribute name by which the result should be grouped. Uses the GROUP BY SQL-clause.
      * * <tt>'select'</tt>: By default, this is * as in SELECT * FROM, but can be changed if you for example want to do a join.
      * * <tt>'distinct'</tt>: Set this to true to make this a distinct calculation, such as SELECT COUNT(DISTINCT posts.id) ...
      *
      * Examples for counting all:
      *   $Person->count();         // returns the total count of all people
      *
      * Examples for count by +conditions+ and +joins+ (this has been deprecated):
      *   $Person->count("age > 26");  // returns the number of people older than 26
      *   $Person->find("age > 26 AND job.salary > 60000", "LEFT JOIN jobs on jobs.person_id = ".$Person->id); // returns the total number of rows matching the conditions and joins fetched by SELECT COUNT(*).
      *
      * Examples for count with options:
      *   $Person->count('conditions' => "age > 26");
      *   $Person->count('conditions' => "age > 26 AND job.salary > 60000", 'joins' => "LEFT JOIN jobs on jobs.person_id = $Person->id"); // finds the number of rows matching the conditions and joins.
      *   $Person->count('id', 'conditions' => "age > 26"); // Performs a COUNT(id)
      *   $Person->count('all', 'conditions' => "age > 26"); // Performs a COUNT(*) ('all' is an alias for '*')
      *
      * Note: $Person->count('all') will not work because it will use 'all' as the condition.  Use $Person->count() instead.
      */
    function count()
    {
        $args = func_get_args();
        list($column_name, $options) = $this->_constructCountOptionsFromLegacyArgs($args);
        return $this->calculate('count', $column_name, $options);
    }

    /**
      * Calculates average value on a given column.  The value is returned as a float.  See #calculate for examples with options.
      *  
      *     $Person->average('age');
      */
    function average($column_name, $options = array())
    {
        return $this->calculate('avg', $column_name, $options);
    }

    /**
      * Calculates the minimum value on a given column.  The value is returned with the same data type of the column..  See #calculate for examples with options.
      *
      *   $Person->minimum('age');
      */
    function minimum($column_name, $options = array())
    {
        return $this->calculate('min', $column_name, $options);
    }

    /**
      * Calculates the maximum value on a given column.  The value is returned with the same data type of the column..  See #calculate for examples with options.
      *
      *   $Person->maximum('age');
      */
    function maximum($column_name, $options = array())
    {
        return $this->calculate('max', $column_name, $options);
    }

    /**
      * Calculates the sum value on a given column.  The value is returned with the same data type of the column..  See #calculate for examples with options.
      *
      *   $Person->sum('age');
      */
    function sum($column_name, $options = array())
    {
        return $this->calculate('sum', $column_name, $options);
    }

    /**
      * This calculates aggregate values in the given column:  Methods for count, sum, average, minimum, and maximum have been added as shortcuts.
      * Options such as 'conditions', 'order', 'group', 'having', and 'joins' can be passed to customize the query.
      *
      * There are two basic forms of output:
      *   * Single aggregate value: The single value is type cast to integer for COUNT, float for AVG, and the given column's type for everything else.
      *   * Grouped values: This returns an ordered hash of the values and groups them by the 'group' option.  It takes a column name.
      *
      *       $values = $Person->maximum('age', array('group' => 'last_name'));
      *       echo $values["Drake"]
      *       => 43
      *
      * Options:
      * * <tt>'conditions'</tt>: An SQL fragment like "administrator = 1" or array( "user_name = ?", username ). See conditions in the intro.
      * * <tt>'joins'</tt>: An SQL fragment for additional joins like "LEFT JOIN comments ON comments.post_id = id". (Rarely needed).
      *   The records will be returned read-only since they will have attributes that do not correspond to the table's columns.
      * * <tt>'order'</tt>: An SQL fragment like "created_at DESC, name" (really only used with GROUP BY calculations).
      * * <tt>'group'</tt>: An attribute name by which the result should be grouped. Uses the GROUP BY SQL-clause.
      * * <tt>'select'</tt>: By default, this is * as in SELECT * FROM, but can be changed if you for example want to do a join.
      * * <tt>'distinct'</tt>: Set this to true to make this a distinct calculation, such as SELECT COUNT(DISTINCT posts.id) ...
      *
      * Examples:
      *   $Person->calculate('count', 'all'); // The same as $Person->count();
      *   $Person->average('age'); // SELECT AVG(age) FROM people...
      *   $Person->minimum('age', array('conditions' => array('last_name != ?', 'Drake'))); // Selects the minimum age for everyone with a last name other than 'Drake'
      *   $Person->minimum('age', array('having' => 'min(age) > 17', 'group' => 'last'_name)); // Selects the minimum age for any family without any minors
      */
    function calculate($operation, $column_name, $options = array())
    {
        $this->_validateCalculationOptions($options);
        $column_name = empty($options['select']) ? $column_name : $options['select'];
        $column_name = $column_name == 'all' ? '*' : $column_name;
        $column      = $this->_getColumnFor($column_name);
        if (!empty($options['group'])){
            return $this->_executeGroupedCalculation($operation, $column_name, $column, $options);
        }else{
            return $this->_executeSimpleCalculation($operation, $column_name, $column, $options);
        }

        return 0;
    }

    /**
    * @access private
    */
    function _constructCountOptionsFromLegacyArgs($args)
    {
        $options = array();
        $column_name = 'all';

        /*
        We need to handle
        count()
        count(options=array())
        count($column_name='all', $options=array())
        count($conditions=null, $joins=null)
        */
        if(count($args) > 2){
            trigger_error(Ak::t("Unexpected parameters passed to count(\$options=array())", E_USER_ERROR));
        }elseif(count($args) > 0){
            if(!empty($args[0]) && is_array($args[0])){
                $options = $args[0];
            }elseif(!empty($args[1]) && is_array($args[1])){
                $column_name = array_shift($args);
                $options = array_shift($args);
            }else{
                $options = array('conditions' => $args[0]);
                if(!empty($args[1])){
                    $options = array_merge($options, array('joins' => $args[1]));
                }
            }
        }
        return array($column_name, $options);
    }


    /**
    * @access private
    */
    function _constructCalculationSql($operation, $column_name, $options)
    {
        $operation = strtolower($operation);
        $aggregate_alias = $this->_getColumnAliasFor($operation, $column_name);
        $use_workaround = $operation == 'count' && !empty($options['distinct']) && $this->_getDatabaseType() == 'sqlite';

        $sql = $use_workaround ?
        "SELECT COUNT(*) AS $aggregate_alias" : // A (slower) workaround if we're using a backend, like sqlite, that doesn't support COUNT DISTINCT.
        "SELECT $operation(".(empty($options['distinct'])?'':'DISTINCT ')."$column_name) AS $aggregate_alias";


        $sql .= empty($options['group']) ? '' : ", {$options['group_field']} AS {$options['group_alias']}";
        $sql .= $use_workaround ? " FROM (SELECT DISTINCT {$column_name}" : '';
        $sql .=  " FROM ".$this->getTableName()." ";

        $sql .=  empty($options['joins']) ? '' : " {$options['joins']} ";

        empty($options['conditions']) ? null : $this->addConditions($sql, $options['conditions']);

        if (!empty($options['group'])){
            $sql .=  " GROUP BY {$options['group_field']} ";
            $sql .= empty($options['having']) ? '' : " HAVING {$options['having']} ";
        }

        $sql .= empty($options['order']) ? '' : " ORDER BY {$options['order']} ";
        $this->_db->addLimitAndOffset($sql, $options);
        $sql .= $use_workaround ? ')' : '';
        return $sql;
    }


    /**
    * @access private
    */
    function _executeSimpleCalculation($operation, $column_name, $column, $options)
    {
        $value = $this->_db->selectValue($this->_constructCalculationSql($operation, $column_name, $options));
        return $this->_typeCastCalculatedValue($value, $column, $operation);
    }

    /**
    * @access private
    */
    function _executeGroupedCalculation($operation, $column_name, $column, $options)
    {
        $group_field = $options['group'];
        $group_alias = $this->_getColumnAliasFor($group_field);
        $group_column = $this->_getColumnFor($group_field);
        $options = array_merge(array('group_field' => $group_field, 'group_alias' => $group_alias),$options);
        $sql = $this->_constructCalculationSql($operation, $column_name, $options);
        $calculated_data = $this->_db->select($sql);
        $aggregate_alias = $this->_getColumnAliasFor($operation, $column_name);

        $all = array();
        foreach ($calculated_data as $row){
            $key = $this->_typeCastCalculatedValue($row[$group_alias], $group_column);
            $all[$key] = $this->_typeCastCalculatedValue($row[$aggregate_alias], $column, $operation);
        }
        return $all;
    }

    /**
    * @access private
    */
    function _validateCalculationOptions($options = array())
    {
        $invalid_options = array_diff(array_keys($options),$this->_calculation_options);
        if(!empty($invalid_options)){
            trigger_error(Ak::t('%options are not valid calculation options.', array('%options'=>join(', ',$invalid_options))), E_USER_ERROR);
        }
    }

    /**
    * Converts a given key to the value that the database adapter returns as
    * as a usable column name.
    *   users.id #=> users_id
    *   sum(id) #=> sum_id
    *   count(distinct users.id) #=> count_distinct_users_id
    *   count(*) #=> count_all
    *
    * @access private
    */
    function _getColumnAliasFor()
    {
        $args = func_get_args();
        $keys = strtolower(join(' ',(!empty($args) ? (is_array($args[0]) ? $args[0] : $args) : array())));
        return preg_replace(array('/\*/','/\W+/','/^ +/','/ +$/','/ +/'),array('all',' ','','','_'), $keys);
    }

    /**
    * @access private
    */
    function _getColumnFor($field)
    {
        $field_name = ltrim(substr($field,strpos($field,'.')),'.');
        if(in_array($field_name,$this->getColumnNames())){
            return $field_name;
        }
        return $field;
    }

    /**
    * @access private
    */
    function _typeCastCalculatedValue($value, $column, $operation = null)
    {
        $operation = strtolower($operation);
        if($operation == 'count'){
            return intval($value);
        }elseif ($operation == 'avg'){
            return floatval($value);
        }else{
            return empty($column) ? $value : AkActiveRecord::castAttributeFromDatabase($column, $value);
        }
    }

    /*/Calculations*/

    function hasBeenModified()
    {
        return Ak::objectHasBeenModified($this);
    }

    /**
    * Just freeze the attributes hash, such that associations are still accessible even on destroyed records.
    * 
    * @todo implement freeze correctly for its intended use
    */
    function freeze()
    {
        return $this->_freeze = true;
    }

    function isFrozen()
    {
        return !empty($this->_freeze);
    }

    /**
    * Alias for getModelName()
    */
    function getType()
    {
        return $this->getModelName();
    }

    function &objectCache()
    {
        static $cache;
        $false = false;
        $args =& func_get_args();
        if(count($args) == 2){
            if(!isset($cache[$args[0]])){
                $cache[$args[0]] =& $args[1];
            }
        }elseif(!isset($cache[$args[0]])){
            return $false;
        }
        return $cache[$args[0]];
    }


    /**
                        Connection adapters
    ====================================================================
    Right now Akelos uses phpAdodb for bd abstraction. This are functionalities not
    provided in phpAdodb and that will move to a separated driver for each db 
    engine in a future
    */
    function _extractValueFromDefault($default)
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


}


?>
