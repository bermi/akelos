<?php

/**
 * Helpers are normally loaded in the context of a controller call, but some
 * times they might be useful in Mailers, Comand line tools or for unit testing
 * 
 * Some helpers might require information available only on a conroller context
 * such as current URL, Request and Response information among others.
 */
class AkHelperLoader extends AkObject
{
    var $_Controller;
    var $_Handler;

    function __construct()
    {
        $this->_Handler = new stdClass();
    }

    function setController(&$ControllerInstance)
    {
        $this->_Controller =& $ControllerInstance;
        $this->setHandler($this->_Controller);
    }

    /**
     * $HandlerInstance is the object where all the helpers will be instantiated as attributes.
     * 
     * Like setController but for Mailers and Testing
     */
    function setHandler(&$HandlerInstance)
    {
        $this->_Handler =& $HandlerInstance;
    }

    /**
     * Creates an instance of each available helper and links it into into current handler.
     * 
     * Per example, if a helper TextHelper is located into the file text_helper.php. 
     * An instance is created on current controller
     * at $this->text_helper. This instance is also available on the view by calling $text_helper.
     * 
     * Helpers can be found at lib/AkActionView/helpers (this might change in a future)
     */
    function instantiateHelpers()
    {
        $helpers = $this->getDefaultHelpers();
        $helpers = array_merge($helpers, $this->getApplicationHelpers());
        $helpers = array_merge($helpers, $this->getPluginHelpers());

        if(!empty($this->_Controller)){
            $helpers = array_merge($helpers, $this->_Controller->getModuleHelper());
            $helpers = array_merge($helpers, $this->_Controller->getCurrentControllerHelper());
        }

        $handler =& $this->_Handler;

        require_once(AK_LIB_DIR.DS.'AkActionView'.DS.'AkActionViewHelper.php');

        $available_helpers = array();
        foreach ($helpers as $file=>$helper){
            $helper_class_name = AkInflector::camelize(AkInflector::demodulize(strstr($helper, 'Helper') ? $helper : $helper.'Helper'));
            $full_path = preg_match('/[\\\\\/]+/',$file);
            $file_path = $full_path ? $file : AK_LIB_DIR.DS.'AkActionView'.DS.'helpers'.DS.$file;
            include_once($file_path);

            if(class_exists($helper_class_name)){
                $attribute_name = $full_path ? AkInflector::underscore($helper_class_name) : substr($file,0,-4);
                $available_helpers[] = $attribute_name;
                $handler->$attribute_name =& new $helper_class_name(&$handler);
                if(method_exists($handler->$attribute_name,'setController')){
                    $handler->$attribute_name->setController(&$handler);
                }
                if(method_exists($handler->$attribute_name,'init')){
                    $handler->$attribute_name->init();
                }
            }
        }
        !defined('AK_ACTION_CONTROLLER_AVAILABLE_HELPERS') && define('AK_ACTION_CONTROLLER_AVAILABLE_HELPERS', join(',',$available_helpers));
    }



    function getDefaultHelpers()
    {
        $handler =& $this->_Handler;
        $handler->helpers = !isset($handler->helpers) ? 'default' : $handler->helpers;

        if($handler->helpers == 'default'){
            $available_helpers = Ak::dir(AK_LIB_DIR.DS.'AkActionView'.DS.'helpers',array('dirs'=>false));
            $helper_names = array();
            foreach ($available_helpers as $available_helper){
                $helper_names[$available_helper] = AkInflector::classify(substr($available_helper,0,-10));
            }
            return $helper_names;
        }else{
            $handler->helpers = Ak::toArray($handler->helpers);
        }
        return $handler->helpers;
    }

    function getApplicationHelpers()
    {
        $handler =& $this->_Handler;
        $handler->app_helpers = !isset($handler->app_helpers) ? null : $handler->app_helpers;

        $helper_names = array();
        if ($handler->app_helpers == 'all'){
            $available_helpers = Ak::dir(AK_HELPERS_DIR,array('dirs'=>false));
            $helper_names = array();
            foreach ($available_helpers as $available_helper){
                $helper_names[AK_HELPERS_DIR.DS.$available_helper] = AkInflector::classify(substr($available_helper,0,-10));
            }

        } elseif (!empty($handler->app_helpers)){
            foreach (Ak::toArray($handler->app_helpers) as $helper_name){
                $helper_names[AK_HELPERS_DIR.DS.AkInflector::underscore($helper_name).'_helper.php'] = AkInflector::camelize($helper_name);
            }
        }
        return $helper_names;
    }

    function getPluginHelpers()
    {
        $handler =& $this->_Handler;
        $handler->plugin_helpers = !isset($handler->plugin_helpers) ? 'all' : $handler->plugin_helpers;

        $helper_names = AkHelperLoader::addPluginHelper(false); // Trick for getting helper names set by AkPlugin::addHelper
        if(empty($helper_names)){
            return array();
        }elseif ($handler->plugin_helpers == 'all'){
            return $helper_names;
        }else {
            $selected_helper_names = array();
            foreach (Ak::toArray($handler->plugin_helpers) as $helper_name){
                $helper_name = AkInflector::camelize($helper_name);
                if($path = array_shift(array_keys($helper_names, AkInflector::camelize($helper_name)))){
                    $selected_helper_names[$path] = $helper_names[$path];
                }
            }
            return $selected_helper_names;
        }
    }

    /**
     * Used for adding helpers to the base class like those added by the plugins engine.
     *
     * @param string $helper_name Helper class name like CalendarHelper
     * @param array $options - path: Path to the helper class, defaults to AK_PLUGINS_DIR/helper_name/lib/helper_name.php
     */
    function addPluginHelper($helper_name, $options = array())
    {
        static $helpers = array();
        if($helper_name === false){
            return $helpers;
        }
        $underscored_helper_name = AkInflector::underscore($helper_name);
        $default_options = array(
        'path' => AK_PLUGINS_DIR.DS.$underscored_helper_name.DS.'lib'.DS.$underscored_helper_name.'.php'
        );
        $options = array_merge($default_options, $options);
        $helpers[$options['path']] = $helper_name;
    }
}

?>