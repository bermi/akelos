<?php

class LocaleDetectionController extends ApplicationController 
{
    var $layout = false;
    function index()
    {
        $this->renderText('Hello from LocaleDetectionController');
    }
    
    function check_header()
    {
        $this->renderText($_SERVER['HTTP_ACCEPT_LANGUAGE']);
    }
    
    function get_language()
    {
        $this->renderText(Ak::lang());
    }
    
    function get_param()
    {
        $this->renderText($this->params[$this->params['param']]);
    }
        
    function session()
    {
        if(!empty($this->params['id']) && $this->params['id'] == 1234){
            $_SESSION['value'] = 1234;
        }
        $this->renderText(@$_SESSION['value']);
    }
    
}


?>