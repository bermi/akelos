<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2006, Akelos Media, S.L.  & Bermi Ferrer Martinez |
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
 * @package ActionView
 * @subpackage Helpers
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

class AkActionViewHelper extends AkObject 
{
    var $locales_namespace = 'helpers';
    
    function AkActionViewHelper()
    {
        $args = func_get_args();
        if(!empty($args[0]) && is_array($args[0])){
            foreach (array_keys($args[0]) as $object_name){
                $this->addObject($object_name, $args[0][$object_name]);
            }
        }
    }
    
    function addObject($object_name, &$object)
    {
        $this->_object[$object_name] =& $object;
        if(!isset($this->_controller->$object_name)){
            $this->_controller->$object_name =& $object;
        }
    }

    function &getObject($object_name)
    {
        return $this->_object[$object_name];
    }

    function setController(&$controller)
    {
        $this->_controller =& $controller;
    }
    
    function t($string, $array = null, $name_space = null)
    {
        $name_space = empty($name_space) ? $this->locales_namespace : $name_space;
        return Ak::t($string, $array, $name_space);
    }
}


?>
