<?php
class ModifyController extends ApplicationController 
{
    function index()
    {
        return $this->renderText('index');
    }
    
    function test1()
    {
        return $this->renderText('test1');
    }
    
    function testid()
    {
        return $this->renderText('testid:'.@$this->params['id']);
    }
}
?>