<?php

class AkelosCore_VirtualAssetsController extends AkelosCoreController {

    public $layout = false;
        
    public function stylesheets(){
        $this->renderAction(@$this->params['id']);
    }
    public function javascripts(){
        $this->renderAction(@$this->params['id']);
    }
    public function images(){
        $this->sendFile(AkConfig::getDir('views').DS.'akelos_core'.DS.'virtual_assets'.DS.'images'.DS.str_replace('.', '',@$this->params['id']).'.'.@$this->params['format'], array('disposition' => 'inline'));
    }
}
