<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

/**
 * This is the base class for all sort of models (Mailers or Active records)
 * It handles the naming conventions for models.
 *
 * See also <AkActiveRecord> and <AkActionMailer> as those are the ones you will usually inherit from
 *
 * @method void addError()  Adds an error to the base object instead of any particular attribute. This is used to report errors that doesn't tie to any specific attribute, but rather to the object as a whole. These error messages doesn't get prepended with any field name when iterating with yieldEachFullError, so they should be complete sentences.
 * @method void addErrorOnBlank() Will add an error message to each of the attributes in $attributes that is blank (using $this->isBlank).
 * @method void addErrorOnBoundaryBreaking() Will add an error message to each of the attributes in $attributes that has a length outside of the passed boundary $range. If the length is above the boundary, the too_long_message message will be used. If below, the too_short_message.
 * @method void addErrorOnBoundryBreaking() Alias of addErrorOnBoundaryBreaking
 * @method void addErrorOnEmpty() Will add an error message to each of the attributes in $attributes that is empty.
 * @method void addErrorToBase() Adds an error to the base object instead of any particular attribute. This is used to report errors that doesn't tie to any specific attribute, but rather to the object as a whole. These error messages doesn't get prepended with any field name when iterating with yieldEachFullError, so they should be complete sentences.
 * @method void addObserver() Register the reference to an object object
 * @method void clearErrors() Removes all the errors that have been added.
 * @method integer countErrors() Returns the total number of errors added. Two errors added to the same attribute will be counted as such with this as well.
 * @method void dbug() Toggles SQL query output
 * @method bool dbugging() Will return true if we are in debug mode and will output the message given as param.
 * @method void debug() Outputs a string representation of the debugged parameter.
 * @method string errorsToString() Generate a HTML list that contains current errors.
 * @method AkBaseModel fromJson() Reads Json string and returns an AkBaseModel Object
 * @method AkBaseModel fromXml() Reads Xml and returns an AkBaseModel Object
 * @method array getBaseErrors() Returns errors assigned to base object through addToBase according to the normal rules of getErrorsOn($attribute).
 * @method string getDefaultErrorMessageFor() Returns the default error message for an specific error type.
 * @method array getErrors() Returns the Errors array that holds all information about attribute error messages.
 * @method mixed getErrorsOn() Returns false, if no errors are associated with the specified $attribute.  Returns the error message, if one error is associated with the specified $attribute.  Returns an array of error messages, if more than one error is associated with the specified $attribute.
 * @method array getFullErrorMessages() Returns all the full error messages in an array.
 * @method string getObservableState() Returns current observable state.
 * @method array getObservers() Gets the list of observers for the current model.
 * @method bool hasErrors() Returns true if no errors have been added.
 * @method bool isBlank() Returns true if the value is a blank string or null.
 * @method bool isInvalid() Returns true if the specified $attribute has errors associated with it.
 * @method bool isValid() Returns true if no errors were added otherwise false.
 * @method bool needsValidation() Returns true if the model needs validation.
 * @method bool notifyObservers() Calls the $method using the reference to each registered observer.
 * @method void setObservableState() Sets current observable state.
 * @method string toJson() Generate a json representation of the model record.
 * @method string toString() Displays a tring representation of the model for debugging.
 * @method string toXml() Generate a xml representation of the model record.
 * @method string toYaml() Generate a YAML representation of the model record.
 * @method void validate() Overwrite this method for validation checks on all saves and use addError($field, $message); for invalid attributes.
 * @method void validateOnCreate() Overwrite this method for validation checks used only on creation.
 * @method void validateOnUpdate() Overwrite this method for validation checks used only on updates.
 * @method void validatesAcceptanceOf() Encapsulates the pattern of wanting to validate the acceptance of a terms of service check box (or similar agreement).
 * @method void validatesAssociated() Validates whether the associated object or objects are all valid themselves. Works with any kind of association.
 * @method void validatesConfirmationOf() Encapsulates the pattern of wanting to validate a password or email address field with a confirmation.
 * @method void validatesExclusionOf() Validates that the value of the specified attribute is not in a particular array of elements.
 * @method void validatesFormatOf() Validates whether the value of the specified attribute is of the correct form by matching it against the regular expression provided.
 * @method void validatesInclusionOf() Validates whether the value of the specified attribute is available in a particular array of elements.
 * @method void validatesLengthOf() Validates that the specified attribute matches the length restrictions supplied.
 * @method void validatesNumericalityOf() Validates whether the value of the specified attribute is numeric.
 * @method void validatesPresenceOf() Validates that the specified attributes are not blank (as defined by AkBaseModel::isBlank()).
 * @method void validatesSizeOf() Alias for validatesLengthOf.
 * @method void validatesUniquenessOf() Validates whether the value of the specified attributes are unique across the system. Useful for making sure that only one user can be named "james".
 * @method void yieldEachError() Yields each attribute and associated message per error added.
 * @method void yieldEachFullError() Yields each full error message added. So Person->addError("first_name", "can't be empty") will be returned through iteration as "First name can't be empty".
 * @method void yieldError() Prints the error as HTML.
 */
class AkBaseModel extends AkLazyObject
{
    public $locale_namespace;
    public $_modelName;
    protected
    $_report_undefined_attributes = false;

    /**
    * Returns current model name
    */
    public function getModelName() {
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
    public function setModelName($model_name = null) {
        if(!empty($model_name)){
            $this->_modelName = $model_name;
        }else{
            $this->_modelName = get_class($this);
        }
        return true;
    }

    public function getParentModelName() {
        if(!isset($this->_parentModelName)){
            if(!$this->setParentModelName()){
                return false;
            }
        }
        return $this->_parentModelName;
    }

    public function setParentModelName($model_name = null) {
        if(!empty($model_name)){
            $this->_parentModelName = $model_name;
        }else{
            $class_name = AkInflector::camelize(get_parent_class($this));
            if($class_name == 'AkActiveRecord'){
                trigger_error(Ak::t('The Akelos Framework could not automatically configure your model name.'.
                ' This might be caused because your model file is not located on %path. Please call $this->setParentModelName("YourParentModelName");'.
                ' in your model constructor in order to make this work.',array('%path'=>AK_MODELS_DIR.DS)), E_USER_ERROR);
                return false;
            }
            $this->_parentModelName = $class_name;
        }
        return true;
    }

    public function t($string, $array = null) {
        return Ak::t($string, $array, 
                AkConfig::getOption('locale_namespace', 
                    (!empty($this->locale_namespace) ? $this->locale_namespace : (
                        defined('AK_DEFAULT_LOCALE_NAMESPACE') ? AK_DEFAULT_LOCALE_NAMESPACE : 
                        AkInflector::underscore($this->getModelName())
                        )
                    )
                )
            );
    }


    public function getAttributeCondition($argument) {
        return is_array($argument) ? 'IN (?)' : (is_null($argument) ? 'IS ?' : '= ?');
    }


    protected function _enableObservers() {
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


    protected function _enableErrors() {
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


    protected function _enableValidations() {
        $this->extendClassLazily('AkModelValidations',
        array(
        'methods' => array(
        'validate',
        'validateOnCreate',
        'validateOnUpdate',
        'needsValidation',
        'isBlank',
        'isValid',
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
        'validatesAssociated',
        ),
        'autoload_path' => AK_ACTIVE_SUPPORT_DIR.DS.'models'.DS.'validations.php'
        ));
    }
    

    protected function _enableUtilities() {
        $this->extendClassLazily('AkModelUtilities',
        array(
        'methods' => array(
        'fromXml',
        'toJson',
        'fromJson',
        'toXml',
        'toYaml',
        ),
        'autoload_path' => AK_ACTIVE_SUPPORT_DIR.DS.'models'.DS.'utilities.php'
        ));
    }
    protected function _enableDebug() {
        $this->extendClassLazily('AkModelDebug',
        array(
        'methods' => array(
        'dbug',
        'toString',
        'dbugging',
        'debug'
        ),
        'autoload_path' => AK_ACTIVE_SUPPORT_DIR.DS.'models'.DS.'debug.php'
        ));
    }

}

class AkModelExtenssion
{
    protected $_Model;
    public function setExtendedBy(&$Model) {
        $this->_Model = $Model;
    }
}

