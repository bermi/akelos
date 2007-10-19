<?php

class AuthenticationController extends ApplicationController
{
    var $_authorized_users = array('bermi' => 'secret');
    
    function __construct(){
        $this->beforeFilter(array('authenticate' => array('except' => array('index'))));
    }

    function index() {
        $this->renderText("Everyone can see me!");
    }

    function edit(){
        $this->renderText("I'm only accessible if you know the password");
    }

    function authenticate(){
        return $this->_authenticateOrRequestWithHttpBasic('App name', $this->_authorized_users);
    }
}

?>