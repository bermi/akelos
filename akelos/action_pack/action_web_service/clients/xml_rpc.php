<?php

require_once(AK_CONTRIB_DIR.DS.'incutio'.DS.'IXR_Library.inc.php');

class AkXmlRpcClient extends IXR_Client
{
    public $_RemoteObjects;
    public $options = array();
    public $errors = array();
    public $_IxrParameters = array();
    public $WebServiceClient;
    public $error_handler;


    public function AkXmlRpcClient(&$WebServiceClient) {
        $this->WebServiceClient = $WebServiceClient;
    }

    public function init() {
        $options = func_get_args();
        $num_args = count($options);

        if(!empty($options[$num_args-1]) && is_array($options[$num_args-1])){
            $this->options = array_pop($options);
        }

        $default_options = array(
        'user_agent' => 'Akelos XML-RPC Client',
        'build' => true,
        'remote_object_prefix' => 'AkRemote_',
        'debug' => false,
        /**
         * @todo add a better cache system for remote methods
         */
         'cache_remote_methods' => isset($_SESSION) && AK_ACTION_WEBSERVICE_CACHE_REMOTE_METHODS
         );

         $this->options = array_merge($default_options, $this->options);
         $this->_setIxrParameters($options);
         $this->_getIxrInstance();
         $this->_addOptionsToIxrInstance();

         $this->options['class_name'] = empty($this->options['class_name']) ?
         'Remote_'.$this->_getIdForRequest() : $this->options['class_name'];

         if($this->options['build']){
             $this->_buildRemoteObjects();
         }
    }

    public function _getIxrInstance() {
        call_user_func_array(array($this, 'IXR_Client'), $this->_getIxrOptions());
    }

    public function _addOptionsToIxrInstance() {
        foreach ($this->options as $k=>$v){
            $k = strtolower(str_replace('_','',$k));
            $this->$k = $v;
        }
    }

    public function _setIxrParameters($parameters = array()) {
        $this->_IxrParameters = $parameters;
    }

    public function _getIxrOptions() {
        return !empty($this->_IxrParameters) && is_array($this->_IxrParameters) ? $this->_IxrParameters : array();
    }

    public function _buildRemoteObjects() {
        $methods = $this->_getRemoteMethods();
        $objects = array_keys($methods);
        foreach ($methods as $class=>$methods){
            $class_code = $this->_buildClass($class, $methods);
            eval(' ?>'.$class_code.'<?php ');
        }

        foreach ($objects as $object){
            $class_name = $this->options['remote_object_prefix'].$object;
            $remote_attribute_name = $object;
            $remote_attribute_name_camelized = ucfirst($object);
            $this->WebServiceClient->$remote_attribute_name = new $class_name($this);
            $this->WebServiceClient->$remote_attribute_name_camelized = $this->WebServiceClient->$remote_attribute_name;
        }
    }

    public function _buildClass($class_name, $methods) {
        $reserved_words = array('and', 'as', 'break', 'case', 'cfunction', 'class', 'continue', 'declare', 'default', 'die', 'do', 'echo', 'else', 'elseif', 'empty', 'enddeclare', 'endfor', 'endforeach', 'endif', 'endswitch', 'endwhile', 'eval', 'exit', 'extends', 'false', 'for', 'foreach', 'function', 'global', 'if', 'include', 'include_once', 'list', 'new', 'not', 'null', 'old_function', 'or', 'parent', 'print', 'require', 'require_once', 'return', 'static', 'stdclass', 'switch', 'true', 'var', 'virtual', 'while', 'xor');

        $class_methods = '';
        foreach ($methods as $method){
            $function_name = in_array(strtolower($method),$reserved_words) ? 'get'.ucfirst($method) : $method;
            $method_implementation = "
    \$args = func_num_args() > 0 ? func_get_args() : array();
    array_unshift(\$args, '$class_name.$method');
    if(!call_user_func_array(array(\$this->XmlRpcClient,'query'), \$args)){
        \$this->addError(\$this->XmlRpcClient->getErrorCode().' : '.\$this->XmlRpcClient->getErrorMessage());
    }
    return !\$this->hasErrors() ? \$this->XmlRpcClient->getResponse() : false;
    ";
            $class_methods .= ' function '.$function_name."()\n    { $method_implementation \n    } ";
        }

        $ixr_options = $this->_getIxrOptions();
        foreach ($ixr_options as $option){
            $ixr_vars[] = var_export($option, true);
        }


        return "<?php \nclass {$this->options['remote_object_prefix']}$class_name \n{
        var \$XmlRpcClient;
        var \$errors;
    public function {$this->options['remote_object_prefix']}$class_name(&\$XmlRpcClient)\n    {
    \$this->XmlRpcClient = new IXR_Client(".join(',', $ixr_vars).");

     foreach (\$XmlRpcClient->options as \$k=>\$v){
         \$k = strtolower(str_replace('_','',\$k));
         \$this->XmlRpcClient->\$k = \$v;
     }
    }
        public function addError(\$error)\n    {\n        \$this->errors[\$error] = '';\n    }
        public function hasErrors(){\n        return !empty(\$this->errors);\n    }
        public function getErrors(){\n        return array_keys(\$this->errors);\n    }
        public function getMethods(){\n        return ".var_export($methods,true).";\n    }
        $class_methods
}\n?>";
    }

    public function _getRemoteMethods() {
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
        if(is_array($_remote_methods)){
            foreach ($_remote_methods as $method){
                $parts = explode('.', $method);
                $remote_methods[array_shift($parts)][] = array_shift($parts);
            }

            if($this->options['cache_remote_methods']){
                $_SESSION['__XML-RPC_methods_for_'.$this->options['class_name']] = $remote_methods;
            }
        }

        return $remote_methods;
    }

    public function call() {
        $args = func_num_args() > 0 ? func_get_args() : array();

        if(!call_user_func_array(array($this,'query'), $args)){
            $this->addError($this->getErrorCode().' : '.$this->getErrorMessage());
        }
        return !$this->hasErrors() ? $this->getResponse() : false;
    }


    public function addError($error) {
        $this->errors[$error] = '';
    }

    public function hasErrors() {
        return !empty($this->errors);
    }

    public function getErrors() {
        return array_keys($this->errors);
    }

    public function _getIdForRequest() {
        return md5($this->server.$this->port.$this->path);
    }
}

