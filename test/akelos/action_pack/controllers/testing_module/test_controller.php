<?php

class TestingModule_TestController extends TestingModuleController 
{
    public function index() {
        $this->renderText($this->urlFor(array('action'=>'listing')));
    }
}

?>