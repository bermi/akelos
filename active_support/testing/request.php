<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

class AkTestRequest extends AkRequest
{
    public function &recognize($Map = null) {
        $this->_startSession();
        $this->_enableInternationalizationSupport();
        $this->mapRoutes($Map);

        $params = $this->getParams();

        $module_path = $module_class_peffix = '';
        if(!empty($params['module'])){
            $module_path = trim(str_replace(array('/','\\'), DS, Ak::sanitize_include($params['module'], 'high')), DS).DS;
            $module_shared_model = AkConfig::getDir('controllers').DS.trim($module_path,DS).'_controller.php';
            $module_class_peffix = str_replace(' ','_',AkInflector::titleize(str_replace(DS,' ', trim($module_path, DS)))).'_';
        }

        $controller_file_name = AkInflector::underscore($params['controller']).'_controller.php';
        $controller_class_name = $module_class_peffix.AkInflector::camelize($params['controller']).'Controller';
        $controller_path = AkConfig::getDir('controllers').DS.$module_path.$controller_file_name;

        if(!empty($module_path) && file_exists($module_shared_model)){
            include_once($module_shared_model);
        }

        if(!is_file($controller_path) || !include_once($controller_path)){
            AK_LOG_EVENTS && Ak::getLogger()->error('Controller '.$controller_path.' not found.');
            if(AK_ENVIRONMENT == 'development'){
                trigger_error(Ak::t('Could not find the file /app/controllers/<i>%controller_file_name</i> for '.
                'the controller %controller_class_name',
                array('%controller_file_name'=> $controller_file_name,
                '%controller_class_name' => $controller_class_name)), E_USER_ERROR);
            }elseif(@include(AkConfig::getDir('public').DS.'404.php')){
                $response = new AkTestResponse();
                $response->addHeader('Status',404);
                return false;
                //exit;
            }else{
                //header("HTTP/1.1 404 Not Found");
                $response = new AkResponse();
                $response->addHeader('Status',404);
                return false;
                //die('404 Not found');
            }
        }
        if(!class_exists($controller_class_name)){
            AK_LOG_EVENTS && Ak::getLogger()->error('Controller '.$controller_path.' does not implement '.$controller_class_name.' class.');
            if(AK_ENVIRONMENT == 'development'){
                trigger_error(Ak::t('Controller <i>%controller_name</i> does not exist',
                array('%controller_name' => $controller_class_name)), E_USER_ERROR);
            }elseif(@include(AkConfig::getDir('public').DS.'405.php')){
                exit;
            }else{
                $response = new AkResponse();
                $response->addHeader('Status',405);
                return false;
                //header("HTTP/1.1 405 Method Not Allowed");
                //die('405 Method Not Allowed');
            }
        }
        $Controller = new $controller_class_name(array('controller'=>true));
        $Controller->setModulePath($module_path);
        isset($_SESSION) ? $Controller->session =& $_SESSION : null;
        return $Controller;

    }

    public function getHost() {
        if(!empty($this->_host)){
            return $this->_host;
        }
        return isset($this->env['SERVER_NAME']) ? $this->env['SERVER_NAME'] : 'localhost';
    }
}
