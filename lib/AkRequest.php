<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2006, Akelos Media, S.L.  & Bermi Ferrer Martinez |
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
 * @package ActionController
 * @subpackage Request
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

if(!defined('AK_DEFAULT_CONTROLLER')){
    define('AK_DEFAULT_CONTROLLER', 'page');
}
if(!defined('AK_DEFAULT_ACTION')){
    define('AK_DEFAULT_ACTION', 'index');
}

defined('AK_HIGH_LOAD_MODE') ? null : define('AK_HIGH_LOAD_MODE', false);
defined('AK_AUTOMATIC_DB_CONNECTION') ? null : define('AK_AUTOMATIC_DB_CONNECTION', !AK_HIGH_LOAD_MODE);
defined('AK_AUTOMATIC_SESSION_START') ? null : define('AK_AUTOMATIC_SESSION_START', !AK_HIGH_LOAD_MODE);

// IIS does not provide a valid REQUEST_URI so we need to guess it from the script name + query string
$_SERVER['REQUEST_URI'] = (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $_SERVER['SCRIPT_NAME'].(( isset($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '')));

require_once(AK_LIB_DIR.DS.'AkRequestMimeType.php');
/**
* Class that handles incoming request.
* 
* The Request Object handles user request (CLI, GET, POST, session or
* cookie requests), transforms it and sets it up for the
* ApplicationController class, who takes control of the data
* flow.
* 
* @author Bermi Ferrer <bermi@akelos.com>
* @copyright Copyright (c) 2002-2005, Akelos Media, S.L. http://www.akelos.org
* @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
*/
class AkRequest extends AkObject
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
    var $_request = array();

    var $_init_check = false;
    var $__internationalization_support_enabled = false;

    var $action = AK_DEFAULT_ACTION;
    var $controller = AK_DEFAULT_CONTROLLER;
    var $view;

    /**
    * Holds information about current environment. Initially a reference to $_SERVER
    *
    * @var array
    */
    var $env = array();
    
    var $mime_types = array( 
                'text/html'                => 'html', 
                'application/xhtml+xml'    => 'html', 
                'application/xml'          => 'xml', 
                'text/xml'                 => 'xml', 
                'text/javascript'          => 'js', 
                'application/javascript'   => 'js', 
                'application/x-javascript' => 'js', 
                'application/json'         => 'json',
                'text/x-json'              => 'json', 
                'application/rss+xml'      => 'rss', 
                'application/atom+xml'     => 'atom', 
                '*/*'                      => 'html', 
                //'application/x-www-form-urlencoded' => 'www-form', 
                //'application/x-www-form-urlencoded' => 'www-form',
                'default'                  => 'html', 
            );
            
    var $_format;
    /**
    * String parse method.
    * 
    * This method gets a petition as parameter, using the "Ruby
    * on Rails" request format (see prettyURL in RoR documentation). The format is:
    * file.php?ak=/controller/action/id&paramN=valueN
    * 
    * This method requires for a previous execution of the _mergeRequest() method, 
    * in order to merge all the request all i one array.
    *
    * This method expands dynamically the class Request, adding a public property for
    * every parameter sent in the request.
    *
    * 
    * @access public
    * @return array
    */
    function _parseAkRequestString($ak_request_string, $pattern = '/')
    {
        $result = array();
        $ak_request = trim($ak_request_string,$pattern);
        if(strstr($ak_request,$pattern)){
            $result = explode($pattern,$ak_request);
        }
        return $result;
    }


    function __construct ()
    {
        $this->init();
        $this->getFormat();
        
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
    function init()
    {
        if(!$this->_init_check){
            $this->env =& $_SERVER;
            $this->_fixGpcMagic();
            $this->_urlDecode();

            $this->_mergeRequest();

            if(is_array($this->_request)){
                foreach ($this->_request as $k=>$v){
                    $this->_addParam($k, $v);
                }
            }

            $this->_init_check = true;
        }

        if(defined('AK_LOG_EVENTS') && AK_LOG_EVENTS){
            $this->Logger =& Ak::getLogger();
            $this->Logger->message($this->Logger->formatText('Request','green').' from '.$this->getRemoteIp(), $this->getParams());
        }
    }

    function get($var_name)
    {
        return $this->_request[$var_name];
    }

    function getParams()
    {
        return array_merge(array('controller'=>$this->controller,'action'=>$this->action),$this->_request);
    }

    function getAction()
    {
        return $this->action;
    }

    function getController()
    {
        return $this->controller;
    }

    function reset()
    {
        $this->_request = array();
        $this->_init_check = false;
    }

    function set($variable, $value)
    {
        $this->_addParam($variable, $value);
    }


    function checkForRoutedRequests(&$Router)
    {
        $ak_request = isset($this->_request['ak']) ? '/'.trim($this->_request['ak'],'/').'/' : '/';

        if($found = $Router->toParams($ak_request)){
            if(!isset($found['controller'])){
                trigger_error(Ak::t('No controller was specified.'), E_USER_WARNING);
            }
            if(!isset($found['action'])){
                trigger_error(Ak::t('No action was specified.'), E_USER_WARNING);
            }

            if(isset($found['controller'])){
                if($this->_addParam('controller',$found['controller'])){
                    $this->controller = $this->_request['controller'] = $found['controller'];
                }
            }
            if(isset($found['action'])){
                if($this->_addParam('action',$found['action'])){
                    $this->action = $this->_request['action'] = $found['action'];
                }
            }
            if(isset($found['module'])){
                if($this->_addParam('module',$found['module'])){
                    $this->module = $this->_request['module'] = $found['module'];
                }
            }

            foreach ($found as $k=>$v){
                if($this->_addParam($k,$v)){
                    $this->_request[$k] = $v;
                }
            }
        }
    }


    function isValidControllerName($controller_name)
    {
        return $this->_validateTechName($controller_name);
    }

    function isValidActionName($action_name)
    {
        return $this->_validateTechName($action_name);
    }

    function isValidModuleName($module_name)
    {
        return preg_match('/^[A-Za-z]{1,}[A-Za-z0-9_\/]*$/', $module_name);
    }



    /**
    * Returns both GET and POST parameters in a single array.
    */
    function getParameters()
    {
        if(empty($this->parameters)){
            $this->parameters = $this->getParams();
        }
        return $this->parameters;
    }

    function setPathParameters($parameters)
    {
        $this->_path_parameters = $parameters;
    }

    function getPathParameters()
    {
        return empty($this->_path_parameters) ? array() : $this->_path_parameters;
    }

    function getUrlParams()
    {
        return $_GET;
    }

    /**
    * Must be implemented in the concrete request
    */
    function getQueryParameters ()
    {
    }
    function getRequestParameters ()
    {
    }

    /**
     * Returns the path minus the web server relative installation directory. This method returns null unless the web server is apache.
     */
    function getRelativeUrlRoot()
    {
        return str_replace('/index.php','', @$this->env['PHP_SELF']);
    }

    /**
     * Returns the locale identifier of current URL
     */
    function getLocaleFromUrl()
    {
        $locale = Ak::get_url_locale();
        if(strstr(AK_CURRENT_URL,AK_SITE_URL.'/'.$locale)){
            return $locale;
        }
        return '';
    }

    /**
    * Returns the HTTP request method as a lowercase symbol ('get, for example)
    */
    function getMethod()
    {
        return strtolower(isset($this->env['REQUEST_METHOD'])?$this->env['REQUEST_METHOD']:'get');
    }

    /**
    * Is this a GET request?  Equivalent to $Request->getMethod() == 'get'
    */
    function isGet()
    {
        return $this->getMethod() == 'get';
    }

    /**
    * Is this a POST request?  Equivalent to $Request->getMethod() == 'post'
    */
    function isPost()
    {
        return $this->getMethod() == 'post';
    }

    /**
    * Is this a PUT request?  Equivalent to $Request->getMethod() == 'put'
    */
    function isPut()
    {
        return isset($this->env['REQUEST_METHOD']) ? $this->getMethod() == 'put' : false;
    }

    /**
    * Is this a DELETE request?  Equivalent to $Request->getMethod() == 'delete'
    */
    function isDelete()
    {
        return $this->getMethod() == 'delete';
    }

    /**
    * Is this a HEAD request?  Equivalent to $Request->getMethod() == 'head'
    */
    function isHead()
    {
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
    function getRemoteIp()
    {
        if(!empty($this->env['HTTP_CLIENT_IP'])){
            return $this->env['HTTP_CLIENT_IP'];
        }
        if(!empty($this->env['HTTP_X_FORWARDED_FOR'])){
            foreach ((strstr($this->env['HTTP_X_FORWARDED_FOR'],',') ? split(',',$this->env['HTTP_X_FORWARDED_FOR']) : array($this->env['HTTP_X_FORWARDED_FOR'])) as $remote_ip){
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
    * Returns the domain part of a host, such as akelos.com in 'www.akelos.com'. You can specify
    * a different <tt>tld_length</tt>, such as 2 to catch akelos.co.uk in 'www.akelos.co.uk'.
    */
    function getDomain($tld_length = 1)
    {
        return preg_match('/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/',$this->getHost()) ?
        null :
        join('.',array_slice(explode('.',$this->getHost()),(1 + $tld_length)*-1));
    }

    /**
    * Returns all the subdomains as an array, so ['dev', 'www'] would be returned for 'dev.www.akelos.com'.
    * You can specify a different <tt>tld_length</tt>, such as 2 to catch ['www'] instead of ['www', 'akelos']
    * in 'www.akelos.co.uk'.
    */
    function getSubdomains($tld_length = 1)
    {
        return preg_match('/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/',$this->getHost()) ||
        !strstr($this->getHost(),'.') ? array() : (array)array_slice(explode('.',$this->getHost()),0,(1 + $tld_length)*-1);
    }


    /**
    * Returns the request URI correctly
    */
    function getRequestUri()
    {
        return $this->getProtocol().$this->getHostWithPort();
    }

    /**
    * Return 'https://' if( this is an SSL request and 'http://' otherwise.
    */
    function getProtocol()
    {
        return $this->isSsl() ? 'https://' : 'http://';
    }

    /**
    * Is this an SSL request?
    */
    function isSsl()
    {
        return isset($this->env['HTTPS']) && ($this->env['HTTPS'] === true || $this->env['HTTPS'] == 'on');
    }

    /**
    * Returns the interpreted path to requested resource
    */
    function getPath()
    {
        return strstr($this->env['REQUEST_URI'],'?') ? substr($this->env['REQUEST_URI'],0,strpos($this->env['REQUEST_URI'],'?')) : $this->env['REQUEST_URI'];
    }

    /**
    * Returns the port number of this request as an integer.
    */
    function getPort()
    {
        $this->port_as_int = AK_WEB_REQUEST ? AK_SERVER_PORT : 80;
        return $this->port_as_int;
    }

    /**
    * Returns the standard port number for this request's protocol
    */
    function getStandardPort()
    {
        return $this->isSsl() ? 443 : 80;
    }

    /**
    * Returns a port suffix like ':8080' if( the port number of this request
    * is not the default HTTP port 80 or HTTPS port 443.
    */
    function getPortString()
    {
        $port = $this->getPort();
        return $port == $this->getStandardPort() ? '' : ($port ? ':'.$this->getPort() : '');
    }

    /**
    * Returns a host:port string for this request, such as example.com or
    * example.com:8080.
    */
    function getHostWithPort()
    {
        return $this->getHost() . $this->getPortString();
    }


    function getHost()
    {
        if(!empty($this->_host)){
            return $this->_host;
        }
        return AK_WEB_REQUEST ? $this->env['SERVER_NAME'] : 'localhost';
    }

    function &getSession()
    {
        return $_SESSION;
    }

    function resetSession()
    {
        $_SESSION = array();
    }

    function &getCookies()
    {
        return $_COOKIE;
    }


    function &getEnv()
    {
        return $this->env;
    }


    function getServerSoftware()
    {
        if(!empty($this->env['SERVER_SOFTWARE'])){
            if(preg_match('/^([a-zA-Z]+)/', $this->env['SERVER_SOFTWARE'],$match)){
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
    function isXmlHttpRequest()
    {
        return !empty($this->env['HTTP_X_REQUESTED_WITH']) && strstr(strtolower($this->env['HTTP_X_REQUESTED_WITH']),'xmlhttprequest');
    }
    function xhr()
    {
        return $this->isXmlHttpRequest();
    }

    function isAjax()
    {
        return $this->isXmlHttpRequest();
    }


    /**
     * Receive the raw post data.
     * This is useful for services such as REST, XMLRPC and SOAP
     * which communicate over HTTP POST but don't use the traditional parameter format.
     */
    function getRawPost()
    {
        return empty($_ENV['RAW_POST_DATA']) ? '' : $_ENV['RAW_POST_DATA'];
    }


    function _validateTechName($name)
    {
        return preg_match('/^[A-Za-z]{1,}[A-Za-z0-9_]*$/',$name);
    }



    // {{{ _mergeRequest()

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
    function _mergeRequest()
    {
        $this->_request = array();

        $session_params = isset($_SESSION['request']) ? $_SESSION['request'] : null;
        $command_line_params = !empty($_REQUEST)  ? $_REQUEST : null;

        $requests = array($command_line_params, $_GET, array_merge_recursive($_POST, $this->getPutParams(), $this->_getNormalizedFilesArray()), $_COOKIE, $session_params);

        foreach ($requests as $request){
            $this->_request = (!is_null($request) && is_array($request)) ?
            array_merge($this->_request,$request) : $this->_request;
        }
    }

    // }}}

    function _getNormalizedFilesArray($params = null, $first_call = true)
    {
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
    function _addParam($variable, $value)
    {
        if($variable[0] != '_'){
            if( ( $variable == 'action' && !$this->isValidActionName($value)) ||
            ( $variable == 'controller' && !$this->isValidControllerName($value)) ||
            ( $variable == 'module' && !$this->isValidModuleName($value))
            ){
                return false;
            }
            $this->$variable = $value;
            return true;
        }
        return false;
    }

    // }}}


    /**
    * Correct double-escaping problems caused by "magic quotes" in some PHP
    * installations.
    */
    function _fixGpcMagic()
    {
        if(!defined('AK_GPC_MAGIC_FIXED')){
            if (get_magic_quotes_gpc()) {
                array_walk($_GET, array('AkRequest', '_fixGpc'));
                array_walk($_POST, array('AkRequest', '_fixGpc'));
                array_walk($_COOKIE, array('AkRequest', '_fixGpc'));
            }
            define('AK_GPC_MAGIC_FIXED',true);
        }
    }

    function _fixGpc(&$item)
    {
        if (is_array($item)) {
            array_walk($item, array('AkRequest', '_fixGpc'));
        }else {
            $item = stripslashes($item);
        }
    }


    function _urlDecode()
    {
        if(!defined('AK_URL_DECODED')){
            array_walk($_GET, array('AkRequest', '_performUrlDecode'));
            define('AK_URL_DECODED',true);
        }
    }

    function _performUrlDecode(&$item)
    {
        if (is_array($item)) {
            array_walk($item, array('AkRequest', '_performUrlDecode'));
        }else {
            $item = urldecode($item);
        }
    }
    function getAccepts()
    {
        $accept_header = isset($this->env['HTTP_ACCEPT'])?$this->env['HTTP_ACCEPT']:'';
        $accepts = array();
        foreach (explode(',',$accept_header) as $index=>$acceptable){ 
                 $mime_struct = $this->_parseMimeType($acceptable); 
                 if (empty($mime_struct['q'])) $mime_struct['q'] = '1.0'; 
                  
                 //we need the original index inside this structure  
                 //because usort happily rearranges the array on equality 
                 //therefore we first compare the 'q' and then 'i' 
                 $mime_struct['i'] = $index; 
                 $accepts[] = $mime_struct; 
             } 
             usort($accepts,array(&$this,'_sortAcceptHeader')); 
              
             //we throw away the old index 
             foreach ($accepts as $array){ 
                 unset($array['i']); 
             } 
             return $accepts; 
    }
    function setFormat($format)
    {
        $this->_format = $format;
    }
    
    function getFormat()
    {

        
        if (isset($this->_format)) {
            return $this->_format;
        } else if (isset($this->_request['format'])) {
            $this->_format = $this->_request['format'];
        } else {
            list($format, $requestPath) = AkRequestMimeType::getFormat(@$this->_request['ak']);
            
            $this->_format = $format;
            $this->_request['format'] = $format;
            if ($requestPath!=null) {
                $this->_request['ak'] = $requestPath;
            }
        }
        return $this->_format;
    }
    

    // {{{ recognize()

    /**
    * Recognizes a Request and returns the responsible controller instance
    * 
    * @return AkActionController
    */
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
                exit;
            }else{
                header("HTTP/1.1 404 Not Found");
                die('404 Not found');
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
                header("HTTP/1.1 405 Method Not Allowed");
                die('405 Method Not Allowed');
            }
        }
        $Controller =& new $controller_class_name(array('controller'=>true));
        $Controller->_module_path = $module_path;
        isset($_SESSION) ? $Controller->session =& $_SESSION : null;
        return $Controller;

    }

    // }}}

    function _enableInternationalizationSupport()
    {
        if(AK_AVAILABLE_LOCALES != 'en'){
            require_once(AK_LIB_DIR.DS.'AkLocaleManager.php');

            $LocaleManager = new AkLocaleManager();
            $LocaleManager->init();
            $LocaleManager->initApplicationInternationalization($this);
            $this->__internationalization_support_enabled = true;
        }
    }

    function _mapRoutes($Map = null)
    {
        require_once(AK_LIB_DIR.DS.'AkRouter.php');
        if(is_file(AK_ROUTES_MAPPING_FILE)){
            if(empty($Map)){
                $Map =& AkRouter();
            }
            include(AK_ROUTES_MAPPING_FILE);
            // Set this routes for being used via Ak::toUrl
            Ak::toUrl($Map,true);
            $this->checkForRoutedRequests($Map);
        }
    }


    function _connectToDatabase()
    {
        if(AK_AUTOMATIC_DB_CONNECTION){
            Ak::db(AK_DEFAULT_DATABASE_PROFILE);
        }
    }

    function _startSession()
    {
        if(AK_AUTOMATIC_SESSION_START){
            if(!isset($_SESSION)){
                require_once(AK_LIB_DIR.DS.'AkSession.php');
                $SessionHandler = &AkSession::initHandler();
                @session_start();
            }
        }
    }

    function getPutParams()
    {
        if(!isset($this->put) && $this->isPut() && $data = $this->getPutRequestData()){
            $this->put = array();
            parse_str(urldecode($data), $this->put);
        }
        return isset($this->put) ? $this->put : array();
    }

    function getPutRequestData()
    {
        if(!empty($_SERVER['CONTENT_LENGTH'])){
            $putdata = fopen('php://input', 'r');
            $result = fread($putdata, $_SERVER['CONTENT_LENGTH']);
            fclose($putdata);
            return $result;
        }else{
            return false;
        }
    }
}

function &AkRequest()
{
    $null = null;
    $AkRequest =& Ak::singleton('AkRequest', $null);
    return $AkRequest;
}

?>
