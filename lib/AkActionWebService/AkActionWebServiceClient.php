<?php

class AkActionWebServiceClient extends AkObject 
{
    var $_available_drivers = array('xml_rpc');
    var $_Client;
    
    function __construct($client_driver)
    {
        $client_driver = AkInflector::underscore($client_driver);
        if(in_array($client_driver, $this->_available_drivers)){
            $client_class_name = 'Ak'.AkInflector::camelize($client_driver).'Client';
            require_once(AK_LIB_DIR.DS.'AkActionWebservice'.DS.'Clients'.DS.$client_class_name.'.php');
            
            $this->_Client =& new $client_class_name($options);
            
        }else {
            trigger_error(Ak::t('Invalid Web Service driver provided. Available Drivers are: %drivers', array('%drivers'=>join(', ',$this->_available_drivers))), E_USER_ERROR);
        }
    }
    
    function init($options = array())
    {
        $this->_Client($options);
    }
    
    function serve($action, $params = array())
    {
        call_user_func_array(array(&$this->_Client, $action), $params);
    }
}

?>