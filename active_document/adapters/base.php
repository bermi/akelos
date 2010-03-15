<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

class AkOdbAdapter
{
    private $_Adapter;
    public $settings_namespace = 'object_database';

    public function setupAdapter($settings = null) {
        if(!$this->_loadAdapter($settings)){
            return false;
        }
        return true;
    }

    public function connect($settings = null, $force_reconect = false) {
        if(!$this->isConnected() || $force_reconect){
            if(!$this->setupAdapter($settings)){
                return false;
            }
        }
        return $this->_Adapter->connect();
    }

    public function isConnected() {
        return !empty($this->_Adapter) ? $this->_Adapter->isConnected() : false;
    }

    private function _loadAdapter($settings = array()) {
        if(!$settings = $this->_getSettings($settings)){
            return false;
        }
        if(empty($settings['type'])){
            trigger_error(Ak::t('You must supply a valid adapter "type"'), E_USER_WARNING);
            return false;
        }
        if($this->_instantiateAdapterClass($settings['type'], $settings)){
            return true;
        }else{
            return false;
        }
    }

    private function _instantiateAdapterClass($type, $settings = array()) {
        $class_name = strstr($type,'Adapter') ? $type : 'AkOdb'.AkInflector::camelize($type).'Adapter';
        if(!class_exists($class_name) && !$this->_includeAdapterClass($settings['type'])){
            trigger_error(Ak::t('Could not find document adapter class %class', array('%class' => $class_name)), E_USER_ERROR);
            return false;
        }
        $this->_Adapter = new $class_name($settings);
        return true;
    }

    private function _includeAdapterClass($type) {
        return include_once($this->_getAdapterFilePath($type));
    }

    private function _getAdapterFilePath($type) {
        return AK_ACTIVE_DOCUMENT_DIR.DS.'adapters'.DS.AkInflector::underscore($type).'.php';
    }

    private function _getSettings($settings = null) {
        if(is_string($settings)) {
            $this->_useCustomNamespace($settings);
        }
        $settings = !is_array($settings) ? Ak::getSettings($this->settings_namespace, false) : $settings;
        if(empty($settings)){
            trigger_error(Ak::t('You must provide a connection settings array or create a config/%namespace.yml based on config/sample/object_database.yml', array('%namespace' => $this->settings_namespace)), E_USER_WARNING);
            return false;
        }
        return $settings;
    }

    private function _useCustomNamespace($namespace) {
        if(file_exists(AkConfig::getDir('config').DS.$namespace.'.yml')){
            $this->settings_namespace = $namespace;
        }
    }

    public function __call($name, $attributes = array()) {
        return call_user_func_array(array($this->_Adapter, $name), $attributes);
    }
}