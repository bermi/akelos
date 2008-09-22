<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2006, Akelos Media, S.L.  & Bermi Ferrer Martinez |
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+



defined('AK_PLUGINS_DIR') ? null : define('AK_PLUGINS_DIR', AK_APP_DIR.DS.'vendor'.DS.'plugins');
defined('AK_PLUGINS') ? null : define('AK_PLUGINS', 'auto');

/**
 * Base class that all Akelos plugins should extend
 * 
 * @package    Plugins
 * @subpackage Base
 * @author     Bermi Ferrer <bermi a.t akelos c.om> 2007
 * @copyright  Copyright (c) 2002-2007, Akelos Media, S.L. http://www.akelos.org
 * @license    GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */
class AkPlugin
{

    /**
     * Plugin priority
     * @var    integer
     * @access public 
     */
    var $priority = 100;

    /**
     * This method will add the functionality of the code available at $path_to_code which
     * inherits from $class_name to a new class named Extensible$class_name
     * 
     * You can extend the same object from multiple plugins. So you can doo something like
     * 
     * Example:
     * 
     * finder_on_steroids
     * 
     *  @ app/vendor/plugins/finder_on_steroids/init.php
     * 
     * class FinderOnSteroidsPlugin extends AkPlugin {
     *      function load(){
     *          $this->extendClassWithCode('AkActiveRecord', 'lib/FinderOnSteroids.php');
     *      }
     * }
     * 
     *  @ app/vendor/plugins/finder_on_steroids/lib/FinderOnSteroids.php
     * 
     * class FinderOnSteroids extends AkActiveRecord {
     *      function findSteroids(){
     *          //
     *      }
     * }
     * 
     * This will create a new class named ExtensibleAkActiveRecord class you can use 
     * as parent of your ActiveRecord class at app/shared_model.php
     * 
     * @param    string $class_name   Class name to extend
     * @param    string $path_to_code Path to the source code file relative to your plugin base path.
     * @priority int $priority Multiple plugins can chain methods for extending classes. 
     *           A higher priority will will take precedence over a low priority.
     */
    function extendClassWithCode($class_name, $path_to_code, $priority = 100)
    {
        if(empty($this->PluginManager->ClassExtender)){
            require_once(AK_LIB_DIR.DS.'AkClassExtender.php');
            $this->PluginManager->ClassExtender =& new AkClassExtender();
        }

        $this->PluginManager->ClassExtender->extendClassWithSource($class_name, $this->getPath().DS.ltrim($path_to_code, './\\'), $priority);
    }


    function observeModel($model_name, &$Observer, $priority = 100)
    {

    }
    
    function addHelper($helper_name, $helper_path = null)
    {
        require_once(AK_LIB_DIR.DS.'AkActionView'.DS.'AkHelperLoader.php');
        $helper_name = AkInflector::camelize($helper_name);
        $helper_path = empty($helper_path) ? $this->getPath().DS.'lib'.DS.AkInflector::underscore($helper_name).'.php' : $helper_path;
        AkHelperLoader::addPluginHelper($helper_name, array('path' => $helper_path));
    }

    /**
     * Gets the base path for a given plugin
     * 
     * @return string Plugin path
     * @access public
     */
    function getPath()
    {
        return $this->PluginManager->getBasePath($this->name);
    }
}


/**
 * The Plugin loader inspects for plugins, loads them in order and instantiates them.
 * 
 * @package    Plugins
 * @subpackage Loader
 * @author     Bermi Ferrer <bermi a.t akelos c.om> 2007
 * @copyright  Copyright (c) 2002-2007, Akelos Media, S.L. http://www.akelos.org
 * @license    GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */
class AkPluginLoader
{

    /**
     * Base path for plugins
     * @var    string
     * @access public 
     */
    var $plugins_path = AK_PLUGINS_DIR;

    /**
     * List of available plugins
     * @var    array
     * @access private
     */
    var $_available_plugins = array();

    /**
     * Plugin instances
     * @var    array  
     * @access private
     */
    var $_plugin_instances = array();

    /**
     * Priority plugins
     * @var    array  
     * @access private
     */
    var $_priorized_plugins = array();

    /**
     * Goes trough the plugins directory and loads them.
     * 
     * @return void  
     * @access public
     */
    function loadPlugins()
    {
        $this->instantiatePlugins();
        $Plugins =& $this->_getPriorizedPlugins();
        foreach (array_keys($Plugins) as $k) {
            if(method_exists($Plugins[$k], 'load')){
                $Plugins[$k]->load();
            }
        }

        $this->extendClasses();
    }

    /**
     * Extends core classes with plugin code. EXPERIMENTAL
     * 
     * @return void  
     * @access public
     */
    function extendClasses()
    {
        if(isset($this->ClassExtender)){
            $this->ClassExtender->extendClasses();
        }
    }


    /**
     * Short description for function
     * 
     * Long description (if any) ...
     * 
     * @return void  
     * @access public
     */
    function instantiatePlugins()
    {
        foreach ($this->getAvailablePlugins() as $plugin){
            $this->instantiatePlugin($plugin);
        }
    }

    /**
     * Instantiates a plugin
     * 
     * If the plugin has a init.php file in its root path with a PluginNamePlugin class, it will instantiate the plugin
     * and add it to the plugin instance stack
     * 
     * @param  string $plugin_name Plugin name
     * @return boolean Returns true if can instantiate the plugin and false if the plugin could not be intantiated.   
     * @access public 
     */
    function instantiatePlugin($plugin_name)
    {
        $init_path = $this->getBasePath($plugin_name).DS.'init.php';
        if(file_exists($init_path)){
            $plugin_class_name = AkInflector::camelize($plugin_name).'Plugin';
            require_once($init_path);
            if(class_exists($plugin_class_name)){
                $Plugin =& new $plugin_class_name();
                $Plugin->name = $plugin_name;
                $Plugin->priority = empty($Plugin->priority) ? 10 : $Plugin->priority;
                $Plugin->PluginManager =& $this;
                $this->_plugin_instances[$Plugin->priority][] =& $Plugin;
                return true;
            }else{
                trigger_error(Ak::t('"%name" class does not exist and it\'s needed by the "%plugin_name" plugin. ', array('%name'=>$plugin_class_name, '%plugin_name'=>$plugin_name)), E_USER_WARNING);
            }
        }
        
        return false;
    }

    /**
     * Gets a list of available plugins.
     * 
     * If AK_PLUGINS is set to 'auto' it will get a list of existing directories at AK_PLUGINS_DIR
     * 
     * @return array    Array of existing plugins
     * @access public
     */
    function getAvailablePlugins()
    {
        if(empty($this->_available_plugins)){
            if(AK_PLUGINS == 'auto'){
                $this->_findPlugins();
            }else{
                $this->_available_plugins = AK_PLUGINS === false ? array() : Ak::toArray(AK_PLUGINS);
            }
        }
        return $this->_available_plugins;
    }

    /**
     * Gets a plugin base path.) ...
     * 
     * @param  string $plugin_name Plugins name
     * @return string Plugin root path
     * @access public 
     */
    function getBasePath($plugin_name)
    {
        return AK_PLUGINS_DIR.DS.Ak::sanitize_include($plugin_name);
    }
    
    /**
     * Gets a priorized list of plugins, where the priority is defined by the var $priority attribute
     * 
     * @return array   Priorized plugins
     * @access private
     */
    function &_getPriorizedPlugins()
    {
        if(!empty($this->_plugin_instances) && empty($this->_priorized_plugins)){
            ksort($this->_plugin_instances);
            foreach (array_keys($this->_plugin_instances) as $priority){
                foreach (array_keys($this->_plugin_instances[$priority]) as $k){
                    $this->_priorized_plugins[] =& $this->_plugin_instances[$priority][$k];
                }
            }
        }
        return $this->_priorized_plugins;
    }

    /**
     * Loads a list of existing plugins to $this->_available_plugins by inspecting the plugins directory.
     * 
     * @return void   
     * @access private
     */
    function _findPlugins()
    {
        $plugin_dirs = Ak::dir(AK_PLUGINS_DIR, array('dirs' => true, 'files' => false));
        $this->_available_plugins = array();
        foreach ($plugin_dirs as $plugin_dir){
            $plugin_dir = array_pop($plugin_dir);
            if($plugin_dir[0] != '.'){
                $this->_available_plugins[] = $plugin_dir;
            }
        }
    }
}

?>