<?php
require_once(AK_LIB_DIR . DS . 'AkDispatcher.php');
require_once(AK_LIB_DIR . DS .'AkUnitTest'. DS . 'AkTestRequest.php');
require_once(AK_LIB_DIR . DS .'AkUnitTest'. DS . 'AkTestResponse.php');
class AkTestDispatcher extends AkDispatcher
{
    var $_controllerVars;
    
    function AkTestDispatcher($controllerVars = array())
    {
        $this->_controllerVars = $controllerVars;
        
    }
    
    function __construct($controllerVars = array()) 
    {
        $this->_controllerVars = $controllerVars;
    }
    function get($url)
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        return $this->process($url);
    }
    
    function post($url, $postParams)
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        if (is_array($postParams)) {
            $_POST = $postParams;
        } else {
            $_ENV['RAW_POST_DATA'] = $postParams;
        }
        return $this->process($url);
    }
    
    function put($url, $data)
    {
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_SERVER['CONTENT_LENGTH'] = strlen($data);
        $fh = fopen('php://input', 'w');
        if ($fh) {
            fputs($fh, $data);
            fclose($fh);
            return $this->process($url);
        } else {
            return false;
        }
    }
    
    function delete($url)
    {
        $_SERVER['REQUEST_METHOD'] = 'DELETE';
        return $this->process($url);
    }
    function process($url)
    {
        $_SERVER['PHP_SELF'] = '/index.php';
        $parts = parse_url($url);
        if (isset($parts['scheme'])) {
            $_SERVER['HTTPS']=$parts['scheme']=='https'; 
            
        }
        if (isset($parts['query'])) {
            $parts = preg_split('&', $parts['query']);
            foreach ($parts as $p) {
                $gets = split('=',$p);
                $_GET[$gets[0]]=isset($gets[1])?$gets[1]:null;
            }
        }
        $_REQUEST['ak'] = isset($parts['path'])?$parts['path']:'/';
        $_SERVER['REQUEST_URI'] = isset($parts['path'])?$parts['path']:'/';
        $_SERVER['SERVER_NAME'] =  isset($parts['host'])?$parts['host']:null;
        
        
        return $this->dispatch();
    }

    function dispatch()
    {
        $this->Request = &new AkTestRequest();
        $this->Response = &new AkTestResponse();
        $controller = & $this->Request->recognize();
        if ($controller === false) {
            return false;
        } else {
            $this->Controller = &$controller;
            if (is_array($this->_controllerVars)) {
                foreach ($this->_controllerVars as $key=>$value) {
                    $this->Controller->$key = $value;
                }
            }
            $this->Controller->process(&$this->Request, &$this->Response);

        }
        return true;
    }
}