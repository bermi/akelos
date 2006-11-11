<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2006, Akelos Media, S.L.  & Bermi Ferrer Martinez |
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

Ak::compat('http_build_query');

/**
 * Native PHP URL rewriting for the Akelos Framework.
 * 
 * @package AkelosFramework
 * @subpackage Reporting
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */


// ---- Required Files ---- //
require_once(AK_LIB_DIR.DS.'AkObject.php');

if(!defined('OPTIONAL')){
    define('OPTIONAL', false);
}

if(!defined('COMPULSORY')){
    define('COMPULSORY', true);
}

if(!defined('COMPULSORY_REGEX')){
    define('COMPULSORY_REGEX', '([^\/]+){1}');
}




/**
* Native PHP URL rewriting for the Akelos Framework
*
* This class implements PHP based URL rewriting for the Akelos Framework, thus shifting the responsibility of URL parsing from the webserver to the Akelos Framework itself. This has been a requested feature for two primary reasons.
*
* - Not all webservers support rewriting. By moving this code to the core, the framework is able to function out of the box on almost all webservers.
*
* - A rewriting implementation in the Akelos Framework can also be used to generate custom URLs by linking it to the standard URL helpers such as url_for, link_to, and redirect_to.
*
* @package AkelosFramework
* @subpackage AkActionController
* @author Bermi Ferrer <bermi a.t akelos d.t c.om>
* @copyright Copyright (c) 2002-2005, Akelos Media, S.L. http://www.akelos.org
* @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
*/
class AkRouter extends AkObject
{
    // {{{ properties


    // --- Private properties --- //


    /**
    * Routes setting container
    *
    * @see getRoutes
    * @access private
    * @var array $_loaded_routes
    */
    var $_loaded_routes = array();

    // }}}


    // ------ CLASS METHODS ------ //


    // ---- Constructor ---- //

    function __construct()
    {
        /**
        * We will try to guess if mod_rewrite is enabled.
        * Set AK_ENABLE_URL_REWRITE in your config 
        * to avoid the overhead this function causes
        */
        if(!defined('AK_ENABLE_URL_REWRITE') || (defined('AK_ENABLE_URL_REWRITE') && AK_ENABLE_URL_REWRITE !== false)){
            $this->_loadUrlRewriteSettings();
        }
    }


    // ---- Getters ---- //


    // {{{ getRoutes()

    /**
    * $this->_loaded_routes getter
    *
    * Use this method to get $this->_loaded_routes value
    *
    * @access public
    * @return array Returns Loaded Routes array.
    */
    function getRoutes()
    {
        return $this->_loaded_routes;
    }

    // }}}


    // ---- Public methods ---- //


    // {{{ toUrl()

    /**
    * Generates a custom URL, depending on current rewrite rules.
    *
    * Generates a custom URL, depending on current rewrite rules.
    *
    * @access public
    * @param    array    $params    An array with parameters to include in the url.
    * - <code>array('controller'=>'post','action'=>'view','id'=>'10')</code>
    * - <code>array('controller'=>'page','action'=>'view_page','webpage'=>'contact_us')</code>
    * @return string Having the following rewrite rules:
    * <code>
    * $Router =& new AkRouter();
    *
    * $Router->map('/setup/*config_settings',array('controller'=>'setup'));
    * $Router->map('/customize/*options/:action',array('controller'=>'themes','options'=>3));
    * $Router->map('/blog/:action/:id',array('controller'=>'post','action'=>'list','id'=>OPTIONAL),array('id'=>'/\d{1,}/'));
    * $Router->map('/:year/:month/:day', array('controller' => 'articles','action' => 'view_headlines','year' => COMPULSORY,'month' => 'all','day' => OPTIONAL) , array('year'=>'/(20){1}\d{2}/','month'=>'/((1)?\d{1,2}){2}/','day'=>'/(([1-3])?\d{1,2}){2}/'));
    * $Router->map('/:webpage', array('controller' => 'page', 'action' => 'view_page', 'webpage' => 'index'),array('webpage'=>'/(\w|_)+/'));
    * $Router->map('/', array('controller' => 'page', 'action' => 'view_page', 'webpage'=>'index'));
    * $Router->map('/:controller/:action/:id');
    * </code>
    *
    * We get the following results:
    *
    * <code>$Router->toUrl(array('controller'=>'page','action'=>'view_page','webpage'=>'contact_us'));</code>
    * Produces: /contact_us/
    *
    * <code>$Router->toUrl(array('controller'=>'page','action'=>'view_page','webpage'=>'index'));</code>
    * Produces: /
    *
    * <code>$Router->toUrl(array('controller'=>'post','action'=>'list','id'=>null));</code>
    * Produces: /blog/
    *
    * <code>$Router->toUrl(array('controller'=>'post','action'=>'view','id'=>null));</code>
    * Produces: /blog/view/
    *
    * <code>$Router->toUrl(array('controller'=>'post','action'=>'view','id'=>'10'));</code>
    * Produces: /blog/view/10/
    *
    * <code>$Router->toUrl(array('controller'=>'blog','action'=>'view','id'=>'newest'));</code>
    * Produces: /blog/view/newest/
    *
    * <code>$Router->toUrl(array('controller' => 'articles','action' => 'view_headlines','year' => '2005','month' => '10', 'day' => null));</code>
    * Produces: /2005/10/
    *
    * <code>$Router->toUrl(array('controller'</code> => 'articles','action' => 'view_headlines','year' => '2006','month' => 'all', 'day' => null));</code>
    * Produces: /2006/
    *
    * <code>$Router->toUrl(array('controller' => 'user','action' => 'list','id' => '12'));</code>
    * Produces: /user/list/12/
    *
    * <code>$Router->toUrl(array('controller' => 'setup','config_settings' => array('themes','clone','12')));</code>
    * Produces: /setup/themes/clone/12/
    *
    * <code>$Router->toUrl(array('controller' => 'themes','options' => array('blue','css','sans_serif'), 'action'=>'clone'));</code>
    * Produces: /customize/blue/css/sans_serif/clone/
    */
    function toUrl($params=array())
    {
        $parsed = '';

        if(isset($params[AK_SESSION_NAME]) && isset($_COOKIE)){
            unset($params[AK_SESSION_NAME]);
        }


        foreach ($this->_loaded_routes as $route){
            $params_copy = $params;
            $parsed = '';
            $_controller = '';
            foreach ($params_copy as $k=>$v){
                if(isset($$k)){
                    unset($$k);
                }
            }
            extract($params);

            if(isset($route['options'])){
                foreach ($route['options'] as $option=>$value){
                    if(isset($params_copy[$option]) && $value == $params_copy[$option] && $value !== OPTIONAL && $value !== COMPULSORY){
                        if($option == 'controller'){
                            $_controller = $value;
                        }
                        unset($params_copy[$option]);
                        unset($$option);

                    }
                }
            }

            foreach ($route['arr_params'] as $arr_route){
                if(isset($$arr_route) && is_array($$arr_route)){
                    $$arr_route = join('/',$$arr_route);
                }
            }

            $_url_pieces = array();
            foreach ($route['url_pieces'] as $v){
                if(strstr($v,':') || strstr($v,'*')){
                    $v = substr($v,1);
                    if(isset($params[$v])){
                        if (isset($route['options'][$v]) && $params[$v] != $route['options'][$v] || !isset($route['options'][$v]) || isset($route['options'][$v]) && $route['options'][$v] === COMPULSORY){
                            $_url_pieces[] = is_array($params[$v]) ? join('/',$params[$v]) : $params[$v];
                        }
                    }
                }else{
                    $_url_pieces[] = is_array($v) ? join('/',$v) : $v;
                }
            }
            $parsed = str_replace('//','/','/'.join('/',$_url_pieces).'/');

            // This might be faster but using eval here might cause security issues
            //@eval('$parsed = "/".trim(str_replace("//","/","'.str_replace(array('/:','/*'),'/$','/'.join('/',$route['url_pieces']).'/').'"),"/")."/";');

            if($parsed == '//'){
                $parsed = '/';
            }

            if(is_string($parsed)){
                if($parsed_arr = $this->toParams($parsed)){
                    if($parsed == '/' && count(array_diff($params,$parsed_arr)) == 0){
                        return '/';
                    }

                    if( isset($parsed_arr['controller']) &&
                    ((isset($controller) && $parsed_arr['controller'] == $controller) ||
                    (isset($_controller) && $parsed_arr['controller'] == $_controller))){


                        if( isset($route['options']['controller']) &&
                        $route['options']['controller'] !== OPTIONAL &&
                        $route['options']['controller'] !== COMPULSORY &&
                        $parsed_arr['controller'] != $route['options']['controller'] &&
                        count(array_diff(array_keys($route['options']),array_keys($parsed_arr))) > 0){
                            continue;
                        }

                        $url_params = array_merge($parsed_arr,$params_copy);

                        if($parsed != '/'){
                            foreach ($parsed_arr as $k=>$v){
                                if(isset($url_params[$k]) && $url_params[$k] == $v){
                                    unset($url_params[$k]);
                                }
                            }
                        }

                        foreach ($route['url_pieces'] as $piece){
                            $piece = str_replace(array(':','*'),'', $piece);
                            if(isset($$piece)){
                                if(strstr($parsed,'/'.$$piece.'/')){
                                    unset($url_params[$piece]);
                                }
                            }
                        }

                        foreach ($url_params as $k=>$v){
                            if($v == null){
                                unset($url_params[$k]);
                            }
                        }

                        if($parsed == '/' && !empty($url_params['controller'])){
                            $parsed = '/'.join('/',array_diff(array($url_params['controller'],@$url_params['action'],@$url_params['id']),array('')));
                            unset($url_params['controller'],$url_params['action'],$url_params['id']);
                        }

                        if(defined('AK_URL_REWRITE_ENABLED') && AK_URL_REWRITE_ENABLED === true){
                            if(isset($url_params['ak'])){
                                unset($url_params['ak']);
                            }
                            if(isset($url_params['lang'])){
                                $parsed = '/'.$url_params['lang'].$parsed;
                                unset($url_params['lang']);
                            }
                            $parsed .= count($url_params) ? '?'.http_build_query($url_params) : '';
                        }else{
                            $parsed = count($url_params) ? '/?ak='.$parsed.'&'.http_build_query($url_params) : '/?ak='.$parsed;
                        }
                        return $parsed;
                    }
                }
            }
        }

        (array)$extra_parameters = @array_diff($params_copy,$parsed_arr);


        if($parsed == '' && is_array($params)){
            $parsed = '?'.http_build_query(array_merge($params,(array)$extra_parameters));
        }
        if($parsed == '//'){
            $parsed = '/';
        }

        if(defined('AK_URL_REWRITE_ENABLED') && AK_URL_REWRITE_ENABLED === false && $parsed{0} != '?'){
            $parsed = '?ak='.trim($parsed,'/');
        }

        $parsed .= empty($extra_parameters) ? '' : (strstr($parsed,'?') ? '&' : '?').http_build_query($extra_parameters);

        return $parsed;
    }

    // }}}
    // {{{ toParams()

    /**
    * Gets the parameters from a Akelos Framework friendly URL.
    *
    * This method returns the parameters found in an Akelos Framework friendly URL.
    *
    * This function will inspect the rewrite rules and will return the params that match the first one.
    *
    * @access public
    * @param    string    $url    URL to get params from.
    * @return mixed Having the following rewrite rules:
    * <code>
    * $Router =& new AkRouter();
    *
    * $Router->map('/setup/*config_settings',array('controller'=>'setup'));
    * $Router->map('/customize/*options/:action',array('controller'=>'themes','options'=>3));
    * $Router->map('/blog/:action/:id',array('controller'=>'post','action'=>'list','id'=>OPTIONAL),array('id'=>'/\d{1,}/'));
    * $Router->map('/:year/:month/:day', array('controller' => 'articles','action' => 'view_headlines','year' => COMPULSORY,'month' => 'all','day' => OPTIONAL) , array('year'=>'/(20){1}\d{2}/','month'=>'/((1)?\d{1,2}){2}/','day'=>'/(([1-3])?\d{1,2}){2}/'));
    * $Router->map('/:webpage', array('controller' => 'page', 'action' => 'view_page', 'webpage' => 'index'),array('webpage'=>'/(\w|_)+/'));
    * $Router->map('/', array('controller' => 'page', 'action' => 'view_page', 'webpage'=>'index'));
    * $Router->map('/:controller/:action/:id');
    * </code>
    *
    * We get the following results:
    *
    * <code>$Router->toParams('/contact_us');</code>
    * Produces: array('controller'=>'page','action'=>'view_page','webpage'=>'contact_us');
    *
    * <code>$Router->toParams('/');</code>
    * Produces: array('controller'=>'page','action'=>'view_page','webpage'=>'index');
    *
    * <code>$Router->toParams('');</code>
    * Produces: array('controller'=>'page','action'=>'view_page','webpage'=>'index');
    *
    * <code>$Router->toParams('/blog/');</code>
    * Produces: array('controller'=>'post','action'=>'list','id'=>null);
    *
    * <code>$Router->toParams('/blog/view');</code>
    * Produces: array('controller'=>'post','action'=>'view','id'=>null);
    *
    * <code>$Router->toParams('/blog/view/10/');</code>
    * Produces: array('controller'=>'post','action'=>'view','id'=>'10');
    *
    * <code>$Router->toParams('/blog/view/newest/');</code>
    * Produces: array('controller'=>'blog','action'=>'view','id'=>'newest');
    *
    * <code>$Router->toParams('/2005/10/');</code>
    * Produces: array('controller' => 'articles','action' => 'view_headlines','year' => '2005','month' => '10', 'day' => null);
    *
    * <code>$Router->toParams('/2006/');</code>
    * Produces: array('controller' => 'articles','action' => 'view_headlines','year' => '2006','month' => 'all', 'day' => null);
    *
    * <code>$Router->toParams('/user/list/12');</code>
    * Produces: array('controller' => 'user','action' => 'list','id' => '12');
    *
    * <code>$Router->toParams('/setup/themes/clone/12/');</code>
    * Produces: array('controller' => 'setup','config_settings' => array('themes','clone','12'));
    *
    * <code>$Router->toParams('/customize/blue/css/sans_serif/clone/');</code>
    * Produces: array('controller' => 'themes','options' => array('blue','css','sans_serif'), 'action'=>'clone');
    *
    * This function returns false in case no rule is found for selected URL
    */
    function toParams($url)
    {
        $url = $url == '/' || $url == '' ? '/' : '/'.trim($url,'/').'/';
        $nurl = $url;

        foreach ($this->_loaded_routes as $route){
            $params = array();

            if(preg_match($route['regex'], $url)){

                foreach ($route['regex_array'] as $single_regex_arr){

                    $k = key($single_regex_arr);

                    $single_regex = $single_regex_arr[$k];
                    $single_regex = '/^(\/'.$single_regex.'){1}/';
                    preg_match($single_regex, $url, $got);

                    if(in_array($k,$route['arr_params'])){
                        $url_parts = strstr(trim($url,'/'),'/') ? explode('/',trim($url,'/')) : array(trim($url,'/'));

                        $pieces = (isset($route['options'][$k]) && $route['options'][$k] > 0) ? $route['options'][$k] : count($url_parts);
                        while ($pieces>0) {
                            $pieces--;
                            $url_part = array_shift($url_parts);
                            $url = substr_replace($url,'',1,strlen($url_part)+1);

                            if(preg_match($single_regex, '/'.$url_part)){
                                $params[$k][] = $url_part;
                            }
                        }
                    }elseif(!empty($got[0])){
                        $url = substr_replace($url,'',1,strlen($got[0]));
                        if(in_array($k,$route['var_params'] )){
                            $param = trim($got[0],'/');
                            $params[$k] = $param;
                        }
                    }
                    if(isset($route['options'][$k])){

                        if($route['options'][$k] !== COMPULSORY &&
                        $route['options'][$k] !== OPTIONAL &&
                        $route['options'][$k] != '' &&
                        ((!isset($params[$k]))||(isset($params[$k]) && $params[$k] == ''))){
                            $params[$k] = $route['options'][$k];
                        }
                    }
                }

                if(isset($route['options'])){
                    foreach ($route['options'] as $option=>$value){
                        if($value !== COMPULSORY && $value !== OPTIONAL && $value != '' && !isset($params[$option])){
                            $params[$option] = $value;
                        }
                    }
                }
            }
            if(count($params)){
                $params = array_map(array(&$this,'_urlDecode'),$params);
                return $params;
            }
        }
        return false;
    }
    // }}}
    // {{{ map()

    /**
    * Add a rewrite rule
    *
    * Rewrite rules are defined on the file <code>config/routes.php</code>
    *
    * Rules that are defined first take precedence over the rest.
    *
    * @access public
    * @param    string    $url_pattern    URL patterns have the following format:
    *
    * - <b>/static_text</b>
    * - <b>/:variable</b>  (will load $variable)
    * - <b>/*array</b> (will load $array as an array)
    * @param    array    $options    Options is an array with and array pair of field=>value
    * The following example <code>array('controller' => 'page')</code> sets var 'controler' to 'page' if no 'controller' is specified in the $url_pattern param this value will be used.
    *
    * The following constants can be used as values:
    * <code>
    * OPTIONAL // 'var_name'=> OPTIONAL, will set 'var_name' as an option
    * COMPULSORY // 'var_name'=> COMPULSORY, will require 'var_name' to be set
    * </code>
    * @param    array    $requirements    $requirements holds an array with and array pair of field=>value where value is a perl compatible regular expression that will be used to validate rewrite rules
    * The following example <code>array('id'=>'/\d+/')</code> will require that var 'id' must be a numeric field.
    *
    * NOTE:If option <b>'id'=>OPTIONAL</b> this requirement will be used in case 'id' is set to something
    * @return void
    */
    function connect($url_pattern, $options = array(), $requirements = null)
    {

        if(!empty($options['requirements'])){
            $requirements = empty($requirements) ? $options['requirements'] : array_merge($options['requirements'],$requirements);
            unset($options['requirements']);
        }

        preg_match_all('/(([^\/]){1}(\/\/)?){1,}/',$url_pattern,$found);
        $url_pieces = $found[0];

        $regex_arr = array();
        $optional_pieces = array();
        $var_params = array();
        $arr_params = array();
        foreach ($url_pieces as $piece){
            $is_var = $piece[0] == ':';
            $is_arr = $piece[0] == '*';
            $is_constant = !$is_var && !$is_arr;

            $piece = $is_constant ? $piece : substr($piece,1);

            if(isset($requirements[$piece]) && $requirements[$piece][0] == '/'){
                //$options[$piece] = COMPULSORY;
            }

            if($is_var && !isset($options[$piece])){
                $options[$piece] = OPTIONAL;
            }

            if($is_arr && !isset($options[$piece])){
                $options[$piece] = OPTIONAL;
            }

            //COMPULSORY

            if(isset($requirements[$piece])){
                if ($options[$piece] !== COMPULSORY){
                    $regex_arr[] = array($piece=> '(('.trim($requirements[$piece],'/').'){1})?');
                }elseif($options[$piece] !== OPTIONAL){
                    $regex_arr[] = array($piece=> '(('.trim($requirements[$piece],'/').'){1}|('.$options[$piece].'){1}){1}');
                }else{
                    $regex_arr[] = array($piece=> '('.trim($requirements[$piece],'/').'){1}');
                }
            }elseif(isset($options[$piece])){
                if($options[$piece] === OPTIONAL){
                    $regex_arr[] = array($piece=>'([^\/]+)?');
                }elseif ($options[$piece] === COMPULSORY){
                    $regex_arr[] = array($piece=> COMPULSORY_REGEX);
                }elseif(is_string($options[$piece]) && $options[$piece][0] == '/' &&
                ($_tmp_close_char = strlen($options[$piece])-1 || $options[$piece][$_tmp_close_char] == '/')){
                    $regex_arr[] = array($piece=> substr($options[$piece],1,$_tmp_close_char*-1));
                }elseif ($options[$piece] != ''){
                    $regex_arr[] = array($piece=>'([^\/]+)?');
                    $optional_pieces[$piece] = $piece;
                }
            }else{
                $regex_arr[] = array($piece=> $is_constant ? '('.$piece.'){1}' : $piece);
            }


            if($is_var){
                $var_params[] = $piece;
            }
            if($is_arr){
                $arr_params[] = $piece;
            }

            if(isset($options[$piece]) && $options[$piece] === OPTIONAL){
                $optional_pieces[$piece] = $piece;
            }
        }

        foreach (array_reverse($regex_arr) as $pos=>$single_regex_arr){
            $var_name = key($single_regex_arr);
            if((isset($options[$var_name]) && $options[$var_name] === COMPULSORY) || (isset($requirements[$var_name]) && $requirements[$var_name] === COMPULSORY)){
                $last_optional_var = $pos;
                break;
            }
        }
        /*
        $allow_optional_values = !empty($allow_optional_values) || @$options[$k] === COMPULSORY || @$requirements[$k] === COMPULSORY;
        if($allow_optional_values)
        Ak::trace("$k");
        */

        $regex = '/^((\/)?';
        $pieces_count = count($regex_arr);

        foreach ($regex_arr as $pos=>$single_regex_arr){
            $k = key($single_regex_arr);
            $single_regex = $single_regex_arr[$k];

            $slash_delimiter = isset($last_optional_var) && ($last_optional_var <= $pos) ? '{1}' : '?';

            if(isset($optional_pieces[$k])){
                $terminal = (is_numeric($options[$k]) && $options[$k] > 0 && in_array($k,$arr_params)) ? '{'.$options[$k].'}' : ($pieces_count == $pos+1 ? '?' : '{1}');
                $regex .= $is_arr ? '('.$single_regex.'(\/)'.$slash_delimiter.')+' : '('.$single_regex.'(\/)'.$slash_delimiter.')'.$terminal;
            }else{
                $regex .= $is_arr ? $single_regex.'(\/)+' : $single_regex.'(\/)'.($pieces_count == $pos+1 ? '?' : $slash_delimiter);
            }
        }
        $regex = rtrim($regex ,'/').'){1}$/';
        $regex = str_replace('/^\$/','/^\\/?$/',$regex);


        $this->_loaded_routes[] = array(
        'url_path' => $url_pattern,
        'options' => $options,
        'requirements' => $requirements,
        'url_pieces' => $url_pieces,
        'regex' => $regex,
        'regex_array' => $regex_arr,
        'optional_params' => $optional_pieces,
        'var_params' => $var_params,
        'arr_params' => $arr_params
        );

    }

    // }}}

    // {{{ map()

    /**
    * Alias for map
    * 
    * @see map
    */
    function map($url_pattern, $options = array(), $requirements = null)
    {
        return $this->connect($url_pattern, $options, $requirements);
    }

    // }}}
    // {{{ _urlDecode()

    /**
    * Url decode a strin or an array of strings
    */
    function _urlDecode($input)
    {
        if(!empty($input)){
            if (is_string($input)){
                return urldecode($input);
            }elseif (is_array($input)){
                return array_map(array(&$this,'_urlDecode'),$input);
            }
        }
        return '';
    }
    // }}}


    // {{{ _loadUrlRewriteSettings()
    /**
    * This method tries to determine if url rewrite is enabled on this server.
    * It has only been tested on apache.
    * It is strongly recomended that you manually define the constant 
    * AK_URL_REWRITE_ENABLED on your config file to the avoid overload
    * this function causes and to prevent from missfunctioning
    */
    function _loadUrlRewriteSettings()
    {
        static $result;
        if(isset($result)){
            return $result;
        }

        if(defined('AK_URL_REWRITE_ENABLED')){
            $result = AK_URL_REWRITE_ENABLED;
            return AK_URL_REWRITE_ENABLED;
        }
        if(AK_DESKTOP){
            if(!defined('AK_URL_REWRITE_ENABLED')){
                define('AK_URL_REWRITE_ENABLED',false);
                $result = AK_URL_REWRITE_ENABLED;
                return false;
            }
        }
        if(defined('AK_ENABLE_URL_REWRITE') && AK_ENABLE_URL_REWRITE == false){
            if(!defined('AK_URL_REWRITE_ENABLED')){
                define('AK_URL_REWRITE_ENABLED',false);
            }
            $result = AK_URL_REWRITE_ENABLED;
            return false;
        }

        $url_rewrite_status = false;

        //echo '<pre>'.print_r(get_defined_functions(), true).'</pre>';

        if( isset($_SERVER['REDIRECT_STATUS'])
        && $_SERVER['REDIRECT_STATUS'] == 200
        && isset($_SERVER['REDIRECT_QUERY_STRING'])
        && strstr($_SERVER['REDIRECT_QUERY_STRING'],'ak=')){

            if(strstr($_SERVER['REDIRECT_QUERY_STRING'],'&')){
                $tmp_arr = explode('&',$_SERVER['REDIRECT_QUERY_STRING']);
                $ak_request = $tmp_arr[0];
            }else{
                $ak_request = $_SERVER['REDIRECT_QUERY_STRING'];
            }
            $ak_request = trim(str_replace('ak=','',$ak_request),'/');

            if(strstr($_SERVER['REDIRECT_URL'],$ak_request)){
                $url_rewrite_status = true;
            }else {
                $url_rewrite_status = false;
            }
        }

        // We check if available by investigating the .htaccess file if no query has been set yet
        elseif(function_exists('apache_get_modules')){

            $available_modules = apache_get_modules();

            if(in_array('mod_rewrite',(array)$available_modules)){

                // Local session name is changed intentionally from .htaccess
                // So we can see if the file has been loaded.
                // if so, we restore the session.name to its original
                // value
                if(ini_get('session.name') == 'AK_SESSID'){
                    $session_name = defined('AK_SESSION_NAME') ? AK_SESSION_NAME : get_cfg_var('session.name');
                    ini_set('session.name',$session_name);
                    $url_rewrite_status = true;

                    // In some cases where session.name cant be set up by htaccess file,
                    // we can check for modrewrite status on this file
                }elseif (file_exists(AK_BASE_DIR.DS.'.htaccess')){
                    $htaccess_file = Ak::file_get_contents(AK_BASE_DIR.DS.'.htaccess');
                    if(stristr($htaccess_file,'RewriteEngine on')){
                        $url_rewrite_status = true;
                    }
                }
            }

            // If none of the above works we try to fetch a file that should be remaped
        }elseif (isset($_SERVER['REDIRECT_URL']) && $_SERVER['REDIRECT_URL'] == '/' && isset($_SERVER['REDIRECT_STATUS']) && $_SERVER['REDIRECT_STATUS'] == 200){
            $url_rewrite_test_url = AK_URL.'mod_rewrite_test';
            if(!empty($_SERVER['PHP_AUTH_USER']) && !empty($_SERVER['PHP_AUTH_PW'])){
                $url_rewrite_test_url = AK_PROTOCOL.$_SERVER['PHP_AUTH_USER'].':'.$_SERVER['PHP_AUTH_PW'].'@'.AK_HOST.'/mod_rewrite_test';
            }

            $url_rewrite_status = strstr(@file_get_contents($url_rewrite_test_url), 'AK_URL_REWRITE_ENABLED');
            $AK_URL_REWRITE_ENABLED = "define(\\'AK_URL_REWRITE_ENABLED\\', ".($url_rewrite_status ? 'true' : 'false').");\n";

            register_shutdown_function(create_function('',"Ak::file_put_contents(AK_CONFIG_DIR.DS.'config.php',
            str_replace('<?php\n','<?php\n\n$AK_URL_REWRITE_ENABLED',Ak::file_get_contents(AK_CONFIG_DIR.DS.'config.php')));"));
        }



        if(!defined('AK_URL_REWRITE_ENABLED')){
            define('AK_URL_REWRITE_ENABLED', $url_rewrite_status);
        }
        $result = AK_URL_REWRITE_ENABLED;
        return AK_URL_REWRITE_ENABLED;
    }
    // }}}

}


function &AkRouter()
{
    $null = null;
    $AkRouter =& Ak::singleton('AkRouter', $null);
    return $AkRouter;
}


?>
