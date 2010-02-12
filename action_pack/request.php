<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

/**
* Class that handles incoming request.
*
* The Request Object handles user request (CLI, GET, POST, session or
* cookie requests), transforms it and sets it up for the
* ApplicationController class, who takes control of the data
* flow.
*/
class AkRequest
{

    /**
    * Array containing the request parameters.
    *
    * This property stores the parameters parsed from the
    * parseRequest() method. This array is used by addParams()
    * method.
    *
    * @access private
    * @var array $_request
    */
    public $_request = array();

    /**
     * Keeps the original routing params received via the url,
     * this is needed for later modifying the current url for example
     *
     * @var array
     */
    public $_route_params = array();

    public $_init_check = false;
    public $__internationalization_support_enabled = false;

    public $action = AK_DEFAULT_ACTION;
    public $controller = AK_DEFAULT_CONTROLLER;
    public $view;


    /**
    * Holds information about current environment. Initially a reference to $_SERVER
    *
    * @var array
    */
    public $env = array();

    public $_format;

    protected $_url_decoded = false;

    public function __construct () {
        $this->init();
    }


    /**
    * Initialization method.
    *
    * Initialization method. Use this via the class constructor.
    *
    * @access public
    * @uses parseRequest
    * @return void
    */
    public function init($force = false) {
        if(!$this->_init_check || $force){
            $this->env =& $_SERVER;
            $this->_decodeUrl();

            $this->_mergeRequest();

            if(is_array($this->_request)){
                foreach ($this->_request as $k=>$v){
                    $this->_addParam($k, $v);
                }
            }

            $this->_init_check = true;
        }
    }

    /**
    * String parse method.
    *
    * This method gets a petition as parameter, using the "Akelos" request 
    * format. The format is:
    * 
    *       file.php?ak=/controller/action/id&paramN=valueN
    *
    * This method requires for a previous execution of the _mergeRequest() method,
    * in order to merge all the request all i one array.
    *
    * This method expands dynamically the class Request, adding a public property for
    * every parameter sent in the request.
    *
    * @access public
    * @return array
    */
    public function parseAkRequestString($ak_request_string, $pattern = '/') {
        $result = array();
        $ak_request = trim($ak_request_string,$pattern);
        if(strstr($ak_request,$pattern)){
            $result = explode($pattern,$ak_request);
        }
        return $result;
    }


    public function get($var_name) {
        return isset($this->_request[$var_name]) ? $this->_request[$var_name] : null;
    }

    public function getParams() {
        return $this->_request;
    }

    public function getAction() {
        return $this->action;
    }

    public function getController() {
        return $this->controller;
    }

    public function reset() {
        $this->_request = array();
        $this->_init_check = false;
    }

    public function set($variable, $value) {
        $this->_addParam($variable, $value);
    }

    private $requested_url;

    public function getRequestedUrl() {
        if ($this->requested_url) return $this->requested_url;

        $requested_url = isset($this->_request['ak']) ? '/'.trim($this->_request['ak'],'/') : '/';
        return $this->requested_url = $requested_url;
    }

    private $parameters_from_url;

    public function getParametersFromRequestedUrl() {
        return $this->parameters_from_url;
    }

    public function checkForRoutedRequests(AkRouter &$Router) {
        $this->parameters_from_url = $params = $Router->match($this);

        if(!isset($params['controller']) || !$this->isValidControllerName($params['controller'])){
            throw new NoMatchingRouteException('No route matches "'.$this->getPath().'" with {:method=>:'.$this->getMethod().'}');
        }
        if(empty($params['action'])){
            $params['action'] = 'index';
        }
        if(!$this->isValidActionName($params['action'])){
            throw new NoMatchingRouteException('No action was specified.');
        }
        if(!empty($params['module'])){
            if(!$this->isValidModuleName($params['module'])){
                throw new DispatchException('Invalid module '.$params['module'].'.');
            }
        }else{
            unset($this->_request['module']);
        }

        isset($params['module']) ? $this->module = $params['module'] : null;
        $this->controller = $params['controller'];
        $this->action     = $params['action'];

        if(isset($params['lang'])){
            AkLocaleManager::rememberNavigationLanguage($params['lang']);
            Ak::lang($params['lang']);
        }

        $this->_request = array_merge($this->_request, $params);
    }



    public function getRouteParams() {
        return $this->_route_params;
    }

    public function isValidControllerName($controller_name) {
        return $this->_validateTechName($controller_name);
    }

    public function isValidActionName($action_name) {
        return $this->_validateTechName($action_name);
    }

    public function isValidModuleName($module_name) {
        return preg_match('/^[A-Za-z]{1,}[A-Za-z0-9_\/]*$/', $module_name);
    }


    /**
    * Returns both GET and POST parameters in a single array.
    */
    public function getParameters() {
        if(empty($this->parameters)){
            $this->parameters = $this->getParams();
        }
        return $this->parameters;
    }

    public function getUrlParams() {
        return $_GET;
    }

    public function getUrl(){
        return AK_CURRENT_URL;
    }
    
    /**
     * Returns the path minus the web server relative installation directory. This method returns null unless the web server is apache.
     */
    public function getRelativeUrlRoot() {
        if(AK_CLI) return '';
        return str_replace('/index.php','', @$this->env['PHP_SELF']);
    }

    /**
     * Returns the locale identifier of current URL
     */
    public function getLocaleFromUrl() {
        $locale = Ak::get_url_locale();
        if(strstr($this->getUrl(),AK_SITE_URL.$locale)){
            return $locale;
        }
        return '';
    }

    public function getAcceptHeader() {
        if (!isset($this->env['HTTP_ACCEPT'])) return false;

        $accept_header = $this->env['HTTP_ACCEPT'];

        $accepts = array();
        foreach (explode(',',$accept_header) as $index=>$acceptable){
            $mime_struct = AkMimeType::parseMimeType($acceptable);

            if (empty($mime_struct['q'])) $mime_struct['q'] = '1.0';

            //we need the original index inside this structure
            //because usort happily rearranges the array on equality
            //therefore we first compare the 'q' and then 'i'
            $mime_struct['i'] = $index;
            $accepts[] = $mime_struct;
        }
        usort($accepts,array('AkMimeType','sortAcceptHeader'));

        //we throw away the old index
        foreach ($accepts as &$array){
            unset($array['i']);
        }
        return $accepts;
    }
    
    public function getBestAcceptType() {
        $mime_types = AkMimeType::getRegistered();
        if($acceptables = $this->getAcceptHeader()){
            // we group by 'quality'
            $grouped_acceptables = array();
            foreach ($acceptables as $acceptable){
                $grouped_acceptables[$acceptable['q']][] = $acceptable['type'];
            }

            foreach ($grouped_acceptables as $array_with_acceptables_of_same_quality){
                foreach (array_keys($mime_types) as $mime_type){
                    foreach ($array_with_acceptables_of_same_quality as $acceptable){
                        if ($mime_type == $acceptable){
                            return $mime_type;
                        }
                    }
                }
            }
        }
        return 'text/html';
    }

    public function getContentType() {
        if (empty($this->env['CONTENT_TYPE'])) return false;
        $mime_type_struct = AkMimeType::parseMimeType($this->env['CONTENT_TYPE']);
        return $mime_type_struct['type'];
    }

    /**
     * @return string Their mime_type, f.i. 'application/xml'
     */
    public function getMimeType() {
        if ($this->isPost() || $this->isPut()) return $this->getContentType();
        return $this->getBestAcceptType();
    }

    /**
     * @return string Our mime_type, f.i. 'xml'
     */
    public function getFormat() {
        if (isset($this->_request['format'])){
            if(!AkMimeType::isFormatRegistered($this->_request['format'])) throw new NotAcceptableException('Invalid format. Please register new formats in your config/ using AkMimeType::register("text/'.$this->_request['format'].'", "'.$this->_request['format'].'")');
            return $this->_request['format'];
        }
        return $this->lookupMimeType($this->getMimeType());
    }


    public function lookupMimeType($mime_type) {
        $mime_types = AkMimeType::getRegistered();
        if (!isset($mime_types[$mime_type])) throw new NotAcceptableException('Invalid content type. Please register new content types in your config/ using AkMimeType::register("application/vnd.ms-excel", "xls")');
        return $mime_types[$mime_type];
    }

    /**
    * Returns the HTTP request method as a lowercase symbol ('get, for example)
    */
    public function getMethod() {
        return strtolower(isset($this->env['REQUEST_METHOD'])?$this->env['REQUEST_METHOD']:'get');
    }

    /**
    * Is this a GET request?  Equivalent to $Request->getMethod() == 'get'
    */
    public function isGet() {
        return $this->getMethod() == 'get';
    }

    /**
    * Is this a POST request?  Equivalent to $Request->getMethod() == 'post'
    */
    public function isPost() {
        return $this->getMethod() == 'post';
    }

    /**
    * Is this a PUT request?  Equivalent to $Request->getMethod() == 'put'
    */
    public function isPut() {
        return isset($this->env['REQUEST_METHOD']) ? $this->getMethod() == 'put' : false;
    }

    /**
    * Is this a DELETE request?  Equivalent to $Request->getMethod() == 'delete'
    */
    public function isDelete() {
        return $this->getMethod() == 'delete';
    }

    /**
    * Is this a HEAD request?  Equivalent to $Request->getMethod() == 'head'
    */
    public function isHead() {
        return $this->getMethod() == 'head';
    }



    /**
    * Determine originating IP address.  REMOTE_ADDR is the standard
    * but will fail if( the user is behind a proxy.  HTTP_CLIENT_IP and/or
    * HTTP_X_FORWARDED_FOR are set by proxies so check for these before
    * falling back to REMOTE_ADDR.  HTTP_X_FORWARDED_FOR may be a comma-
    * delimited list in the case of multiple chained proxies; the first is
    * the originating IP.
    */
    public function getRemoteIp() {
        if(!empty($this->env['HTTP_CLIENT_IP'])){
            return $this->env['HTTP_CLIENT_IP'];
        }
        if(!empty($this->env['HTTP_X_FORWARDED_FOR'])){
            foreach ((strstr($this->env['HTTP_X_FORWARDED_FOR'],',') ? explode(',',$this->env['HTTP_X_FORWARDED_FOR']) : array($this->env['HTTP_X_FORWARDED_FOR'])) as $remote_ip){
                if($remote_ip == 'unknown' ||
                preg_match('/^((25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])\.){3}(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])$/', $remote_ip) ||
                preg_match('/^([0-9a-fA-F]{4}|0)(\:([0-9a-fA-F]{4}|0)){7}$/', $remote_ip)
                ){
                    return $remote_ip;
                }
            }
        }
        return empty($this->env['REMOTE_ADDR']) ? '' : $this->env['REMOTE_ADDR'];

    }

    /**
    * Returns the domain part of a host, such as bermilabs.com in 'www.bermilabs.com'. You can specify
    * a different <tt>tld_length</tt>, such as 2 to catch akelos.co.uk in 'www.akelos.co.uk'.
    */
    public function getDomain($tld_length = 1) {
        return preg_match('/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/',$this->getHost()) ?
        null :
        join('.',array_slice(explode('.',$this->getHost()),(1 + $tld_length)*-1));
    }

    /**
    * Returns all the subdomains as an array, so ['dev', 'www'] would be returned for 'dev.www.bermilabs.com'.
    * You can specify a different <tt>tld_length</tt>, such as 2 to catch ['www'] instead of ['www', 'akelos']
    * in 'www.akelos.co.uk'.
    */
    public function getSubdomains($tld_length = 1) {
        return preg_match('/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/',$this->getHost()) ||
        !strstr($this->getHost(),'.') ? array() : (array)array_slice(explode('.',$this->getHost()),0,(1 + $tld_length)*-1);
    }


    /**
    * Returns the request URI correctly
    */
    public function getRequestUri() {
        return $this->getProtocol().$this->getHostWithPort();
    }

    /**
    * Return 'https://' if( this is an SSL request and 'http://' otherwise.
    */
    public function getProtocol() {
        return $this->isSsl() ? 'https://' : 'http://';
    }

    /**
    * Is this an SSL request?
    */
    public function isSsl() {
        return isset($this->env['HTTPS']) && ($this->env['HTTPS'] === true || $this->env['HTTPS'] == 'on');
    }

    /**
    * Returns the interpreted path to requested resource
    */
    public function getPath() {
        return strstr($this->env['REQUEST_URI'],'?') ? substr($this->env['REQUEST_URI'],0,strpos($this->env['REQUEST_URI'],'?')) : $this->env['REQUEST_URI'];
    }

    /**
    * Returns the port number of this request as an integer.
    */
    public function getPort() {
        $this->port_as_int = AK_WEB_REQUEST ? AK_SERVER_PORT : 80;
        return $this->port_as_int;
    }

    /**
    * Returns the standard port number for this request's protocol
    */
    public function getStandardPort() {
        return $this->isSsl() ? 443 : 80;
    }

    /**
    * Returns a port suffix like ':8080' if( the port number of this request
    * is not the default HTTP port 80 or HTTPS port 443.
    */
    public function getPortString() {
        $port = $this->getPort();
        return $port == $this->getStandardPort() ? '' : ($port ? ':'.$this->getPort() : '');
    }

    /**
    * Returns a host:port string for this request, such as example.com or
    * example.com:8080.
    */
    public function getHostWithPort() {
        return $this->getHost() . $this->getPortString();
    }


    public function getHost() {
        if(!empty($this->_host)){
            return $this->_host;
        }
        return AK_WEB_REQUEST ? $this->env['SERVER_NAME'] : 'localhost';
    }

    public function &getSession() {
        return $_SESSION;
    }

    public function resetSession() {
        $_SESSION = array();
    }

    public function &getCookies() {
        return $_COOKIE;
    }


    public function &getEnv() {
        return $this->env;
    }

    public function &getHeaders(){
        return $this->env;
    }
    
    public function getServerSoftware() {
        if(!empty($this->env['SERVER_SOFTWARE'])){
            if(preg_match('/^([a-zA-Z]+)/', $this->env['SERVER_SOFTWARE'], $match)){
                return strtolower($match[0]);
            }
        }
        return '';
    }


    /**
    * Returns true if the request's 'X-Requested-With' header contains
    * 'XMLHttpRequest'. (The Prototype Javascript library sends this header with
    * every Ajax request.)
    */
    public function isXmlHttpRequest() {
        return !empty($this->env['HTTP_X_REQUESTED_WITH']) && strstr(strtolower($this->env['HTTP_X_REQUESTED_WITH']),'xmlhttprequest');
    }
    public function xhr() {
        return $this->isXmlHttpRequest();
    }

    public function isAjax() {
        return $this->isXmlHttpRequest();
    }

    static function isLocal(){
        return in_array(AkConfig::getOption('Request.remote_ip', AK_REMOTE_IP), AkConfig::getOption('local_ips', array('localhost','127.0.0.1','::1')));
    }

    /**
     * Receive the raw post data.
     * This is useful for services such as REST, XMLRPC and SOAP
     * which communicate over HTTP POST but don't use the traditional parameter format.
     */
    public function getRawPost() {
        return empty($_ENV['RAW_POST_DATA']) ? '' : $_ENV['RAW_POST_DATA'];
    }


    public function _validateTechName($name) {
        return preg_match('/^[A-Za-z]{1,}[A-Za-z0-9_]*$/',$name);
    }


    /**
    * Populates $this->_request attribute with incoming request in the following precedence:
    *
    * $_SESSION['request'] <- This will override options provided by previous methods
    * $_COOKIE
    * $_POST
    * $_GET
    * Command line params
    *
    * @access public
    * @return void Void returned. Modifies the private property "
    */
    public function _mergeRequest() {
        $this->_request = array();

        $session_params = isset($_SESSION['request']) ? $_SESSION['request'] : null;
        $command_line_params = !empty($_REQUEST)  ? $_REQUEST : null;

        $requests = array($command_line_params, $_GET, array_merge_recursive($this->getPostParams(), $this->getPutParams(), $this->_getNormalizedFilesArray()), $_COOKIE, $session_params);

        foreach ($requests as $request){
            $this->_request = (!is_null($request) && is_array($request)) ?
            array_merge($this->_request,$request) : $this->_request;
        }
    }

    public function _getNormalizedFilesArray($params = null, $first_call = true) {
        $params = $first_call ? $_FILES : $params;
        $result = array();

        $params = array_diff($params,array(''));
        if(!empty($params) && is_array($params)){
            foreach ($params as $name=>$details){

                if(is_array($details) && !empty($details['name']) &&  !empty($details['tmp_name']) &&  !empty($details['size'])){
                    if(is_array($details['tmp_name'])){
                        foreach ($details['tmp_name'] as $item=>$item_details){
                            if(is_array($item_details)){
                                foreach (array_keys($item_details) as $k){
                                    if(UPLOAD_ERR_NO_FILE != $details['error'][$item][$k]){
                                        $result[$name][$item][$k] = array(
                                        'name'=>$details['name'][$item][$k],
                                        'tmp_name'=>$details['tmp_name'][$item][$k],
                                        'size'=>$details['size'][$item][$k],
                                        'type'=>$details['type'][$item][$k],
                                        'error'=>$details['error'][$item][$k],
                                        );
                                    }
                                }
                            }else{
                                if(UPLOAD_ERR_NO_FILE != $details['error'][$item]){
                                    $result[$name][$item] = array(
                                    'name'=>$details['name'][$item],
                                    'tmp_name'=>$details['tmp_name'][$item],
                                    'size'=>$details['size'][$item],
                                    'type'=>$details['type'][$item],
                                    'error'=>$details['error'][$item],
                                    );
                                }
                            }
                        }
                    }elseif ($first_call){
                        $result[$name] = $details;
                    }else{
                        $result[$name][] = $details;
                    }
                }elseif(is_array($details)){
                    $_nested = $this->_getNormalizedFilesArray($details, false);

                    if(!empty($_nested)){
                        $result = array_merge(array($name=>$_nested), $result);
                    }
                }
            }
        }

        return $result;
    }

    // {{{ _addParams()

    /**
    * Builds (i.e., "expands") the Request class for accessing
    * the request parameters as public properties.
    * For example, when the requests is "ak=/controller/action/id&parameter=value",
    * once parsed, you can access the parameters of the request just like
    * an object, e.g.:
    *
    *   $value_to_get = $request->parameter
    *
    * @access private
    * @return void
    */
    public function _addParam($variable, $value) {
        if($variable[0] != '_'){
            return $this->$variable = $value;
        }
        return false;
    }

    /**
    * Recognizes a Request and returns the responsible controller instance
    *
    * @return AkActionController
    */
    public function &recognize($Map = null) {
        $this->_startSession();
        $this->_enableInternationalizationSupport();
        if($this->mapRoutes($Map)){
            if(AK_LOG_EVENTS) Ak::getLogger()->info('Processing '.$this->getController().'#'.$this->getAction().' (for '.$this->getRemoteIp().')');
            $Controller = $this->_getControllerInstance();
            return $Controller;
        }else{
            $false = false;
            return $false;
        }
    }

    public function getPutParams() {
        if (!$this->isPut()) return array();
        return $this->parseMessageBody($this->getMessageBody());
    }

    public function getPostParams() {
        if (!$this->isPost()) return array();
        //PHP automatically parses the input on the standard content_types 'application/x-www-form-urlencoded' etc
        if (!empty($_POST)) return $_POST;

        return $_POST = $this->parseMessageBody($this->getMessageBody());
    }

    private function parseMessageBody($data) {
        if (empty($data)) return array();

        $content_type = $this->getContentType();

        switch ($this->lookupMimeType($content_type)){
            case 'html':
                $as_array = array();
                parse_str($data,$as_array);
                return $as_array;
            case 'xml':
                return Ak::convert('xml', 'params_array',$data);
                break;
            case 'json':
                return json_decode($data,true);
            default:
                return array('put_body'=>$data);
                break;
        }
    }

    public $message_body;

    public function getMessageBody() {
        if ($this->message_body) return $this->message_body;

        $result = '';
        if(!empty($_SERVER['CONTENT_LENGTH'])){
            $putdata = fopen('php://input', 'r');
            $result = fread($putdata, $_SERVER['CONTENT_LENGTH']);
            fclose($putdata);
        }
        return $this->message_body = $result;
    }

    static function getInstance() {
        if (!$Request = Ak::getStaticVar('AkRequestSingleton')){
            $Request = new AkRequest();
            Ak::setStaticVar('AkRequestSingleton', $Request);
        }
        return $Request;
    }


    public function getReferer() {
        $referer = AK_HOST;
        if(isset($_SESSION['_akrf']) && preg_match('/^\w+:\/\/.*/', $_SESSION['_akrf'])){
            $referer = $_SESSION['_akrf'];
        }elseif(isset($this->env['HTTP_REFERER']) && preg_match('/^\w+:\/\/.*/', $this->env['HTTP_REFERER'])){
            $referer = $this->env['HTTP_REFERER'];
        }
        return $referer;
    }

    public function reportError($options = array()){
        if(AK_LOG_EVENTS && !empty($options['log'])) Ak::getLogger()->error($options['log']);

        if(AK_DEV_MODE && !empty($options['message'])){
            trigger_error($options['message'], E_USER_ERROR);
        }else{
            $status_code = intval(empty($options['status_code']) ? 501 : $options['status_code']);
            $status_header = AkResponse::getStatusHeader($status_code);
            if(!@include(AkConfig::getDir('public').DS.$status_code.'.php')){
                @Ak::header($status_header);
                echo str_replace('HTTP/1.1 ', '', $status_header);
            }
        }
        exit(0);
    }

    public function saveRefererIfNotRedirected() {
        if(isset($_SESSION) && !$this->isAjax()){
            $_SESSION['_akrf'] = $this->getRequestUri().$this->getPath();
        }
        return true;
    }

    public function mapRoutes($Router = null) {
        if(empty($Router)){
            $Router = AkRouter::getInstance();
        }
        try{
            $this->checkForRoutedRequests($Router);
        }catch(Exception $e){
            if(AK_TEST_MODE){
                throw $e;
            }else{
                $ExceptionDispatcher = new AkExceptionDispatcher();
                $ExceptionDispatcher->renderException($e);
                return false;
            }
        }
        return true;
    }

    protected function _decodeUrl() {
        if(!$this->_url_decoded){
            array_walk($_GET, array($this, '_decodeUrlItem'));
            $this->_url_decoded = true;
        }
    }

    protected function _decodeUrlItem(&$item) {
        if (is_array($item)) {
            array_walk($item, array($this, '_decodeUrlItem'));
        }else {
            $item = urldecode($item);
        }
    }

    private function &_getControllerInstance(){
        $params = $this->getParams();
        if($rebase_path = AkConfig::getOption('rebase_path', false)){
            AkConfig::rebaseApp($rebase_path);
        }
        $module_details = $this->_getModuleDetailsFromParams($params);
        $controller_details = $this->_getControllerDetailsFromParamsAndModuleDetails($params, $module_details);

        $this->_includeModuleSharedController($module_details);
        $this->_includeController($controller_details);
        $this->_ensureControllerClassExists($controller_details);

        $Controller = new $controller_details['class_name'](array('controller'=>true));
        $Controller->setModulePath($module_details['path']);
        $this->_linkSessionToController($Controller);
        return $Controller;
    }

    private function _linkSessionToController(&$Controller){
        if(isset($_SESSION)){
            $Controller->session =& $_SESSION;
            $this->saveRefererIfNotRedirected();
        }
    }

    private function _getModuleDetailsFromParams($params = array()){
        $details = array();
        $details['name'] =
        $details['path'] =
        $details['class_peffix'] = '';
        if(!empty($params['module'])){
            $details['name'] = Ak::sanitize_include($params['module'], 'high');
            $details['path'] = trim(str_replace(array('/','\\'), DS, $details['name']), DS).DS;
            $details['shared_controller'] = AkConfig::getDir('controllers').DS.trim($details['path'],DS).'_controller.php';
            $details['class_peffix'] = AkInflector::camelize($params['module']).'_';
        }
        return $details;
    }

    private function _includeModuleSharedController($module_details = array()){
        if(!empty($module_details['path']) && isset($module_details['shared_controller']) && file_exists($module_details['shared_controller'])){
            include_once($module_details['shared_controller']);
        }
    }

    private function _includeController($controller_details = array()){
        if(!is_file($controller_details['path']) || !@include_once($controller_details['path'])){
            $Exception = new Exception(Ak::t('Could not find the file %controller_file_name for '.
            'the controller %controller_class_name',
            array('%controller_file_name'=> $controller_details['path'],
            '%controller_class_name' => $controller_details['class_name'])));
            $Exception->controller = $controller_details['class_name'];
            $Exception->params = $this->getParams();
            throw $Exception;
        }
    }

    private function _ensureControllerClassExists($controller_details = array()){
        if(!class_exists($controller_details['class_name'])){
            $Exception = new Exception(Ak::t('Expected %file to define %controller_name',
            array('%file' => $controller_details['path'], '%controller_name' => $controller_details['class_name'])));
            $Exception->controller = $controller_details['class_name'];
            $Exception->params = $this->getParams();
            throw $Exception;
        }
    }

    private function _getControllerDetailsFromParamsAndModuleDetails($params = array(), $module_details = array()){
        $details = array();
        $controller = isset($params['controller'])?$params['controller']:'';
        $details['file_name'] = AkInflector::underscore($controller).'_controller.php';
        $details['class_name'] = $module_details['class_peffix'].AkInflector::camelize($controller).'Controller';
        $details['path'] = AkConfig::getDir('controllers').DS.$module_details['path'].$details['file_name'];
        return $details;
    }

    protected function _enableInternationalizationSupport() {
        if(AK_AVAILABLE_LOCALES != 'en'){
            $LocaleManager = new AkLocaleManager();
            $LocaleManager->init();
            $LocaleManager->initApplicationInternationalization($this);
            $this->__internationalization_support_enabled = true;
        }
    }

    protected function _startSession() {
        if(AK_AUTOMATIC_SESSION_START){
            if(!isset($_SESSION)){
                AkSession::initHandler();
            }
        }
    }
}

