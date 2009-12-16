<?php

class AkelosPanel_VirtualAssetsController extends AkelosPanelController {

    public $layout = false;
        
    public function stylesheets(){
        $this->renderAction(@$this->params['id']);
    }
    public function javascripts(){
        $this->renderAction(@$this->params['id']);
    }
    public function images(){
        $file_path = AkConfig::getDir('views').DS.'akelos_panel'.DS.'virtual_assets'.DS.'images'.DS.str_replace('.', '',@$this->params['id']).'.'.@$this->params['format'];
        $this->sendFile($file_path, array('disposition' => 'inline'));
    }
    public function guide_images(){
        $file_path = AkConfig::getDir('views').DS.'akelos_panel'.DS.'virtual_assets'.DS.'guide_images'.DS.str_replace('.', '',@$this->params['id']).'.'.@$this->params['format'];
        $this->sendFile($file_path, array('disposition' => 'inline'));
    }
}
