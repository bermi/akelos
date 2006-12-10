<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2006, Akelos Media, S.L.  & Bermi Ferrer Martinez |
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
 * @package AkelosFramework
 * @subpackage AkActionWebService
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

class AkActionWebServiceServer extends AkObject
{
    var $_available_drivers = array('xml_rpc');
    var $_Server;
    var $_services = array();

    function __construct($server_driver)
    {
        $server_driver = AkInflector::underscore($server_driver);
        if(in_array($server_driver, $this->_available_drivers)){
            $server_class_name = 'Ak'.AkInflector::camelize($server_driver).'Server';
            require_once(AK_LIB_DIR.DS.'AkActionWebService'.DS.'Servers'.DS.$server_class_name.'.php');

            $this->_Server =& new $server_class_name($this);

        }else {
            trigger_error(Ak::t('Invalid Web Service driver provided. Available Drivers are: %drivers', array('%drivers'=>join(', ',$this->_available_drivers))), E_USER_WARNING);
        }
    }

    function addService($service)
    {
        $service_file = AkInflector::underscore($service);
        if(substr($service_file,-8) != '_service'){
            $service_file = $service_file.'_service';
        }
        $service_model = AkInflector::camelize($service_file);
        $service_name_space = substr($service_file,0,-8);
        
        if(empty($this->_services[$service_name_space])){

            require_once(AK_MODELS_DIR.DS.$service_file.'.php');

            if(!class_exists($service_model)){
                trigger_error(Ak::t('Could not find class for the service %service at %models_dir', array('%service'=>$service_model, '%models_dir' => AK_MODELS_DIR), E_USER_ERROR));
                return false;
            }
            $this->_services[$service_name_space] =& new $service_model();
        }
    }

    function init()
    {
        if(method_exists($this->_Server, 'init')){
            $args = func_get_args();
            call_user_func_array(array(&$this->_Server, 'init'), $args);
        }
    }

    function serve()
    {
        $this->_Server->serve();
    }
}

?>