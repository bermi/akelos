<?php

class IntranetController extends ApplicationController 
{

    function index()
    {
        $this->renderText('Intranet Controller Works');
    }

    function _forbidden()
    {
        $this->renderText('Holly s**t, fix this!');
    }
}

?>