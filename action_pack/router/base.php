<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

/**
 * Native PHP URL rewriting for the Akelos Framework.
 */

class NoMatchingRouteException extends Exception
{ }

class AkRouter
{
    public  $automatic_lang_segment = true;
    public  $generate_helper_functions = AK_GENERATE_HELPER_FUNCTIONS_FOR_NAMED_ROUTES;

    public $routes = array();
    private $resources = array();

    public function connect($url_pattern, $defaults = array(), $requirements = array(), $conditions = array()) {
        return $this->connectNamed(null,$url_pattern,$defaults,$requirements,$conditions);
    }
    
    public function root($defaults = array(), $requirements = array(), $conditions = array()) {
        return $this->connectNamed('root', '/', $defaults,$requirements,$conditions);
    }

    protected function handleApiShortcuts(&$url_pattern,&$defaults,&$requirements) {
        $this->addLanguageSegment($url_pattern);
        $this->deprecatedMoveExplicitRequirementsFromDefaultsToRequirements($defaults, $requirements);
        $this->deprecatedMoveImplicitRequirementsFromDefaultsToRequirements($defaults, $requirements);
        $this->deprecatedRemoveDelimitersFromRequirements($requirements);
        $this->deprecatedRemoveExplicitOptional($defaults);
    }

    private function addLanguageSegment(&$url_pattern) {
        if ($this->automatic_lang_segment) $url_pattern = '/:lang/'.ltrim($url_pattern, '/');
    }

    private function deprecatedRemoveDelimitersFromRequirements(&$requirements) {
        foreach ($requirements as &$value){
            if ($value{0}=='/'){
                #Ak::deprecateWarning('Don\'t use delimiters in the requirements of your routes.');
                $value = trim($value,'/');
            }
        }
    }

    private function deprecatedMoveImplicitRequirementsFromDefaultsToRequirements(&$defaults, &$requirements) {

        foreach ($defaults as $key=>$value){
            if (is_string($value) && $value{0}=='/'){
                #Ak::deprecateWarning('Don\'t use implicit requirements in the defaults-array. Move it explicitly to the requirements-array.');
                $requirements[$key] = trim($value,'/');
                unset ($defaults[$key]);
            }
        }
    }

    private function deprecatedRemoveExplicitOptional(&$defaults) {
        foreach ($defaults as $key=>$value){
            if ($value === OPTIONAL){
                unset ($defaults[$key]);
            }
        }
    }

    private function deprecatedMoveExplicitRequirementsFromDefaultsToRequirements(&$defaults, &$requirements) {
        if (isset($defaults['requirements'])){
            $requirements = array_merge($defaults['requirements'], $requirements);
            unset($defaults['requirements']);
        }
    }

    public function addRoute($name = null, AkRoute $Route) {
        $name && !isset($this->routes[$name]) ? ($this->routes[$name] = $Route) : ($this->routes[] = $Route);
        return $Route;
    }

    public function &getRoutes() {
        return $this->routes;
    }

    public function match(AkRequest $Request) {
        foreach ($this->routes as $Route){
            try {
                $params = $Route->parametrize($Request);
                $this->currentRoute = $Route;
                return $params;
            } catch (RouteDoesNotMatchRequestException $e) {}
        }
        throw new NoMatchingRouteException('No route matches "'.$Request->getPath().'" with {:method=>:'.$Request->getMethod().'}');
    }

    public function urlize($params, $name = null) {
        if ($name){
            if(!isset($this->routes[$name])){
                throw new NoMatchingRouteException('Named route '.$name.' is not available within this router instance.');
            }
            return $this->routes[$name]->urlize($params);
        }

        foreach ($this->routes as $Route){
            try {
                $url = $Route->urlize($params);
                return $url;
            } catch (RouteDoesNotMatchParametersException $e) { }
        }
        throw new NoMatchingRouteException(json_encode($params));
    }

    public function toUrl($params) {
        return $this->urlize($params);
    }

    /**
     * catches
     *    :name_url($params) and maps to ->urlizeUsingNamedRoute(:name,$params) 
     *    :name($args*)      and maps to ->connectNamed(:name,$args*)
     */
    public function __call($name,$args) {
        if (preg_match('/^(.*)_url$/',$name,$matches)){
            $args[] = $matches[1];
            return call_user_func_array(array($this,'urlize'),$args);
        }elseif (!empty($args)){
            array_unshift($args,$name);
            return call_user_func_array(array($this,'connectNamed'),$args);
        }
    }

    public function resources($name, $options = array()) {
        $Resources = new AkResources($this);
        return $Resources->resources($name, $options);
    }

    public function resource($name, $options = array()) {
        $Resources = new AkResources($this);
        return $Resources->resource($name, $options);
    }


    public function connectNamed($name, $url_pattern = '', $defaults = array(), $requirements = array(), $conditions = array()) {
        $this->_logNamedRoute($name);
        $this->handleApiShortcuts($url_pattern, $defaults,  $requirements);

        if(isset($defaults['conditions'])){
            throw new Exception('Could not connect named route with conditions');
        }

        $Route = new AkRoute($url_pattern,$defaults,$requirements,$conditions);
        if ($this->generate_helper_functions && !empty($name)){
            AkRouterHelper::generateHelperFunctionsFor($name,$Route);
        }
        return $this->addRoute($name,$Route);
    }

    private $_named_routes = array();
    private function _logNamedRoute($name){
        if(is_string($name)){
            $this->_named_routes[] = $name;
        }
    }

    public function getNamedRouteNames(){
        return $this->_named_routes;
    }

    /**
     * @return AkRouter
     */
    static function getInstance() {
        if (!$Router = Ak::getStaticVar('AkRouterSingleton')){
            $Router = new AkRouter();
            $Router->loadMap();
            Ak::setStaticVar('AkRouterSingleton', $Router);
        }
        return $Router;
    }

    public function loadMap($file_name=AK_ROUTES_MAPPING_FILE) {
        $Map =& $this;

        if(!@include($file_name)){
            $this->connectDefaultRoutes();
        }
    }

    public function connectDefaultRoutes(){
        /*
        if(AK_DEV_MODE && AkRequest::isLocal()){
            $this->connect('/:controller/:action/:id', array(
            'controller' => 'akelos_dashboard',
            'action' => 'index',
            'module' => 'akelos_panel',
            'rebase' => AK_AKELOS_UTILS_DIR.DS.'akelos_panel'
            ));
            $this->connect('/', array(
            'controller' => 'akelos_dashboard',
            'action' => 'index',
            'module' => 'akelos_panel'));
            return;
        }
        */
        $this->connect(':controller/:action/:id');
        $this->connect(':controller/:action/:id.:format');
    }

}

//somehow dirty and therefore outsourced
if (!defined('AK_URL_REWRITE_ENABLED')){
    if (!defined('AK_ENABLE_URL_REWRITE') || AK_ENABLE_URL_REWRITE){
        AkRouterConfig::loadUrlRewriteSettings();
    }
}

