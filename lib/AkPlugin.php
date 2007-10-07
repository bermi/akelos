<?php

defined('AK_PLUGINS_DIR') ? null : define('AK_PLUGINS_DIR', AK_APP_DIR.DS.'vendor'.DS.'plugins');
defined('AK_PLUGINS') ? null : define('AK_PLUGINS', 'auto');

class AkPlugin
{
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
     * @param string $class_name Class name to extend
     * @param string $path_to_code Path to the source code file relative to your plugin base path.
     * @priority int $priority Multiple plugins can chain methods for extending classes. 
     * A higher priority will will take precedence over a low priority.
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

    function getPath()
    {
        return $this->PluginManager->getBasePath($this->name);
    }
}


/**
 * The Plugin loader inspects for plugins, loads them in order and instantiates them.
 *
 */
class AkPluginLoader
{
    var $plugins_path = AK_PLUGINS_DIR;
    var $_available_plugins = array();
    var $_plugin_instances = array();
    var $_priorized_plugins = array();

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

    function extendClasses()
    {
        if(isset($this->ClassExtender)){
            $this->ClassExtender->extendClasses();
        }
    }

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

    function instantiatePlugins()
    {
        foreach ($this->getAvailablePlugins() as $plugin){
            $this->instantiatePlugin($plugin);
        }
    }

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
            }else{
                trigger_error(Ak::t('"%name" class does not exist and it\'s needed by the "%plugin_name" plugin. ', array('%name'=>$plugin_class_name, '%plugin_name'=>$plugin_name)), E_USER_WARNING);
            }
        } else {
            trigger_error(Ak::t('Could not load plugin %name. No no init file was found at %path', array('%name'=>$plugin_name, '%path' => $init_path)), E_USER_WARNING);
        }
    }

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

    function getBasePath($plugin_name)
    {
        return AK_PLUGINS_DIR.DS.Ak::sanitize_include($plugin_name);
    }
}

?>