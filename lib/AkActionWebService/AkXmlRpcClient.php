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

require_once('vendor'.DS.'incutio'.DS.'IXR_Library.inc.php');

/**
 * //$Client = new AkXmlRpcClient('http://localhost:8888', array('debug'=>true, 'remote_methods'=>array('Calendar'=>array('getMonth'))));
$Client = new AkXmlRpcClient('http://localhost:8888', array(
'debug'=>false,
'build'=>true));

echo $Client->Num->div(2006, 20);
 *
 */

class AkXmlRpcClient extends IXR_Client
{
    var $_RemoteObjects;
    var $options = array();
    var $errors = array();
    var $_IxrParameters = array();

    function AkXmlRpcClient()
    {
        $args = func_get_args();
        $num_args = func_num_args();

        if(is_array($args[$num_args-1])){
            $this->options = array_pop($args);
        }

        $default_options = array(
        'user_agent' => 'Akelos XML-RPC Client',
        'build' => false,
        'remote_object_prefix' => 'Remote_',
        'debug' => false,
        /**
         * @todo add a better cache system for remote methods
         */
         'cache_remote_methods' => isset($_SESSION)
         );

         $this->options = array_merge($default_options, $this->options);

         $this->_setIxrParameters($args);
         $this->_getIxrInstance();
         $this->_addOptionsToIxrInstance();

         $this->options['class_name'] = empty($this->options['class_name']) ?
         'Remote_'.$this->_getIdForRequest() : $this->options['class_name'];

         if($this->options['build']){
             $this->_buildRemoteObjects();
         }
    }

    function _getIxrInstance()
    {
        call_user_func_array(array(&$this, 'IXR_Client'), $this->_getIxrOptions());
    }

    function _addOptionsToIxrInstance()
    {
        foreach ($this->options as $k=>$v){
            $k = strtolower(str_replace('_','',$k));
            $this->$k = $v;
        }
    }

    function _setIxrParameters($parameters = array())
    {
        $this->_IxrParameters = $parameters;
    }

    function _getIxrOptions()
    {
        return !empty($this->_IxrParameters) && is_array($this->_IxrParameters) ? $this->_IxrParameters : array();
    }

    function _buildRemoteObjects()
    {
        $methods = $this->_getRemoteMethods();
        $objects = array_keys($methods);
        foreach ($methods as $class=>$methods){
            $class_code = $this->_buildClass($class, $methods);

            eval('?>'.$class_code.'<?');
        }

        foreach ($objects as $object){
            $class_name = $this->options['remote_object_prefix'].$object;
            $remote_attribute_name = $object;
            $remote_attribute_name_camelized = ucfirst($object);
            $this->$remote_attribute_name =& new $class_name($this);
            $this->$remote_attribute_name_camelized =& $this->$remote_attribute_name;
        }
    }

    function _buildClass($class_name, $methods)
    {
        $class_methods = '';
        foreach ($methods as $method){
            $method_implementation = "
            \$args = func_num_args() > 0 ? func_get_args() : array();
            array_unshift(\$args, '$class_name.$method');
            if(!call_user_func_array(array(&\$this->XmlRpcClient,'query'), \$args)){
                \$this->addError(\$this->XmlRpcClient->getErrorCode().' : '.\$this->XmlRpcClient->getErrorMessage());
            }
            return !\$this->hasErrors() ? \$this->XmlRpcClient->getResponse() : false;
            ";
            $class_methods .= ' function '.$method."(){ $method_implementation } ";
        }

        $ixr_vars = array();
        foreach ($this->_getIxrOptions() as $option){
            $ixr_vars[] = var_export($option, true);
        }


        return "<?php class {$this->options['remote_object_prefix']}$class_name {
        var \$XmlRpcClient;
        var \$errors;
        function {$this->options['remote_object_prefix']}$class_name(&\$XmlRpcClient){ 
            \$options = \$XmlRpcClient->_getIxrOptions();
            
            \$this->XmlRpcClient =& new IXR_Client(".join(',', $ixr_vars).");

             foreach (\$XmlRpcClient->options as \$k=>\$v){
                 \$k = strtolower(str_replace('_','',\$k));
                 \$this->XmlRpcClient->\$k = \$v;
             }
        }
        function addError(\$error) { \$this->errors[\$error] = '';}
        function hasErrors(){ return !empty(\$this->errors);}
        function getErrors(){ return array_keys(\$this->errors);} 
        function getMethods(){ return ".var_export($methods,true)."; }
        $class_methods 
        } ?>";
    }

    function _getRemoteMethods()
    {
        if(isset($this->options['remote_methods'])){
            return $this->options['remote_methods'];
        }
        if($this->options['cache_remote_methods'] && isset($_SESSION['__XML-RPC_methods_for_'.$this->options['class_name']])){
            return $_SESSION['__XML-RPC_methods_for_'.$this->options['class_name']];
        }

        if (!$this->query('system.listMethods')) {
            $this->addError('Something went wrong - '.$this->getErrorCode().' : '.
            $this->getErrorMessage());
        }

        $_remote_methods = $this->getResponse();
        $remote_methods = array();
        foreach ($_remote_methods as $method){
            $parts = explode('.', $method);
            $remote_methods[array_shift($parts)][] = array_shift($parts);
        }

        if($this->options['cache_remote_methods']){
            $_SESSION['__XML-RPC_methods_for_'.$this->options['class_name']] = $remote_methods;
        }

        return $remote_methods;
    }

    function call()
    {
        $args = func_num_args() > 0 ? func_get_args() : array();

        if(!call_user_func_array(array(&$this,'query'), $args)){
            $this->addError($this->getErrorCode().' : '.$this->getErrorMessage());
        }
        return !$this->hasErrors() ? $this->getResponse() : false;
    }


    function addError($error)
    {
        $this->errors[$error] = '';
    }

    function hasErrors()
    {
        return !empty($this->errors);
    }

    function getErrors()
    {
        return array_keys($this->errors);
    }

    function _getIdForRequest()
    {
        return md5($this->server.$this->port.$this->path);
    }
}

?>