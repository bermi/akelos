<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2006, Akelos Media, S.L.  & Bermi Ferrer Martinez |
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
 * @package AkelosFramework
 * @subpackage AkActionController
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

defined('AK_WEB_REQUEST_CONNECT_TO_DATABASE_ON_INSTANTIATE') ?  null :
define('AK_WEB_REQUEST_CONNECT_TO_DATABASE_ON_INSTANTIATE', true);

defined('AK_WEB_REQUEST_START_SESSION_ON_INSTANTIATE') ?  null :
define('AK_WEB_REQUEST_START_SESSION_ON_INSTANTIATE', true);

defined('AK_WEB_REQUEST_ENABLE_INTERNATIONALIZATION_SUPPORT_ON_INSTANTIATE') ?  null :
define('AK_WEB_REQUEST_ENABLE_INTERNATIONALIZATION_SUPPORT_ON_INSTANTIATE', true);

/**
* Http Web Browser requests are handled by this class
*/
class AkWebRequest extends AkActionController
{
    var $__ParentController;
    var $AppController;
    
    function init(&$ParentController)
    {
        $this->__ParentController =& $ParentController;
        
        $this->__ParentController->_ssl_requirement ? $this->__ParentController->beforeFilter('_ensureProperProtocol') : false;

        if($this->__ParentController->_autoIncludePaginator){
            require_once(AK_LIB_DIR.DS.'AkActionController'.DS.'AkPaginator.php');
        }

        if(AK_WEB_REQUEST_CONNECT_TO_DATABASE_ON_INSTANTIATE){
            $this->__ParentController->__connectToDatabase();
        }

        if(AK_WEB_REQUEST_START_SESSION_ON_INSTANTIATE){
            $this->__ParentController->__startSession();
        }

        require_once(AK_LIB_DIR.DS.'AkRequest.php');
        $this->__ParentController->Request =& AkRequest();

        if(AK_WEB_REQUEST_ENABLE_INTERNATIONALIZATION_SUPPORT_ON_INSTANTIATE && AK_AVAILABLE_LOCALES != 'en'){
            $this->__ParentController->__enableInternationalizationSupport();
        }

        $this->__ParentController->__mapRoutes();
        
    }

    
    function handle()
    {
        $this->__ParentController->params = $this->__ParentController->Request->getParams();
        
        $this->_file_name = AkInflector::underscore($this->__ParentController->params['controller']).'_controller.php';
        $this->_class_name = AkInflector::camelize($this->__ParentController->params['controller']).'Controller';
        
        $this->_includeController();

        Ak::t('Akelos'); // We need to get locales ready

        $class_name = $this->_class_name;
        $this->AppController =& new $class_name(array('controller'=>true));

        if(!empty($this->AppController)){
            $this->AppController->beforeFilter('instantiateHelpers');
        }

        // Mixing bootstrap controller attributes with this controller attributes
        foreach (array_keys(get_class_vars('AkActionController')) as $varname){
            if(empty($this->AppController->$varname)){
                $this->AppController->$varname =& $this->__ParentController->$varname;
            }
        }

        empty($this->__ParentController->params) ? 
        ($this->__ParentController->params = $this->__ParentController->Request->getParams()) : null;

        $action_name = $this->_getActionName();

        $this->_before($this->AppController);

        $this->AppController->performActionWithFilters($action_name);

		$this->_after($this->AppController);

        $this->AppController->Response->outputResults();

    }

    
    function _before(&$Controller)
    {
        empty($Controller->model) ? ($Controller->model = $Controller->params['controller']) : null;
        empty($Controller->models) ? ($Controller->models = array()) : null;
        empty($Controller->_assigns) ? ($Controller->_assigns = array()) : null;
        empty($Controller->_default_render_status_code) ? ($Controller->_default_render_status_code = '200 OK') : null;
        $Controller->_enableLayoutOnRender = 
            !isset($Controller->_enableLayoutOnRender) ? true : $Controller->_enableLayoutOnRender;

        empty($Controller->cookies) && isset($_COOKIE) ? ($Controller->cookies =& $_COOKIE) : null;

        if(empty($Controller->Response)){
            require_once(AK_LIB_DIR.DS.'AkResponse.php');
            $Controller->Response =& AkResponse();
        }

        if(empty($Controller->Template)){
            require_once(AK_LIB_DIR.DS.'AkActionView.php');
            require_once(AK_LIB_DIR.DS.'AkActionView'.DS.'AkPhpTemplateHandler.php');
            $Controller->Template =& new AkActionView(AK_APP_DIR.DS.'views'.DS.$Controller->Request->getController(),
            $Controller->Request->getParameters(),$Controller->Request->getController());
            
            $Controller->Template->_controllerInstance =& $Controller;
            $Controller->Template->_registerTemplateHandler('tpl','AkPhpTemplateHandler');
        }

        $Controller->passed_args = !isset($Controller->Request->pass)? array() : $Controller->Request->pass;

        $Controller->instantiateIncludedModelClasses();

        
        if(isset($Controller->api)){
            require_once(AK_LIB_DIR.DS.'AkActionWebService.php');
            $Controller->aroundFilter(new AkActionWebService($Controller));
        }
    }

    function _after(&$Controller)
    {
        $Controller->_handleFlashAttribute();
        if (!$Controller->_hasPerformed()){
            $Controller->_enableLayoutOnRender ? $Controller->renderWithLayout() : $Controller->renderWithoutLayout();
        }
        if(!AK_DESKTOP && defined('AK_ENABLE_STRICT_XHTML_VALIDATION') && AK_ENABLE_STRICT_XHTML_VALIDATION || !empty($Controller->validate_output)){
            $Controller->_validateGeneratedXhtml();
        }
    }
    
    
    
    function _includeController()
    {
        $controller_path = AK_CONTROLLERS_DIR.DS.$this->_file_name;
        if(!file_exists($controller_path)){
            $this->_raiseError(
                Ak::t('Could not find the file /app/controllers/<i>%controller_file_name</i> for '.
                        'the controller %controller_class_name',
                array('%controller_file_name'=>$this->_file_name, 
                    '%controller_class_name'=>$this->_class_name)));
        }
        require_once(AK_APP_DIR.DS.'application_controller.php');
        require_once($controller_path);
        if(!class_exists($this->_class_name)){
            $this->_raiseError(Ak::t('Controller <i>%controller_name</i> does not exist', 
            array('%controller_name' => $this->_class_name)));
        }
    }
    
    function _getActionName()
    {
        $this->AppController->_action_name = 
            empty($this->AppController->_action_name) ? 
            (AkInflector::underscore($this->AppController->params['action'])) : 
            $this->AppController->_action_name;
        
        if ($this->AppController->_action_name[0] == '_' || 
            !method_exists($this->AppController, $this->AppController->_action_name)){
            $this->_raiseError(Ak::t('Action <i>%action</i> does not exist for controller <i>%controller_name</i>',
            array('%controller_name'=>$this->_class_name,'%action'=>$this->AppController->_action_name)));
        }
        return $this->AppController->_action_name;
    }
    
    function _raiseError($error)
    {
            if(AK_ENVIRONMENT == 'production'){
                header('Status: HTTP/1.1 404 Not Found');
                header('Location: '.AK_URL.'404.html');
                exit;
            }else{
                trigger_error($error, E_USER_ERROR);
            }
    }

}

?>