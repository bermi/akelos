<?php

class AkOdbAdapter
{
    private $_Adapter;
    public $settings_namespace = 'object_database';

    public function connect($settings = null, $force_reconect = false)
    {
        if(!$this->isConnected() || $force_reconect){
            if(!$this->_loadAdapter($settings)){
                return false;
            }
        }
        return true;
    }

    public function isConnected()
    {
        return !empty($this->_Adapter) ? $this->_Adapter->isConnected() : false;
    }

    private function _loadAdapter($settings = array())
    {
        if(!$settings = $this->_getSettings($settings)){
            return false;
        }
        return true;
    }

    private function _getSettings($settings = null)
    {
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

    private function _useCustomNamespace($namespace)
    {
        if(file_exists(AK_CONFIG_DIR.DS.$namespace.'.yml')){
            $this->settings_namespace = $namespace;
        }
    }
}