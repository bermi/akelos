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
 * @subpackage Ak
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

/**
* Define LOG Levels
* @todo Log events
*/
define('AK_LOG_NOTICE', 0);
define('AK_LOG_WARNING', 1);
define('AK_LOG_ERROR', 2);

defined('AK_FRAMEWORK_LANGUAGE') ? null : define('AK_FRAMEWORK_LANGUAGE', 'en');
defined('AK_DEV_MODE') ? null : define('AK_DEV_MODE', false);
defined('AK_AUTOMATIC_CONFIG_VARS_ENCRYPTION') ? null : define('AK_AUTOMATIC_CONFIG_VARS_ENCRYPTION', false);


/**
* Akelos Framework static functions
*
* Ak contains all the Akelos Framework static functions. This
* class acts like a name space to avoid naming collisions
* when PHP gets new functions into its core. And also to provide 
* additional functionality to existing PHP functions mantaining the same interface  
*
* @package AkelosFramework
* @subpackage Ak::static_functions
* @author Bermi Ferrer <bermi at akelos com>
* @copyright Copyright (c) 2002-2005, Akelos Media, S.L. http://www.akelos.org
* @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
*/
class Ak
{

    /**
    * Gets an instance of AdoDb database connection
    *
    * Whenever a database connection is required you can get a
    * reference to the default database connection by doing:
    *
    * $db =& Ak:db(); // get an adodb instance
    * 
    * AdoDB manual can be found at http://phplens.com/adodb/
    *
    * @access public
    * @param    string    $dns    A string containing Data Source Name (information
    * regarding database connection)
    * http://phplens.com/adodb/code.initialization.html#dsnsupport
    * @static
    * @return resource Php AdoDb instance.
    */
    function &db($dsn = null, $connection_id = null)
    {
        static $db;

        // In order to retrieve a database connection we just need to provide its identifier
        $connection_id = empty($connection_id) ?
        (empty($dsn) || strstr($dsn,':') ? 'default' : $dsn) :
        $connection_id;

        if(empty($db[$connection_id])){
            require_once(AK_CONTRIB_DIR.DS.'adodb'.DS.'adodb.inc.php');

            if(substr($dsn, 0, 6) == 'mysql:'){
                $dsn = substr_replace($dsn, 'mysqlt:', 0, 6);
            }

            if (!$db[$connection_id] = (AK_DEBUG ? NewADOConnection($dsn) : @NewADOConnection($dsn))){
                trigger_error(Ak::t('Connection to the database failed'), E_USER_ERROR);
                exit;
            }
            $db[$connection_id]->debug = AK_DEBUG == 2;
            defined('AK_DATABASE_CONNECTION_AVAILABLE') ? null : define('AK_DATABASE_CONNECTION_AVAILABLE', true);
            $dsn = '';
        }
        return $db[$connection_id];
    }



    /**
    * Gets a cache object singleton instance
    */
    function &cache()
    {
        static $cache;
        if(!isset($cache)){
            require_once(AK_LIB_DIR.DS.'AkCache.php');
            $cache =& new AkCache();
        }
        return $cache;
    }


    function toUrl($options, $set_routes = false)
    {
        static $Map;
        if(empty($Map)){
            if($set_routes){
                $Map = $options;
                return;
            }else{
                require_once(AK_LIB_DIR.DS.'AkRouter.php');
                $Map =& new AkRouter();
                if(is_file(AK_ROUTES_MAPPING_FILE)){
                    include(AK_ROUTES_MAPPING_FILE);
                }
            }
        }
        $Map->debug = true;
        return $Map->toUrl($options);
    }


    /**
    * Translate strings to the current locale.
    *
    * When using Ak::t(), try to put entire sentences and strings
    * in one Ak::t() call.
    * This makes it easier for translators. HTML markup within
    * translation strings
    * is acceptable, if necessary. The suggested syntax for a
    * link embedded
    * within a translation string is:
    *
    * @access public
    * @static
    * @param    string    $string    A string containing the English string to
    * translate.
    * @param    array    $args    An associative array of replacements to make after
    * translation. Incidences of any key in this array
    * are replaced with the corresponding value.
    * @return string The translated string.
    */
    function t($string, $args = null, $controller = null)
    {
        static $framework_dictionary = array(), $lang, $_dev_shutdown = true;

        if(AK_AUTOMATICALLY_UPDATE_LANGUAGE_FILES && !empty($string) && is_string($string)){
            require_once(AK_LIB_DIR.DS.'AkLocaleManager.php');
            // This adds used strings to a stack for storing new entries on the locale file after shutdown
            AkLocaleManager::getUsedLanguageEntries($string, $controller);
            if($_dev_shutdown){
                register_shutdown_function(array('AkLocaleManager','updateLocaleFiles'));
                $_dev_shutdown = false;
            }
        }

        if(!isset($lang)){
            if(!empty($_SESSION['lang'])){
                $lang =  $_SESSION['lang'];
            }else{
                $lang = Ak::lang();
            }
            if(is_file(AK_CONFIG_DIR.DS.'locales'.DS.$lang.'.php')){
                require(AK_CONFIG_DIR.DS.'locales'.DS.$lang.'.php');
                $framework_dictionary = array_merge($framework_dictionary,$dictionary);
            }
            if(!defined('AK_LOCALE')){
                define('AK_LOCALE', $lang);
            }
            if(!empty($locale) && is_array($locale)){
                Ak::locale(null, $lang, $locale);
            }
        }

        if(!empty($string) && is_array($string)){
            if(!empty($string[$lang])){
                return $string[$lang];
            }
            $try_whith_lang = $args !== false && empty($string[$lang]) ? Ak::base_lang() : $lang;
            if(empty($string[$try_whith_lang]) && $args !== false){
                foreach (Ak::langs() as $try_whith_lang){
                    if(!empty($string[$try_whith_lang])){
                        return $string[$try_whith_lang];
                    }
                }
            }
            return @$string[$try_whith_lang];
        }

        if(isset($controller) && !isset($framework_dictionary[$controller.'_dictionary']) && is_file(AK_APP_DIR.DS.'locales'.DS.$controller.DS.$lang.'.php')){
            require(AK_APP_DIR.DS.'locales'.DS.$controller.DS.$lang.'.php');
            $framework_dictionary[$controller.'_dictionary'] = (array)$dictionary;
        }

        if(isset($controller) && isset($framework_dictionary[$controller.'_dictionary'][$string])){
            $string = $framework_dictionary[$controller.'_dictionary'][$string];
        }else {
            $string = isset($framework_dictionary[$string]) ? $framework_dictionary[$string] : $string;
        }

        if(isset($args) && is_array($args)){
            $string = @str_replace(array_keys($args), array_values($args),$string);
        }

        /**
        * @todo Prepare for multiple locales by inspecting AK_DEFAULT_LOCALE
        */

        return $string;
    }



    /**
     * Gets information about current locale from the locale settings on config/locales/LOCALE.php 
     * 
     * This are common settings on the locale file:
     * 'description' // Locale description Example. Spanish
     * 'charset' // 'ISO-8859-1';
     * 'date_time_format' // '%d/%m/%Y %H:%i:%s';
     * 'date_format' // '%d/%m/%Y';
     * 'long_date_format' // '%d/%m/%Y';
     * 'time_format' // '%H:%i';
     * 'long_time_format' // '%H:%i:%s';
     */
    function locale($locale_setting, $locale = null)
    {
        static $settings;

        // We initiate the locale settings
        Ak::t('Akelos');

        $locale = empty($locale) ? (defined('AK_LOCALE') ? AK_LOCALE : (Ak::t('Akelos') && Ak::locale($locale_setting))) : $locale;

        if (empty($settings[$locale])) {
            if(func_num_args() != 3){ // First time we ask for something using this locale so we will load locale details
                $requested_locale = $locale;
                @require(AK_CONFIG_DIR.DS.'locales'.DS.Ak::sanitize_include($requested_locale).'.php');
                $locale = !empty($locale) && is_array($locale) ? $locale : array();
                Ak::locale(null, $requested_locale, $locale);
                return Ak::locale($locale_setting, $requested_locale);
            }else{
                $settings[$locale] = func_get_arg(2);
                if(isset($settings[$locale]['charset'])){
                    defined('AK_CHARSET') ? null : (define('AK_CHARSET',$settings[$locale]['charset']) && @ini_set('default_charset', AK_CHARSET));
                }
            }
        }

        return isset($settings[$locale][$locale_setting]) ? $settings[$locale][$locale_setting] : false;
    }


    function lang($set_language = null)
    {
        static $lang;
        $lang = empty($set_language) ? (empty($lang) ? AK_FRAMEWORK_LANGUAGE : $lang) : $set_language;
        return $lang;
    }


    function get_url_locale($set_locale = null)
    {
        static $locale;
        if(!empty($locale)){
            return $locale;
        }
        $locale = empty($set_locale) ? '' : $set_locale;
        return $locale;
    }



    function langs()
    {
        static $langs;
        if(!empty($lang)){
            return $lang;
        }
        $lang = Ak::lang();
        if(defined('AK_APP_LOCALES')){
            $langs = array_diff(explode(',',AK_APP_LOCALES.','),array(''));
        }
        $langs = empty($langs) ? array($lang) : $langs;
        return $langs;
    }

    function base_lang()
    {
        return array_shift(Ak::langs());
    }



    function dir($path, $options = array())
    {
        $result = array();

        $path = rtrim($path, '/\\');
        $default_options = array(
        'files' => true,
        'dirs' => true,
        'recurse' => false,
        );

        $options = array_merge($default_options, $options);

        if(is_file($path)){
            $result = array($path);
        }elseif(is_dir($path)){
            if ($id_dir = opendir($path)){
                while (false !== ($file = readdir($id_dir))){
                    if ($file != "." && $file != ".." && $file != '.svn'){
                        if(!empty($options['files']) && !is_dir($path.DS.$file)){
                            $result[] = $file;
                        }elseif(!empty($options['dirs'])){
                            $result[][$file] = !empty($options['recurse']) ? Ak::dir($path.DS.$file, $options) : $file;
                        }
                    }
                }
                closedir($id_dir);
            }
        }

        return array_reverse($result);
    }


    function file_put_contents($file_name, $content, $options = array())
    {

        $default_options = array(
        'ftp' => defined('AK_UPLOAD_FILES_USING_FTP') && AK_UPLOAD_FILES_USING_FTP,
        'base_path' => AK_BASE_DIR,
        );
        $options = array_merge($default_options, $options);

        if(!function_exists('file_put_contents')){
            include_once(AK_CONTRIB_DIR.DS.'pear'.DS.'PHP'.DS.'Compat.php');
            PHP_Compat::loadFunction(array('file_put_contents'));
        }

        $file_name = trim(str_replace($options['base_path'], '',$file_name),DS);

        if($options['ftp']){
            require_once(AK_LIB_DIR.DS.'AkFtp.php');
            $file_name = trim(str_replace(array(DS,'//'),array('/','/'),$file_name),'/');
            if(!AkFtp::is_dir(dirname($file_name))){
                AkFtp::make_dir(dirname($file_name));
            }

            return AkFtp::put_contents($file_name, $content);
        }else{
            if(!is_dir(dirname($options['base_path'].DS.$file_name))){
                Ak::make_dir(dirname($options['base_path'].DS.$file_name), $options);
            }

            if(!$result = file_put_contents($options['base_path'].DS.$file_name, $content)){
                if(!empty($content)){
                    Ak::trace("Please change file/dir permissions or enable FTP file handling by".
                    " setting the following on your config/".AK_ENVIRONMENT.".php file \n<pre>define('AK_UPLOAD_FILES_USING_FTP', true);\n".
                    "define('AK_READ_FILES_USING_FTP', false);\n".
                    "define('AK_DELETE_FILES_USING_FTP', true);\n".
                    "define('AK_FTP_PATH', 'ftp://username:password@example.com/path_to_the_framework');\n".
                    "define('AK_FTP_AUTO_DISCONNECT', true);\n</pre>");
                }
            }
            return $result;
        }
    }


    function file_get_contents($file_name, $options = array())
    {
        $default_options = array(
        'ftp' => defined('AK_READ_FILES_USING_FTP') && AK_READ_FILES_USING_FTP,
        'base_path' => AK_BASE_DIR,
        );
        $options = array_merge($default_options, $options);

        $file_name = trim(str_replace($options['base_path'], '',$file_name),DS);
        if($options['ftp']){
            require_once(AK_LIB_DIR.DS.'AkFtp.php');
            $file_name = trim(str_replace(array(DS,'//'),array('/','/'),$file_name),'/');
            return AkFtp::get_contents($file_name);
        }else{
            return file_get_contents($options['base_path'].DS.$file_name);
        }
    }

    /**
     * @todo Optimize this code (dirty add-on to log command line interpreter results)
     */
    function file_add_contents($file_name, $content, $options = array())
    {
        $original_content = @Ak::file_get_contents($file_name, $options);
        return Ak::file_put_contents($file_name, $original_content.$content, $options);
    }

    function file_delete($file_name, $options = array())
    {
        $default_options = array(
        'ftp' => defined('AK_DELETE_FILES_USING_FTP') && AK_DELETE_FILES_USING_FTP,
        'base_path' => AK_BASE_DIR,
        );
        $options = array_merge($default_options, $options);

        $file_name = trim(str_replace($options['base_path'], '',$file_name),DS);
        if($options['ftp']){
            require_once(AK_LIB_DIR.DS.'AkFtp.php');
            $file_name = trim(str_replace(array(DS,'//'),array('/','/'),$file_name),'/');
            return AkFtp::delete($file_name, true);
        }else{
            return unlink($options['base_path'].DS.$file_name);
        }
    }

    function directory_delete($dir_name, $options = array())
    {
        $default_options = array(
        'ftp' => defined('AK_DELETE_FILES_USING_FTP') && AK_DELETE_FILES_USING_FTP,
        'base_path' => AK_BASE_DIR,
        );
        $options = array_merge($default_options, $options);

        $sucess = true;
        $dir_name = str_replace('..','', rtrim($dir_name,'\\/. '));
        if($dir_name == ''){
            return false;
        }
        $dir_name = trim(str_replace($options['base_path'], '',$dir_name),DS);
        if($options['ftp']){
            require_once(AK_LIB_DIR.DS.'AkFtp.php');
            $dir_name = trim(str_replace(array(DS,'//'),array('/','/'),$dir_name),'/');
            return AkFtp::delete($dir_name);
        }else{
            if($fs_items = glob($options['base_path'].DS.$dir_name."/*")){
                $items_to_delete = array('directories'=>array(), 'files'=>array());
                foreach($fs_items as $fs_item) {
                    $items_to_delete[ (is_dir($fs_item) ? 'directories' : 'files') ][] = $fs_item;
                }
                foreach ($items_to_delete['files'] as $file){
                    Ak::file_delete($file, $options);
                }
                foreach ($items_to_delete['directories'] as $directory){
                    $sucess = $sucess ? Ak::directory_delete($directory, $options) : $sucess;
                }
                return $sucess;
            }
            return rmdir($options['base_path'].DS.$dir_name);
        }
    }

    function make_dir($path, $options = array())
    {
        $default_options = array(
        'ftp' => defined('AK_READ_FILES_USING_FTP') && AK_READ_FILES_USING_FTP,
        'base_path' => AK_BASE_DIR
        );
        $options = array_merge($default_options, $options);

        $path = trim(str_replace($options['base_path'], '',$path),DS);
        if($options['ftp']){
            require_once(AK_LIB_DIR.DS.'AkFtp.php');
            $path = trim(str_replace(array(DS,'//'),array('/','/'),$path),'/');
            return AkFtp::make_dir($path);
        }else{
            $path = $options['base_path'].DS.$path;
            if (!file_exists($path)){
                Ak::make_dir(dirname($path), $options);
                return mkdir($path);
            }
        }
        return false;
    }


    /**
     * Perform a web request using curl
     * 
     * @param string $url URL we are going to request.
     * @param array $options Options for current request.
Options are:
     * * referer: URL that will be set as referer url. Default is current url
     * * params: Parameter for the request. Can be an array of key=>values or a url params string like key=value&key2=value2
     * * method: In case params are given the will be requested using post method by default. Specify get if post is not what you need.
     * * browser_name: How are we going to be presented to the website. Default is 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)'
     * @return string
     */
    function url_get_contents($url, $options = array())
    {
        ak_compat('http_build_query');

        $default_options = array(
        'referer' => $url,
        'method' => 'post',
        'params' => '',
        'browser_name' => 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)',
        );

        $options = array_merge($default_options, $options);

        $options['params'] = !empty($options['params']) ? (is_array($options['params']) ? http_build_query(array_map('urlencode', $options['params'])) : $options['params']) : '';
        $options['method'] = strtolower($options['method']) == 'post' ? 'post' : 'get';

        $ch = curl_init();

        curl_setopt ($ch, CURLOPT_URL, $options['referer']);
        curl_setopt ($ch, CURLOPT_USERAGENT, $options['browser_name']);
        curl_setopt ($ch, CURLOPT_HEADER, 0);

        if(!empty($options['params']) && $options['method'] == 'post'){
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $options['params']);
        }elseif(!empty($options['params'])){
            $url = trim($url,'?').'?'.trim($options['params'], '?');
        }

        curl_setopt ($ch, CURLOPT_REFERER, $url);

        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 100);

        $result = curl_exec ($ch);
        curl_close ($ch);

        return $result;
    }


    /**
    * Trace helper function for development purposes
    *
    * @access public
    * @static
    * @param    string    $text    Helper text
    * @param    string    $line    Helper line
    * @param    string    $file    Helper file
    * @return echoes result to screen
    */
    function trace($text = null, $line = null, $file = null)
    {
        static $counter = 0;
        if(!AK_DEBUG){
            //return;
        }

        $line = isset($line) ? "Line: $line".(AK_CLI?"\n":"<br />") : "";
        $file = isset($file) ? "File: $file".(AK_CLI?"\n":"<br />") : "";

        if(!isset($text)){
            $counter++;
            $text = '';
        }else {
            $text = AK_CLI?'---> '.$text:'<b>---&gt;</b>'.$text;
        }

        echo AK_CLI?"----------------\n$line $file $text\n----------------\n":"<hr /><div>$line $file $text</div><hr />\n";

    }




    /**
    * Outputs debug info given a PHP resource (vars, objects,
    * arrays...)
    *
    * @access public
    * @static
    * @param    mixed    $data    Data to debug. It can be an object, array,
    * resource..
    * @return void Prints debug info.
    */
    function debug ($data, $_functions=0)
    {
        if(!AK_DEBUG && !AK_DEV_MODE){
            return;
        }

        if($_functions!=0) {
            $sf=1;
        } else {
            $sf=0 ;
        }
        if(is_object($data) && method_exists($data, 'debug')){
            echo AK_CLI ?
            "\n------------------------------------\nEntering on ".get_class($data)." debug() method\n\n":
            "<hr /><h2>Entering on ".get_class($data)." debug() method</h2>";
            if(!empty($data->__activeRecordObject)){
                $data->toString(true);
            }
            $data->debug();
            return ;
        }
        if (isset ($data)) {
            if (is_array($data) || is_object($data)) {

                if (count ($data)) {
                    echo AK_CLI ? "/--\n" : "<ol>\n";
                    while (list ($key,$value) = each ($data)) {
                        $type=gettype($value);
                        if ($type=="array" || $type == "object") {
                            ob_start();
                            Ak::debug ($value,$sf);
                            $lines = explode("\n",ob_get_clean()."\n");
                            foreach ($lines as $line){
                                echo "\t".$line."\n";
                            }
                        } elseif (eregi ("function", $type)) {
                            if ($sf) {
                                AK_CLI ? printf ("\t* (%s) %s:\n",$type, $key, $value) :
                                printf ("<li>(%s) <b>%s</b> </li>\n",$type, $key, $value);
                            }
                        } else {
                            if (!$value) {
                                $value="(none)";
                            }
                            AK_CLI ? printf ("\t* (%s) %s = %s\n",$type, $key, $value) :
                            printf ("<li>(%s) <b>%s</b> = %s</li>\n",$type, $key, $value);
                        }
                    }
                    echo AK_CLI ? "\n--/\n" : "</ol>fin.\n";
                } else {
                    echo "(empty)";
                }
            }
        }
    }





    /**
    * Gets information about given object
    *
    * @access public
    * @static
    * @uses Ak::get_this_object_methods
    * @uses Ak::get_this_object_attributes
    * @param    object    &$object    Object to get info from
    * @param    boolean    $include_inherited_info    By setting this to true, parent Object properties
    * and methods will be included.
    * @return string html output with Object info
    */
    function get_object_info($object, $include_inherited_info = false)
    {
        $object_name = get_class($object);
        $methods = $include_inherited_info ? get_class_methods($object) : Ak::get_this_object_methods($object);
        $vars = $include_inherited_info ? get_class_vars($object_name) : Ak::get_this_object_attributes($object);
        $var_desc = '';
        if(is_array($vars)){
            $var_desc = '<ul>';
            foreach ($vars as $varname=>$var_value){
                $var_desc .= "<li>$varname = $var_value (". gettype($var_value) .")</li>\n";
            }
            $var_desc .= "</ul>";
        }
        return Ak::t('Object <b>%object_name</b> information:<hr> <b>object Vars:</b><br>%var_desc <hr> <b>object Methods:</b><br><ul><li>%methods</li></ul>',array('%object_name'=>$object_name,'%var_desc'=>$var_desc,'%methods'=>join("();</li>\n<li>",$methods) .'();'));
    }




    /**
    * Gets selected object methods.
    *
    * WARNING: Inherited methods are not returned by this
    * function. You can fetch them by using PHP native function
    * get_class_methods
    *
    * @access public
    * @static
    * @see get_this_object_attributes
    * @see get_object_info
    * @param    object    &$object    Object to inspect
    * @return array Returns an array with selected object methods. It
    * does not return inherited methods
    */
    function get_this_object_methods($object)
    {
        $array1 = get_class_methods($object);
        if($parent_object = get_parent_class($object)){
            $array2 = get_class_methods($parent_object);
            $array3 = array_diff($array1, $array2);
        }else{
            $array3 = $array1;
        }
        return array_values((array)$array3);
    }




    /**
    * Get selected objects default attributes
    *
    * WARNING: Inherited attributes are not returned by this
    * function. You can fetch them by using PHP native function
    * get_class_vars
    *
    * @access public
    * @static
    * @see get_this_object_methods
    * @see get_object_info
    * @param    object    &$object    Object to inspect
    * @return void Returns an array with selected object attributes.
    * It does not return inherited attributes
    */
    function get_this_object_attributes($object)
    {
        $object = get_class($object);
        $array1 = get_class_vars($object);
        if($parent_object = get_parent_class($object)){
            $array2 = get_class_vars($parent_object);
            $array3 = array_diff_assoc($array1, $array2);
        }else{
            $array3 = $array1;
        }
        return (array)$array3;
    }



    function &getLogger()
    {
        static $Logger;
        if(empty($Logger)){
            require_once(AK_LIB_DIR.DS.'AkLogger.php');
            $Logger =& new AkLogger();
        }
        return $Logger;
    }


    function get_constants()
    {
        $constants = get_defined_constants();
        $keys = array_keys($constants);
        foreach ($keys as $k){
            if(substr($k,0,3) != 'AK_'){
                unset($constants[$k]);
            }
        }
        return $constants;
    }


    /**
    * @todo Use timezone time
    */
    function time()
    {
        return time()+(defined('AK_TIME_DIFERENCE') ? AK_TIME_DIFERENCE*3600 : 0);
    }

    function gmt_time()
    {
        return Ak::time()+(AK_TIME_DIFERENCE_FROM_GMT*3600);
    }


    /**
    * Gets a timestamp for input date provided in one of this formats: "year-month-day hour:min:sec", "year-month-day", "hour:min:sec"
    */
    function getTimestamp($iso_date_or_hour = null)
    {
        if(empty($iso_date_or_hour)){
            return Ak::time();
        }
        if (!preg_match("|^([0-9]{4})[-/\.]?([0-9]{1,2})[-/\.]?([0-9]{1,2})[ -]?(([0-9]{1,2}):?([0-9]{1,2}):?([0-9\.]{1,4}))?|", ($iso_date_or_hour), $rr)){
            if (preg_match("|^(([0-9]{1,2}):?([0-9]{1,2}):?([0-9\.]{1,4}))?|", ($iso_date_or_hour), $rr)){
                return mktime($rr[2],$rr[3],$rr[4]);
            }
        }else{
            if($rr[1]>=2038 || $rr[1]<=1970){
                require_once(AK_CONTRIB_DIR.DS.'adodb'.DS.'adodb-time.inc.php');
                return isset($rr[5]) ? adodb_mktime($rr[5],$rr[6],$rr[7],$rr[2],$rr[3],$rr[1]) : adodb_mktime(0,0,0,$rr[2],$rr[3],$rr[1]);
            }else{
                return isset($rr[5]) ? mktime($rr[5],$rr[6],$rr[7],$rr[2],$rr[3],$rr[1]) : mktime(0,0,0,$rr[2],$rr[3],$rr[1]);
            }
        }
        trigger_error(Ak::t('Invalid ISO date. You must supply date in one of the following formats: "year-month-day hour:min:sec", "year-month-day", "hour:min:sec"'));
        return false;
    }

    /**
	* Return formatted date.
	* 
	* You can supply a format as defined at http://php.net/date
	* 
	* Default date is in ISO format 
    */
    function getDate($timestamp = null, $format = null)
    {
        $timestamp = !isset($timestamp) ? Ak::time() : $timestamp;
        $use_adodb = $timestamp <= -3600 || $timestamp >= 2147468400;
        if($use_adodb){
            require_once(AK_CONTRIB_DIR.DS.'adodb'.DS.'adodb-time.inc.php');
        }
        if(!isset($format)){
            return $use_adodb ? adodb_date('Y-m-d H:i:s', $timestamp) : date('Y-m-d H:i:s', $timestamp);
        }elseif (!empty($format)){
            return $use_adodb ? adodb_date($format, $timestamp) : date($format, $timestamp);
        }
        trigger_error(Ak::t('You must supply a valid UNIX timetamp. You can get the timestamp by calling Ak::getTimestamp("2006-09-27 20:45:57")'));
        return false;
    }


    /**
    *   mail function substitute. Uses the PEAR::Mail() function API.
    *
    *   Messaging subsystem for user communication. See PEAR::Mail() function in PHP
    *   documentation for information.
    *
    *   User must declare any of these variables for specify the outgoing method. Currently,
    *   only Sendmail and STMP methods are available . Variables
    *   for using any of these methods are:
    *
    *   AK_SENDMAIL = 0
    *   AK_SMTP = 1
    *
    *   For future upgrades, you must define which constants must be declared and add 
    *   the functionality.
    *
    *   NOTE: If messaging method is SMTP, you must declare in config file (/config/config.php)
    *   the outgoing SMTP server and the authentication pair user/password as constants
    *   AK_SMTP_SERVER, AK_SMTP_USER and AK_SMTP_PASSWORD, respectively.
    *
    * 
    *   @param $from
    *   
    *   User who sends the mail.
    *   
    *   @param $to
    *
    *   Receiver, or receivers of the mail.
    *
    *   The formatting of this string must comply with RFC 2822. Some examples are:
    *
    *   user@example.com
    *   user@example.com, anotheruser@example.com
    *   User <user@example.com>
    *   User <user@example.com>, Another User <anotheruser@example.com>
    *    
    *   @param $subject
    *
    *   Subject of the email to be sent.  This must not contain any newline
    *   characters, or the mail may not be sent properly.
    *    
    *   @param $body
    *
    *   Message to be sent.
    *           
    *   @param additional_headers (optional)   
    *
    *   Array to be inserted at the end of the email header.
    *
    *   This is typically used to add extra headers (Bcc) in an associative array, where the 
    *   array key is the header name (i.e., 'Bcc'), and the array value is the header value 
    *   (i.e., 'test'). The header produced from those values would be 'Bcc: test'.
    *    
    *   @return boolean whether message has been sent or not.
    *
    */

    function mail ($from, $to, $subject, $body, $additional_headers = array())
    {
        require_once(AK_CONTRIB_DIR.DS.'pear'.DS.'Mail.php');

        static $mail_connector;

        if(!isset($mail_connector)){
            if (defined('AK_SENDMAIL')) {
                // Using Sendmail daemon without parameters.
                $mail_connector = Mail::factory('sendmail');
            } else if (defined('AK_SMTP') && AK_SMTP) {
                // Using external SMTP server.
                $params['host'] = AK_SMTP_SERVER;
                $params['username'] = AK_SMTP_USER;
                $params['password'] = AK_SMTP_PASSWORD;

                $mail_connector = Mail::factory('smtp', $params);
            } else {
                // Using PHP mail() function thru PEAR. Factory without parameters.
                $mail_connector = Mail::factory('mail');
            }
        }

        $recipients['To'] = $to;

        if (!empty($additional_headers)) {
            foreach ($additional_headers as $k=>$v) {

                if (strtolower($k)=='cc' || strtolower($k)=='cc:') {
                    $recipients['cc'] = $v;
                    unset($additional_headers['cc']);
                }

                if (strtolower($k)=='bcc' || strtolower($k)=='bcc:') {
                    $recipients['bcc'] = $v;
                    unset($additional_headers['bcc']);
                }
            }
        }

        $headers['From'] = $from;
        $headers['Subject'] = $subject;
        $headers['Content-Type'] = empty($headers['Content-Type']) ? 'text/plain; charset='.Ak::locale('charset').'; format=flowed' : $headers['Content-Type'];

        $headers = array_merge($headers, $additional_headers);

        $error_code = $mail_connector->send($recipients, $headers, $body);

        return $error_code;
    }


    /**
	* @todo move this out of here and use Pear Benchmark instead
	*/
    function profile($message = '')
    {
        static $profiler;
        if(AK_DEV_MODE && AK_ENABLE_PROFILER){
            if(!isset($profiler)){
                @require_once(AK_LIB_DIR.DS.'AkProfiler.php');
                $profiler = new AkProfiler();
                $profiler->init();
                register_shutdown_function(array(&$profiler,'showReport'));
            }else {
                $profiler->setFlag($message);
            }
        }
    }


    /**
    * Gets the size of given element. Counts arrays, returns numbers, string length or executes size() method on given object
    */
    function size($element)
    {
        if(is_array($element)){
            return count($element);
        }elseif (is_numeric($element)){
            return $element;
        }elseif (is_string($element)){
            return strlen($element);
        }elseif (is_object($element) && method_exists($element,'size')){
            return $element->size();
        }else{
            return 0;
        }
    }


    /**
     * Select is a function for selecting items from double depth array.
     * This is useful when you just need some fields for generating 
     * tables, select lists with only desired fields.
     *
     *   $People = array(
     *    array('name'=>'Jose','email'=>'jose@example.com','address'=>'Colon, 52'),
     *    array('name'=>'Alicia','email'=>'alicia@example.com','address'=>'Mayor, 45'),
     *    array('name'=>'Hilario','email'=>'hilario@example.com','address'=>'Carlet, 78'),
     *    array('name'=>'Bermi','email'=>'bermi@example.com','address'=>'Vilanova, 33'),
     *   );
     * 
     *    $people_for_table_generation = Ak::select($People,'name','email');
     *    
     *    Now $people_for_table_generation will hold an array with
     *    array (
     *        array ('name' => 'Jose','email' => 'jose@example.com'),
     *        array ('name' => 'Alicia','email' => 'alicia@example.com'),
     *        array ('name' => 'Hilario','email' => 'hilario@example.com'),
     *        array ('name' => 'Bermi','email' => 'bermi@example.com')
     *    );
     */

    function select(&$source_array)
    {
        $resulting_array = array();
        if(!empty($source_array) && is_array($source_array) && func_num_args() > 1) {
            $args = array_slice(func_get_args(),1);
            foreach ($source_array as $source_item){
                $item_fields = array();
                foreach ($args as $arg){
                    if(is_object($source_item) && isset($source_item->$arg)){
                        $item_fields[$arg] = $source_item->$arg;
                    }elseif(is_array($source_item) && isset($source_item[$arg])){
                        $item_fields[$arg] = $source_item[$arg];
                    }
                }
                if(!empty($item_fields)){
                    $resulting_array[] = $item_fields;
                }
            }
        }
        return $resulting_array;
    }

    function collect(&$source_array, $key_index, $value_index)
    {
        $resulting_array = array();
        if(!empty($source_array) && is_array($source_array)) {
            foreach ($source_array as $source_item){
                if(is_object($source_item)){
                    $resulting_array[@$source_item->$key_index] = @$source_item->$value_index;
                }elseif(is_array($source_item)){
                    $resulting_array[@$source_item[$key_index]] = @$source_item[$value_index];
                }
            }
        }
        return $resulting_array;
    }

    function delete($source_array, $attributes_to_delete_from_array)
    {
        $resulting_array = (array)$source_array;
        $args = array_slice(func_get_args(),1);
        $args = count($args) == 1 ? Ak::toArray($args[0]) : $args;
        foreach ($args as $arg){
            unset($resulting_array[$arg]);
        }
        return $resulting_array;
    }

    function &singleton($class_name, &$arguments)
    {
        static $instances;
        if(!isset($instances[$class_name])) {
            if(is_object($arguments)){
                $instances[$class_name] =& new $class_name($arguments);
            }else{
                if(Ak::size($arguments) > 0){
                    eval("\$instances[\$class_name] =& new \$class_name(".var_export($arguments, true)."); ");
                }else{
                    $instances[$class_name] =& new $class_name();
                }
            }
            $instances[$class_name]->__singleton_id = md5(microtime().rand(1000,9000));
        }
        return $instances[$class_name];
    }


    function xml_to_array ($xml_data)
    {
        $xml_parser = xml_parser_create ();
        xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($xml_parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parse_into_struct ($xml_parser, $xml_data, $vals, $index);
        xml_parser_free ($xml_parser);
        $params = array();
        $ptrs[0] = & $params;
        foreach ($vals as $xml_elem) {
            $level = $xml_elem['level'] - 1;
            switch ($xml_elem['type']) {
                case 'open':
                    $tag_or_id = (array_key_exists ('attributes', $xml_elem)) ? @$xml_elem['attributes']['ID'] : $xml_elem['tag'];
                    $ptrs[$level][$tag_or_id][] = array ();
                    $ptrs[$level+1] = & $ptrs[$level][$tag_or_id][count($ptrs[$level][$tag_or_id])-1];
                    break;
                case 'complete':
                    $ptrs[$level][$xml_elem['tag']] = (isset ($xml_elem['value'])) ? $xml_elem['value'] : '';
                    break;
            }
        }
        return ($params);
    }

    function array_to_xml($array, $header = "<?xml version=\"1.0\"?>\r\n", $parent = 'EMPTY_TAG')
    {
        static $_tags = array();
        $xml = $header;
        foreach ($array as $key => $value) {
            $key = is_numeric($key) ? $parent : $key;
            $value = is_array($value) ? "\r\n".xmlFromArray($value, '', $key) : $value;
            $_tags[$key] = $key;
            $xml .= sprintf("<%s>%s</%s>\r\n", $key, $value, $key);
            $parent = $key;
        }
        foreach ($_tags as $_tag){
            $xml = str_replace(array("<$_tag>\r\n<$_tag>","</$_tag>\r\n</$_tag>"),array("<$_tag>","</$_tag>"),$xml);
        }
        return $xml;
    }


    function encrypt($data, $key = 'Ak3los-m3D1a')
    {
        srand((double)microtime() *1000000);
        $k2 = md5(rand(0, 32000));
        $c = 0;
        $m = '';
        for ($i = 0 ; $i < strlen($data) ; $i++) {
            if ($c == strlen($k2)) $c = 0;
            $m.= substr($k2, $c, 1) .(substr($data, $i, 1) ^substr($k2, $c, 1));
            $c++;
        }
        $k = md5($key);
        $c = 0;
        $t = $m;
        $m = '';
        for ($i = 0 ; $i < strlen($t) ; $i++) {
            if ($c == strlen($k)) {
                $c = 0;
            }
            $m.= substr($t, $i, 1) ^substr($k, $c, 1);
            $c++;
        }
        return base64_encode($m);
    }

    function decrypt($encrypted_data, $key = 'Ak3los-m3D1a')
    {
        $t = base64_decode($encrypted_data);
        $k = md5($key);
        $c = 0;
        $m = '';
        for ($i = 0 ; $i < strlen($t) ; $i++) {
            if ($c == strlen($k)) $c = 0;
            $m.= substr($t, $i, 1) ^substr($k, $c, 1);
            $c++;
        }
        $t = $m;
        $m = '';
        for ($i = 0 ; $i < strlen($t) ; $i++) {
            $d = substr($t, $i, 1);
            $i++;
            $m.= (substr($t, $i, 1) ^$d);
        }
        return $m;
    }


    function blowfishEncrypt($data, $key = 'Ak3los-m3D1a')
    {
        $key = substr($key,0,56);
        require_once(AK_CONTRIB_DIR.DS.'pear'.DS.'Crypt'.DS.'Blowfish.php');
        $Blowfish =& Ak::singleton('Crypt_Blowfish', $key);
        $Blowfish->setKey($key);
        return $Blowfish->encrypt(base64_encode($data));
    }

    function blowfishDecrypt($encrypted_data, $key = 'Ak3los-m3D1a')
    {
        $key = substr($key,0,56);
        require_once(AK_CONTRIB_DIR.DS.'pear'.DS.'Crypt'.DS.'Blowfish.php');
        $Blowfish =& Ak::singleton('Crypt_Blowfish', $key);
        $Blowfish->setKey($key);
        return base64_decode($Blowfish->decrypt($encrypted_data));
    }


    function randomString($max_length = 8)
    {
        $randomString = '';
        srand((double)microtime()*1000000);
        for($i=0;$i<$max_length;$i++){
            $randnumber = rand(48,120);
            while (($randnumber >= 58 && $randnumber <= 64) || ($randnumber >= 91 && $randnumber <= 96)){
                $randnumber = rand(48,120);
            }
            $randomString .= chr($randnumber);
        }
        return $randomString;
    }

    function compress($data, $format = 'gzip')
    {
        $key = Ak::randomString(15);
        $compressed_file = AK_CACHE_DIR.DS.'tmp'.DS.'d'.$key;
        $uncompressed_file = AK_CACHE_DIR.DS.'tmp'.DS.'s'.$key;

        if(@Ak::file_put_contents($uncompressed_file, $data)){
            $compressed = gzopen($compressed_file,'w9');
            $uncompressed = fopen($uncompressed_file, 'rb');
            while(!feof($uncompressed)){
                $string = fread($uncompressed, 1024*512);
                gzwrite($compressed, $string, strlen($string));
            }
            fclose($uncompressed);
            gzclose($compressed);
        }else{
            trigger_error(Ak::t('Could not write to temporary directory for generating compressed file using Ak::compress(). Please provide write access to %dirname', array('%dirname'=>AK_CACHE_DIR)), E_USER_ERROR);
        }
        $result = Ak::file_get_contents($compressed_file);
        return $result;
    }

    function uncompress($compressed_data, $format = 'gzip')
    {
        $key = Ak::randomString(15);
        $compressed_file = AK_CACHE_DIR.DS.'tmp'.DS.'s'.$key;
        $uncompressed_file = AK_CACHE_DIR.DS.'tmp'.DS.'d'.$key;

        if(@Ak::file_put_contents($compressed_file, $compressed_data)){
            $compressed = gzopen($compressed_file, "r");
            $uncompressed = fopen($uncompressed_file, "w");
            while(!gzeof($compressed)){
                $string = gzread($compressed, 4096);
                fwrite($uncompressed, $string, strlen($string));
            }
            gzclose($compressed);
            fclose($uncompressed);
        }else{
            trigger_error(Ak::t('Could not write to temporary directory for generating uncompressing file using Ak::uncompress(). Please provide write access to %dirname', array('%dirname'=>AK_CACHE_DIR)), E_USER_ERROR);
        }
        $result = Ak::file_get_contents($uncompressed_file);
        return $result;
    }


    function unzip($file_to_unzip, $destination_folder)
    {
        require_once(AK_LIB_DIR.DS.'AkZip.php');
        $ArchiveZip =& new AkZip($file_to_unzip);
        $ArchiveZip->extract(array('add_path'=>str_replace(DS,'/',$destination_folder)));
    }


    function decompress($compressed_data, $format = 'gzip')
    {
        return Ak::uncompress($compressed_data, $format);
    }


    function handleStaticCall()
    {
        if (AK_PHP5) {
            trigger_error(Ak::t('Static calls emulation is not supported by PHP5 < 5.4'));
            die();
        }
        $static_call = array_slice(debug_backtrace(),1,1);
        return  call_user_func_array(array(new $static_call[0]['class'](),$static_call[0]['function']),$static_call[0]['args']);
    }


    /**
     * Gets an array or a comma separated list of models. Then it includes its 
     * respective files and returns an array of available models.
     *
     * @return unknown
     */
    function import()
    {
        $args = func_get_args();
        $args = is_array($args[0]) ? $args[0] : (func_num_args() > 1 ? $args : Ak::stringToArray($args[0]));
        $models = array();
        foreach ($args as $arg){
            $model_name = AkInflector::camelize($arg);
            $model = AkInflector::toModelFilename($model_name);
            if(file_exists($model)){
                $models[] = $model_name;
                include_once($model);
            }elseif (class_exists($model_name)){
                $models[] = $model_name;
            }
        }

        return $models;
    }

    function uses()
    {
        $args = func_get_args();
        return call_user_func_array(array('Ak','import'),$args);
    }

    function stringToArray($string)
    {
        $args = $string;
        if(count($args) == 1 && !is_array($args)){
        (array)$args = array_unique(array_map('trim',array_diff(explode(',',strtr($args.',',';|-',',,,')),array(''))));
        }
        return $args;
    }


    function toArray()
    {
        $args = func_get_args();
        return is_array($args[0]) ? $args[0] : (func_num_args() === 1 ? Ak::stringToArray($args[0]) : $args);
    }


    /**
     * Includes PHP functions that are not available on current PHP version
     */
    function compat($function_name)
    {
        ak_compat($function_name);
    }


    /**
    * The Akelos Framework has an standardized way to convert between formats.
    * You can find available converters on AkConverters
    * 
    * Usage Example: In order to convert from HTML to RTF you just need to call.
    * $rtf = Ak::convert('html','rtf', $my_html_file, array('font_size'=> 24));
    * 
    * Where the last option is an array of options for selected converter.
    * 
    * Previous example is the same as.
    * 
    * $rtf = Ak::convert(array('from'=>'html','to'=>'rtf', 'source' => $my_html_file, 'font_size'=> 24));
    * 
    * In order to create converters, you just need to name them "SourceFormatName + To + DestinationFormatName".
    * Whenever you need to call the, you need to specify the "path" option where your converter is located.
    * The only thing you converter must implement is a convert function. Passes options will be made available
    * as attributes on the converter.
    * If your converter needs to prepare something before the convert method is called, you just need to implement
    * a "init" method. You can avoid this by inspecting passed attributes to your constructor
    */
    function convert()
    {
        $args = func_get_args();
        $number_of_arguments = func_num_args();
        if($number_of_arguments > 1){
            $options = array();
            if($number_of_arguments > 3 && is_array($args[$number_of_arguments-1])){
                $options = array_pop($args);
            }
            $options['from'] = $args[0];
            $options['to'] = $args[1];
            $options['source'] = $args[2];
        }else{
            $options = $args;
        }

        $options['class_prefix'] = empty($options['class_prefix']) && empty($options['path']) ? 'Ak' : $options['class_prefix'];
        $options['path'] = rtrim(empty($options['path']) ? AK_LIB_DIR.DS.'AkConverters' : $options['path'], DS."\t ");

        $converter_class_name = $options['class_prefix'].AkInflector::camelize($options['from']).'To'.AkInflector::camelize($options['to']);
        if(!class_exists($converter_class_name)){
            $file_name = $options['path'].DS.$converter_class_name.'.php';
            if(!file_exists($file_name)){
                trigger_error(Ak::t('Could not locate %from to %to converter on %file_name',array('%from'=>$options['from'],'%to'=>$options['to'],'%file_name'=>$file_name)),E_USER_NOTICE);
                return false;
            }
            require_once($file_name);
        }
        if(!class_exists($converter_class_name)){
            trigger_error(Ak::t('Could not load %converter_class_name converter class',array('%converter_class_name'=>$converter_class_name)),E_USER_NOTICE);
            return false;
        }

        $converter = new $converter_class_name($options);
        foreach ($options as $option=>$value){
            $option[0] != '_' ? $converter->$option = $value : null;
        }

        if(method_exists($converter, 'init')){
            $converter->init();
        }
        return $converter->convert();
    }


    /**
     * Converts given string to UTF-8
     *
     * @param string $text 
     * @param string $input_string_encoding
     * @return string UTF-8 encoded string
     */
    function utf8($text, $input_string_encoding = null)
    {
        $input_string_encoding = empty($input_string_encoding) ? Ak::encoding() : $input_string_encoding;
        require_once(AK_LIB_DIR.DS.'AkCharset.php');
        $Charset =& Ak::singleton('AkCharset',$text);
        return $Charset->RecodeString($text,'UTF-8',$input_string_encoding);
    }

    function recode($text, $output_string_encoding = null, $input_string_encoding = null)
    {
        $input_string_encoding = empty($input_string_encoding) ? Ak::encoding() : $input_string_encoding;
        require_once(AK_LIB_DIR.DS.'AkCharset.php');
        $Charset =& Ak::singleton('AkCharset',$text);
        return $Charset->RecodeString($text,$output_string_encoding,$input_string_encoding);
    }

    function encoding()
    {
        static $encoding;
        if(empty($encoding)){
            // This will force system language settings
            Ak::t('Akelos');
            $encoding = Ak::locale('charset', Ak::lang());
            $encoding = empty($encoding) ? 'UTF-8' : $encoding;
        }
        return $encoding;
    }

    /**
     * Get the encoding in which current user is sending the request
     */
    function userEncoding()
    {
        static $encoding;

        if(!isset($encoding)){
            $encoding = Ak::encoding();
            if(!empty($_SERVER['HTTP_ACCEPT_CHARSET'])){
                $accepted_charsets = array_map('strtoupper', array_diff(explode(';',str_replace(',',';',$_SERVER['HTTP_ACCEPT_CHARSET']).';'), array('')));
                if(!in_array($encoding,$accepted_charsets)){
                    $encoding = array_shift($accepted_charsets);
                }
            }
        }
        return $encoding;
    }

    /**
     * strlen for UTF-8 strings
     * Taken from anpaza at mail dot ru post at http://php.net/strlen
     */
    function strlen_utf8($str)
    {
        $i = $count = 0;
        $len = strlen ($str);
        while ($i < $len){
            $chr = ord ($str[$i]);
            $count++;
            $i++;
            if ($i >= $len){
                break;
            }
            if ($chr & 0x80){
                $chr <<= 1;
                while ($chr & 0x80){
                    $i++;
                    $chr <<= 1;
                }
            }
        }
        return $count;
    }

    /**
     * Convert an arbitrary PHP value into a JSON representation string.
     * 
     * For AJAX driven pages, JSON can come in handy – you can return send JavaScript objects 
     * directly from your actions.
     */
    function toJson($php_value)
    {
        require_once(AK_VENDOR_DIR.DS.'pear'.DS.'Services'.DS.'JSON.php');
        $use = 0;
        $json =& Ak::singleton('Services_JSON', $use);
        return $json->encode($php_value);
    }

    /**
     * Converts a JSON representation string into a PHP value.
     */    
    function fromJson($json_string)
    {
        require_once(AK_VENDOR_DIR.DS.'pear'.DS.'Services'.DS.'JSON.php');
        $use = 0;
        $json =& Ak::singleton('Services_JSON', $use);
        return $json->decode($json_string);
    }

    function &memory_cache($key, &$value)
    {
        static $memory, $md5;
        if($value === false){
            // remove the object from cache
            $memory[$key] = null;
            $md5[$key] = null;
        }elseif($value === true){
            //check if the object is on cache or unaltered
            $result = !empty($memory[$key]) ? $md5[$key] == Ak::getStatusKey($memory[$key]) : false;
            return $result;
        }elseif ($value === null){
            //get the object
            return $memory[$key];
        }else{
            //set the object
            $md5[$key] = Ak::getStatusKey($value);
            $memory[$key] =& $value;
        }

        return $value;
    }

    function getStatusKey($element)
    {
        if(AK_PHP5){
            $element = clone($element);
        }
        if(isset($element->___status_key)){
            unset($element->___status_key);
        }
        return md5(serialize($element));
    }

    function logObjectForModifications(&$object)
    {
        $object->___status_key = empty($object->___status_key) ? Ak::getStatusKey($object) : $object->___status_key;
        return $object->___status_key;
    }

    function resetObjectModificationsWacther(&$object)
    {
        unset($object->___status_key);
    }

    function objectHasBeenModified(&$object)
    {
        if(isset($object->___status_key)){
            $old_status = $object->___status_key;
            $new_key = Ak::getStatusKey($object);
            return $old_status != $new_key;
        }else{
            Ak::logObjectForModifications($object);
            return false;
        }
        return true;
    }

    function &call_user_func_array($function_name, $parameters)
    {
        if(AK_PHP5){
            $result = call_user_func_array($function_name, $parameters);
            return $result;
        }
        $user_function_name = is_string($function_name) ? $function_name : (is_object($function_name[0]) ? '$function_name[0]->'.$function_name[1] : $function_name[0].'::'.$function_name[1]);
        $arguments = array();
        $argument_keys = array_keys($parameters);
        foreach($argument_keys as $k){
            $arguments[] = '$parameters['.$argument_keys[$k].']';
        }
        eval('$_result =& '.$user_function_name.'('.implode($arguments, ', ').');');
        // Dirty hack for avoiding pass by reference warnings.
        $result =& $_result;
        return $result;
    }


    function &array_sort_by($array,  $key = null, $direction = 'asc')
    {
        $array_copy = $sorted_array = array();
        foreach (array_keys($array) as $k) {
            $array_copy[$k] =& $array[$k][$key];
        }

        natcasesort($array_copy);
        if(strtolower($direction) == 'desc'){
            $array_copy = array_reverse(&$array_copy, true);
        }

        foreach (array_keys($array_copy) as $k){
            $sorted_array[$k] =& $array[$k];
        }

        return $sorted_array;
    }


    function mime_content_type($file)
    {
        static $mime_types;
        ak_compat('mime_content_type');

        $mime = mime_content_type($file);

        if(empty($mime)){
            empty($mime_types) ? require(AK_LIB_DIR.DS.'utils'.DS.'mime_types.php') : null;
            $file_name = substr($file,strrpos($file,'/')+1);
            $file_extension = substr($file_name,strrpos($file_name,'.')+1);
            $mime = !empty($mime_types[$file_extension]) ? $mime_types[$file_extension] : false;
        }

        return $mime;
    }

    function stream($path, $buffer_size = 4096)
    {
        ob_implicit_flush();
        $len = empty($buffer_size) ? 4096 : $buffer_size;
        $fp = fopen($path, "rb");
        while (!feof($fp)) {
            echo fread($fp, $len);
        }
    }

    function _nextPermutation($p, $size)
    {
        for ($i = $size - 1; isset($p[$i]) && isset($p[$i+1]) && $p[$i] >= $p[$i+1]; --$i) { }
        if ($i == -1) { return false; }
        for ($j = $size; $p[$j] <= $p[$i]; --$j) { }
        $tmp = $p[$i]; $p[$i] = $p[$j]; $p[$j] = $tmp;
        for (++$i, $j = $size; $i < $j; ++$i, --$j) {
            $tmp = $p[$i]; $p[$i] = $p[$j]; $p[$j] = $tmp;
        }
        return $p;
    }

    /**
     * Returns all the possible permutations of given array
     */
    function permute($array, $join_with = false)
    {
        $size = count($array) - 1;
        $perm = range(0, $size);
        $j = 0;
        do {
            foreach ($perm as $i) {
                $perms[$j][] = $array[$i];
            }
        } while ($perm = Ak::_nextPermutation($perm, $size) AND ++$j);

        if($join_with){
            foreach ($perms as $perm){
                $joined_perm[] = join(' ',$perm);
            }
            return $joined_perm;
        }
        return $perms;
    }

    /**
     * Generates a Universally Unique IDentifier, version 4.
     *
     * RFC 4122 (http://www.ietf.org/rfc/rfc4122.txt) defines a special type of Globally
     * Unique IDentifiers (GUID), as well as several methods for producing them. One
     * such method, described in section 4.4, is based on truly random or pseudo-random
     * number generators, and is therefore implementable in a language like PHP.
     *
     * We choose to produce pseudo-random numbers with the Mersenne Twister, and to always
     * limit single generated numbers to 16 bits (ie. the decimal value 65535). That is
     * because, even on 32-bit systems, PHP's RAND_MAX will often be the maximum *signed*
     * value, with only the equivalent of 31 significant bits. Producing two 16-bit random
     * numbers to make up a 32-bit one is less efficient, but guarantees that all 32 bits
     * are random.
     *
     * The algorithm for version 4 UUIDs (ie. those based on random number generators)
     * states that all 128 bits separated into the various fields (32 bits, 16 bits, 16 bits,
     * 8 bits and 8 bits, 48 bits) should be random, except : (a) the version number should
     * be the last 4 bits in the 3rd field, and (b) bits 6 and 7 of the 4th field should
     * be 01. We try to conform to that definition as efficiently as possible, generating
     * smaller values where possible, and minimizing the number of base conversions.
     *
     * @copyright  Copyright (c) CFD Labs, 2006. This function may be used freely for
     *              any purpose ; it is distributed without any form of warranty whatsoever.
     * @author      David Holmes <dholmes@cfdsoftware.net>
     *
     * @return  string  A UUID, made up of 32 hex digits and 4 hyphens.
     */
    function uuid()
    {

        // The field names refer to RFC 4122 section 4.1.2
        return sprintf('%04x%04x-%04x-%03x4-%04x-%04x%04x%04x',
        mt_rand(0, 65535), mt_rand(0, 65535), // 32 bits for "time_low"
        mt_rand(0, 65535), // 16 bits for "time_mid"
        mt_rand(0, 4095),  // 12 bits before the 0100 of (version) 4 for "time_hi_and_version"
        bindec(substr_replace(sprintf('%016b', mt_rand(0, 65535)), '01', 6, 2)),
        // 8 bits, the last two of which (positions 6 and 7) are 01, for "clk_seq_hi_res"
        // (hence, the 2nd hex digit after the 3rd hyphen can only be 1, 5, 9 or d)
        // 8 bits for "clk_seq_low"
        mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535) // 48 bits for "node"
        );
    }


    function test($test_case_name, $use_sessions = false)
    {
        ak_test($test_case_name, $use_sessions);
    }

    /**
     * Use this function for securing includes. This way you can prevent file inclusion attacks
     */
    function sanitize_include($include, $mode = 'normal')
    {
        $rules = array(
        'paranoid' => '/([^A-Z^a-z^0-9^_^-^ ]+)/',
        'normal' => '/([^A-Z^a-z^0-9^_^-^ ^\.^\/^\\\]+)/'
        );
        $mode = array_key_exists($mode,$rules) ? $mode : 'normal';
        return preg_replace($rules[$mode],'',$include);
    }

    /**
     * Returns a PHP Object from an API resource
     * 
     */
    function client_api($resource, $options = array())
    {
        $default_options = array(
        'protocol' => 'xml_rpc',
        'build' => true
        );
        $options = array_merge($default_options, $options);

        require(AK_LIB_DIR.DS.'AkActionWebService'.DS.'AkActionWebServiceClient.php');
        $Client =& new AkActionWebServiceClient($options['protocol']);
        $Client->init($resource, $options);
        return $Client;
    }


    /**
     * Cross PHP version replacement for html_entity_decode. Emulates PHP5 behaviour on PHP4 on UTF-8 entities
     */
    function html_entity_decode($html, $translation_table_or_quote_style = null)
    {
        if(AK_PHP5){
            return html_entity_decode($html,empty($translation_table_or_quote_style) ? ENT_QUOTES : $translation_table_or_quote_style,'UTF-8');
        }
        require_once(AK_LIB_DIR.DS.'AkCharset.php');
        $html = preg_replace('~&#x([0-9a-f]+);~ei', 'AkCharset::_CharToUtf8(hexdec("\\1"))', $html);
        $html = preg_replace('~&#([0-9]+);~e', 'AkCharset::_CharToUtf8("\\1")', $html);
        if(empty($translation_table_or_quote_style)){
            $translation_table_or_quote_style = get_html_translation_table(HTML_ENTITIES);
            $translation_table_or_quote_style = array_flip($translation_table_or_quote_style);
        }
        return strtr($html, $translation_table_or_quote_style);
    }
}


// Now some static functions that are needed by the whole framework

function translate($string, $args = null, $controller = null)
{
    return Ak::t($string, $args, $controller);
}


function ak_test($test_case_name, $use_sessions = false)
{
    if(!defined('ALL_TESTS_CALL')){
        $use_sessions ? @session_start() : null;
        $test = &new $test_case_name();
        if (defined('AK_CLI') && AK_CLI || TextReporter::inCli() || (defined('AK_CONSOLE_MODE') && AK_CONSOLE_MODE) || (defined('AK_WEB_REQUEST') && !AK_WEB_REQUEST)) {
            $test->run(new TextReporter());
        }else{
            $test->run(new HtmlReporter());
        }
    }
}

function ak_compat($function_name)
{
	if(!function_exists($function_name)){
        require_once(AK_VENDOR_DIR.DS.'pear'.DS.'PHP'.DS.'Compat'.DS.'Function'.DS.$function_name.'.php');
    }
}

function ak_generate_mock($name)
{
	static $Mock;
	if(empty($Mock)){
		$Mock = new Mock();
	}
	$Mock->generate($name);
}


/**
 * PHP4 triggers "Only variable references should be returned by reference" error when 
 * a method that should return an object reference returns a boolean/array
 * 
 * The old method was to use a global variables, but it can lead into hard to debug bugs.
 * 
 * Now you'll need to use the following technique if you whant to build functions that
 * can return Object references or TRUE/FALSE.
 * 
 *  $result = false;
 *  return $result;
 */

/**
 * Globals are deprecated. Used ak_false, ak_true and ak_array instead
 *
 * @deprecated
 */
$GLOBALS['false'] = false;
$GLOBALS['true'] = true;


AK_PHP5 ? null : eval('function clone($object){return $object;}');

Ak::profile('Ak.php class included'.__FILE__);

?>
