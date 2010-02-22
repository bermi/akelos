<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

class AkelosPanelController extends AkActionController {
    public $application_name = AK_APP_NAME;
    
    public function __construct(){
        if(!(AK_DEV_MODE && AkRequest::isLocal())){
            throw new ForbiddenActionException('You can only access the Akelos Panel from localhost and when running development environment.');
        }
    }

}

