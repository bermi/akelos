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

/**
 * ActionWebservice enables .... (add docs)
 *
 */
class AkActionWebService extends AkObject
{
    var $_controller;
    var $_callback_settings = array();
    var $_serverClassCode = '';

    function AkActionWebService(&$Controller)
    {
        $this->_controller =& $Controller;
    }

    function _loadCallbackSettings()
    {
        $options = $this->_getDefaultCallbackSettings();

        if(is_array($this->_controller->api)){
            $options = array_merge($options, $this->_controller->api);
        }
        $this->_callback_settings = $options;
    }

    function _getDefaultCallbackSettings()
    {
        $methods = get_class_methods($this->_controller);
        $callback_settings = array();
        foreach ($methods as $method){
            if(substr($method,0, 5) == '_api_'){
                $api_method = substr($method, 5);
                $callback_settings[AkInflector::variablize($api_method)] = array(
                'method'=>$method,
                'doc' => 'nodoc',
                'returns' => array('string')
                );
            }
        }

        return $callback_settings;
    }

    function _generateServerClassCode()
    {
        $this->_serverClassCode = "<?php
class AkActionWebServer extends AkIxrInstrospectionServer {
    var \$_controller;
    function AkActionWebServer(&\$Controller) {
        \$this->_controller =& \$Controller;
        \$this->_controller->_ApiServer =& \$this;
        \$this->IXR_IntrospectionServer();";

        $init = '';
        $methods = '';
        foreach ($this->_callback_settings as $method_name=>$details){
            $details['method'] = !empty($details['method']) ? $details['method'] : 
            (is_numeric($method_name) && is_string($details) ? $details : $method_name);

            if(strstr($method_name, '.')){
                $method_parts = explode('.',$method_name);
                $method_name = AkInflector::variablize(array_shift($method_parts));
                $namespace = AkInflector::variablize(array_shift($method_parts));
            }else{
                $method_name = AkInflector::variablize($method_name);
                $namespace = AkInflector::variablize($this->_controller->getControllerName());
            }

            $init .= "\$this->addCallback(
            '".$namespace.'.'.$method_name."',
            'this:".@$details['method']."',".
            var_export(@is_array(@$details['returns'])?@$details['returns']:array(@$details['returns']), true).','.
            "'".str_replace("'","\\'", @$details['doc'])."');\n";
            
                $methods .= "function ".$details['method']."(){\$args = func_get_args(); ".
                "return call_user_func_array(array(&\$this->_controller, '".$details['method']."'), \$args); }\n";
           
        }

        $this->_serverClassCode .= $init.' $this->serve(); } '.$methods.'} ?>';

    }

    function _runServer()
    {
        eval('?>'.$this->_serverClassCode.'<?');

        $Server = new AkActionWebServer($this->_controller);

        return $this->_controller->afterAction('api');
        exit;
    }

}

require_once(AK_VENDOR_DIR.DS.'incutio'.DS.'IXR_Library.inc.php');

class AkIxrInstrospectionServer extends IXR_IntrospectionServer
{
    function output($xml)
    {
        $xml = '<?xml version="1.0"?>'."\n".$xml;
        $length = strlen($xml);
        header('Connection: close');
        header('Content-Length: '.$length);
        header('Content-Type: text/xml');
        header('Date: '.date('r'));
        echo $xml;
    }
}


?>