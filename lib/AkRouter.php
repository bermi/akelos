<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2006, Akelos Media, S.L.  & Bermi Ferrer Martinez |
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

ak_compat('http_build_query');

/**
 * Native PHP URL rewriting for the Akelos Framework.
 * 
 * @package ActionController
 * @subpackage Request
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */


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
* @author Bermi Ferrer <bermi a.t akelos d.t c.om>
* @copyright Copyright (c) 2002-2005, Akelos Media, S.L. http://www.akelos.org
* @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
*/
class AkRouter extends AkObject
{

    /**
    * Routes setting container
    *
    * @see getRoutes
    * @access private
    * @var array $_loaded_routes
    */
    var $_loaded_routes = array();


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
        static $_cache;
        $_cache_key = md5(serialize($params));
        if(!isset($_cache[$_cache_key])){
            $_parsed = '';

            if(isset($params[AK_SESSION_NAME]) && isset($_COOKIE)){
                unset($params[AK_SESSION_NAME]);
            }

            $params = array_map(array(&$this, '_urlEncode'), $params);

            foreach ($this->_loaded_routes as $_route){
                $params_copy = $params;
                $_parsed = '';
                $_controller = '';
                foreach ($params_copy as $_k=>$_v){
                    if(isset($$_k)){
                        unset($$_k);
                    }
                }
                extract($params);

                if(isset($_route['options'])){
                    foreach ($_route['options'] as $_option=>$_value){
                        if(
                        !empty($_route['url_pieces']) &&
                        isset($_route['options'][$_option]) &&
                        array_search(':'.$_option, $_route['url_pieces']) === false &&
                        array_search('*'.$_option, $_route['url_pieces']) === false &&
                        (
                        is_string($_value) ||
                        is_integer($_value)) &&
                        (
                        !isset($params_copy[$_option]
                        ) ||
                        $params_copy[$_option] != $_value
                        )
                        )
                        {
                            continue 2;
                        }
                        if(isset($params_copy[$_option]) &&
                        $_value == $params_copy[$_option] &&
                        $_value !== OPTIONAL &&
                        $_value !== COMPULSORY)
                        {
                            if($_option == 'controller'){
                                $_controller = $_value;
                            }
                            unset($params_copy[$_option]);
                            unset($$_option);
                        }
                    }
                }


                foreach ($_route['arr_params'] as $arr_route){
                    if(isset($$arr_route) && is_array($$arr_route)){
                        $$arr_route = join('/',$$arr_route);
                    }
                }

                $_url_pieces = array();
                foreach (array_reverse($_route['url_pieces']) as $_v){
                    if(strstr($_v,':') || strstr($_v,'*')){
                        $_v = substr($_v,1);
                        if(isset($params[$_v])){
                            if (count($_url_pieces) || isset($_route['options'][$_v]) && $params[$_v] != $_route['options'][$_v] || !isset($_route['options'][$_v]) || isset($_route['options'][$_v]) && $_route['options'][$_v] === COMPULSORY){
                                $_url_pieces[] = is_array($params[$_v]) ? join('/',$params[$_v]) : $params[$_v];
                            }
                        }
                    }else{
                        $_url_pieces[] = is_array($_v) ? join('/',$_v) : $_v;
                    }
                }

                $_parsed = str_replace('//','/','/'.join('/',array_reverse($_url_pieces)).'/');

                // This might be faster but using eval here might cause security issues
                //@eval('$_parsed = "/".trim(str_replace("//","/","'.str_replace(array('/:','/*'),'/$','/'.join('/',$_route['url_pieces']).'/').'"),"/")."/";');

                if($_parsed == '//'){
                    $_parsed = '/';
                }

                if(is_string($_parsed)){
                    if($_parsed_arr = $this->toParams($_parsed)){

                        if($_parsed == '/' && count(array_diff($params,$_parsed_arr)) == 0){
                            $_cache[$_cache_key] = '/';
                            return $_cache[$_cache_key];
                        }

                        if( isset($_parsed_arr['controller']) &&
                        ((isset($controller) && $_parsed_arr['controller'] == $controller) ||
                        (isset($_controller) && $_parsed_arr['controller'] == $_controller))){


                            if( isset($_route['options']['controller']) &&
                            $_route['options']['controller'] !== OPTIONAL &&
                            $_route['options']['controller'] !== COMPULSORY &&
                            $_parsed_arr['controller'] != $_route['options']['controller'] &&
                            count(array_diff(array_keys($_route['options']),array_keys($_parsed_arr))) > 0){
                                continue;
                            }

                            $url_params = array_merge($_parsed_arr, $params_copy);

                            if($_parsed != '/'){
                                foreach ($_parsed_arr as $_k=>$_v){
                                    if(isset($url_params[$_k]) && $url_params[$_k] == $_v){
                                        unset($url_params[$_k]);
                                    }
                                }
                            }

                            foreach (array_reverse($_route['url_pieces'], true) as $position => $piece){
                                $piece = str_replace(array(':','*'),'', $piece);
                                if(isset($$piece)){
                                    if(strstr($_parsed,'/'.$$piece.'/')){
                                        unset($url_params[$piece]);
                                    }
                                }
                            }

                            foreach ($url_params as $_k=>$_v){
                                if($_v == null){
                                    unset($url_params[$_k]);
                                }
                            }

                            if($_parsed == '/' && !empty($url_params['controller'])){
                                $_parsed = '/'.join('/',array_diff(array($url_params['controller'],@$url_params['action'],@$url_params['id']),array('')));
                                unset($url_params['controller'],$url_params['action'],$url_params['id']);
                            }

                            if(defined('AK_URL_REWRITE_ENABLED') && AK_URL_REWRITE_ENABLED === true){
                                if(isset($url_params['ak'])){
                                    unset($url_params['ak']);
                                }
                                if(isset($url_params['lang'])){
                                    $_parsed = '/'.$url_params['lang'].$_parsed;
                                    unset($url_params['lang']);
                                }
                                $_parsed .= count($url_params) ? '?'.http_build_query($url_params) : '';
                            }else{
                                $_parsed = count($url_params) ? '/?ak='.$_parsed.'&'.http_build_query($url_params) : '/?ak='.$_parsed;
                            }
                            $_cache[$_cache_key] = $_parsed;
                            return $_parsed;
                        }
                    }
                }
            }

            (array)$extra_parameters = @array_diff($params_copy,$_parsed_arr);


            if($_parsed == '' && is_array($params)){
                $_parsed = '?'.http_build_query(array_merge($params,(array)$extra_parameters));
            }
            if($_parsed == '//'){
                $_parsed = '/';
            }

            if(defined('AK_URL_REWRITE_ENABLED') && AK_URL_REWRITE_ENABLED === false && $_parsed{0} != '?'){
                $_parsed = '?ak='.trim($_parsed,'/');
            }

            $_parsed .= empty($extra_parameters) ? '' : (strstr($_parsed,'?') ? '&' : '?').http_build_query($extra_parameters);
            $_cache[$_cache_key] = $_parsed;
        }
        return $_cache[$_cache_key];
    }

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

        foreach ($this->_loaded_routes as $_route){
            $params = array();

            if(preg_match($_route['regex'], $url)){
                foreach ($_route['regex_array'] as $single_regex_arr){

                    $k = key($single_regex_arr);

                    $single_regex = $single_regex_arr[$k];
                    $single_regex = '/^(\/'.$single_regex.'){1}/';
                    preg_match($single_regex, $url, $got);

                    if(in_array($k,$_route['arr_params'])){
                        $url_parts = strstr(trim($url,'/'),'/') ? explode('/',trim($url,'/')) : array(trim($url,'/'));

                        $pieces = (isset($_route['options'][$k]) && $_route['options'][$k] > 0) ? $_route['options'][$k] : count($url_parts);
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
                        if(in_array($k,$_route['var_params'] )){
                            $param = trim($got[0],'/');
                            $params[$k] = $param;
                        }
                    }
                    if(isset($_route['options'][$k])){

                        if($_route['options'][$k] !== COMPULSORY &&
                        $_route['options'][$k] !== OPTIONAL &&
                        $_route['options'][$k] != '' &&
                        ((!isset($params[$k]))||(isset($params[$k]) && $params[$k] == ''))){
                            $params[$k] = $_route['options'][$k];
                        }
                    }
                }

                if(isset($_route['options'])){
                    foreach ($_route['options'] as $_option => $_value){
                        if($_value !== COMPULSORY && $_value !== OPTIONAL && $_value != '' && !isset($params[$_option])){
                            $params[$_option] = $_value;
                        }
                    }
                }
            }
            if(count($params)){
                $params = array_map(array(&$this,'_urlDecode'), $params);
                return $params;
            }
        }
        return false;
    }

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

            if($is_var && !isset($options[$piece])){
                $options[$piece] = OPTIONAL;
            }

            if($is_arr && !isset($options[$piece])){
                $options[$piece] = OPTIONAL;
            }

            if(($is_arr || $is_var) && $piece == 'this'){
                trigger_error(Ak::t('You can\'t use the reserved word this for mapping URLs'), E_USER_ERROR);
            }

            //COMPULSORY

            if($is_constant){
                $regex_arr[] = array('_constant_'.$piece => '('.$piece.'(?=(\/|$))){1}');
            }elseif(isset($requirements[$piece])){
                if (isset($options[$piece]) && $options[$piece] !== COMPULSORY){
                    $regex_arr[] = array($piece=> '(('.trim($requirements[$piece],'/').'){1})?');
                }elseif(isset($options[$piece]) && $options[$piece] !== OPTIONAL){
                    $regex_arr[] = array($piece=> '(('.trim($requirements[$piece],'/').'){1}|('.$options[$piece].'){1}){1}');
                }else{
                    $regex_arr[] = array($piece=> '('.trim($requirements[$piece],'/').'){1}');
                }
            }elseif(isset($options[$piece])){
                if($options[$piece] === OPTIONAL){
                    $regex_arr[] = array($piece=>'[^\/]*');
                }elseif ($options[$piece] === COMPULSORY){
                    $regex_arr[] = array($piece=> COMPULSORY_REGEX);
                }elseif(is_string($options[$piece]) && $options[$piece][0] == '/' &&
                ($_tmp_close_char = strlen($options[$piece])-1 || $options[$piece][$_tmp_close_char] == '/')){
                    $regex_arr[] = array($piece=> substr($options[$piece],1,$_tmp_close_char*-1));
                }elseif ($options[$piece] != ''){
                    $regex_arr[] = array($piece=>'[^\/]*');
                    $optional_pieces[$piece] = $piece;
                }
            }else{
                $regex_arr[] = array($piece => $piece);
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

        $regex = '/^((\/)?';
        $pieces_count = count($regex_arr);

        foreach ($regex_arr as $pos=>$single_regex_arr){
            $k = key($single_regex_arr);
            $single_regex = $single_regex_arr[$k];

            $slash_delimiter = isset($last_optional_var) && ($last_optional_var <= $pos) ? '{1}' : '?';

            if(isset($optional_pieces[$k])){
                $terminal = (is_numeric($options[$k]) && $options[$k] > 0 && in_array($k,$arr_params)) ? '{'.$options[$k].'}' : ($pieces_count == $pos+1 ? '?' : '{1}');
                $regex .= $is_arr ? '('.$single_regex.'\/'.$slash_delimiter.')+' : '('.$single_regex.'\/'.$slash_delimiter.')'.$terminal;
            }else{
                $regex .= $is_arr ? $single_regex.'\/+' : $single_regex.'\/'.($pieces_count == $pos+1 ? '?' : $slash_delimiter);
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


    /**
    * Alias for map
    * 
    * @see map
    */
    function map($url_pattern, $options = array(), $requirements = null)
    {
        return $this->connect($url_pattern, $options, $requirements);
    }

    /**
    * Url decode a string or an array of strings
    */
    function _urlDecode($input)
    {
        if(!empty($input)){
            if (is_scalar($input)){
                return urldecode($input);
            }elseif (is_array($input)){
                return array_map(array(&$this,'_urlDecode'),$input);
            }
        }
        return '';
    }

    /**
    * Url encodes a string or an array of strings
    */
    function _urlEncode($input)
    {
        if(!empty($input)){
            if (is_scalar($input)){
                return urlencode($input);
            }elseif (is_array($input)){
                return array_map(array(&$this,'_urlEncode'),$input);
            }
        }
        return '';
    }

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
}


function &AkRouter()
{
    $null = null;
    $AkRouter =& Ak::singleton('AkRouter', $null);
    return $AkRouter;
}

?>
