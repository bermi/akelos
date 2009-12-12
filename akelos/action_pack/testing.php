<?php

class AkActionControllerTest extends AkWebTestCase
{
    // Any objects that are stored as instance variables in actions for use in views.
    public $assigns = array();
    // Any cookies that are set.
    public $cookies = array();
    // Any message/object living in the flash.
    public $flash = array();
    // Any object living in session variables.
    public $session = array();

    public $Controller; // The controller processing the request
    public $Request;    // The request
    public $Response;   // The response

    public function get($action, $params = array(), $session = array(), $flash = array()){
    }

    public function post()  {   }
    public function put()   {   }
    public function head()  {   }
    public function delete(){   }

    public function assertSelect(){}
    public function cssSelect(){}

    public function getControllerInstance(){
        //$this->get(AkConfig::getOption('testing_url').'/action_pack/public/index.php?ak=invalid');
    }

    public function getControllerName(){
    }
}

class AkHelperTest extends AkUnitTest
{
}