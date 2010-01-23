<?php

class AkelosPanelController extends AkActionController {
    public $application_name = AK_APP_NAME;
    
    public function __construct(){
        if(!(AK_DEV_MODE && AkRequest::isLocal())){
            die('Disabled for security reasons');
        }
    }

}

