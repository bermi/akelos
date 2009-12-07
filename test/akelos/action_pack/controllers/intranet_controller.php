<?php

class IntranetController extends ApplicationController
{
    public function index() {
        $this->renderText('Intranet Controller Works');
    }

    public function _forbidden() {
        $this->renderText('Holly s**t, fix this!');
    }
}
