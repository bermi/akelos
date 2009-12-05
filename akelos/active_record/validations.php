<?php

/**
* Active Records implement validation by overwriting AkActiveRecord::validate (or the variations, validateOnCreate and
* validateOnUpdate). Each of these methods can inspect the state of the object, which usually means ensuring
* that a number of attributes have a certain value (such as not empty, within a given range, matching a certain regular expression).
*
* Example:
*
*   class Person extends ActiveRecord
*   {
*       public function validate()
*       {
*           $this->addErrorOnEmpty(array('first_name', 'last_name'));
*           if(!preg_match('/[0-9]{4,12}/', $this->phone_number)){
*               $this->addError("phone_number", "has invalid format");
*           }
*       }
*
*       public function validateOnCreate() // is only run the first time a new object is saved
*       {
*           if(!isValidDiscount($this->membership_discount)){
*               $this->addError("membership_discount", "has expired");
*           }
*       }
*
*       public function validateOnUpdate()
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
* You can use "getErrors()" for getting and array of erros on Active Records.
*
*/
class AkActiveRecordValidations extends AkActiveRecordExtenssion
{
    /**
    * Overwrite this method for validation checks on all saves and use addError($field, $message); for invalid attributes.
    */
    public function validate()
    {
    }

    /**
    * Overwrite this method for validation checks used only on creation.
    */
    public function validateOnCreate()
    {
    }

    /**
    * Overwrite this method for validation checks used only on updates.
    */
    public function validateOnUpdate()
    {
    }

    /**
      * Encapsulates the pattern of wanting to validate a password or email address field with a confirmation. Example:
      *
      *  Model:
      *     class Person extends ActiveRecord
      *     {
      *         public function validate()
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
    public function validatesConfirmationOf($attribute_names, $message = 'confirmation')
    {
        $message = $this->_ActiveRecord->getDefaultErrorMessageFor($message, true);
        $attribute_names = Ak::toArray($attribute_names);
        foreach ($attribute_names as $attribute_name){
            $attribute_accessor = $attribute_name.'_confirmation';
            if(isset($this->_ActiveRecord->$attribute_accessor) && @$this->_ActiveRecord->$attribute_accessor != @$this->_ActiveRecord->$attribute_name){
                $this->_ActiveRecord->addError($attribute_name, $message);
            }
        }
    }

    /**
      * Encapsulates the pattern of wanting to validate the acceptance of a terms of service check box (or similar agreement). Example:
      *
      * class Person extends ActiveRecord
      * {
      *     public function validateOnCreate()
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
    public function validatesAcceptanceOf($attribute_names, $message = 'accepted', $accept = 1)
    {
        $message = $this->_ActiveRecord->getDefaultErrorMessageFor($message, true);

        $attribute_names = Ak::toArray($attribute_names);
        foreach ($attribute_names as $attribute_name){
            if(@$this->_ActiveRecord->$attribute_name != $accept){
                $this->_ActiveRecord->addError($attribute_name, $message);
            }
        }
    }

    /**
    * Validates whether the associated object or objects are all valid themselves. Works with any kind of association.
    *
    *   class Book extends ActiveRecord
    *   {
    *       public $has_many = 'pages';
    *       public $belongs_to = 'library';
    *
    *       public function validate(){
    *           $this->validatesAssociated(array('pages', 'library'));
    *       }
    *   }
    *
    *
    * Warning: If, after the above definition, you then wrote:
    *
    *   class Page extends ActiveRecord
    *   {
    *       public $belongs_to = 'book';
    *       public function validate(){
    *           $this->validatesAssociated('book');
    *       }
    *   }
    *
    * ...this would specify a circular dependency and cause infinite recursion.
    *
    * NOTE: This validation will not fail if the association hasn't been assigned. If you want to ensure that the association
    * is both present and guaranteed to be valid, you also need to use validatesPresenceOf.
    */
    public function validatesAssociated($attribute_names, $message = 'invalid')
    {
        $message = $this->_ActiveRecord->getDefaultErrorMessageFor($message, true);
        $attribute_names = Ak::toArray($attribute_names);
        foreach ($attribute_names as $attribute_name){
            if(!empty($this->_ActiveRecord->$attribute_name)){
                if(is_array($this->_ActiveRecord->$attribute_name)){
                    foreach(array_keys($this->_ActiveRecord->$attribute_name) as $k){
                        if(($this->_ActiveRecord->{$attribute_name}[$k] instanceof AkActiveRecord) && !$this->_ActiveRecord->{$attribute_name}[$k]->isValid()){
                            $this->_ActiveRecord->addError($attribute_name, $message);
                        }
                    }
                }elseif (($this->_ActiveRecord->$attribute_name instanceof AkActiveRecord) && !$this->_ActiveRecord->$attribute_name->isValid()){
                    $this->_ActiveRecord->addError($attribute_name, $message);
                }
            }
        }
    }

    public function isBlank($value = null)
    {
        return trim((string)$value) == '';
    }

    /**
      * Validates that the specified attributes are not blank (as defined by AkActiveRecord::isBlank()).
      */
    public function validatesPresenceOf($attribute_names, $message = 'blank')
    {
        $message = $this->_ActiveRecord->getDefaultErrorMessageFor($message, true);

        $attribute_names = Ak::toArray($attribute_names);
        foreach ($attribute_names as $attribute_name){
            $this->_ActiveRecord->addErrorOnBlank($attribute_name, $message);
        }
    }

    /**
      * Validates that the specified attribute matches the length restrictions supplied. Only one option can be used at a time:
      *
      * class Person extends ActiveRecord
      * {
      *     public function validate()
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
    public function validatesLengthOf($attribute_names, $options = array())
    {
        // Merge given options with defaults.
        $default_options = array(
        'too_long'      => $this->_ActiveRecord->getDefaultErrorMessageFor('too_long'),
        'too_short'     => $this->_ActiveRecord->getDefaultErrorMessageFor('too_short'),
        'wrong_length'  => $this->_ActiveRecord->getDefaultErrorMessageFor('wrong_length'),
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
                trigger_error(Ak::t('Range unspecified.  Specify the "within", "maximum", "minimum, or "is" option.').Ak::getFileAndNumberTextForError(1), E_USER_ERROR);
                return false;
                break;
            case 1:
                $options = array_merge($default_options, $options);
                break;
            default:
                trigger_error(Ak::t('Too many range options specified.  Choose only one.').Ak::getFileAndNumberTextForError(1), E_USER_ERROR);
                return false;
                break;
        }


        switch ($option) {
            case 'within':
            case 'in':
                if(empty($option_value) || !is_array($option_value) || count($option_value) != 2 || !is_numeric($option_value[0]) || !is_numeric($option_value[1])){
                    trigger_error(Ak::t('%option must be a Range (array(min, max))',array('%option',$option)).Ak::getFileAndNumberTextForError(1), E_USER_ERROR);
                    return false;
                }
                $attribute_names = Ak::toArray($attribute_names);

                foreach ($attribute_names as $attribute_name){
                    if((!empty($option['allow_null']) && !isset($this->_ActiveRecord->$attribute_name)) || (Ak::size($this->_ActiveRecord->$attribute_name)) < $option_value[0]){
                        $this->_ActiveRecord->addError($attribute_name, sprintf($options['too_short'], $option_value[0]));
                    }elseif((!empty($option['allow_null']) && !isset($this->_ActiveRecord->$attribute_name)) || (Ak::size($this->_ActiveRecord->$attribute_name)) > $option_value[1]){
                        $this->_ActiveRecord->addError($attribute_name, sprintf($options['too_long'], $option_value[1]));
                    }
                }
                break;

            case 'is':
            case 'minimum':
            case 'maximum':

                if(empty($option_value) || !is_numeric($option_value) || $option_value <= 0){
                    trigger_error(Ak::t('%option must be a nonnegative Integer',array('%option',$option_value)).Ak::getFileAndNumberTextForError(1), E_USER_ERROR);
                    return false;
                }

                // Declare different validations per option.
                $validity_checks = array('is' => "==", 'minimum' => ">=", 'maximum' => "<=");
                $message_options = array('is' => 'wrong_length', 'minimum' => 'too_short', 'maximum' => 'too_long');

                $message = sprintf(!empty($options['message']) ? $options['message'] : $options[$message_options[$option]],$option_value);

                $attribute_names = Ak::toArray($attribute_names);
                foreach ($attribute_names as $attribute_name){
                    if((!$options['allow_null'] && !isset($this->_ActiveRecord->$attribute_name)) ||
                    eval("return !(".Ak::size(@$this->_ActiveRecord->$attribute_name)." {$validity_checks[$option]} $option_value);")){
                        $this->_ActiveRecord->addError($attribute_name, $message);
                    }
                }
                break;
            default:
                break;
        }

        return true;
    }

    public function validatesSizeOf($attribute_names, $options = array())
    {
        return validatesLengthOf($attribute_names, $options);
    }

    /**
    * Validates whether the value of the specified attributes are unique across the system. Useful for making sure that only one user
    * can be named "davidhh".
    *
    *  class Person extends ActiveRecord
    *   {
    *       public function validate()
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
    *       public function validate()
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
    public function validatesUniquenessOf($attribute_names, $options = array())
    {
        $default_options = array('case_sensitive'=>true, 'message'=>'taken');
        $options = array_merge($default_options, $options);

        if(!empty($options['if'])){
            if(method_exists($this->_ActiveRecord,$options['if'])){
                if($this->_ActiveRecord->{$options['if']}() === false){
                    return true;
                }
            }else {
                eval('$__eval_result = ('.rtrim($options['if'],';').');');
                if(empty($__eval_result)){
                    return true;
                }
            }
        }

        $message = $this->_ActiveRecord->getDefaultErrorMessageFor($options['message'], true);
        unset($options['message']);

        foreach ((array)$attribute_names as $attribute_name){
            $value = isset($this->_ActiveRecord->$attribute_name) ? $this->_ActiveRecord->$attribute_name : null;

            if($value === null || ($options['case_sensitive'] || !$this->_ActiveRecord->hasColumn($attribute_name))){
                $condition_sql = $this->_ActiveRecord->getTableName().'.'.$attribute_name.' '.$this->_ActiveRecord->getAttributeCondition($value);
                $condition_params = array($value);
            }else{
                include_once(AK_CONTRIB_DIR.DS.'phputf8'.DS.'utf8.php');
                $condition_sql = 'LOWER('.$this->_ActiveRecord->getTableName().'.'.$attribute_name.') '.$this->_ActiveRecord->getAttributeCondition($value);
                $condition_params = array(is_array($value) ? array_map('utf8_strtolower',$value) : utf8_strtolower($value));
            }

            if(!empty($options['scope'])){
                foreach ((array)$options['scope'] as $scope_item){
                    $scope_value = $this->_ActiveRecord->get($scope_item);
                    $condition_sql .= ' AND '.$this->_ActiveRecord->getTableName().'.'.$scope_item.' '.$this->_ActiveRecord->getAttributeCondition($scope_value);
                    $condition_params[] = $scope_value;
                }
            }

            if(!$this->_ActiveRecord->isNewRecord()){
                $condition_sql .= ' AND '.$this->_ActiveRecord->getTableName().'.'.$this->_ActiveRecord->getPrimaryKey().' <> ?';
                $condition_params[] = $this->_ActiveRecord->getId();
            }
            array_unshift($condition_params,$condition_sql);
            if ($this->_ActiveRecord->find('first', array('conditions' => $condition_params))){
                $this->_ActiveRecord->addError($attribute_name, $message);
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
    *       public function validate()
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
    public function validatesFormatOf($attribute_names, $regular_expression, $message = 'invalid', $regex_function = 'preg_match')
    {
        $message = $this->_ActiveRecord->getDefaultErrorMessageFor($message, true);

        $attribute_names = Ak::toArray($attribute_names);
        foreach ($attribute_names as $attribute_name){
            if(!isset($this->_ActiveRecord->$attribute_name) || !$regex_function($regular_expression, $this->_ActiveRecord->$attribute_name)){
                $this->_ActiveRecord->addError($attribute_name, $message);
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
    public function validatesInclusionOf($attribute_names, $array_of_possibilities, $message = 'inclusion', $allow_null = false)
    {
        $message = $this->_ActiveRecord->getDefaultErrorMessageFor($message, true);

        $attribute_names = Ak::toArray($attribute_names);
        foreach ($attribute_names as $attribute_name){
            if($allow_null ? (@$this->_ActiveRecord->$attribute_name != '' ? (!in_array($this->_ActiveRecord->$attribute_name,$array_of_possibilities)) : @$this->_ActiveRecord->$attribute_name === 0 ) : (isset($this->_ActiveRecord->$attribute_name) ? !in_array(@$this->_ActiveRecord->$attribute_name,$array_of_possibilities) : true )){
                $this->_ActiveRecord->addError($attribute_name, $message);
            }
        }
    }

    /**
    * Validates that the value of the specified attribute is not in a particular array of elements.
    *
    *   class Person extends ActiveRecord
    *   {
    *       public function validate()
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
    public function validatesExclusionOf($attribute_names, $array_of_possibilities, $message = 'exclusion', $allow_null = false)
    {
        $message = $this->_ActiveRecord->getDefaultErrorMessageFor($message, true);

        $attribute_names = Ak::toArray($attribute_names);
        foreach ($attribute_names as $attribute_name){

            if($allow_null ? (!empty($this->_ActiveRecord->$attribute_name) ? (in_array(@$this->_ActiveRecord->$attribute_name,$array_of_possibilities)) : false ) : (isset($this->_ActiveRecord->$attribute_name) ? in_array(@$this->_ActiveRecord->$attribute_name,$array_of_possibilities) : true )){
                $this->_ActiveRecord->addError($attribute_name, $message);
            }
        }
    }




    /**
    * Validates whether the value of the specified attribute is numeric.
    *
    *   class Person extends ActiveRecord
    *   {
    *       public function validate()
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
    public function validatesNumericalityOf($attribute_names, $message = 'not_a_number', $only_integer = false, $allow_null = false)
    {
        $message = $this->_ActiveRecord->getDefaultErrorMessageFor($message, true);

        $attribute_names = Ak::toArray($attribute_names);
        foreach ($attribute_names as $attribute_name){
            if (isset($this->_ActiveRecord->$attribute_name)){
                $value = $this->_ActiveRecord->$attribute_name;
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
                $this->_ActiveRecord->addError($attribute_name, $message);
            }
        }
    }



    /**
    * Returns true if no errors were added otherwise false.
    */
    public function isValid()
    {
        $this->_ActiveRecord->clearErrors();
        if($this->_ActiveRecord->beforeValidation() && $this->_ActiveRecord->notifyObservers('beforeValidation')){


            if($this->_ActiveRecord->_set_default_attribute_values_automatically){
                $this->_setDefaultAttributeValuesAutomatically();
            }

            $this->_ActiveRecord->validate();

            if($this->_ActiveRecord->_automated_validators_enabled){
                $this->_ActiveRecord->_runAutomatedValidators();
            }

            $this->_ActiveRecord->afterValidation();
            $this->_ActiveRecord->notifyObservers('afterValidation');

            if ($this->_ActiveRecord->isNewRecord()){
                if($this->_ActiveRecord->beforeValidationOnCreate()){
                    $this->_ActiveRecord->notifyObservers('beforeValidationOnCreate');
                    $this->_ActiveRecord->validateOnCreate();
                    $this->_ActiveRecord->afterValidationOnCreate();
                    $this->_ActiveRecord->notifyObservers('afterValidationOnCreate');
                }
            }else{
                if($this->_ActiveRecord->beforeValidationOnUpdate()){
                    $this->_ActiveRecord->notifyObservers('beforeValidationOnUpdate');
                    $this->_ActiveRecord->validateOnUpdate();
                    $this->_ActiveRecord->afterValidationOnUpdate();
                    $this->_ActiveRecord->notifyObservers('afterValidationOnUpdate');
                }
            }
        }

        return !$this->_ActiveRecord->hasErrors();
    }

    /**
    * By default the Active Record will validate for the maximum length for database columns. You can
    * disable the automated validators by setting $this->_automated_validators_enabled to false.
    * Specific validators are (for now):
    * $this->_automated_max_length_validator = false; // false by default, but you can set it to true on your model
    * $this->_automated_not_null_validator = false; // disabled by default
    */
    protected function _runAutomatedValidators()
    {
        foreach ($this->_ActiveRecord->_columns as $column_name=>$column_settings){
            if($this->_ActiveRecord->_automated_max_length_validator &&
            empty($column_settings['primaryKey']) &&
            !empty($this->_ActiveRecord->$column_name) &&
            !empty($column_settings['maxLength']) && $column_settings['maxLength'] > 0 &&
            strlen($this->_ActiveRecord->$column_name) > $column_settings['maxLength']){
                $this->_ActiveRecord->addError($column_name, sprintf($this->_ActiveRecord->getDefaultErrorMessageFor('too_long'), $column_settings['maxLength']));
            }elseif($this->_ActiveRecord->_automated_not_null_validator && empty($column_settings['primaryKey']) && !empty($column_settings['notNull']) && (!isset($this->_ActiveRecord->$column_name) || is_null($this->_ActiveRecord->$column_name))){
                $this->_ActiveRecord->addError($column_name,'empty');
            }
        }
    }




    /**
    * $this->_set_default_attribute_values_automatically = true; // This enables automated attribute setting from database definition
    */
    protected function _setDefaultAttributeValuesAutomatically()
    {
        foreach ($this->_ActiveRecord->getColumns() as $column_name=>$column_settings){
            if(empty($column_settings['primaryKey']) && isset($column_settings['hasDefault']) && $column_settings['hasDefault'] && (!isset($this->_ActiveRecord->$column_name) || is_null($this->_ActiveRecord->$column_name))){
                if(empty($column_settings['defaultValue'])){
                    if($column_settings['type'] == 'integer' && empty($column_settings['notNull'])){
                        $this->_ActiveRecord->$column_name = 0;
                    }elseif(($column_settings['type'] == 'string' || $column_settings['type'] == 'text') && empty($column_settings['notNull'])){
                        $this->_ActiveRecord->$column_name = '';
                    }
                }else {
                    $this->_ActiveRecord->$column_name = $column_settings['defaultValue'];
                }
            }
        }
    }
}

