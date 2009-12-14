<?php

class DocsController extends ApplicationController
{
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

