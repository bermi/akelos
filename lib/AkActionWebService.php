<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2006, Akelos Media, S.L.  & Bermi Ferrer Martinez |
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
 * @package ActionWebservice
 * @subpackage Base
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

class AkActionWebService extends AkObject
{
    var $_apis = array();
    
    function __construct()
    {
        $this->_linkWebServiceApis();
    }

    function _linkWebServiceApis()
    {
        if(!empty($this->web_service_api)){
            $this->web_service_api = Ak::toArray($this->web_service_api);
            foreach ($this->web_service_api as $api){
                $this->_linkWebServiceApi($api);
            }
        }
    }
    
    function _linkWebServiceApi($api)
    {
        $api_path = AkInflector::underscore($api);
        if(substr($api_path,-4) != '_api'){
            $api_name_space = $api_path;
            $api_path = $api_path.'_api';
        }else{
            $api_name_space = substr($api_path,0,-4);
        }
        $api_class_name = AkInflector::camelize($api_path);
        
        require_once(AK_LIB_DIR.DS.'AkActionWebService'.DS.'AkActionWebServiceApi.php');
        require_once(AK_APIS_DIR.DS.$api_path.'.php');
        
        $this->_apis[$api_name_space] =& new $api_class_name;
    }
    
    function &getApis()
    {
        return $this->_apis;
    }
}


?>