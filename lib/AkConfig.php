<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// WARNING THIS CODE IS EXPERIMENTAL

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2006, Akelos Media, S.L.  & Bermi Ferrer Martinez |
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
 * @package AkelosFramework
 * @subpackage Configuration
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

class AkConfig
{
    var $_settings = array();
    var $scope = 'core';
    var $default_options = array('storage'=>'AkPhpConfigFile');
    var $options = array();
    var $Storage;

    function AkConfig()
    {
        $options = func_get_args();
        $this->options = array_merge($this->default_options, $this->options);

        if(empty($this->Storage)){
            $storage_class_name = $this->options['storage'];
            require_once(AK_LIB_DIR.DS.'AkConfig'.DS.$this->options['storage'].'.php');
            $this->Storage =& new $storage_class_name();
        }
    }

    function set($attribute, $value)
    {
        $scope = $this->scope;
        if(strstr('/',$attribute)){
            list($scope, $attribute) = explode('/', $attribute);
        }elseif($this->getType() == 'plugin'){
            return $this->set($this->getPluginName().'/'.$attribute, $value);
        }
        return $this->_set($scope, $attribute, $value);
    }

    function _set($scope, $attribute, $value)
    {
        $this->_saveAfterExecution();
        $this->_settings[$scope][$attribute] = $value;
    }

    function loadAttributes()
    {
        $this->_settings = $this->Storage->getAll();
    }

    function get($attribute)
    {
        $scope = $this->scope;
        if(strstr('/',$attribute)){
            list($scope, $attribute) = explode('/', $attribute);
        }elseif($this->getType() == 'plugin'){
            return $this->get($this->getPluginName().'/'.$attribute, $value);
        }
        return $this->_get($scope, $attribute);
    }

    function _get($scope, $attribute)
    {
        return isset($this->_settings[$scope][$attribute]) ? $this->_settings[$scope][$attribute] : false;
    }

    function _saveAfterExecution($action)
    {
        static $registered;
        if(!isset($registered)){
            $registered = true;
            register_shutdown_function(array(&$this,'saveAll'));
        }
    }

    function saveAll()
    {
        $this->Storage->save($this->_settings);
    }
}

?>