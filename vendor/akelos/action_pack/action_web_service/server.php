<?php

class AkActionWebServiceServer
{
    public $_available_drivers = array('xml_rpc');
    public $_Server;
    public $_services = array();

    public function __construct($server_driver) {
        $server_driver = AkInflector::underscore($server_driver);
        if(in_array($server_driver, $this->_available_drivers)){
            $server_class_name = 'Ak'.AkInflector::camelize($server_driver).'Server';
            require_once(AK_ACTION_PACK_DIR.DS.'action_web_service'.DS.'servers'.DS.$server_driver.'.php');
            $this->_Server = new $server_class_name($this);

        }else {
            trigger_error(Ak::t('Invalid Web Service driver provided. Available Drivers are: %drivers', array('%drivers'=>join(', ',$this->_available_drivers))), E_USER_WARNING);
        }
    }

    public function addService($service) {
        $service_file = AkInflector::underscore($service);
        if(substr($service_file,-8) != '_service'){
            $service_file = $service_file.'_service';
        }
        $service_model = AkInflector::camelize($service_file);
        $service_name_space = substr($service_file,0,-8);

        if(empty($this->_services[$service_name_space])){

            require_once(AkConfig::getDir('models').DS.$service_file.'.php');

            if(!class_exists($service_model)){
                trigger_error(Ak::t('Could not find class for the service %service at %models_dir', array('%service'=>$service_model, '%models_dir' => AkConfig::getDir('models')), E_USER_ERROR));
                return false;
            }
            $this->_services[$service_name_space] = new $service_model();
        }
    }

    public function init() {
        if(method_exists($this->_Server, 'init')){
            $args = func_get_args();
            call_user_func_array(array($this->_Server, 'init'), $args);
        }
    }

    public function serve() {
        $this->_Server->serve();
    }
}

