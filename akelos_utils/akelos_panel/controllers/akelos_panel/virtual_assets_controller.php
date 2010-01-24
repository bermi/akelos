<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

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
