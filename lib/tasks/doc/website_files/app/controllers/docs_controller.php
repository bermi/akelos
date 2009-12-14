<?php

class DocsController extends ApplicationController
{
    private $_authorized_users = array('akelos' => 'docs');
    
    public function __construct(){
        if(!in_array(AK_REMOTE_IP, array('localhost','127.0.0.1','::1'))){
            parent::init();
            $this->beforeFilter(array('authenticate' => array('except' => array('index'))));
        }
    }
    
    public function authenticate() {
        return $this->authenticateOrRequestWithHttpBasic('Docs', $this->_authorized_users);
    }
    
    public function index () {
        $this->redirectToAction('guide');
    }

    public function guide () {
        $this->layout = AkConfig::getDir('views').DS.'layouts'.DS.'docs'.DS.'guide.tpl';
        $this->docs_helper->docs_path = 'akelos'.DS.'guides';
        $this->guide = $this->docs_helper->get_doc_contents(
            empty($this->params['id']) ? 'getting_started' : $this->params['id']);
    }
}

