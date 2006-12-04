<?php

class AkActionWebServiceServer extends AkObject 
{
    var $_available_drivers = array('xml_rpc');
    var $_Server;
    
    function __construct($server_driver)
    {
        $server_driver = AkInflector::underscore($server_driver);
        if(in_array($server_driver, $this->_available_drivers)){
            $server_class_name = 'Ak'.AkInflector::camelize($server_driver).'Server';
            require_once(AK_LIB_DIR.DS.'AkActionWebservice'.DS.'Servers'.DS.$server_class_name.'.php');
            
            $this->_Server =& new $server_class_name($options);
            
        }else {
            trigger_error(Ak::t('Invalid Web Service driver provided. Available Drivers are: %drivers', array('%drivers'=>join(', ',$this->_available_drivers))), E_USER_ERROR);
        }
    }
    
    function init($options = array())
    {
        $this->_Server($options);
    }
    
    function serve($action, $params = array())
    {
        call_user_func_array(array(&$this->_Server, $action), $params);
    }
}

?>