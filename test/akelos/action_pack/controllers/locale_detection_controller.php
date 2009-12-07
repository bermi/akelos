<?php

class LocaleDetectionController extends ApplicationController
{
    public $layout = false;
    public function index() {
        $this->renderText('Hello from LocaleDetectionController');
    }

    public function check_header() {
        $this->renderText($_SERVER['HTTP_ACCEPT_LANGUAGE']);
    }

    public function get_language() {
        $this->renderText(Ak::lang());
    }

    public function get_param() {
        $this->renderText($this->params[$this->params['param']]);
    }

    public function session() {
        if(!empty($this->params['id']) && $this->params['id'] == 1234){
            $_SESSION['value'] = 1234;
        }
        $this->renderText($_SESSION['value']);
    }
}

