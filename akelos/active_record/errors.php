<?php

class AkActiveRecordErrors extends AkActiveRecordExtenssion
{
    protected $_errors = array();
    protected $_defaultErrorMessages = array( // Holds a hash with all the default error messages, such that they can be replaced by your own copy or localizations.
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

    /**
    * Returns the Errors array that holds all information about attribute error messages.
    */
    public function getErrors()
    {
        return $this->_errors;
    }

    /**
    * Adds an error to the base object instead of any particular attribute. This is used
    * to report errors that doesn't tie to any specific attribute, but rather to the object
    * as a whole. These error messages doesn't get prepended with any field name when iterating
    * with yieldEachFullError, so they should be complete sentences.
    */
    public function addErrorToBase($message)
    {
        $this->addError($this->_ActiveRecord->getModelName(), $message);
    }

    /**
    * Returns errors assigned to base object through addToBase according to the normal rules of getErrorsOn($attribute).
    */
    public function getBaseErrors()
    {
        $errors = $this->getErrors();
        return (array)@$errors[$this->_ActiveRecord->getModelName()];
    }


    /**
    * Adds an error message ($message) to the ($attribute), which will be returned on a call to <tt>getErrorsOn($attribute)</tt>
    * for the same attribute and ensure that this error object returns false when asked if <tt>hasErrors</tt>. More than one
    * error can be added to the same $attribute in which case an array will be returned on a call to <tt>getErrorsOn($attribute)</tt>.
    * If no $message is supplied, "invalid" is assumed.
    */
    public function addError($attribute, $message = 'invalid')
    {
        $message = $this->getDefaultErrorMessageFor($message, true);
        $this->_errors[$attribute][] = $message;
    }

    /**
    * Will add an error message to each of the attributes in $attributes that is empty.
    */
    public function addErrorOnEmpty($attribute_names, $message = 'empty')
    {
        $message = $this->getDefaultErrorMessageFor($message, true);
        $attribute_names = Ak::toArray($attribute_names);
        foreach ($attribute_names as $attribute){
            if(empty($this->_ActiveRecord->$attribute)){
                $this->addError($attribute, $message);
            }
        }
    }

    /**
    * Will add an error message to each of the attributes in $attributes that is blank (using $this->isBlank).
    */
    public function addErrorOnBlank($attribute_names, $message = 'blank')
    {
        $message = $this->getDefaultErrorMessageFor($message, true);
        $attribute_names = Ak::toArray($attribute_names);
        foreach ($attribute_names as $attribute){
            if($this->_ActiveRecord->isBlank(@$this->_ActiveRecord->$attribute)){
                $this->addError($attribute, $message);
            }
        }
    }

    /**
    * Will add an error message to each of the attributes in $attributes that has a length outside of the passed boundary $range.
    * If the length is above the boundary, the too_long_message message will be used. If below, the too_short_message.
    */
    public function addErrorOnBoundaryBreaking($attribute_names, $range_begin, $range_end, $too_long_message = 'too_long', $too_short_message = 'too_short')
    {
        $too_long_message = $this->getDefaultErrorMessageFor($too_long_message);
        $too_short_message = $this->getDefaultErrorMessageFor($too_short_message);

        $attribute_names = Ak::toArray($attribute_names);
        foreach ($attribute_names as $attribute){
            if(@$this->_ActiveRecord->$attribute < $range_begin){
                $this->addError($attribute, $too_short_message);
            }
            if(@$this->_ActiveRecord->$attribute > $range_end){
                $this->addError($attribute, $too_long_message);
            }
        }

    }

    public function addErrorOnBoundryBreaking ($attributes, $range_begin, $range_end, $too_long_message = 'too_long', $too_short_message = 'too_short')
    {
        $this->addErrorOnBoundaryBreaking($attributes, $range_begin, $range_end, $too_long_message, $too_short_message);
    }

    /**
    * Returns true if the specified $attribute has errors associated with it.
    */
    public function isInvalid($attribute)
    {
        return $this->getErrorsOn($attribute);
    }

    /**
    * Returns false, if no errors are associated with the specified $attribute.
    * Returns the error message, if one error is associated with the specified $attribute.
    * Returns an array of error messages, if more than one error is associated with the specified $attribute.
    */
    public function getErrorsOn($attribute)
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
    public function yieldEachError()
    {
        foreach ($this->_errors as $errors){
            foreach ($errors as $error){
                $this->yieldError($error);
            }
        }
    }

    public function yieldError($message)
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
    public function yieldEachFullError()
    {
        $full_messages = $this->getFullErrorMessages();
        foreach ($full_messages as $full_message){
            $this->yieldError($full_message);
        }
    }


    /**
    * Returns all the full error messages in an array.
    */
    public function getFullErrorMessages()
    {
        $full_messages = array();

        foreach ($this->_errors as $attribute=>$errors){
            $full_messages[$attribute] = array();
            $attribute_name = AkInflector::humanize($this->_ActiveRecord->_internationalize ? $this->_ActiveRecord->delocalizeAttribute($attribute) : $attribute);
            foreach ($errors as $error){
                $full_messages[$attribute][] = $this->_ActiveRecord->t('%attribute_name %error', array(
                '%attribute_name' => $attribute_name,
                '%error' => $error
                ));
            }
        }
        return $full_messages;
    }

    /**
    * Returns true if no errors have been added.
    */
    public function hasErrors()
    {
        return !empty($this->_errors);
    }

    /**
    * Removes all the errors that have been added.
    */
    public function clearErrors()
    {
        $this->_errors = array();
    }

    /**
    * Returns the total number of errors added. Two errors added to the same attribute will be counted as such
    * with this as well.
    */
    public function countErrors()
    {
        $error_count = 0;
        foreach ($this->_errors as $errors){
            $error_count = count($errors)+$error_count;
        }

        return $error_count;
    }

    public function errorsToString($print = false)
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

    public function getDefaultErrorMessageFor($type, $translated = false)
    {
        if(isset($this->_ActiveRecord->_defaultErrorMessages[$type])){
            $message = $this->_ActiveRecord->_defaultErrorMessages[$type];
        }elseif(isset($this->_defaultErrorMessages[$type])){
            $message = $this->_defaultErrorMessages[$type];
        }
        if(!empty($message)){
            return $translated ? $this->_ActiveRecord->t($message) : $message;
        }
        return $type;
    }
}

