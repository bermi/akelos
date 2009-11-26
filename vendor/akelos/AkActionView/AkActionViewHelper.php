<?php

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+

/**
 * @package ActionView
 * @subpackage Helpers
 * @author Bermi Ferrer <bermi a.t bermilabs c.om>
 */

class AkActionViewHelper extends AkObject
{
    public $locales_namespace = 'helpers';

    public function __construct()
    {
        $args = func_get_args();
        if(!empty($args[0]) && is_array($args[0])){
            foreach (array_keys($args[0]) as $object_name){
                $this->addObject($object_name, $args[0][$object_name]);
            }
        }
    }

    public function addObject($object_name, &$Object)
    {
        $this->_object[$object_name] = $Object;
        if(!isset($this->_controller->$object_name)){
            $this->_controller->$object_name = $Object;
        }
    }

    public function &getObject($object_name)
    {
        return $this->_object[$object_name];
    }

    public function setController(&$Controller)
    {
        $this->_controller = $Controller;
    }

    public function t($string, $array = null, $name_space = AK_DEFAULT_LOCALE_NAMESPACE)
    {
        $name_space = empty($name_space) ? $this->locales_namespace : $name_space;
        return Ak::t($string, $array, $name_space);
    }
}

