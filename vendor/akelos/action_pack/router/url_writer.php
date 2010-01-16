<?php

class AkUrlWriter
{
    /**
     * @var AkRequest
     */
    private $Request;

    /**
     * @var AkRouter
     */
    private $Router;

    public function __construct($Request=null, AkRouter $Router=null) {
        if (!$Router){
            $Router = AkRouter::getInstance();
        }
        if (!$Request){
            $Request = AkRequest::getInstance();
        }
        $this->Request = $Request;
        $this->Router  = $Router;

        $this->persistValuesFromRequest($Request);
    }

    private $values_from_request;
    private $parameters_from_actual_request;

    public function persistValuesFromRequest(AkRequest $Request) {
        $this->values_from_request = array(
        'relative_url_root' => $Request->getRelativeUrlRoot(),
        'protocol'          => $Request->getProtocol(),
        'host'              => $Request->getHostWithPort()
        );
        $this->parameters_from_actual_request = $Request->getParametersFromRequestedUrl();
    }

    public function urlFor($options = array()) {
        return $this->rewrite($options);
    }

    public function rewrite($options = array()) {
        list($params,$options) = $this->extractOptionsFromParameters($options);
        $this->rewriteParameters($params);
        $named_route = $this->extractNamedRoute($params);
        return (string)$this->Router->urlize($params,$named_route)
        ->setOptions(array_merge($this->values_from_request,$options));
    }

    private function extractNamedRoute(&$params) {
        $named_route = @$params['use_named_route'];
        unset($params['use_named_route']);
        return $named_route;
    }

    private function extractOptionsFromParameters($params) {
        $keywords = array('anchor', 'only_path', 'host', 'protocol', 'trailing_slash', 'skip_relative_url_root');

        $options = array_intersect_key($params,array_flip($keywords));
        $params  = array_diff_key($params,$options);

        if (isset($params['password']) && isset($params['user'])){
            $options['user'] = $params['user'];
            $options['password'] = $params['password'];
            unset($params['user'],$params['password']);
        }

        return array($params,$options);
    }

    private function rewriteParameters(&$params) {
        $this->injectParameters($params);
        $this->extractModuleFromControllerIfGiven($params);
        $this->fillInLastParameters($params);
        $this->overwriteParameters($params);
    }

    private function injectParameters(&$params) {
        if(!empty($params['params'])){
            $params = array_merge($params,$params['params']);
            unset($params['params']);
        }
    }

    private function extractModuleFromControllerIfGiven(&$params) {
        if(!empty($params['controller']) && strstr($params['controller'], '/')){
            $params['module'] = substr($params['controller'], 0, strrpos($params['controller'], '/'));
            $params['controller'] = substr($params['controller'], strrpos($params['controller'], '/') + 1);
        }
    }

    private function fillInLastParameters(&$params) {
        $actual_parameters = $this->getParametersFromActualRequest($params);
        if(!empty($actual_parameters)){
            $this->handleLocale($params, $actual_parameters);
            $old_params = array();
            foreach ($actual_parameters as $k=>$v){
                if (array_key_exists($k,$params)){
                    if (is_null($params[$k])) unset($params[$k]);
                    break;
                }
                $old_params[$k] = $v;
            }
            $params = array_merge($old_params,$params);
        }
    }

    private function getParametersFromActualRequest(&$params) {
        if (!isset($params['skip_old_parameters_except'])) return $this->parameters_from_actual_request;
        $actual = array_intersect_key($this->parameters_from_actual_request,array_flip($params['skip_old_parameters_except']));
        unset ($params['skip_old_parameters_except']);
        return $actual;
    }

    private function handleLocale(&$params,&$last_params) {
        if (!empty($params['skip_url_locale'])){
            unset($last_params['lang']);
        }
        unset($params['skip_url_locale']);
    }

    private function overwriteParameters(&$params) {
        if(!empty($params['overwrite_params'])){
            $params = array_merge($params,$params['overwrite_params']);
            unset($params['overwrite_params']);
        }
    }

    static $singleton;

    /**
     * @return AkUrlWriter
     */
    static function getInstance() {
        if (!self::$singleton){
            self::$singleton = new AkUrlWriter();
        }
        return self::$singleton;
    }
}

