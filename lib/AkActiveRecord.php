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
 * @subpackage AkActiveRecord
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

require_once(AK_LIB_DIR.DS.'Ak.php');
require_once(AK_LIB_DIR.DS.'AkInflector.php');
require_once(AK_LIB_DIR.DS.'AkActiveRecord'.DS.'AkAssociatedActiveRecord.php');

if(!defined('AK_ACTIVE_RECORD_VALIDATE_TABLE_NAMES')){
    define('AK_ACTIVE_RECORD_VALIDATE_TABLE_NAMES', true);
}
if(!defined('AK_ACTIVE_RECORD_ENABLE_PERSISTENCE')){
    define('AK_ACTIVE_RECORD_ENABLE_PERSISTENCE', true);
}
if(!defined('AK_NOT_EMPTY_REGULAR_EXPRESSION')){
    define('AK_NOT_EMPTY_REGULAR_EXPRESSION','/.+/');
}
if(!defined('AK_EMAIL_REGULAR_EXPRESSION')){
    define('AK_EMAIL_REGULAR_EXPRESSION',"/^([a-z0-9_\-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([a-z0-9\-]+\.)+))([a-z]{2,4}|[0-9]{1,3})(\]?)$/i");
}
if(!defined('AK_NUMBER_REGULAR_EXPRESSION')){
    define('AK_NUMBER_REGULAR_EXPRESSION',"/^[0-9]+$/");
}
if(!defined('AK_PHONE_REGULAR_EXPRESSION')){
    define('AK_PHONE_REGULAR_EXPRESSION',"/^([\+]?[(]?[\+]?[ ]?[0-9]{2,3}[)]?[ ]?)?[0-9 ()\-]{4,25}$/");
}
if(!defined('AK_DATE_REGULAR_EXPRESSION')){
    define('AK_DATE_REGULAR_EXPRESSION',"/^(([0-9]{1,2}(\-|\/|\.| )[0-9]{1,2}(\-|\/|\.| )[0-9]{2,4})|([0-9]{2,4}(\-|\/|\.| )[0-9]{1,2}(\-|\/|\.| )[0-9]{1,2})){1}$/");
}
if(!defined('AK_IP4_REGULAR_EXPRESSION')){
    define('AK_IP4_REGULAR_EXPRESSION',"/^((25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])\.){3}(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])$/");
}
if(!defined('AK_POST_CODE_REGULAR_EXPRESSION')){
    define('AK_POST_CODE_REGULAR_EXPRESSION',"/^[0-9A-Za-z  -]{2,9}$/");
}

Ak::compat('array_combine');

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
*   $user = new User(array('name' => 'David', 'occupation' => 'Code Artist'));
*   echo $user->name; // Will print "David"
* 
* You can also use a parameter list initialization.:
* 
*   $user = new User('name->', 'David', 'occupation->', 'Code Artist');
* 
* And of course you can just create a bare object and specify the attributes after the fact:
* 
*   $user = new User();
*   $user->name = 'David';
*   $user->occupation = 'Code Artist';
* 
* == Conditions ==
* 
* Conditions can either be specified as a string or an array representing the WHERE-part of an SQL statement.
* The array form is to be used when the condition input is tainted and requires sanitization. The string form can
* be used for statements that doesn't involve tainted data. Examples:
* 
*   class User extends AkActiveRecord
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
*   $Company->findFirst(
*              "id = :id AND name = :name AND division = :division AND created_at > :accounting_date", 
*               array(':id' => 3, ':name' => "37signals", ':division' => "First", ':accounting_date' => '2005-01-01')
*             );
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
* Active Record can serialize any object in text columns. To do so, you must specify this with by setting the attribute serialize whith 
* a comma separated list of columns or an array. 
* This makes it possible to store arrays, hashes, and other non-mappeable objects without doing any additional work. Example:
* 
*   class User extends AkActiveRecord
*   {
*      var $serialize = 'preferences';
*   }
* 
*   $User = new User(array('preferences'=>array("background" => "black", "display" => 'large')));
*   $User->find($user_id);
*   $User->preferences // array("background" => "black", "display" => 'large')
* 
* == Single table inheritance ==
* 
* Active Record allows inheritance by storing the name of the class in a column that by default is called "type" (can be changed 
* by overwriting <tt>AkActiveRecord->_inheritanceColumn</tt>). This means that an inheritance looking like this:
* 
*   class Company extends AkActiveRecord{}
*   class Firm extends Company{}
*   class Client extends Company{}
*   class PriorityClient extends Client{}
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

    var $_tableName;
    var $_db;
    var $_newRecord;
    var $_freeze;
    var $_modelName;
    var $_dataDictionary;
    var $_primaryKey;
    var $_inheritanceColumn;

    var $_associations;

    var $_internationalize;

    var $_errors = array();

    var $_attributes = array();

    var $_protectedAtributes = array();
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

    var $_automated_max_length_validator = true;
    var $_automated_validators_enabled = true;
    var $_automated_not_null_validator = false;
    var $_set_default_attribute_values_automatically = true;

    var $_automated_password_obfuscation = true;

    // This is needed for enabling support for static active record instantation under php
    var $_activeRecordHasBeenInstantiated = true;

    var $__ActsLikeAttributes = array();
    var $__coreActsLikeAttributes = array('nested_set', 'list', 'tree');

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


    function __construct()
    {
        $attributes = (array)func_get_args();
        return $this->init($attributes);
    }

    function init($attributes = array())
    {
        $this->_internationalize = is_null($this->_internationalize) ? count($this->getAvaliableLocales()) > 1 : $this->_internationalize;

        @$this->_instatiateDefaultObserver();

        $this->setConnection();

        if(!empty($this->table_name)){
            $this->setTableName($this->table_name);
        }

        $this->_loadActAsBehaviours();

        if(!empty($this->combined_attributes)){
            foreach ($this->combined_attributes as $combined_attribute){
                call_user_func_array(array(&$this,'addCombinedAttributeConfiguration'), $combined_attribute);
            }
        }

        //$this->initiateAssociations();


        // new AkActiveRecord(array('username'=>'bermi','pass'=>'mypass')); Sets attributes for creating a new entry or finding a new record
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
                $this->setAttributes($record->getAttributes());
            }
            // This option is only used internally for loading found objects
        }elseif(isset($attributes[0]) && isset($attributes[1]) && $attributes[0] == 'attributes' && is_array($attributes[1])){
            foreach($attributes[1] as $k=>$v){
                $attributes[1][$k] = $this->castAttributeFromDatabase($k, $v);
            }
            $this->setAttributes($attributes[1]);
        }else{
            $this->_newRecord = true;
            // new AkActiveRecord('username->','bermi','pass->','mypass'); // Sets attributes for creating a new entry or finding a new record using an special sintax
            if(isset($attributes[0]) && !is_array($attributes[0])){
                $this->parseAkelosArgs($attributes);
            }
            $attributes = $this->attributesFromColumnDefinition($attributes);

            foreach ($attributes as $k=>$v){
                $this->setAttribute($k, $v);
            }

            $this->composeCombinedAttributes();
        }

        if(count($attributes) === 0){
            $this->_newRecord = true;
        }

        $this->_buildFinders();
        $this->loadAssociations();
    }

    function __destruct()
    {

    }



    /**
    * If this macro is used, only those attributed named in it will be accessible for mass-assignment, such as new ModelName($attributes) and $this->attributes($attributes). This is the more conservative choice for mass-assignment protection. If you?d rather start from an all-open default and restrict attributes as needed, have a look at AkActiveRecord::attrProtected.
    */
    function setAccessibleAttributes()
    {
        $args = func_get_args();
        $this->_accessibleAttributes = array_unique(array_merge((array)$this->_accessibleAttributes, $args));
    }

    /**
Attributes named in this macro are protected from mass-assignment, such as new ModelName($attributes) and $this->attributes(attributes). Their assignment will simply be ignored. Instead, you can use the direct writer methods to do assignment. This is meant to protect sensitive attributes to be overwritten by URL/form hackers. Example:
<code>
  class Customer extends AkActiveRecord
  {
    function Customer()
    {
        $this->setProtectedAttributes('credit_rating');
    }
  }

  $Customer = new Customer('name' => 'David', 'credit_rating' => 'Excellent');
  $Customer->credit_rating // => null
  $Customer->attributes(array('description' => 'Jolly fellow', 'credit_rating' => 'Superb'));
  $Customer->credit_rating // => null

  $Customer->credit_rating = 'Average'
  $Customer->credit_rating // => 'Average'
</code>
*/    
    function setProtectedAttributes()
    {
        $args = func_get_args();
        $this->_protectedAtributes = array_unique(array_merge((array)$this->_protectedAtributes, $args));
    }


    /**
    * Returns true if a connection that?s accessible to this class have already been opened.
    */ 
    function isConnected()
    {
        return isset($this->_db);
    }

    /**
    * Returns the connection currently associated with the class. This can also be used to "borrow" the connection to do database work unrelated to any of the specific Active Records.
    */
    function &getConnection()
    {
        return $this->_db;
    }

    /**
    * Set the connection for the class.
    */
    function setConnection($dns = null)
    {
        $this->_db =& Ak::db($dns);
    }

    /**
    * Returns an array of columns objects where the primary id, all columns ending in "_id" or "_count", and columns used for single table inheritance has been removed.
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
    * Creates an object, instantly saves it as a record (if the validation permits it), and returns it. If the save fail under validations, the unsaved object is still returned.
    */
    function &create($attributes = null)
    {
        if(!isset($this->_activeRecordHasBeenInstantiated)){
            return Ak::handleStaticCall();
        }

        if(func_num_args() > 1){
            $attributes = func_get_args();
        }
        $this->parseAkelosArgs($attributes);
        $model = $this->getModelName();

        $object =& new $model();
        $object->setAttributes($attributes);
        $object->save();
        return $object;
    }


    /**
      * Returns the number of records that meet the 'conditions'. Zero is returned if no records match. Example:
      *   $Product->count("sales > 1");
      */
    function count($conditions = null, $joins = null)
    {
        if(!isset($this->_activeRecordHasBeenInstantiated)){
            return Ak::handleStaticCall();
        }

        $sql  = 'SELECT COUNT(*) FROM '.$this->getTableName().' ';
        $sql .= !empty($joins) ? $joins.' ' : '';

        $this->addConditions($sql, $conditions);

        return $this->countBySql($sql);
    }

    /**
      * Returns the result of an SQL statement that should only include a COUNT(*) in the SELECT part.
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
        $rs = $this->_db->Execute($sql);

        return @(integer)$rs->fields[0];
    }

    /**
    * Increments the specified counter by one. So $DiscussionBoard->incrementCounter("post_count", $discussion_board_id); would increment the "post_count" counter on the board responding to $discussion_board_id. This is used for caching aggregate values, so that they doesn't need to be computed every time. Especially important for looping over a collection where each element require a number of aggregate values. Like the $DiscussionBoard that needs to list both the number of posts and comments.
    */
    function incrementCounter($counter_name, $id, $difference = 1)
    {
        $new_value = $this->getAttribute($counter_name) + $difference;
        if($this->updateAll($counter_name.' = '.$new_value, $this->getPrimaryKey().' = '.$this->castAttributeForDatabase($this->getPrimaryKey(), $id)) === 0){
            return false;
        }
        return $new_value;
    }

    /**
    * Works like AkActiveRecord::incrementCounter, but decrements instead.
    */
    function decrementCounter($counter_name, $id, $difference = 1)
    {
        $new_value = $this->getAttribute($counter_name) - $difference;

        if(!$this->updateAll($counter_name.' = '.$new_value, $this->getPrimaryKey().' = '.$this->castAttributeForDatabase($this->getPrimaryKey(), $id)) === 0){
            return false;
        }
        return $new_value;
    }



    /**
    * Finds the record from the passed id, instantly saves it with the passed attributes (if the validation permits it), and returns it. If the save fail under validations, the unsaved object is still returned.
    */
    function update($id, $attributes)
    {
        if(!isset($this->_activeRecordHasBeenInstantiated)){
            return Ak::handleStaticCall();
        }
        if(is_array($id)){
            $results = array();
            foreach ($id as $single_id){
                $results[] = $this->update($single_id, $attributes);
            }
            return $results;
        }else{
            $object = $this->find($id);
            $object->updateAttributes($attributes, $object);
            return $object;
        }
    }

    /**
    * Updates a single attribute and saves the record. This is especially useful for boolean flags on existing records. Note: Make sure that updates made with this method doesn't get subjected to validation checks. Hence, attributes can be updated even if the full object isn't valid.
    */
    function updateAttribute($name, $value)
    {
        $this->setAttribute($name, $value);
        return $this->save(false);
    }


    /**
    * Updates all the attributes in from the passed array and saves the record. If the object is invalid, the saving will fail and false will be returned.
    */
    function updateAttributes($attributes, $object = null)
    {
        foreach ($attributes as $attribute=>$value){
            isset($object) ? $object->setAttribute($attribute, $value) : $this->setAttribute($attribute, $value);
        }

        return isset($object) ? $object->save() : $this->save();
    }




    /**
    * Updates all records with the SET-part of an SQL update statement in updates and returns an integer with the number of rows updates. A subset of the records can be selected by specifying conditions. Example:
    * <code>$Billing->updateAll("category = 'authorized', approved = 1", "author = 'David'");</code>
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
        $sql  .= isset($conditions) ? ' WHERE '.$conditions : '';
        if(!$this->_db->Execute($sql) && AK_DEBUG){
            trigger_error($this->_db->ErrorMsg(), E_USER_NOTICE);
        }
        return $this->_db->Affected_Rows();
    }


    /**
    * Deletes the record with the given id without instantiating an object first. If an array of ids is provided, all of them are deleted.
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
    * Deletes all the records that matches the condition without instantiating the objects first (and hence not calling the destroy method). Example:
    * <code>$Post->destroyAll("person_id = 5 AND (category = 'Something' OR category = 'Else')");</code>
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

        $sql  .= isset($conditions) ? ' WHERE '.$conditions : ($this->_getDatabaseType() == 'sqlite' ? ' WHERE 1' : ''); // (HACK) If where clause is not included sqlite_changes will not get the right result
        if(!$this->_db->Execute($sql) && AK_DEBUG){
            trigger_error($this->_db->ErrorMsg(), E_USER_NOTICE);
        }
        return $this->_db->Affected_Rows() > 0;
    }


    /**
    * Destroys the record with the given id by instantiating the object and calling destroy (all the callbacks are the triggered). If an array of ids is provided, all of them are destroyed.
    * Deletes the record in the database and freezes this instance to reflect that no changes should be made (since they can't be persisted).
    */
    function destroy($id = null)
    {
        if(!isset($this->_activeRecordHasBeenInstantiated)){
            return Ak::handleStaticCall();
        }

        $this->transactionStart();
        $id = func_num_args() > 1 ? func_get_args() : $id;

        if(isset($id)){
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
                if($this->beforeDestroy()){
                    $this->notifyObservers('beforeDestroy');
                    /**
                    * @todo sanitize and quote id
                    */
                    $sql = 'DELETE FROM '.$this->getTableName().' WHERE '.$this->getPrimaryKey().' = '.$this->_db->qstr($this->getId());

                    if(!$this->_db->Execute($sql) && AK_DEBUG){
                        trigger_error($this->_db->ErrorMsg(), E_USER_NOTICE);
                    }
                    $had_success = ($this->_db->Affected_Rows() > 0);
                    if(!$had_success || ($had_success && !$this->afterDestroy())){
                        $this->transactionFail();
                        $had_success = false;
                    }else{
                        $had_success = $this->notifyObservers('afterDestroy') === false ? false : true;
                    }
                    $this->transactionComplete();
                    $this->freeze();
                    return  $had_success;
                }else {
                    $this->transactionFail();
                    $this->transactionComplete();
                    return false;
                }
            }
        }

        if(!$this->afterDestroy()){
            $this->transactionFail();
        }else{
            $this->notifyObservers('afterDestroy');
        }

        $this->transactionComplete();

        return false;
    }



    /**
    * Destroys the objects for all the records that matches the condition by instantiating each object and calling the destroy method. Example:
    * $Person->destroyAll("last_login < '2004-04-04'");
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





    /**
    * Establishes the connection to the database. Accepts an array as input where the 'adapter' key must be specified with the name of a database adapter (in lower-case) example for regular databases (MySQL, Postgresql, etc):

      $AkActiveRecord->establishConnection(
        array(
        'adapter'  => "mysql",
        'host'     => "localhost",
        'username' => "myuser",
        'password' => "mypass",
        'database' => "somedatabase"
        )
    )

    Example for SQLite database:

      $AkActiveRecord->establishConnection(
        array(
        'adapter' => "sqlite",
        'dbfile'  => "path/to/dbfile"
        )
      )
    */
    function &establishConnection($spec = null)
    {
        if(isset($spec)){
            $dns = is_string($spec) ? $spec : '';
            if(!empty($spec['adapter'])){
                $dsn = $spec['adapter'] == 'sqlite' ?
                'sqlite://'.urlencode($spec['dbfile']).'/' :
                $spec['adapter'].'://'.@$spec['username'].':'.@$spec['password'].'@'.@$spec['host'].'/'.@$spec['database'];
            }
            $dsn .= isset($spec['persist']) && $spec['persist'] === false ? '' : '?persist';
            return $this->setConnection($dns);
        }else{
            return false;
        }
    }


    /**
    * Just freeze the attributes hash, such that associations are still accessible even on destroyed records.
    */
    function freeze()
    {
        $this->_freeze = true;
    }

    function isFrozen()
    {
        return !empty($this->_freeze);
    }


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
Find operates with three different retrieval approaches:

    * Find by id: This can either be a specific id find(1), a list of ids find(1, 5, 6), or an array of ids find(array(5, 6, 10)). If no record can be found for all of the listed ids, then RecordNotFound will be raised.
    * Find first: This will return the first record matched by the options used. These options can either be specific conditions or merely an order. If no record can matched, false is returned.
    * Find all: This will return all the records matched by the options used. If no records are found, an empty array is returned.

All approaches accepts an $option array as their last parameter. The options are:

    * 'conditions' => An SQL fragment like "administrator = 1" or array("user_name = ?" => $username). See conditions in the intro.
    * 'order' => An SQL fragment like "created_at DESC, name".
    * 'limit' => An integer determining the limit on the number of rows that should be returned.
    * 'offset' => An integer determining the offset from where the rows should be fetched. So at 5, it would skip the first 4 rows.
    * 'joins' => An SQL fragment for additional joins like "LEFT JOIN comments ON comments.post_id = $id". (Rarely needed).
    * 'include' => Names associations that should be loaded alongside using LEFT OUTER JOINs. The symbols named refer to already defined associations. See eager loading under Associations.

Examples for find by id:

  $Person->find(1);       // returns the object for ID = 1
  $Person->find(1, 2, 6); // returns an array for objects with IDs in (1, 2, 6), Returns false if any of those IDs is not available
  $Person->find(array(7, 17)); // returns an array for objects with IDs in (7, 17)
  $Person->find(array(1));     // returns an array for objects the object with ID = 1
  $Person->find(1, array('conditions' => "administrator = 1", 'order' => "created_on DESC"));

Examples for find first:

  $Person->find('first'); // returns the first object fetched by SELECT * FROM people
  $Person->find('first', array('conditions' => array("user_name = ':user_name'", ':user_name' => $user_name)));
  $Person->find('first', array('order' => "created_on DESC", 'offset' => 5));

Examples for find all:

  $Person->find('all'); // returns an array of objects for all the rows fetched by SELECT * FROM people
  $Person->find(); // Same as $Person->find('all');
  $Person->find('all', array('conditions => array("category IN (categories)", 'categories' => join(','$categories)), 'limit' => 50));
  $Person->find('all', array('offset' => 10, 'limit' => 10));
  $Person->find('all', array('include' => array('account', 'friends'));

*/
    function &find()
    {
        if(!isset($this->_activeRecordHasBeenInstantiated)){
            return Ak::handleStaticCall();
        }

        $num_args = func_num_args();
        if($num_args === 2 && func_get_arg(0) == 'set arguments'){
            $args  = func_get_arg(1);
            $num_args = count($args);
        }

        $args = $num_args > 0 ? (!isset($args) ? func_get_args() : $args) : array('all');

        if($num_args === 1 && is_numeric($args[0]) && $args[0] > 0){
            $args[0] = (integer)$args[0]; //Cast query by Id
        }

        $options = $num_args > 0 && (is_array($args[$num_args-1]) && isset($args[0][0]) && !is_numeric($args[0][0])) ? array_pop($args) : array();

        //$options = func_get_arg(func_num_args()-1);
        if(!empty($options['conditions']) && is_array($options['conditions'])){
            if (isset($options['conditions'][0]) && strstr($options['conditions'][0], '?') && count($options['conditions']) > 1){
                $pattern = array_shift($options['conditions']);
                $options['bind'] = array_values($options['conditions']);
                $options['conditions'] = $pattern;
            }elseif (isset($options['conditions'][0])){
                $pattern = array_shift($options['conditions']);
                $options['conditions'] = str_replace(array_keys($options['conditions']), array_values($this->getSanitizedConditionsArray($options['conditions'])),$pattern);
            }else{
                $options['conditions'] = join(' AND ',(array)$this->getAttributesQuoted($options['conditions']));
            }
        }

        if ($num_args === 2 && !empty($args[0]) && !empty($args[1]) && is_string($args[0]) && ($args[0] == 'all' || $args[0] == 'first') && is_string($args[1])){
            if (!is_array($args[1]) && $args[1] > 0 && $args[0] == 'first'){
                $num_args = 1;
                $args = array($args[1]);
                $options = array();
            }else{
                $options['conditions'] = $args[1];
                $args = array($args[0]);
            }
        }elseif ($num_args === 1 && isset($args[0]) && is_string($args[0]) && $args[0] != 'all' && $args[0] != 'first'){
            $options = array('conditions'=> $args[0]);
            $args = array('first');
        }

        if(!empty($options['conditions']) && is_numeric($options['conditions']) && $options['conditions'] > 0){
            unset($options['conditions']);
        }

        if($num_args > 1){
            if(!empty($args[0]) && is_string($args[0]) && strstr($args[0],'?')){
                $options = array_merge(array('conditions' => array_shift($args)), $options);
                $options['bind'] = $args;
                $args = array('all');
            }elseif (!empty($args[1]) && is_string($args[1]) && strstr($args[1],'?')){
                $_tmp_mode = array_shift($args);
                $options = array_merge(array('conditions' => array_shift($args)),$options);
                $options['bind'] = $args;
                $args = array($_tmp_mode);
            }
        }

        switch ($args[0]) {
            case 'first':
            $result =& $this->find('all', array_merge($options, array('limit'=>1)));
            if(!empty($result) && is_array($result)){
                $_result =& $result[0];
            }else{
                $_result =& $GLOBALS['false'];
            }
            return  $_result;
            break;

            case 'all':


            $limit = isset($options['limit']) ? $options['limit'] : null;
            $offset = isset($options['offset']) ? $options['offset'] : null;
            if((empty($options['conditions']) && empty($options['order']) && is_null($offset) && $this->_getDatabaseType() == 'postgre' ? 1 : 0)){
                $options['order'] = $this->getPrimaryKey();
            }
            $sql = $this->constructFinderSql($options);
            if(!empty($options['bind']) && is_array($options['bind']) && strstr($sql,'?')){
                $sql = array_merge(array($sql),$options['bind']);
            }

            if((!empty($options['include']) && $this->hasAssociations())){
                $result =& $this->findWithAssociations($options,  $limit, $offset);
            }else{
                $result =& $this->findBySql($sql, $limit, $offset);
            }

            if(!empty($result) && is_array($result)){
                $_result =& $result;
            }else{
                $_result =& $GLOBALS['false'];
            }
            return  $_result;
            break;

            default:

            $ids = array_unique(isset($args[0]) ? (is_array($args[0]) ? $args[0] : (array)$args) : array());

            $num_ids = count($ids);
            $num_args = count($args);

            if(isset($ids[$num_ids-1]) && is_array($ids[$num_ids-1])){
                $options =  array_merge($options, array_pop($ids));
                $num_ids--;
            }

            if($num_args === 1 && !$args[0] > 0){
                $options['conditions'] = $args[0];
            }

            $conditions = !empty($options['conditions']) ? ' AND '.$options['conditions'] : '';

            switch ($num_ids){
                case 0 :
                trigger_error($this->t('Couldn\'t find %object_name without an ID%conditions',array('%object_name'=>$this->getModelName(),'%conditions'=>$conditions)), E_USER_ERROR);
                break;

                case 1 :

                $result =& $this->find('first', array_merge($options, array('conditions' => $this->getTableName().'.'.$this->getPrimaryKey().' = '.$ids[0].$conditions)));
                if(is_array($args[0]) && $result !== false){
                    //This is a dirty hack for avoiding PHP4 pass by reference error
                    $result_for_ref = array(&$result);
                    $_result =& $result_for_ref;
                }else{
                    $_result =& $result;
                }
                return  $_result;

                break;

                default:
                $result =& $this->find('all', array_merge($options, array('conditions' => $this->getTableName().'.'.$this->getPrimaryKey().' IN ('.join(', ',$ids).')'.$conditions)));
                if(is_array($result) && count($result) == $num_ids){
                    if($result === false){
                        $_result =& $GLOBALS['false'];
                    }else{
                        $_result =& $result;
                    }
                    return $_result;
                }else{
                    $result =& $GLOBALS['false'];
                    return $result;
                }
                break;
            }
            break;
        }
        $result =& $GLOBALS['false'];
        return $result;
    }

    function &findFirst()
    {
        if(!isset($this->_activeRecordHasBeenInstantiated)){
            return Ak::handleStaticCall();
        }
        $args = func_get_args();
        return Ak::call_user_func_array(array(&$this,'find'), array_merge(array('first'),$args));
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


    function &objectCache()
    {
        static $cache;
        $args =& func_get_args();
        if(count($args) == 2){
            if(!isset($cache[$args[0]])){
                $cache[$args[0]] =& $args[1];
            }
        }elseif(!isset($cache[$args[0]])){
            return false;
        }
        return $cache[$args[0]];
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

    // Gets the column name for use with single table inheritance ? can be overridden in subclasses.
    function getInheritanceColumn()
    {
        return empty($this->_inheritanceColumn) ? ($this->hasColumn('type') ? 'type' : false ) : $this->_inheritanceColumn;
    }

    // Defines the column name for use with single table inheritance ? can be overridden in subclasses.
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

    function getColumnsWithRegexBoundaries()
    {
        $columns = array_keys($this->getColumns());
        foreach ($columns as $k=>$column){
            $columns[$k] = '/([^\.])\b('.$column.')\b/';
        }
        return $columns;
    }



    //SELECT t.code as codigo , c.description  as descripcion FROM technical_listings as t LEFT OUTER JOIN categories as c ON c.id = t.category_id



    /**
    * Works like find_all, but requires a complete SQL string. Examples:
    * $Post->findBySql("SELECT p.*, c.author FROM posts p, comments c WHERE p.id = c.post_id");
    * $Post->findBySql(array("SELECT * FROM posts WHERE author = ? AND created_on > ?", $author_id, $start_date));
    */
    function &findBySql($sql, $limit = null, $offset = null)
    {
        if(!isset($this->_activeRecordHasBeenInstantiated)){
            return Ak::handleStaticCall();
        }
        if(is_array($sql)){
            $sql_query = array_shift($sql);
            $bindings = is_array($sql) && count($sql) > 0 ? $sql : array($sql);
            $sql = $sql_query;
        }
        $this->setConnection();
        $objects = array();
        if(is_integer($limit)){
            if(is_integer($offset)){
                $results = !empty($bindings) ? $this->_db->SelectLimit($sql, $limit, $offset, $bindings) : $this->_db->SelectLimit($sql, $limit, $offset);
            }else {
                $results = !empty($bindings) ? $this->_db->SelectLimit($sql, $limit, -1, $bindings) : $this->_db->SelectLimit($sql, $limit);
            }
        }else{
            $results = !empty($bindings) ? $this->_db->Execute($sql, $bindings) : $this->_db->Execute($sql);
        }

        if(!$results){
            AK_DEBUG ? trigger_error($this->_db->ErrorMsg(), E_USER_NOTICE) : null;
        }else{
            $objects = array();
            while ($record = $results->FetchRow()) {
                $objects[] =& $this->instantiate($this->removeUnavailableAttributes($record), false);
            }
        }

        return $objects;
    }



    /**
    * This function pretends to emulate ror finders until AkActiveRecord::addMethod becomes stable on future PHP versions.
    * @todo use PHP5 __call method for handling the magic finder methods like findFirstByUnsenameAndPassword('bermi','pass')
    */
    function &findFirstBy()
    {
        if(!isset($this->_activeRecordHasBeenInstantiated)){
            return Ak::handleStaticCall();
        }
        $args = func_get_args();
        if($args[0] != 'first'){
            array_unshift($args,'first');
        }
        $result =& Ak::call_user_func_array(array(&$this,'findBy'), $args);
        return $result;
    }

    function &findLastBy()
    {
        if(!isset($this->_activeRecordHasBeenInstantiated)){
            return Ak::handleStaticCall();
        }
        $args = func_get_args();
        $options = array_pop($args);
        if(!is_array($options)){
            array_push($args, $options);
            $options = array();
        }
        $options['order'] = $this->getPrimaryKey().' DESC';
        array_push($args, $options);
        return Ak::call_user_func_array(array(&$this,'findFirstBy'), $args);
    }

    function &findAllBy()
    {
        if(!isset($this->_activeRecordHasBeenInstantiated)){
            return Ak::handleStaticCall();
        }
        $args = func_get_args();
        if($args[0] == 'first'){
            array_shift($args);
        }
        $result =& Ak::call_user_func_array(array(&$this,'findBy'), $args);
        return $result;
    }

    function &findBy()
    {
        if(!isset($this->_activeRecordHasBeenInstantiated)){
            return Ak::handleStaticCall();
        }
        $args = func_get_args();
        $sql = array_shift($args);
        if($sql == 'all' || $sql == 'first'){
            $fetch = $sql;
            $sql = array_shift($args);
        }else{
            $fetch = 'all';
        }

        $options = array_pop($args);

        if(!is_array($options)){
            array_push($args, $options);
            $options = array();
        }

        $query_values = $args;
        $query_arguments_count = count($query_values);

        $sql = str_replace(array('(',')','||','|','&&','&','  '),array(' ( ',' ) ',' OR ',' OR ',' AND ',' AND ',' '),$sql);
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

        if($query_arguments_count != count($requested_args)){
            trigger_error(Ak::t('Argument list did not match expected set. Requested arguments are:').join(', ',$requested_args),E_USER_ERROR);
            return $GLOBALS['false'];
        }

        $true_bool_values = array(true,1,'true','True','TRUE','1','y','Y','yes','Yes','YES','s','Si','SI','V','v','T','t');

        foreach ($requested_args as $k=>$v){
            switch ($this->getColumnType($v)) {
                case 'boolean':
                $query_values[$k] = in_array($query_values[$k],$true_bool_values) ? 1 : 0;
                break;

                case 'date':
                case 'datetime':
                $query_values[$k] = str_replace('/','-', $this->castAttributeForDatabase($k,$query_values[$k],false));
                break;

                default:
                break;
            }
        }

        $_find_arguments = array();
        $_find_arguments[] = $fetch;
        $_find_arguments[] = $new_sql;
        foreach ($query_values as $value){
            $_find_arguments[] = $value;
        }
        $_find_arguments[] = $options;

        $_result =& $this->find('set arguments', $_find_arguments);
        $result =& $_result; // Pass by reference hack
        return $result;
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
            $inheritance_model_name = AkInflector::modulize($inheritance_column);
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

        (AK_CLI && AK_ENVIRONMENT == 'development') ? $object ->toString() : null;

        $object->_buildFinders();
        $object->loadAssociations();

        return $object;
    }



    function constructFinderSql($options, $select_from_prefix = 'default')
    {
        $sql = isset($options['select_prefix']) ? $options['select_prefix'] : ($select_from_prefix == 'default' ? 'SELECT * FROM '.$this->getTableName() : $select_from_prefix);
        $sql  .= !empty($options['joins']) ? ' '.$options['joins'] : '';


        if(isset($options['conditions'])){
            $this->addConditions($sql, $options['conditions']);
        }elseif ($this->getInheritanceColumn() !== false){
            $this->addConditions($sql, array());
        }

        // Create an alias for order
        if(empty($options['order']) && !empty($options['sort'])){
            $options['order'] = $options['sort'];
        }

        $sql  .= !empty($options['order']) ? ' ORDER BY  '.$options['order'] : '';

        return $sql;
    }


    /**
    * Adds a sanitized version of $conditions to the $sql string. Note that the passed $sql string is changed.
    */
    function addConditions(&$sql, $conditions = null)
    {
        $concat = empty($sql) ? '' : ' WHERE ';
        if(!empty($conditions)){
            $sql  .= $concat.$conditions;
            $concat = ' AND ';
        }

        if($this->descendsFromActiveRecord($this) && $this->getInheritanceColumn() !== false){
            $type_condition = $this->typeCondition();
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


    function typeCondition()
    {
        $inheritance_column = $this->getInheritanceColumn();
        $type_condition = array();
        $table_name = $this->getTableName();
        $available_types = array_merge(array($this->getModelName()),$this->getSubclasses());
        foreach ($available_types as $subclass){
            $type_condition[] = ' '.$table_name.'.'.$inheritance_column.' = \''.AkInflector::demodulize($subclass).'\' ';
        }
        return empty($type_condition) ? '' : '('.join('OR',$type_condition).') ';
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

    function _quoteColumnName($column_name)
    {
        return $this->_db->nameQuote.$column_name.$this->_db->nameQuote;
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
    */
    function parseAkelosArgs(&$args)
    {
        $params = array();
        foreach ($args as $k=>$v){
            if($k % 2 == 0 && is_string($v) && (substr($v,-2) == '->' || substr($v,-2) == '=>')){
                $key = trim($v, '=-> ');
            }elseif(isset($key)) {
                $params[$key] = $v;
                unset($key);
            }
        }
        $args = count($params) > 0 ? $params : $args;
    }

    // EXPERIMENTAL: Will allow to create finders when PHP includes aggregate_methods as a stable feature
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
    * New objects can be instantiated as either empty (pass no construction parameter) or pre-set with attributes but not yet saved
    * (pass a hash with key names matching the associated table column names). 
    * In both instances, valid attribute keys are determined by the column names of the associated table ? hence you can't 
    * have attributes that aren't part of the table columns.
    */
    function newRecord($attributes)
    {
        $this->_newRecord = true;
        if(isset($attributes) && !is_array($attributes)){
            $attributes = func_get_args();
            $this->parseAkelosArgs($attributes);
        }
        $attributes = $this->attributesFromColumnDefinition($attributes);

        foreach ($attributes as $k=>$v){
            $this->setAttribute($k, $v);
        }
        $this->composeCombinedAttributes();
    }


    function setAttribute($attribute, $value, $inspect_for_callback_child_method = true, $compose_after_set = true)
    {
        static $watchdog;

        if($attribute[0] == '_'){
            return false;
        }

        if($this->isFrozen()){
            return false;
        }

        if($inspect_for_callback_child_method === true && method_exists($this,'set'.ucfirst($attribute))){
            $watchdog[$attribute] = @$watchdog[$attribute]+1;
            if($watchdog[$attribute] == 5000){
                if((!defined('AK_ACTIVE_RECORD_PROTECT_SET_RECURSION')) || defined('AK_ACTIVE_RECORD_PROTECT_SET_RECURSION') && AK_ACTIVE_RECORD_PROTECT_SET_RECURSION){
                    trigger_error(Ak::t('You are calling recursivelly AkActiveRecord::setAttribute by placing parent::setAttribute() or  parent::set() on your model "%method" method. In order to avoid this, set the 3rd paramenter of parent::setAttribute to FALSE. If this was the behaviour you expected, please define the constant AK_ACTIVE_RECORD_PROTECT_SET_RECURSION and set it to false',array('%method'=>'set'.ucfirst($attribute))),E_USER_ERROR);
                    return false;
                }
            }
            $this->{$attribute.'_before_type_cast'} = $value;
            return $this->{'set'.ucfirst($attribute)}($value);
        }
        if($this->hasAttribute($attribute)){
            $this->{$attribute.'_before_type_cast'} = $value;
            $this->$attribute = $value;
            if($compose_after_set && !$this->requiredForCombination($attribute)){
                $combined_attributes = $this->_getCombinedAttributesWhereThisAttributeIsUsed($attribute);
                foreach ($combined_attributes as $combined_attribute){
                    $this->composeCombinedAttribute($combined_attribute);
                }
            }
            if ($compose_after_set && $this->isCombinedAttribute($attribute)){
                $this->decomposeCombinedAttribute($attribute);
            }
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

    function getAttribute($attribute, $inspect_for_callback_child_method = true)
    {
        static $watchdog;

        if($attribute[0] == '_'){
            return false;
        }

        if($inspect_for_callback_child_method === true && method_exists($this,'get'.AkInflector::camelize($attribute))){
            $watchdog[@$attribute] = @$watchdog[$attribute]+1;
            if($watchdog[$attribute] == 66){
                if((!defined('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION')) || defined('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION') && AK_ACTIVE_RECORD_PROTECT_GET_RECURSION){
                    trigger_error(Ak::t('You are calling recursivelly AkActiveRecord::getAttribute by placing parent::getAttribute() or  parent::get() on your model "%method" method. In order to avoid this, set the 2nd paramenter of parent::getAttribute to FALSE. If this was the behaviour you expected, please define the constant AK_ACTIVE_RECORD_PROTECT_GET_RECURSION and set it to false',array('%method'=>'get'.ucfirst($attribute))),E_USER_ERROR);
                    return false;
                }
            }
            $value = $this->{'get'.ucfirst($attribute)}();
            return $this->getInheritanceColumn() != false  ? AkInflector::demodulize($value) : $value;
        }
        if(isset($this->$attribute) || (!isset($this->$attribute) && $this->isCombinedAttribute($attribute))){
            if($this->hasAttribute($attribute)){
                if ($this->isCombinedAttribute($attribute)){
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
    * Allows you to set all the attributes at once by passing in an array with keys matching the attribute names (which again matches the column names). Sensitive attributes can be protected from this form of mass-assignment by using the $this->setProtectedAttributes method. Or you can alternatively specify which attributes can be accessed in with the $this->setAccessibleAttributes method. Then all the attributes not included in that won?t be allowed to be mass-assigned.
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

    function removeAttributesProtectedFromMassAssignment($attributes)
    {
        if(!empty($this->_accessibleAttributes) && is_array($this->_accessibleAttributes) &&  is_array($attributes)){
            foreach (array_keys($attributes) as $k){
                if(!in_array($k,$this->_accessibleAttributes)){
                    unset($attributes[$k]);
                }
            }
        }elseif (!empty($this->_protectedAtributes) && is_array($this->_protectedAtributes) &&  is_array($attributes)){
            foreach (array_keys($attributes) as $k){
                if(in_array($k,$this->_protectedAtributes)){
                    unset($attributes[$k]);
                }
            }
        }
        return $attributes;
    }


    /**
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
    * $this->setAttribute('date', '2008-11-30');
    * All the 'year', 'month' and 'day' getters will be fired (if they exist) the following attributes will be set
    * $this->year // will be 2008
    * $this->month // will be 11 and
    * $this->day // will be 27
    * 
    * Sometimes you might need a pattern for composing and another for decomposing attributes. In this case you can specify
    * an array as the pattern values, where first element will be the composing pattern and second element will be used
    * for decomposing.
    * 
    * You can also specify a callback method from this object function instead of a pattern. You can also assign a callback
    * for composing and another for decomposing by passing their names as an array like on the patterns.
    *
    * 
    <?php 
    class User extends AkActiveRecord 
    { 
        function User()
        {
            // You can use a multiple patterns array where "%s, %s" will be used for combining fields and "%[^,], %s" will be used
            // for decomposing fields. (as you can see you can also use regular expressions on your patterns)
            $User->addCombinedAttributeConfiguration('name', array("%s, %s","%[^,], %s"), 'last_name', 'first_name');
            
            //Here we set email_link so compose_email_link() will be triggered for building up the field and parse_email_link will
            // be used for getting the fields out
            $User->addCombinedAttributeConfiguration('email_link', array("compose_email_link","parse_email_link"), 'email', 'name');

        }
        function compose_email_link()
        {
            $args = func_get_arg(0); 
            return "<a href=\'mailto:{$args[\'email\']}\'>{$args[\'name\']}</a>"; 
        } 
        function parse_email_link($email_link) 
        { 
            $results = sscanf($email_link, "<a href=\'mailto:%[^\']\'>%[^<]</a>"); 
            return array(\'email\'=>$results[0],\'name\'=>$results[1]); 
        } 
        
    } 
?>
    * @param $attribute
    * @param $mapping
    */
    function addCombinedAttributeConfiguration($attribute)
    {
        $args = func_get_args();
        $columns = array_slice($args,2);
        $invalid_columns = array();
        foreach ($columns as $colum){
            if(!$this->hasAttribute($colum)){
                $invalid_columns[] = $colum;
            }
        }
        //$invalid_columns = array_diff($columns, array_filter($columns, array(&$this,'hasAttribute'))); // This made PHP Crash sometimes
        if(!empty($invalid_columns)){
            trigger_error(Ak::t('There was an error while setting the composed field "%field_name", the following mapping column/s "%columns" do not exist',
            array('%field_name'=>$args[0],'%columns'=>join(', ',$invalid_columns))), E_USER_ERROR);
        }else{
            $attribute = array_shift($args);
            $this->_combinedAttributes[$attribute] = $args;
            $this->composeCombinedAttributes();
        }
    }

    /**
    * Use a printf pattern to glue sub-attributes into an attribute. Sub-attribute getters will are fired (if they exist).
    */
    function composeCombinedAttributes()
    {

        if(!empty($this->_combinedAttributes)){
            $attributes = array_keys($this->_combinedAttributes);
            foreach ($attributes as $attribute){
                $this->composeCombinedAttribute($attribute);
            }
        }
    }
    /*
    if(!empty($this->_combinedAttributes)){
    foreach ($this->_combinedAttributes as $attribute=>$rule){
    $pattern = $rule[0];
    $ary = array();
    $ary = array();
    for ($x=1;$x<count($rule);$x++){
    $subattribute = $rule[$x];
    $ary[$subattribute] = $this->getAttribute($subattribute);
    }
    $this->$attribute = method_exists(&$this, $pattern.'Compose') ? $this->{$pattern.'Compose'}($ary) : vsprintf($pattern, $ary);
    }
    }
    */
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



    /**
    * Use a scanf pattern match to break an attribute down into it's sub-attributes.
    */
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
    * Initializes the attribute to zero if null and subtracts one. Only makes sense for number-based attributes. Returns attribute value.
    */
    function decrementAttribute($attribute)
    {
        if(!isset($this->$attribute)){
            $this->setAttribute($attribute, 0);
            return 0;
        }else {
            $value = $this->getAttribute($attribute) -1;
            $this->setAttribute($attribute, $value);
            return $value;
        }
    }

    /**
    * Decrements the attribute and saves the record.
    */
    function decrementAndSaveAttribute($attribute)
    {
        $value = $this->decrementAttribute($attribute);
        if($this->updateAttribute($attribute, $value)){
            return $value;
        }
        return false;
    }


    /**
    * Initializes the attribute to zero if null and adds one. Only makes sense for number-based attributes. Returns attribute value.
    */
    function incrementAttribute($attribute)
    {
        if(!isset($this->$attribute)){
            $this->setAttribute($attribute, 0);
            return 0;
        }else {
            $value = $this->getAttribute($attribute) +1;
            $this->setAttribute($attribute, $value);
            return $value;
        }
    }

    /**
    * Increments the attribute and saves the record.
    */
    function incrementAndSaveAttribute($attribute)
    {
        $value = $this->incrementAttribute($attribute);
        if($this->updateAttribute($attribute, $value)){
            return $value;
        }
        return false;
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
     * This function is usefull in case you need to know if attribtes have been assigned to an object.
     */
    function hasAttributesDefined()
    {
        $attributes = join('',$this->getAttributes());
        return empty($attributes);
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

    function createOrUpdate($validate = true)
    {
        if($validate && !$this->isValid() || !($this->isNewRecord() ? $this->afterValidationOnCreate() : $this->afterValidationOnUpdate())){
            $this->transactionFail();
            return false;
        }
        return $this->isNewRecord() ? $this->_create() : $this->_update();
    }


    /**
    * Turns an attribute that?s currently true into false and vice versa. Returns attribute value.
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

    /**
    * Every Active Record class must use "id" as their primary ID. This getter overwrites the native id method, which isn?t being used in this context.
    */
    function getId()
    {
        return $this->{$this->getPrimaryKey()};
    }

    function quotedId()
    {
        return $this->castAttributeForDatabase($this->getPrimaryKey(), $this->getId());
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


    /**
        Quote strings appropriately for SQL statements.
        that a new instance, or one populated from a passed-in array, still has all the attributes
        that instances loaded from the database would.
    */
    function attributesFromColumnDefinition($attributes)
    {
        $filtered_attributes = array();
        if(is_array($attributes)){
            foreach ($attributes as $k=>$v){
                if($this->hasAttribute($k) &&  !in_array($k, $this->_protectedAtributes)){
                    $filtered_attributes[$k] = $v;
                }
            }
        }
        return $filtered_attributes;
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


    /**
    * Resets all the cached information about columns, which will cause they to be reloaded on the next request.
    */
    function resetColumnInformation()
    {
        if(isset($_SESSION['__activeRecordColumnsSettingsCache'][$this->getModelName()])){
            unset($_SESSION['__activeRecordColumnsSettingsCache'][$this->getModelName()]);
        }
        $this->_columnNames = $this->_columns = $this->_columnsSettings = $this->_contentColumns = array();
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


    /**
    * Returns an array of all the attributes that have been specified for serialization as keys and the objects as values.
    */
    function getSerializedAttributes()
    {
        return isset($this->_serializedAttributes) ? $this->_serializedAttributes : array();
    }



    function t($string, $array = null)
    {
        return Ak::t($string, $array, AkInflector::underscore($this->getModelName()));
    }

    function getAvailableCombinedAttributes()
    {
        $combined_attributes = array();
        foreach ($this->_combinedAttributes as $attribute=>$details){
            $combined_attributes[$attribute] = array('name'=>$attribute, 'type'=>'string', 'path' => array_shift($details), 'uses'=>$details);
        }
        return !empty($this->_combinedAttributes) && is_array($this->_combinedAttributes) ? $combined_attributes : array();
    }

    function getAvailableAttributes()
    {
        return array_merge($this->getColumns(), $this->getAvailableCombinedAttributes());
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


    function getAttributeCaption($attribute)
    {
        return $this->t(AkInflector::humanize($attribute));
    }

    /**
    * Returns an array of column objects for the table associated with this class.
    */
    function getColumns()
    {
        if(empty($this->_columns)){
            $column_settings = $this->getColumnSettings();
            foreach ($column_settings as $column=>$settings){
                $this->_columns[$column] = $settings;
            }
        }
        return $this->_columns;
    }

    function getColumnSettings()
    {
        if(empty($this->_columnsSettings)){
            $this->loadColumnsSettings();
            $this->initiateColumnsToNull();
        }
        return $this->_columnsSettings;
    }

    function loadColumnsSettings()
    {
        $model_name = $this->getModelName();

        if(is_null($this->_db)){
            $this->setConnection();
        }

        if((empty($this->_columnsSettings) && empty($_SESSION['__activeRecordColumnsSettingsCache'][$model_name.'_column_settings'])) || (AK_ENVIRONMENT != 'development' && !defined('AK_AVOID_ACTIVE_RECORD_DB_SCHEMA_CACHE'))){
            if(empty($this->_dataDictionary)){
                $this->_dataDictionary =& NewDataDictionary($this->_db);
            }

            $column_objects = $this->_databaseTableInternals($this->getTableName());

            if(!is_array($column_objects)){
                trigger_error(Ak::t('Ooops! Could not fetch details for the table %table_name.', array('%table_name'=>$this->getTableName())), E_USER_ERROR);
                return false;
            }else{
                foreach ($column_objects as $column_object){
                    $this->setColumnSettings($column_object->name, $column_object);
                }
            }
            $_SESSION['__activeRecordColumnsSettingsCache'][$model_name.'_column_settings'] = $this->_columnsSettings;
        }else{
            $this->_columnsSettings = $_SESSION['__activeRecordColumnsSettingsCache'][$model_name.'_column_settings'];
        }

        return $this->_columnsSettings;
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
            $this->_primaryKey = $column_object->name;
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
        if(isset($column_object->default_value)){
            $this->_columnsSettings[$column_name]['defaultValue'] = $column_object->default_value;
        }
    }



    /**
     * Active Record localization support methods
     */



    function _isInternationalizeCandidate($column_name)
    {
        $pos = strpos($column_name,'_');
        return $pos === 2 && in_array(substr($column_name,0,$pos),$this->getAvaliableLocales());
    }

    function _addInternationalizedColumn($column_name)
    {
        $this->_columnsSettings[$column_name]['i18n'] = true;
    }

    function getInternationalizedColumns()
    {
        static $cache;
        $model = $this->getModelName();
        $available_locales = $this->getAvaliableLocales();
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

    function getAvaliableLocales()
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
            $available_locales = $this->getAvaliableLocales();
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
        foreach ($this->getAvaliableLocales() as $locale){
            if($this->hasColumn($locale.'_'.$attribute)){
                $attribute_locales[$locale] = $this->getAttributeByLocale($attribute, $locale);
            }
        }
        return $attribute_locales;
    }



    /**
     * Adds an internationalized attribute to an array containing other locales for the same column name
     * 
     * Example:
     *  es_title and en_title will be available unser title = array('es'=>'...', 'en' => '...')
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


    /**/




    function initiateAttributeToNull($attribute)
    {
        if(!isset($this->$attribute)){
            $this->setAttribute($attribute, null, false);
        }
    }

    function initiateColumnsToNull()
    {
        array_map(array(&$this,'initiateAttributeToNull'),array_keys($this->_columnsSettings));
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

        'D' => array('datetime', 'date'), //  Date (some databases do not support this, so we return a datetime type)
        'T' =>  array('datetime', 'timestamp'), //Datetime or Timestamp
        'L' => 'boolean', // Integer field suitable for storing booleans (0 or 1)
        'R' => 'serial', // Serial Integer
        'I' => 'integer', // Integer (mapped to I4)
        'I1' => 'integer', // 1-byte integer
        'I2' => 'integer', // 2-byte integer
        'I4' => 'integer', // 4-byte integer
        'I8' => 'integer', // 8-byte integer
        'F' => 'float', // Floating point number
        'N' => 'integer' //  Numeric or decimal number
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
            if($result == 'integer' && (int)$adodb_column_object->max_length === 1 &&
            stristr($adodb_column_object->type, 'TINYINT')){
                return 'boolean';
            }
        }elseif($this->_getDatabaseType() == 'postgre'){
            if($adodb_column_object->type == 'timestamp' || $result == 'datetime'){
                $adodb_column_object->max_length = 19;
            }

            if($result == 'boolean' && (int)$adodb_column_object->max_length !== 1 && stristr($adodb_column_object->type, 'NUMERIC')){
                return 'integer';
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
    * Returns current model name
    */
    function getModelName()
    {
        if(!isset($this->_modelName)){
            if(!$this->setModelName()){
                trigger_error(Ak::t('Unable to fetch current model name'),E_USER_NOTICE);
            }
        }
        return $this->_modelName;
    }

    /**
    * Sets current model name
    *
    * Use this option if the model name can't be guessed by the Active Record
    */
    function setModelName($model_name = null)
    {
        if(!empty($model_name)){
            $this->_modelName = $model_name;
        }else{
            $this->_modelName = $this->_getModelName(get_class($this));
        }
        return true;
    }

    /**
     * This method will nicely handle model names even  on 
     * PHP where class names are all lowercased
     */
    function _getModelName($class_name)
    {
        if(AK_PHP5){
            return $class_name;
        }
        $included_models = $this->_getIncludedModelNames();
        if(!in_array($class_name, $included_models)){
            $class_name = strtolower($class_name);
            foreach ($included_models as $included_model){
                if($class_name == strtolower($included_model)){
                    return $included_model;
                }
            }

            trigger_error(Ak::t('The Akelos Framework could not automatically configure your model name.'.
            ' This might be caused because your model file is not located on %path. Please call $this->setModelName("YourModelName");'.
            ' in your model constructor in order to make this work.',array('%path'=>AK_MODELS_DIR.DS)), E_USER_ERROR);
            return false;
        }
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








    function getParentModelName()
    {
        if(!isset($this->_parentModelName)){
            if(!$this->setParentModelName()){
                return false;
            }
        }
        return $this->_parentModelName;
    }

    function setParentModelName($model_name = null)
    {
        $got_errors = false;
        if(!empty($model_name)){
            $this->_parentModelName = $model_name;
        }else{
            $class_name = AkInflector::modulize(get_parent_class($this));
            if(!AK_PHP5){
                $included_models = $this->_getIncludedModelNames();
                if(!in_array($class_name, $included_models)){
                    $class_name = strtolower($class_name);
                    foreach ($included_models as $included_model){
                        if($class_name == strtolower($included_model)){
                            $this->_parentModelName = $included_model;
                            return true;
                        }
                    }
                    $got_errors = true;
                }
            }
            if($got_errors || $class_name == 'AkActiveRecord'){
                trigger_error(Ak::t('The Akelos Framework could not automatically configure your model name.'.
                ' This might be caused because your model file is not located on %path. Please call $this->setParentModelName("YourParentModelName");'.
                ' in your model constructor in order to make this work.',array('%path'=>AK_MODELS_DIR.DS)), E_USER_ERROR);
                return false;
            }
            $this->_parentModelName = $class_name;
        }
        return true;
    }

    function _getIncludedModelNames()
    {
        $included_files = get_included_files();
        $models = array();
        foreach ($included_files as $file_name){
            if(strstr($file_name,AK_MODELS_DIR)){
                $models[] = AkInflector::modulize(str_replace(array(AK_MODELS_DIR.DS,'.php'),'',$file_name));
            }
        }
        return $models;
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
        static $available_tables;
        if(empty($table_name)){
            $table_name = AkInflector::tableize($this->getModelName());
        }
        if($check_for_existence){
            if(!isset($available_tables) || $check_mode){
                if(!isset($this->_db)){
                    $this->setConnection();
                }
                if(empty($_SESSION['__activeRecordColumnsSettingsCache']['available_tables']) || (AK_ENVIRONMENT != 'development' && !defined('AK_AVOID_ACTIVE_RECORD_DB_SCHEMA_CACHE'))){
                    $_SESSION['__activeRecordColumnsSettingsCache']['available_tables'] = $this->_db->MetaTables();
                }
                $available_tables = $_SESSION['__activeRecordColumnsSettingsCache']['available_tables'];
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

    /**
    * Returns true if given attribute exists for this Model.
    *
    * @param string $attribute
    * @return boolean
    */
    function hasAttribute ($attribute)
    {
        $cols = $this->getColumns();
        return isset($cols[$attribute]) || $this->isCombinedAttribute($attribute);
    }

    /**
    * Returns true if given attribute is a combined attribute for this Model.
    *
    * @param string $attribute
    * @return boolean
    */
    function isCombinedAttribute ($attribute)
    {
        return !empty($this->_combinedAttributes) && array_key_exists($attribute, $this->_combinedAttributes);
    }

    /**
    * Returns true if given attribute exists for this Model.
    *
    * @param string $name Name of table to look in
    * @return boolean
    */
    function hasColumn($column)
    {
        return array_key_exists($column, $this->getColumns());
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
                    echo "<ol>\n";
                    while (list ($key,$value) = each ($data)) {
                        if($key{0} == '_'){
                            continue;
                        }
                        $type=gettype($value);
                        if ($type=="array") {
                            printf ("<li>(%s) <b>%s</b>:\n",$type, $key);
                            Ak::debug ($value,$sf);
                        }elseif($type == "object"){
                            if(method_exists($value,'hasColumn') && $value->hasColumn($key)){
                                printf ("<li>(%s) <b>%s</b>:\n",$type, $key);
                                Ak::debug ($value,$sf);
                            }
                        }elseif (eregi ("function", $type)) {
                            if ($sf) {
                                printf ("<li>(%s) <b>%s</b> </li>\n",$type, $key, $value);
                            }
                        } else {
                            if (!$value) {
                                $value="(none)";
                            }
                            printf ("<li>(%s) <b>%s</b> = %s</li>\n",$type, $key, $value);
                        }
                    }
                    echo "</ol>fin.\n";
                } else {
                    echo "(empty)";
                }
            }
        }
    }



    function removeUnavailableAttributes($attributes)
    {
        $table_name = $this->getTableName();
        $ret_attributes = array();
        if(!empty($attributes) && is_array($attributes)){
            $available_attributes = $this->getAvailableAttributes();
            foreach ($attributes as $k=>$v){
                $k = str_replace($table_name.'.','',$k);
                if(isset($available_attributes[$k]['name'][$k])){
                    $ret_attributes[$k] = $v;
                }
            }
        }
        return $ret_attributes;
    }

    function removeUnavailableColumns(&$attributes)
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


    function castAttributeForDatabase($column_name, $value, $add_quotes = true)
    {
        $result = '';//$add_quotes ? "''" : ''; // $result = $add_quotes ? $this->_db->qstr('') : '';
        switch ($this->getColumnType($column_name)) {
            case 'datetime':
            if(!empty($value)){
                $date_time = $this->_db->DBTimeStamp($this->getValueForDateColumn($column_name, $value));
                $result = $add_quotes ? $date_time : trim($date_time ,"'");
            }else{
                $result = 'null';
            }
            break;

            case 'date':
            if(!empty($value)){
                $date = $this->_db->DBDate($this->getValueForDateColumn($column_name, $value));
                $result = $add_quotes ? $date : trim($date, "'");
            }else{
                $result = 'null';
            }
            break;

            case 'boolean':
            $result = !empty($value) ? '1' : '0';
            break;

            case 'binary':

            if($this->_getDatabaseType() == 'postgre'){
                $result =  " '".$this->_db->BlobEncode($value)."'::bytea ";
            }else{
                $result = $add_quotes ? $this->_db->qstr($value) : $value;
            }

            break;

            case 'serial':
            case 'integer':
            case 'float':
            $result = (empty($value) && @$value !== 0) ? 'null' : (is_numeric($value) ? $value : $this->_db->qstr($value));
            $result = !empty($this->_columns[$column_name]['notNull']) && $result == 'null' && $this->_getDatabaseType() == 'sqlite' ? '0' : $result;
            break;

            default:
            $value = ($column_name=='password' && !empty($value)) ? $this->_castPassword($value) : $value;
            $result = $add_quotes ? $this->_db->qstr($value) : $value;
            break;
        }

        return empty($this->_columns[$column_name]['notNull']) ? ($result === '' ? "''" : $result) : ($result == 'null' ? '' : $result);
    }

    function _castPassword($password)
    {
        if(!empty($this->_automated_password_obfuscation)){
            $password = md5($password);
        }
        return $password;
    }

    function castAttributeFromDatabase($column_name, $value)
    {
        if($this->hasColumn($column_name)){
            switch ($this->getColumnType($column_name)) {
                case 'date':
                if(!empty($value) && strstr(trim($value),' ')){
                    return str_replace(substr($value,strpos($value,' ')), '', $value);
                }
                break;

                case 'binary':
                if($this->_getDatabaseType() == 'postgre'){
                    return $this->_db->BlobDecode($value);
                }else{
                    return $value;
                }

                case 'boolean':
                return (integer)$value === 1 ? true : false;
                break;

                default:
                return $column_name == 'password' ? '' : $value;
                break;
            }
        }
        return $value;
    }

    function _addBlobQueryStack($column_name, $blob_value)
    {
        $this->_BlobQueryStack[$column_name] = $blob_value;
    }

    function _updateBlobFields($condition)
    {
        if(!empty($this->_BlobQueryStack) && is_array($this->_BlobQueryStack)){
            foreach ($this->_BlobQueryStack as $column=>$value){
                $this->_db->UpdateBlob($this->getTableName(), $column, $value, $condition);
            }
            $this->_BlobQueryStack = null;
        }
    }

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

    function getValueForDateColumn($column_name, $value)
    {
        if(!$this->isNewRecord()){
            if(($column_name == 'updated_on' || $column_name == 'updated_at') && empty($value)){
                return null;
            }
        }elseif(($column_name == 'created_on' || $column_name == 'created_at') && empty($value)){
            return Ak::time();
        }elseif($column_name == 'expires_on' || $column_name == 'expires_at'){
            return empty($value) ? Ak::getTimestamp('9999-12-31 23:59:59') : Ak::getTimestamp($value);
        }

        return Ak::getTimestamp($value);
    }

    /**
    * Updates the associated record with values matching those of the instance attributes.
    * Must be called as a result of a call to createOrUpdate.
    */
    function _update()
    {
        if($this->isFrozen()){
            $this->transactionFail();
            return false;
        }
        if($this->beforeUpdate()){
            $this->notifyObservers('beforeUpdate');

            if($this->_recordTimestamps){
                if ($this->hasColumn('updated_at')){
                    $this->setAttribute('updated_at', Ak::getDate());
                }
                if ($this->hasColumn('updated_on')){
                    $this->setAttribute('updated_on', Ak::getDate(null, 'Y-m-d'));
                }
            }


            $lock_check = '';
            if ($this->isLockingEnabled()){
                $previous_value = $this->lock_version;
                $this->setAttribute('lock_version', $previous_value + 1);
                $lock_check = ' AND lock_version = '.$previous_value;
            }

            $quoted_attributes = $this->getAvailableAttributesQuoted();

            if(!empty($quoted_attributes)){
                $sql = 'UPDATE '.$this->getTableName().' '.
                'SET '.join(', ', $quoted_attributes) .' '.
                'WHERE '.$this->getPrimaryKey().'='.$this->quotedId().$lock_check;
            }

            if(!$this->_db->Execute($sql)){
                $this->transactionFail();
                AK_DEBUG ? trigger_error($this->_db->ErrorMsg(), E_USER_NOTICE) : null;
            }

            if ($this->isLockingEnabled()){
                if($this->_db->Affected_Rows() != 1){
                    $this->setAttribute('lock_version', $previous_value);
                    $this->transactionFail();
                    trigger_error(Ak::t('Attempted to update a stale object'), E_USER_NOTICE);
                    return false;
                }
            }

            if(!$this->transactionHasFailed()){
                if($this->afterUpdate()){
                    $this->notifyObservers('afterUpdate');
                }else {
                    $this->transactionFail();
                }
            }

        }else{
            $this->transactionFail();
        }
        return $this;
    }


    /////////////////// OPTIMISTIC LOCKING //////////////////////////////

    /**
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
        return ((isset($this->lock_optimistically) && $this->lock_optimistically !== false) || !isset($this->lock_optimistically)) && $this->hasColumn('lock_version');
    }


    /**
    * Creates a new record with values matching those of the instance attributes.
    * Must be called as a result of a call to createOrUpdate.
    */
    function _create()
    {
        if($this->isFrozen()){
            $this->transactionFail();
            return false;
        }

        if($this->beforeCreate()){

            $this->notifyObservers('beforeCreate');

            if($this->_recordTimestamps){
                if ($this->hasColumn('created_at')){
                    $this->setAttribute('created_at', Ak::getDate());
                }
                if ($this->hasColumn('created_on')){
                    $this->setAttribute('created_on', Ak::getDate(null, 'Y-m-d'));
                }

                if(isset($this->expires_on)){
                    if(isset($this->expires_at) && $this->hasColumn('expires_at')){
                        $this->setAttribute('expires_at',Ak::getDate(strtotime($this->expires_at) + (defined('AK_TIME_DIFFERENCE') ? AK_TIME_DIFFERENCE*60 : 0)));
                    }elseif(isset($this->expires_on) && $this->hasColumn('expires_on')){
                        $this->setAttribute('expires_on',Ak::getDate(strtotime($this->expires_on) + (defined('AK_TIME_DIFFERENCE') ? AK_TIME_DIFFERENCE*60 : 0), 'Y-m-d'));
                    }
                }
            }

            $attributes = $this->removeUnavailableColumns($this->getAttributes());

            if($this->isLockingEnabled()){
                $attributes['lock_version'] = 1;
                $this->setAttribute('lock_version',1);
            }

            $pk = $this->getPrimaryKey();
            $table = $this->getTableName();

            foreach ($attributes as $column=>$value){
                $attributes[$column] = $this->castAttributeForDatabase($column,$value);
            }

            /**
            * @todo sanitize attributes
            * 'beforeValidationOnCreate', 'afterValidationOnCreate'
            */
            if(!isset($this->_generateSequence) || (isset($this->_generateSequence) && $this->_generateSequence !== false)){
                if((empty($attributes[$pk]) || (!empty($attributes[$pk]) && (integer)$attributes[$pk] > 0 ))){
                    if($this->_getDatabaseType() != 'mysql'){
                        $table_details = $this->_databaseTableInternals('seq_'.$table);
                        if(!isset($table_details['ID'])){
                            $this->_db->CreateSequence('seq_'.$table);
                        }
                        $attributes[$pk] = $this->_db->GenID('seq_'.$table);
                    }
                }
            }

            $__attributes = $attributes;
            $attributes = array_diff($attributes, array('',"''"));

            $sql = 'INSERT INTO '.$table.' '.
            '('.join(', ',array_keys($attributes)).') '.
            'VALUES ('.join(',',array_values($attributes)).')';

            if(!$this->_db->Execute($sql)){
                AK_DEBUG ? trigger_error($this->_db->ErrorMsg(), E_USER_NOTICE) : null;
            }

            $id = !empty($attributes[$pk]) ? $attributes[$pk] : $this->_db->Insert_ID($table, $pk);
            $this->setId($id);

            if(!$this->transactionHasFailed()){
                $this->_newRecord = false;
                if(!$this->afterCreate()){
                    $this->transactionFail();
                }else{
                    $this->notifyObservers('afterCreate');
                }
            }
        }else{
            $this->transactionFail();
        }
        return $this;
    }



    /**#@+
    *
    * @todo Investigate a way for setting up callbacks using attributes without causing to much overhead.
    *
    * Callbacks are hooks into the lifecycle of an Active Record object that allows you to trigger logic
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
    *   class CreditCard extends AkActiveRecord
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
    *   class Subscription extends AkActiveRecord
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
    *   class Firm extends AkActiveRecord
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
    * == Cancelling callbacks ==
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
    function afterCreate(){return true;}
    function afterDestroy(){return true;}
    function beforeDestroy(){return true;}
    function afterSave(){return true;}
    /**#@-*/


    function toString($print = false)
    {
        $result = '';
        if(!AK_CLI || AK_ENVIRONMENT == 'testing'){
            $result = "<h2>Details for ".AkInflector::humanize(AkInflector::underscore($this->getModelName()))." with ".$this->getPrimaryKey()." ".$this->getId()."</h2>\n<dl>\n";
            foreach ($this->getColumnNames() as $column=>$caption){
                $result .= "<dt>$caption</dt>\n<dd>".$this->getAttribute($column)."</dd>\n";
            }
            $result .= "</dl>\n<hr />";
            if($print){
                echo $result;
            }
        }elseif(AK_ENVIRONMENT == 'development'){
            $result =   "\n".
            str_replace("\n"," ",var_export($this->getAttributes(),true));
            /*
            "Details for ".AkInflector::humanize(AkInflector::underscore($this->getModelName()))." with ".$this->getPrimaryKey()." ".$this->getId()."\n".
            "-----------------------------------------\n";
            foreach ($this->getColumnNames() as $column=>$caption){
            $result .= "- $caption: ".$this->getAttribute($column)."\n";
            }
            */
            $result .= "\n";
            echo $result;
            return '';
        }
        return $result;
    }



    function toJson()
    {
        return Ak::toJson($this->getAttributes());
    }


    /**#@+
    * Transaction support for database operations
    *
    * Transactions are enabled automatically for Active record objects, But you can nest transactions within models.
    * This transactions are nested, and only the othermost will be executed
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
        if(!$this->isConnected()){
            $this->setConnection();
        }
        return $this->_db->StartTrans();
    }

    function transactionComplete()
    {
        return $this->_db->CompleteTrans();
    }

    function transactionFail()
    {
        return $this->_db->FailTrans();
    }

    function transactionHasFailed()
    {
        return $this->_db->HasFailedTrans();
    }

    /**#@-*/




    ////////////////////////////////////////////////////////////////////
    //////////          VALIDATION      ////////////////////////////////
    ////////////////////////////////////////////////////////////////////


    /**
    * Active Records implement validation by overwriting AkActiveRecord::validate (or the variations, validateOnCreate and
    * validateOnUpdate). Each of these methods can inspect the state of the object, which usually means ensuring
    * that a number of attributes have a certain value (such as not empty, within a given range, matching a certain regular expression).
    * 
    * Example:
    * 
    *   class Person extends AkActiveRecord
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
    * An "Errors" object is automatically created for every Active Record.
    * 
    */

    /**
      * Encapsulates the pattern of wanting to validate a password or email address field with a confirmation. Example:
      * 
      *  Model:
      *     class Person extends AkActiveRecord
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
        $attribute_names = is_array($attribute_names) ? $attribute_names : array($attribute_names);
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
      * class Person extends AkActiveRecord
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

        $attribute_names = is_array($attribute_names) ? $attribute_names : array($attribute_names);
        foreach ($attribute_names as $attribute_name){
            if(@$this->$attribute_name != $accept){
                $this->addError($attribute_name, $message);
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

        $attribute_names = is_array($attribute_names) ? $attribute_names : array($attribute_names);
        foreach ($attribute_names as $attribute_name){
            $this->addErrorOnBlank($attribute_name, $message);
        }
    }

    /**
      * Validates that the specified attribute matches the length restrictions supplied. Only one option can be used at a time:
      * 
      * class Person extends AkActiveRecord
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
      * NOTE: Be aware that $this->validatesLengthOf('field', array('is'=>5)); Will match a string containing 5 characters (Ie. "Spain"), an integer 5, and an array with 5 elements. You must supply additional checking to check for appropiate types.
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
            $attribute_names = is_array($attribute_names) ? $attribute_names : array($attribute_names);
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

            $attribute_names = is_array($attribute_names) ? $attribute_names : array($attribute_names);
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
    *  class Person extends AkActiveRecord
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
    *   class TeacherSchedule extends AkActiveRecord
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
    *   class Person extends AkActiveRecord
    *   {
    *       function validate()
    *       {
    *           $this->validatesFormatOf('email', "/^([^@\s]+)@((?:[-a-z0-9]+\.)+[a-z]{2,})$/");
    *       }
    *   }
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

        $attribute_names = is_array($attribute_names) ? $attribute_names : array($attribute_names);
        foreach ($attribute_names as $attribute_name){
            if(!isset($this->$attribute_name) || !$regex_function($regular_expression, $this->$attribute_name)){
                $this->addError($attribute_name, $message);
            }
        }
    }

    /**
    * Validates whether the value of the specified attribute is available in a particular array of elements.
    *
    * class Person extends AkActiveRecord
    * {
    *   function validate()
    *   {
    *       $this->validatesInclusionOf('gender', array('male', 'female'), "woah! what are you then!??!!");
    *       $this->validatesInclusionOf('age', range(0, 99));
    *   }
    *
    * Parameters:
    * <tt>$array_of_posibilities</tt> - An array of available items
    * <tt>$message</tt> - Specifies a customer error message (default is: "is not included in the list")
    * <tt>$allow_null</tt> - If set to true, skips this validation if the attribute is null (default is: false)
    */
    function validatesInclusionOf($attribute_names, $array_of_posibilities, $message = 'inclusion', $allow_null = false)
    {
        $message = isset($this->_defaultErrorMessages[$message]) ? $this->t($this->_defaultErrorMessages[$message]) : $message;

        /**
            * @todo get values from lists, collections and nested sets
            */

        $attribute_names = is_array($attribute_names) ? $attribute_names : array($attribute_names);
        foreach ($attribute_names as $attribute_name){
            if($allow_null ? (isset($this->$attribute_name) ? (!in_array(@$this->$attribute_name,$array_of_posibilities)) : false ) : (isset($this->$attribute_name) ? !in_array(@$this->$attribute_name,$array_of_posibilities) : true )){
                $this->addError($attribute_name, $message);
            }
        }
    }

    /**
    * Validates that the value of the specified attribute is not in a particular array of elements.
    *
    *   class Person extends AkActiveRecord
    *   {
    *       function validate()
    *       {
    *           $this->validatesExclusionOf('username', array('admin', 'superuser'), "You don't belong here");
    *           $this->validatesExclusionOf('age', range(30,60), "This site is only for under 30 and over 60");
    *       }
    *   }
    * 
    * Parameters:
    * <tt>$array_of_posibilities</tt> - An array of items that the value shouldn't be part of
    * <tt>$message</tt> - Specifies a customer error message (default is: "is reserved")
    * <tt>$allow_null</tt> - If set to true, skips this validation if the attribute is null (default is: false)
    */
    function validatesExclusionOf($attribute_names, $array_of_posibilities, $message = 'exclusion', $allow_null = false)
    {
        $message = isset($this->_defaultErrorMessages[$message]) ? $this->t($this->_defaultErrorMessages[$message]) : $message;

        $attribute_names = is_array($attribute_names) ? $attribute_names : array($attribute_names);
        foreach ($attribute_names as $attribute_name){

            if($allow_null ? (isset($this->$attribute_name) ? (in_array(@$this->$attribute_name,$array_of_posibilities)) : false ) : (isset($this->$attribute_name) ? in_array(@$this->$attribute_name,$array_of_posibilities) : true )){
                $this->addError($attribute_name, $message);
            }
        }
    }




    /**
    * Validates whether the value of the specified attribute is numeric.
    * 
    *   class Person extends AkActiveRecord
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

        $attribute_names = is_array($attribute_names) ? $attribute_names : array($attribute_names);
        foreach ($attribute_names as $attribute_name){
            if(
            $allow_null ?
            (
            isset($this->$attribute_name) ?
            (
            $only_integer ?
            !is_integer(@$this->$attribute_name) :
            !is_numeric(@$this->$attribute_name)
            ) :
            false
            ) :
            (
            isset($this->$attribute_name) ?
            ($only_integer ? !is_integer(@$this->$attribute_name) : !is_numeric(@$this->$attribute_name)) :
            true
            )
            )
            {
                $this->addError($attribute_name, $message);
            }
        }
    }



    /**
    * Returns true if no errors were added otherwise false.
    */
    function isValid()
    {
        if($this->beforeValidation() && $this->notifyObservers('beforeValidation')){

            $this->clearErrors();

            if($this->_set_default_attribute_values_automatically){
                $this->_setDefaultAttributeValuesAutomatically();
            }

            $this->validate();

            if($this->_automated_validators_enabled){
                $this->_runAutomatedValidators();
            }

            if ($this->isNewRecord()){
                if($this->beforeValidationOnCreate()){
                    $this->notifyObservers('beforeValidationOnCreate');
                    $this->validateOnCreate();
                    $this->notifyObservers('afterValidationOnCreate');
                }
            }else{
                if($this->beforeValidationOnUpdate()){
                    $this->notifyObservers('beforeValidationOnUpdate');
                    $this->validateOnUpdate();
                    $this->notifyObservers('afterValidationOnUpdate');
                }
            }
            $this->notifyObservers('afterValidation');
        }

        return !$this->hasErrors();
    }

    /**
    * By default the Active Record will validate for the maximum length for database columns. You can
    * disable the automated validators by setting $this->_automated_validators_enabled to false.
    * Specific validators are (for now):
    * $this->_automated_max_length_validator = true; // true by default, but you can set it to false on your model
    * $this->_automated_not_null_validator = false; // disabled by default
    */
    function _runAutomatedValidators()
    {
        foreach ($this->_columns as $column_name=>$column_settings){
            if($this->_automated_max_length_validator &&
            empty($column_settings['primaryKey']) &&
            !empty($this->$column_name) &&
            !empty($column_settings['maxLength']) &&
            $column_settings['maxLength'] > 0 &&
            strlen($this->$column_name) > $column_settings['maxLength']){
                $this->addError($column_name, sprintf($this->_defaultErrorMessages['too_long'], $column_settings['maxLength']));
            }elseif($this->_automated_not_null_validator && empty($column_settings['primaryKey']) && !empty($column_settings['notNull']) && (!isset($this->$column_name) || is_null($this->$column_name))){
                $this->addError($column_name,'empty');
            }
        }
    }

    /**
    * $this->_set_default_attribute_values_automatically = true; // This enables automated attribute setting from database definition
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
    * Returns the Errors array that holds all information about attribute error messages.
    */
    function getErrors()
    {
        return $this->_errors;
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




    /////////////////////////////////////////////////////////////////////////////
    ////////////////////// OBSERVERS ////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////

    /**
    * Private
    * $state store the state of this observable object
    */
    var $_observable_state;


    function _instatiateDefaultObserver()
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

        for ($i=0;$i<$observer_count;$i++) {
            if(in_array($this->getModelName(),$observers[$i]->_observing)){
                if(method_exists($observers[$i],$method)){
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
    * @return void
    */ 
    function &addObserver(&$observer)
    {
        static $observers, $registered_observers;
        $observer_class_name = get_class($observer);
        if(!isset($registered_observers[$observer_class_name]) && func_num_args() == 1){
            $observers[] =& $observer;
            $registered_observers[$observer_class_name] = count($observers);
        }
        return $observers;
    }

    /**
    * Register the reference to an object object
    * @return void
    */ 
    function &getObservers()
    {
        $observers =& $this->addObserver(&$this, false);
        return $observers;
    }






    /////////////////////////////////////////////////////////////////////////////////
    ////////////////////////// ERROR HANDLING ///////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////


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
        $attribute_names = is_array($attribute_names) ? $attribute_names : array($attribute_names);
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
        $attribute_names = is_array($attribute_names) ? $attribute_names : array($attribute_names);
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

        $attribute_names = is_array($attribute_names) ? $attribute_names : array($attribute_names);
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
    * Returns errors assigned to base object through addToBase according to the normal rules of getErrorsOn($attribute).
    *
    def on_base
    getErrorsOn(:base)
    end
    */

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
                $full_messages[$attribute][] = $this->t(AkInflector::humanize($attribute)).' '.$error;
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


    function getType()
    {
        //return isset($this->_inheritanceClassName) ? $this->getModelName() : 'active record';
        return $this->getModelName();
    }



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

    function _getActAsClassName($behaviour)
    {
        return (in_array(strtolower($behaviour), $this->__coreActsLikeAttributes) ?
        'AkActsAs' : 'ActAs').AkInflector::camelize($behaviour);
    }

    function &_getActAsInstance($class_name, $options)
    {
        if(substr($class_name,0,2) == 'Ak'){
            include_once(AK_LIB_DIR.DS.'AkActiveRecord'.DS.$class_name.'.php');
        }
        if(!class_exists($class_name)){
            trigger_error(Ak::t('The class %class used for handling an "act_as %class" does not exist',array('%class'=>$class_name)), E_USER_ERROR);
            return $GLOBALS['false'];
        }else{
            $ActAsInstance =& new $class_name($this, $options);
            return $ActAsInstance;
        }
    }

    function _loadActAsBehaviours()
    {
        $this->act_as = !empty($this->acts_as) ? $this->acts_as : (empty($this->act_as) ? false : $this->act_as);
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



    function dbug()
    {
        if(!$this->isConnected()){
            $this->setConnection();
        }
        $this->_db->debug = $this->_db->debug ? false : true;
    }

    function dbugging($trace_this_on_debug_mode = null)
    {
        if(!empty($this->_db->debug) && !empty($trace_this_on_debug_mode)){
            $message = !is_scalar($trace_this_on_debug_mode) ? var_export($trace_this_on_debug_mode, true) : (string)$trace_this_on_debug_mode;
            Ak::trace($message);
        }
        return !empty($this->_db->debug);
    }


    function extractOptionsFromArgs(&$args)
    {
        $_tmp_options = !empty($args) && is_array($args) && is_array($args[count($args)]) ? array_pop($args) : array();
        $options = array();
        foreach (array('conditions', 'include', 'joins', 'limit', 'offset', 'order', 'bind', 'select', 'readonly') as $k){
            if(isset($_tmp_options[$k])){
                $options[$k] = $_tmp_options[$k];
            }
        }
        return $options;
    }


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
     * Gets information from the database engine about a single table
     */
    function _databaseTableInternals($table)
    {
        if(empty($_SESSION['__activeRecordColumnsSettingsCache']['database_table_'.$table.'_internals']) || (AK_ENVIRONMENT != 'development' && !defined('AK_AVOID_ACTIVE_RECORD_DB_SCHEMA_CACHE'))){
            $_SESSION['__activeRecordColumnsSettingsCache']['database_table_'.$table.'_internals'] = $this->_db->MetaColumns($table);
        }
        $cache[$table] = $_SESSION['__activeRecordColumnsSettingsCache']['database_table_'.$table.'_internals'];

        return $cache[$table];
    }


    function hasBeenModified()
    {
        return Ak::objectHasBeenModified($this);
    }

    function _getDatabaseType()
    {
        if(strstr($this->_db->databaseType,'mysql')){
            return 'mysql';
        }elseif(strstr($this->_db->databaseType,'sqlite')){
            return 'sqlite';
        }elseif(strstr($this->_db->databaseType,'post')){
            return 'postgre';
        }else{
            return 'unknown';
        }
    }

}


?>
