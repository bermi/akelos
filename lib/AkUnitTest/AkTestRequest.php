<?php
require_once(AK_LIB_DIR.DS.'AkRequest.php');
class AkTestRequest extends AkRequest
{

    function &recognize($Map = null)
    {
        AK_ENVIRONMENT != 'setup' ? $this->_connectToDatabase() : null;
        $this->_startSession();
        $this->_enableInternationalizationSupport();
        $this->_mapRoutes($Map);

        $params = $this->getParams();

        $module_path = $module_class_peffix = '';
        if(!empty($params['module'])){
            $module_path = trim(str_replace(array('/','\\'), DS, Ak::sanitize_include($params['module'], 'high')), DS).DS;
            $module_shared_model = AK_CONTROLLERS_DIR.DS.trim($module_path,DS).'_controller.php';
            $module_class_peffix = str_replace(' ','_',AkInflector::titleize(str_replace(DS,' ', trim($module_path, DS)))).'_';
        }

        $controller_file_name = AkInflector::underscore($params['controller']).'_controller.php';
        $controller_class_name = $module_class_peffix.AkInflector::camelize($params['controller']).'Controller';
        $controller_path = AK_CONTROLLERS_DIR.DS.$module_path.$controller_file_name;
        include_once(AK_APP_DIR.DS.'application_controller.php');

        if(!empty($module_path) && file_exists($module_shared_model)){
            include_once($module_shared_model);
        }

        if(!is_file($controller_path) || !include_once($controller_path)){
            defined('AK_LOG_EVENTS') && AK_LOG_EVENTS && $this->Logger->error('Controller '.$controller_path.' not found.');
            if(AK_ENVIRONMENT == 'development'){
                trigger_error(Ak::t('Could not find the file /app/controllers/<i>%controller_file_name</i> for '.
                'the controller %controller_class_name',
                array('%controller_file_name'=> $controller_file_name,
                '%controller_class_name' => $controller_class_name)), E_USER_ERROR);
            }elseif(@include(AK_PUBLIC_DIR.DS.'404.php')){
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
            defined('AK_LOG_EVENTS') && AK_LOG_EVENTS && $this->Logger->error('Controller '.$controller_path.' does not implement '.$controller_class_name.' class.');
            if(AK_ENVIRONMENT == 'development'){
                trigger_error(Ak::t('Controller <i>%controller_name</i> does not exist',
                array('%controller_name' => $controller_class_name)), E_USER_ERROR);
            }elseif(@include(AK_PUBLIC_DIR.DS.'405.php')){
                exit;
            }else{
                $response = new AkResponse();
                $response->addHeader('Status',405);
                return false;
                //header("HTTP/1.1 405 Method Not Allowed");
                //die('405 Method Not Allowed');
            }
        }
        $Controller =& new $controller_class_name(array('controller'=>true));
        $Controller->_module_path = $module_path;
        isset($_SESSION) ? $Controller->session =& $_SESSION : null;
        return $Controller;

    }
    
    function _fixGpcMagic()
    {
        if(!defined('AK_TEST_REQUEST_GPC_MAGIC_FIXED')){
            if (get_magic_quotes_gpc()) {
                array_walk($_GET, array('AkRequest', '_fixGpc'));
                array_walk($_POST, array('AkRequest', '_fixGpc'));
                array_walk($_COOKIE, array('AkRequest', '_fixGpc'));
            }
            define('AK_TEST_REQUEST_GPC_MAGIC_FIXED',true);
        }
    }
    
    function getHost()
    {
        if(!empty($this->_host)){
            return $this->_host;
        }
        return isset($this->env['SERVER_NAME']) ? $this->env['SERVER_NAME'] : 'localhost';
    }
}

function &AkTestRequest()
{
    $null = null;
    $AkRequest =& Ak::singleton('AkTestRequest', $null);
    return $AkRequest;
}