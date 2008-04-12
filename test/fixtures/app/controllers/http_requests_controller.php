<?php

class HttpRequestsController extends ApplicationController
{
    function index() {
        $this->renderText("Hello unit tester");
    }

    function verb(){
        $this->renderText($this->Request->getMethod());
    }

    function test_header()
    {
        $this->Response->addHeader('x-test-header: akelos');
        $this->renderNothing(200);
    }

    function code()
    {
        $this->renderNothing($this->params['id']);
    }

    function get_user_agent()
    {
        $this->renderText($_SERVER['HTTP_USER_AGENT']);
    }

    function json()
    {
        $this->renderText(Ak::toJson($this->params['testing']));
    }

    function redirect_1()
    {
        $this->redirectToAction('redirect_2');
    }
    
    function redirect_2()
    {
        $this->redirectToAction('print_3');
    }
    
    function print_3()
    {
        $this->renderText('3');
    }
    
    
}

?>