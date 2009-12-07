<?php

class HttpRequestsController extends ApplicationController
{
    public function index() {
        $this->renderText("Hello unit tester");
    }

    public function verb(){
        $this->renderText($this->Request->getMethod());
    }

    public function test_header() {
        $this->Response->addHeader('x-test-header: akelos');
        $this->renderNothing(200);
    }

    public function code() {
        $this->renderNothing($this->params['id']);
    }

    public function get_user_agent() {
        $this->renderText($_SERVER['HTTP_USER_AGENT']);
    }

    public function json() {
        $this->renderText(Ak::toJson($this->params['testing']));
    }

    public function redirect_1() {
        $this->redirectToAction('redirect_2');
    }

    public function redirect_2() {
        $this->redirectToAction('print_3');
    }

    public function print_3() {
        $this->renderText('3');
    }

    public function persisting_cookies() {
        if(!isset($_SESSION['cookie_counter'])){
            $_SESSION['cookie_counter'] = 0;
        }
        $_SESSION['cookie_counter']++;
        $this->renderText($_SESSION['cookie_counter']);
    }
}

