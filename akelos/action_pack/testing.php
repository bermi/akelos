<?php

class AkActionControllerTest extends AkTestApplication
{
    // Any objects that are stored as instance variables in actions for use in views.
    public $assigns = array();
    // Any cookies that are set.
    public $cookies = array();
    // Any message/object living in the flash.
    public $flash = array();
    // Any object living in session variables.
    public $session = array();

    public $constants = array();
    public $controller_vars = array();

    public $controller_name;

    public $Dispatcher;

    private $_default_urlparams = array();

    public function __construct(){
        parent::__construct();
        $this->_default_urlparams = array('controller' => $this->getControllerName());
    }

    public function get($action, $params = array(), $session = array(), $flash = array()){
        return $this->_runHttpVerb(__FUNCTION__, $action, $params, $session, $flash);
    }

    public function post($action, $params = array(), $session = array(), $flash = array())  {
        return $this->_runHttpVerb(__FUNCTION__, $action, $params, $session, $flash);
    }

    public function put($action, $params = array(), $session = array(), $flash = array())   {
        return $this->_runHttpVerb(__FUNCTION__, $action, $params, $session, $flash);
    }

    public function head($action, $params = array(), $session = array(), $flash = array())  {
        return $this->_runHttpVerb(__FUNCTION__, $action, $params, $session, $flash);
    }

    public function delete($action, $params = array(), $session = array(), $flash = array()){
    }

    private function _runHttpVerb($verb, $action, $params = array(), $session = array(), $flash = array()){
        $this->controller_vars['session'] = $session;
        $this->controller_vars['flash'] = $flash;
        return parent::$verb($this->_getUrlForAction($action, $params), $params, $this->constants, $this->controller_vars);
    }

    public function getControllerName(){
        return $this->controller_name = empty($this->controller_name) ? AkInflector::underscore(preg_replace('/(Controller|_).*$/', '', get_class($this))) : $this->controller_name;
    }

    private function _getUrlForAction($action, $params = array()){
        return Ak::toUrl(array_merge($params, array_merge(array('action' => $action), $this->_default_urlparams)));
    }
}

class AkHelperTest extends AkUnitTest
{
    public $Helper;
    public $helper_name;
    public $skip_helper_instantation = false;

    public function __construct(){
        parent::__construct();
        if(!$this->skip_helper_instantation){
            $this->Helper = $this->getHelperInstance();
        }
    }

    public function &getHelperInstance(){
        $helper_name = $this->getHelperName();
        $helper_class_name = $helper_name.'Helper';
        $this->Helper = new $helper_class_name;

        $controller_class_name = $helper_name.'Controller';
        if(method_exists($this->Helper, 'setController') && class_exists($controller_class_name)){
            $this->Helper->setController(new $controller_class_name());
        }
        return $this->Helper;
    }

    public function getHelperName(){
        return $this->helper_name = empty($this->helper_name) ? AkInflector::camelize(preg_replace('/(Helper|_).*$/', '', get_class($this))) : $this->helper_name;
    }

}