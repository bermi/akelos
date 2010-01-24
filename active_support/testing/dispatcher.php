<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

class AkTestDispatcher extends AkDispatcher
{
    public $_controllerVars;

    public function __construct($controllerVars = array()) {
        $this->_controllerVars = $controllerVars;
    }

    public function get($url,$params = array()) {
        $_GET = $params;
        $_SERVER['REQUEST_METHOD'] = 'GET';
        return $this->process($url);
    }

    public function post($url, $postParams) {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        if (is_array($postParams)) {
            $_POST = $postParams;
        } else {
            $_ENV['RAW_POST_DATA'] = $postParams;
        }
        return $this->process($url);
    }

    public function put($url, $data) {
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

    public function delete($url) {
        $_SERVER['REQUEST_METHOD'] = 'DELETE';
        return $this->process($url);
    }

    public function process($url) {

        $_SERVER['PHP_SELF'] = '/index.php';
        $parts = parse_url($url);
        if (isset($parts['scheme'])) {
            $_SERVER['HTTPS']=$parts['scheme']=='https';
        }
        if (isset($parts['query'])) {
            parse_str($parts['query'], $_GET);
        }
        $_REQUEST['ak'] = isset($parts['path']) ? $parts['path'] : '/';
        $_SERVER['REQUEST_URI'] = isset($parts['path'])?$parts['path']:'/';
        $_SERVER['SERVER_NAME'] =  isset($parts['host'])?$parts['host']:null;
        return $this->dispatch();
    }

    public function dispatch() {
        $this->Request =  new AkRequest();
        $this->Response = new AkResponse();
        $controller = $this->Request->recognize();

        if ($controller === false) {
            return false;
        } else {

            $this->Controller = $controller;
            if (is_array($this->_controllerVars)) {
                foreach ($this->_controllerVars as $key=>$value) {
                    $this->Controller->$key = $value;
                }
            }
            $this->Controller->process($this->Request, $this->Response);
        }
        return true;
    }
}