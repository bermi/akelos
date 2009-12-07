<?php

class AkActionWebServiceClient
{
    public $_available_drivers = array('xml_rpc');
    public $_Client;

    public function __construct($client_driver)
    {
        $client_driver = AkInflector::underscore($client_driver);
        if(in_array($client_driver, $this->_available_drivers)){
            $client_class_name = 'Ak'.AkInflector::camelize($client_driver).'Client';
            require_once(AK_ACTION_PACK_DIR.DS.'action_web_service'.DS.'clients'.DS.$client_driver.'.php');
            $this->_Client = new $client_class_name($this);

        }else {
            trigger_error(Ak::t('Invalid Web Service driver provided. Available Drivers are: %drivers', array('%drivers'=>join(', ',$this->_available_drivers))), E_USER_WARNING);
        }
    }

    public function init()
    {
        if(method_exists($this->_Client, 'init')){
            $args = func_get_args();
            call_user_func_array(array($this->_Client, 'init'), $args);
        }
    }

    public function hasErrors()
    {
        return $this->_Client->hasErrors();
    }

    public function getErrors()
    {
        return $this->_Client->getErrors();
    }
}


