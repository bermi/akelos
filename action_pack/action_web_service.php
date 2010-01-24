<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

class AkActionWebService
{
    public $_apis = array();

    public function __construct() {
        $this->_linkWebServiceApis();
    }

    public function _linkWebServiceApis() {
        if(!empty($this->web_service_api)){
            $this->web_service_api = Ak::toArray($this->web_service_api);
            foreach ($this->web_service_api as $api){
                $this->_linkWebServiceApi($api);
            }
        }
    }

    public function _linkWebServiceApi($api) {
        $api_path = AkInflector::underscore($api);
        if(substr($api_path,-4) != '_api'){
            $api_name_space = $api_path;
            $api_path = $api_path.'_api';
        }else{
            $api_name_space = substr($api_path,0,-4);
        }
        $api_class_name = AkInflector::camelize($api_path);

        require_once(AkConfig::getDir('apis').DS.$api_path.'.php');

        $this->_apis[$api_name_space] = new $api_class_name;
    }

    public function &getApis() {
        return $this->_apis;
    }
}

