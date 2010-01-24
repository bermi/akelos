<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

class AkXmlRpcServer
{
    public $_ActionWebServiceServer;
    public $options = array();

    public function AkXmlRpcServer(&$ActionWebServiceServer) {
        $this->_ActionWebServiceServer = $ActionWebServiceServer;
    }

    public function init($options = array()) {
        $default_options = array(
        'dynamic_server_class_name' => 'AkDynamicXmlRpcServer'
        );

        $this->options = array_merge($default_options, $options);

        if(!empty($this->_ActionWebServiceServer->_services)){
            foreach (array_keys($this->_ActionWebServiceServer->_services) as $name_space){
                $this->_addWebService($name_space, $this->_ActionWebServiceServer->_services[$name_space]);
            }
        }
    }

    public function _addWebService($service_name, &$WebService) {
        $Apis = $WebService->getApis();

        foreach (array_keys($Apis) as $k){
            $api_methods = $Apis[$k]->getApiMethods();
            foreach (array_keys($api_methods) as $k){
                $api_method = $api_methods[$k];
                $public_name = AkInflector::variablize($api_method->public_name);
                $signatures = var_export(array_merge($api_method->returns, $api_method->expects),  true);
                $documentation = var_export($this->_getDocumentationForMethod($api_method), true);

                $this->_callbacks[] = "

        \$this->addCallback(
        '$service_name.$public_name',
        'this:_{$service_name}_{$api_method->name}_call',
        $signatures,
        $documentation
        );
            ";

                $this->_methods[] = "

    public function _{$service_name}_{$api_method->name}_call() {
        \$args = func_get_args();
        return call_user_func_array(array(\$this->_{$service_name}, '".$api_method->name."'), (array)\$args[0]);
    }
                    ";

            }
        }
    }

    public function _getDocumentationForMethod($ApiMethod) {

        $doc = !empty($ApiMethod->documentation)? $ApiMethod->documentation."\n" : '';
        foreach (array('expects', 'returns') as $expects_or_returns){
            if(!empty($ApiMethod->{$expects_or_returns})){
                foreach ($ApiMethod->{$expects_or_returns} as $k=>$type){
                    $doc .= "\n".(
                    $expects_or_returns == 'expects' ?
                    Ak::t(AkInflector::ordinalize($k+1)).' parameter as' : 'Returns'
                    )." $type:";
                    if(!empty($ApiMethod->{$expects_or_returns.'_documentation'}[$k])){
                        $doc .= ' '.$ApiMethod->{$expects_or_returns.'_documentation'}[$k];
                    }
                }
            }
        }
        return $doc;

    }


    public function _generateServerClassCode() {
        $this->_serverClassCode = "<?php
class {$this->options['dynamic_server_class_name']} extends AkIxrInstrospectionServer
{
    public function {$this->options['dynamic_server_class_name']}() {
        \$this->IXR_IntrospectionServer();
    }
    ";

        $this->_serverClassCode .= join("\n", $this->_methods);

        $this->_serverClassCode .= '
    public function init() {
    '. join("\n", $this->_callbacks).'

        $this->serve();
    }

}

?>';


    }

    public function serve() {
        $this->_generateServerClassCode();
        eval('?>'.$this->_serverClassCode.'<?php ');
        $Server = new $this->options['dynamic_server_class_name'];
        $this->_linkWebServicesToServer($Server);
        $Server->init();
    }

    public function _linkWebServicesToServer(&$Server) {
        if(!empty($this->_ActionWebServiceServer->_services)){
            foreach (array_keys($this->_ActionWebServiceServer->_services) as $name_space){
                $Server->{'_'.$name_space} = $this->_ActionWebServiceServer->_services[$name_space];
            }
        }
    }

}

require_once(AK_CONTRIB_DIR.DS.'incutio'.DS.'IXR_Library.inc.php');

class AkIxrInstrospectionServer extends IXR_IntrospectionServer
{
    public $_services = array();

    public function output($xml) {
        $xml = '<?xml version="1.0"?>'."\n".$xml;
        $length = strlen($xml);
        header('Connection: close');
        header('Content-Length: '.$length);
        header('Content-Type: text/xml');
        header('Date: '.date('r'));
        echo $xml;
        exit;
    }

    public function _addService($service_name, &$ServiceInstance) {
        $this->_services[$service_name] = $ServiceInstance;
    }
}

