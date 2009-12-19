<?php

class AuthenticationController extends ApplicationController
{
    private $_authorized_users = array('bermi' => 'secret');

    public function __construct(){
        $this->beforeFilter(array('authenticate' => array('except' => array('index'))));
    }

    public function index() {
        $this->renderText("Everyone can see me!");
    }

    public function edit(){
        $this->renderText("I'm only accessible if you know the password");
    }

    public function authenticate() {
        return $this->authenticateOrRequestWithHttpBasic('App name', $this->_authorized_users);
    }
}
