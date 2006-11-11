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

/**
* Active Record validation is reported to and from this object, which is used by AkActiveRecord->save() to
* determine whether the object in a valid state to be saved. See usage example in Validations.
*/

class AkErrors extends AkObject 
{
    /**
    * Holds an instance of the onject that uses the AkError class
    */
    var $_base;
   
    var $errors = array();

    /**
    * Holds a hash with all the default error messages, such that they can be replaced by your own copy or localizations.
    */
    var $defaultErrorMessages = array(
    'inclusion' => "is not included in the list",
    'exclusion' => "is reserved",
    'invalid' => "is invalid",
    'confirmation' => "doesn't match confirmation",
    'accepted ' => "must be accepted",
    'empty' => "can't be empty",
    'blank' => "can't be blank",
    'too_long' => "is too long (max is %d characters)",
    'too_short' => "is too short (min is %d characters)",
    'wrong_length' => "is the wrong length (should be %d characters)",
    'taken' => "has already been taken",
    'not_a_number' => "is not a number"
    );


    function __construct(&$object)
    {
        $this->_base =& $object;
    }

    /**
    * Adds an error to the base object instead of any particular attribute. This is used
    * to report errors that doesn't tie to any specific attribute, but rather to the object
    * as a whole. These error messages doesn't get prepended with any field name when iterating
    * with eachFull, so they should be complete sentences.
    */
    function addToBase($message)
    {
        $this->add($this->_base, $message);
    }

    /**
    * Adds an error message ($message) to the ($attribute), which will be returned on a call to <tt>on($attribute)</tt>
    * for the same attribute and ensure that this error object returns false when asked if <tt>isEmpty</tt>. More than one
    * error can be added to the same $attribute in which case an array will be returned on a call to <tt>on($attribute)</tt>.
    * If no $message is supplied, "invalid" is assumed.
    */
    function add($attribute, $message = 'invalid')
    {
        $message = isset($this->defaultErrorMessages[$message]) ? $this->defaultErrorMessages[$message] : $message;
        $this->errors[$attribute][] = $message;
    }

    /**
    * Will add an error message to each of the attributes in $attributes that is empty.
    */
    function addOnEmpty($attributes, $message = 'empty')
    {
        $message = isset($this->defaultErrorMessages[$message]) ? $this->defaultErrorMessages[$message] : $message;
        foreach ($attributes as $attribute){
            if($this->isEmpty(@$this->_base->$attribute)){
                $this->add($attribute, $message);
            }
        }
    }

    /**
    * Will add an error message to each of the attributes in $attributes that is blank (using $this->isBlank).
    */
    function addOnBlank($attributes, $message = 'blank')
    {
        $message = isset($this->defaultErrorMessages[$message]) ? $this->defaultErrorMessages[$message] : $message;
        foreach ($attributes as $attribute){
            if($this->isBlank(@$this->_base->$attribute)){
                $this->add($attribute, $message);
            }
        }
    }

    /**
    * Will add an error message to each of the attributes in $attributes that has a length outside of the passed boundary $range.
    * If the length is above the boundary, the too_long_message message will be used. If below, the too_short_message.
    */
    function addOnBoundaryBreaking($attributes, $range_begin, $range_end, $too_long_message = 'too_long', $too_short_message = 'too_short')
    {
        $too_long_message = isset($this->defaultErrorMessages[$too_long_message]) ? $this->defaultErrorMessages[$too_long_message] : $too_long_message;
        $too_short_message = isset($this->defaultErrorMessages[$too_short_message]) ? $this->defaultErrorMessages[$too_short_message] : $too_short_message;

        foreach ($attributes as $attribute){
            if(@$this->_base->$attribute < $range_begin){
                $this->add($attribute, $too_short_message);
            }
            if(@$this->_base->$attribute > $range_end){
                $this->add($attribute, $too_long_message);
            }
        }

    }

    function addOnBoundryBreaking ($attributes, $range_begin, $range_end, $too_long_message = 'too_long', $too_short_message = 'too_short')
    {
        $this->addOnBoundaryBreaking($attributes, $range_begin, $range_end, $too_long_message, $too_short_message);
    }

    /**
    * Returns true if the specified $attribute has errors associated with it.
    */
    function isInvalid($attribute)
    {
        isset($this->errors[$attribute]);
    }

    /**
    * Returns false, if no errors are associated with the specified $attribute.
    * Returns the error message, if one error is associated with the specified $attribute.
    * Returns an array of error messages, if more than one error is associated with the specified $attribute.
    */
    function on($attribute)
    {
        if (empty($this->errors[$attribute])){
            return false;
        }elseif (count($this->errors[$attribute]) == 1){
            $k = array_keys($this->errors[$attribute]);
            return $this->errors[$attribute][$k[0]];
        }else{
            return $this->errors[$attribute];
        }
    }

    /**
    * Returns errors assigned to base object through addToBase according to the normal rules of on($attribute).
    *
    def on_base
    on(:base)
    end
    */

    /**
    * Yields each attribute and associated message per error added.
    */
    function each()
    {
        foreach ($this->errors as $errors){
            foreach ($errors as $error){
                $this->yield($error);
            }
        }
    }

    /**
    * Yields each full error message added. So Person.errors.add("first_name", "can't be empty") will be returned
    * through iteration as "First name can't be empty".
    */
    function eachFull()
    {
        $full_messages = $this->fullMessages();
        foreach ($full_messages as $full_message){
            $this->yield($full_message);
        }
    }

    /**
    * Returns all the full error messages in an array.
    */
    function fullMessages()
    {
        $full_messages = array();

        foreach ($this->errors as $attribute->$errors){
            $full_messages[$attribute] = array();
            foreach ($errors as $error){
                $full_messages[] = AkInflector::humanize($attribute).' '.$error;
            }
        }
        return $full_messages;
    }

    /**
    * Returns true if no errors have been added.
    */
    function isEmpty()
    {
        return empty($this->errors);
    }

    /**
    * Removes all the errors that have been added.
    */
    function clear()
    {
        $this->errors = array();
    }

    /**
    * Returns the total number of errors added. Two errors added to the same attribute will be counted as such
    * with this as well.
    */
    function count()
    {
        $error_count = 0;
        foreach ($this->errors as $errors){
            $error_count = count($errors)+$error_count;
        }

        return $error_count;
    }
}


class AkValidator
{
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
    *           $this->errors->addOnEmpty(array('first_name', 'last_name'));
    *           if(!preg_match('/[0-9]{4,12}/', $this->phone_number)){
    *               $this->errors->add("phone_number", "has invalid format");
    *           }
    *       }
    * 
    *       function validateOnCreate() // is only run the first time a new object is saved
    *       {
    *           if(!isValidDiscount($this->membership_discount)){
    *               $this->errors->add("membership_discount", "has expired");
    *           }
    *       }
    * 
    *       function validateOnUpdate()
    *       {
    *           if($this->countChangedAttributes() == 0){
    *               $this->errors->addToBase("No changes have occurred");
    *           }
    *       }
    *   }
    * 
    *   $Person = new Person("first_name" => "David", "phone_number" => "what?");
    *   $Person->save();                        * => false (and doesn't do the save);
    *   $Person->Errors->isEmpty();             * => false
    *   $Person->Errors->count();              * => 2
    *   $Person->Errors->on("last_name");       * => "can't be empty"
    *   $Person->Errors->on("phone_number");    * => "has invalid format"
    *   $Person->Errors->eachFull();            * => "Last name can't be empty \n Phone number has invalid format"
    * 
    *   $Person->setAttributes(array("last_name" => "Heinemeier", "phone_number" => "555-555"));
    *   $Person->save(); // => true (and person is now saved in the database)
    * 
    * An "Errors" object is automatically created for every Active Record.
    * 
    */
    var $validations = array('validate', 'validateOnCreate', 'validateOnUpdate');

    /*
    def self.append_features(base) # :nodoc:
    super
    base.extend ClassMethods
    base.class_eval do
    alias_method :saveWithoutValidation, :save
    alias_method :save, :save_with_validation

    alias_method :update_attribute_without_validation_skipping, :update_attribute
    alias_method :update_attribute, :update_attribute_with_validation_skipping
    end
    end
    */


    /**
    * All of the following validations are defined in the class scope of the model that you're interested in validating.
    * They offer a more declarative way of specifying when the model is valid and when it is not. It is recommended to use
    * these over the low-level calls to validate and validateOnCreate when possible.
    */
    var $default_validation_options = array('on' => 'save', 'allow_null' => false, 'message' => null);
    var $all_range_options = array('is','within', 'in', 'minimum', 'maximum');


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
      *    <php password_field("person", "password"); ?>
      *    <php password_field("person", "password_confirmation"); ?>
      * 
      * The person has to already have a password attribute (a column in the people table), but the password_confirmation is virtual.
      * It exists only as an in-memory variable for validating the password. This check is performed only if password_confirmation
      * is not null.
      * 
      */
    function validatesConfirmationOf($attribute_names, $message = 'confirmation')
    {
        $message = isset($this->Errors->defaultErrorMessages[$message]) ? $this->Errors->defaultErrorMessages[$message] : $message;
        $attribute_names = is_array($attribute_names) ? $attribute_names : array($attribute_names);
        foreach ($attribute_names as $attribute_name){
            $attribute_accessor = $attribute_name.'_confirmation';
            if(isset($this->$attribute_accessor) && $this->$attribute_accessor != $this->$attribute_name){
                $this->Errors->add($attribute_name, $message);
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
        $message = isset($this->Errors->defaultErrorMessages[$message]) ? $this->Errors->defaultErrorMessages[$message] : $message;

        $attribute_names = is_array($attribute_names) ? $attribute_names : array($attribute_names);
        foreach ($attribute_names as $attribute_name){
            if(isset($this->$attribute_name) && $this->$attribute_name != $accept){
                $this->Errors->add($attribute_name, $message);
            }
        }
    }

    function isBlank($value)
    {
        return $value == '';
    }

    /**
      * Validates that the specified attributes are not blank (as defined by AkActiveRecord::isBlank()).
      */
    function validatesPresenceOf($attribute_names, $message = 'blank')
    {
        $message = isset($this->Errors->defaultErrorMessages[$message]) ? $this->Errors->defaultErrorMessages[$message] : $message;

        $attribute_names = is_array($attribute_names) ? $attribute_names : array($attribute_names);
        foreach ($attribute_names as $attribute_name){
            $this->Errors->addOnBlank($attribute_name, $message);
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
      *         $this->validatesLengthOf('last_name', array('maximum'=>30), "less than %d if you don't mind");
      *         $this->validatesLengthOf('last_name', array('within'=>array(7, 32)));
      *         $this->validatesLengthOf('last_name', array('in'=>array(6, 20), 'too_long' => "pick a shorter name", 'too_short' => "pick a longer name"));
      *         $this->validatesLengthOf('fav_bra_size', array('minimum'=>1, 'too_short'=>"please enter at least %d character"));
      *         $this->validatesLengthOf('smurf_leader', array('is'=>4, 'message'=>"papa is spelled with %d characters... don't play me."));
      *     }
      * }
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
      * <tt>on</tt> - Specifies when this validation is active (default is "save", other options "create", "update")
      */
    function validatesLengthOf($attribute_names, $options = array())
    {
        // Merge given options with defaults.
        $default_options = array(
        'too_long'     => $this->Errors->defaultErrorMessages['too_long'],
        'too_short'     => $this->Errors->defaultErrorMessages['too_short'],
        'wrong_length'     => $this->Errors->defaultErrorMessages['wrong_length'],
        );

        $range_options = array_diff($options, array('minimum','maximum','is','within'));
        // Ensure that one and only one range option is specified.
        switch (count($range_options)) {
            case 0:
            trigger_error(Ak::t('Range unspecified.  Specify the "within", "maximum", "minimum, or "is" option.'), E_USER_ERROR);
            return false;
            break;
            case 1:
            $options = array_merge($options, $default_options);
            break;
            default:
            trigger_error(Ak::t('Too many range options specified.  Choose only one.'), E_USER_ERROR);
            return false;
            break;
        }

        // Get range option and value.
        $option = array_keys($range_options);
        $option = $option[0];
        $option_value = $options[$option];

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
                    $this->Errors->add($attribute_name, sprintf($options['too_short'], $option_value[0]));
                }elseif((!empty($option['allow_null']) && !isset($this->$attribute_name)) || (Ak::size($this->$attribute_name)) > $option_value[1]){
                    $this->Errors->add($attribute_name, sprintf($options['too_long'], $option_value[1]));
                }
            }

            case 'is':
            case 'minimum':
            case 'maximum':

            if(empty($option_value) || !is_numeric($option_value) || $option_value >= 0){
                trigger_error(Ak::t('%option must be a nonnegative Integer',array('%option',$option_value)), E_USER_ERROR);
                return false;
            }

            // Declare different validations per option.
            $validity_checks = array('is' => "==", 'minimum' => ">=", 'maximum' => "<=");
            $message_options = array('is' => 'wrong_length', 'minimum' => 'too_short', 'maximum' => 'too_long');

            $message = sprintf(!empty($options['message']) ? $options['message'] : $options[$message_options[$option]],$option_value);

            $attribute_names = is_array($attribute_names) ? $attribute_names : array($attribute_names);
            foreach ($attribute_names as $attribute_name){
                if((!empty($option['allow_null']) && !isset($this->$attribute_name)) ||
                eval("return ".Ak::size(@$this->$attribute_name)." {$validity_checks[$option]} $option_value;")){
                    $this->Errors->add($attribute_name, $message);
                }
            }
            break;
            default:
            break;
        }
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
    *           $this->validatesUniquenessOf('user_name', array('scope' => "account_id"));
    *       }
    *   }
    *
    * When the record is created, a check is performed to make sure that no record exist in the database with the given value for the specified
    * attribute (that maps to a column). When the record is updated, the same check is made but disregarding the record itself.
    *
    * Configuration options:
    * <tt>message</tt> - Specifies a custom error message (default is: "has already been taken")
    * <tt>scope</tt> - Ensures that the uniqueness is restricted to a condition of "scope = record.scope"
    * <tt>if</tt> - Specifies a method, proc or string to call to determine if the validation should
    */
    function validatesUniquenessOf($attribute_names, $options = array('message'=>'taken'))
    {
        $message = empty($options['message']) ? 'taken' : $options['message'];
        $message = isset($this->Errors->defaultErrorMessages[$message]) ? $this->Errors->defaultErrorMessages[$message] : $message;

        $attribute_names = is_array($attribute_names) ? $attribute_names : array($attribute_names);
        foreach ($attribute_names as $attribute_name){
            if(!empty($options['scope']) && $this->hasColumn($options['scope']) && $this->find('first', array('conditions' =>($this->isNewRecord() ?
            array("$attribute_name = ? AND {$options['scope']} = ?", $this->$attribute_name, $this->$options['scope']) :
            array("$attribute_name = ? AND ".$this->getPrimaryKey()." <> ? AND {$options['scope']} = ?", $this->$attribute_name, $this->getId(), $this->$options['scope'])
            )))){
                $this->Errors->add($attribute_name, $message);
            }elseif ($this->find('first', array('conditions' =>($this->isNewRecord() ?
            array("$attribute_name = ?", $this->$attribute_name) :
            array("$attribute_name = ? AND ".$this->getPrimaryKey()." <> ?", $this->$attribute_name, $this->getId())
            )))){
                $this->Errors->add($attribute_name, $message);
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
    * Params:
    * <tt>$message</tt> - A custom error message (default is: "is invalid")
    * <tt>$regular_expression</tt> - The regular expression used to validate the format with (note: must be supplied!)
    */
    function validatesFormatOf($attribute_names, $regular_expression, $message = 'invalid', $regex_function = 'preg_match')
    {
        $message = isset($this->Errors->defaultErrorMessages[$message]) ? $this->Errors->defaultErrorMessages[$message] : $message;

        $attribute_names = is_array($attribute_names) ? $attribute_names : array($attribute_names);
        foreach ($attribute_names as $attribute_name){
            if(!isset($this->$attribute_name) || $regex_function($regular_expression, $this->$attribute_name)){
                $this->Errors->add($attribute_name, $message);
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
    *       $this->validatesInclusionOf('gender', array('in'=>array('male', 'female'), 'message'=>"woah! what are you then!??!!"));
    *       $this->validatesInclusionOf('age', array('in'=>range(0, 99)));
    *   }
    *
    * Parameters:
    * <tt>$array_of_posibilities</tt> - An array of available items
    * <tt>$message</tt> - Specifies a customer error message (default is: "is not included in the list")
    * <tt>$allow_null</tt> - If set to true, skips this validation if the attribute is null (default is: false)
    */
    function validatesInclusionOf($attribute_names, $array_of_posibilities, $message = 'inclusion', $allow_null = false)
    {
        $message = isset($this->Errors->defaultErrorMessages[$message]) ? $this->Errors->defaultErrorMessages[$message] : $message;

        $attribute_names = is_array($attribute_names) ? $attribute_names : array($attribute_names);
        foreach ($attribute_names as $attribute_name){
            if((!$allow_null && !isset($this->$attribute_name)) || !in_array(@$this->$attribute_name,$array_of_posibilities)){
                $this->Errors->add($attribute_name, $message);
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
        $message = isset($this->Errors->defaultErrorMessages[$message]) ? $this->Errors->defaultErrorMessages[$message] : $message;

        $attribute_names = is_array($attribute_names) ? $attribute_names : array($attribute_names);
        foreach ($attribute_names as $attribute_name){
            if((!$allow_null && !isset($this->$attribute_name)) || in_array(@$this->$attribute_name,$array_of_posibilities)){
                $this->Errors->add($attribute_name, $message);
            }
        }
    }


    /**
    * Validates whether the associated object or objects are all themselves valid. Works with any kind of association.
    * 
    *   class Book extends AkActiveRecord
    *   {
    *       var $hasMany = 'pages';
    *       var $belongsTo = 'library';
    *   
    *       function validate()
    *       {
    *           $this->validatesAssociated(array('pages', 'library'));
    *       }
    *   }
    *    
    * 
    * Warning: If, after the above definition, you then wrote:
    * 
    *   class Page extends AkActiveRecord
    *   {
    *       var $belongsTo = 'book';
    *
    *       function validate()
    *       {
    *           $this->validatesAssociated('book');
    *       }
    *   }
    * ...this would specify a circular dependency and cause infinite recursion.
    * 
    * NOTE: This validation will not fail if the association hasn't been assigned. If you want to ensure that the association
    * is both present and guaranteed to be valid, you also need to use validatesPresenceOf.
    *
    */
    function validatesAssociated($attribute_names, $message = 'invalid', $validation_methods = array('validate'))
    {
        $message = isset($this->Errors->defaultErrorMessages[$message]) ? $this->Errors->defaultErrorMessages[$message] : $message;

        $attribute_names = is_array($attribute_names) ? $attribute_names : array($attribute_names);
        foreach ($attribute_names as $attribute_name){
            if(!isset($this->$attribute_name) || !is_object($this->$attribute_name)){
                $this->Errors->add($attribute_name, $message);
            }else{
                foreach ($validation_methods as $method){
                    if(!in_array($method, $this->validations) || !method_exists($this->$attribute_name, $method)){
                        $this->Errors->add($attribute_name, $message);
                    }else{
                        $this->$attribute_name->$method();
                    }
                }
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
        $message = isset($this->Errors->defaultErrorMessages[$message]) ? $this->Errors->defaultErrorMessages[$message] : $message;

        $attribute_names = is_array($attribute_names) ? $attribute_names : array($attribute_names);
        foreach ($attribute_names as $attribute_name){
            if((!$allow_null && !isset($this->$attribute_name)) ||
            (!$only_integer && is_numeric(@$this->$attribute_name)) ||
            ($only_integer && is_integer(@$this->$attribute_name))){
                $this->Errors->add($attribute_name, $message);
            }
        }
    }

    /**
    * The validation process on save can be skipped by passing false. The regular Base#save method is
    * replaced with this when the validations module is mixed in, which it is by default.
    */
    function saveWithValidation($perform_validation = true)
    {
        if($perform_validation && $this->isValid() || !$perform_validation){
            return $this->save(false);
        }else{
            return false;
        }
    }

    /**
        * Returns true if no errors were added otherwise false.
        */
    function isValid()
    {
        $this->errors = array();

        $this->validate();

        if ($this->isNewRecord()){
            $this->validateOnCreate();
        }else{
            $this->validateOnUpdate();
        }
        return empty($this->errors);
    }

    /**
        * Returns the Errors object that holds all information about attribute error messages.
        */
    function errors()
    {
        return @$this->Errors->errors;
    }

    /**
        * Overwrite this method for validation checks on all saves and use Errors->add($field, $message); for invalid attributes.
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

}

?>