<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

class DocsController extends ApplicationController
{
    private $_authorized_users = array('akelos' => 'docs');
    
    public function __construct(){
        if(!AkRequest::isLocal()){
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

