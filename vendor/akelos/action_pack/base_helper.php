<?php

class AkBaseHelper
{
    protected $_locales_namespace = 'helpers';
    protected $_controller;

    public function __construct() {
        $args = func_get_args();
        if(!empty($args[0]) && is_array($args[0])){
            $this->addObjects($args[0]);
        }
    }

    public function addObjects(&$Objects) {
        foreach ($Objects as $placeholder => $Object){
            $this->addObject($placeholder, $Object);
        }
    }

    public function addObject($placeholder, &$Object) {
        $this->_object[$placeholder] = $Object;
        if(isset($this->_controller) && !isset($this->_controller->$placeholder)){
            $this->_controller->$placeholder = $Object;
        }
    }

    public function &getObject($object_name) {
        return $this->_object[$object_name];
    }

    public function setController(&$Controller) {
        $this->_controller = 
        $this->C =
        $Controller;
    }

    public function &getController() {
        return $this->_controller;
    }

    public function t($string, $array = null, $name_space = null) {
        return Ak::t($string, $array, !empty($name_space) ? $name_space :
                AkConfig::getOption('locale_namespace', 
                    (!empty($this->_locale_namespace) ? $this->_locale_namespace : (
                        defined('AK_DEFAULT_LOCALE_NAMESPACE') ? AK_DEFAULT_LOCALE_NAMESPACE : 
                        'helpers'
                        )
                    )
                )
            );
    }
    
}

/* Deprecated */
class AkActionViewHelper extends AkBaseHelper{
}
