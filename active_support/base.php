<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

/**
* Akelos Framework static functions
*
* Ak contains all the Akelos Framework static functions. This
* class acts like a name space to avoid naming collisions
* when PHP gets new functions into its core. And also to provide
* additional functionality to existing PHP functions mantaining the same interface
*/
class Ak
{

    /**
    * Gets an instance of AkDbAdapter
    *
    * Whenever a database connection is required you can get a
    * reference to the default database connection by doing:
    *
    * $db = Ak::db(); // get an adodb instance
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
    static function &db($dsn = null) {
        return AkDbAdapter::getInstance($dsn);
    }

    /**
     * @param string $message
     * @param [OPTIONAL] $fatal triggers even in production-mode
     */
    static function deprecateWarning($message, $fatal=false) {
        if (!$fatal && AK_ENVIRONMENT == 'production'){
            return;
        }
        $backtrace = debug_backtrace();
        $file = @$backtrace[1]['file'];
        $line = @$backtrace[1]['line'];
        if (is_array($message)){
            trigger_error(Ak::t("DEPRECATED WARNING: ".array_shift($message), $message).' '.Ak::t('Called in %file line %line', array('%file' => $file, '%line' => $line)), E_USER_NOTICE);
        } else {
            trigger_error(Ak::t("DEPRECATED WARNING: ".$message).' '.Ak::t('Called in %file line %line', array('%file' => $file, '%line' => $line)), E_USER_NOTICE);
        }
    }

    static function deprecateMethod($method, $new_method) {
        Ak::deprecateWarning("Method $method is deprecated and will be removed on future versions. Please use $new_method instead.");
    }

    /**
    * Gets a cache object singleton instance
    */
    static function &cache() {
        static $cache;
        if(!isset($cache)){
            $cache = new AkCache();
        }
        return $cache;
    }


    /**
     * Gets a route to an URL from the rules defined at config/routes.php
     */
    static function toUrl($options, $set_routes = false) {
        static $Router;
        if(empty($Router)){
            if($set_routes){
                $Router = $options;
                return;
            }else{
                $Router = AkRouter::getInstance();
            }
        } else if (($options instanceof AkRouter) && $set_routes) {
            $Router = $options;
            return;
        }
        return $Router->toUrl($options);
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
    static function t($string, $args = null, $controller = null) {
        static $framework_dictionary = array(), $lang, $_dev_shutdown = true, $locale_manager_class = false, $_custom_dev_shutdown = false;
        $original_string = $string;
        if(AK_AUTOMATICALLY_UPDATE_LANGUAGE_FILES && ($locale_manager_class == false || $locale_manager_class == 'AkLocaleManager')) {
            if(!$_custom_dev_shutdown && defined('AK_LOCALE_MANAGER') && class_exists(AK_LOCALE_MANAGER) && in_array('AkLocaleManager',class_parents(AK_LOCALE_MANAGER))) {
                $locale_manager_class = AK_LOCALE_MANAGER;
                $_custom_dev_shutdown = true;
                register_shutdown_function(array($locale_manager_class,'updateLocaleFiles'));
            } else {
                $locale_manager_class = 'AkLocaleManager';
            }

        } else {
            $locale_manager_class = 'AkLocaleManager';
        }
        if((AK_AUTOMATICALLY_UPDATE_LANGUAGE_FILES || (defined('AK_TEST_TRANSLATIONS') && AK_TEST_TRANSLATIONS)) && !empty($string) && is_string($string)){

            // This adds used strings to a stack for storing new entries on the locale file after shutdown
            call_user_func_array(array($locale_manager_class,'getUsedLanguageEntries'),array($string,$controller));
            if($_dev_shutdown && (!defined('AK_TEST_TRANSLATIONS') || !AK_TEST_TRANSLATIONS)){
                register_shutdown_function(array($locale_manager_class,'updateLocaleFiles'));
                $_dev_shutdown = false;
            }
        }

        if(!isset($lang)){
            if(!empty($_SESSION['lang'])){
                $lang =  $_SESSION['lang'];
            }else{
                $lang = Ak::lang();
            }

            $dictionary=call_user_func_array(array($locale_manager_class,'getCoreDictionary'),array($lang));
            $framework_dictionary = array_merge((array)$framework_dictionary,(array)$dictionary);

            if(!defined('AK_LOCALE')){
                define('AK_LOCALE', $lang);
            }
            if(!empty($locale) && is_array($locale)){
                Ak::locale(null, $lang, $locale);
            }
        }

        if(!empty($string) && is_array($string)){
            if(!empty($string[$lang])){
                if(defined('AK_TRANSLATION_DEBUG') && AK_TRANSLATION_DEBUG){
                    return 'namespace: "'.$controller.'", original: "'.$original_string.'": '.$string[$lang];
                } else {
                    return $string[$lang];
                }
            }
            $try_whith_lang = $args !== false && empty($string[$lang]) ? Ak::base_lang() : $lang;
            if(empty($string[$try_whith_lang]) && $args !== false){
                foreach (Ak::langs() as $try_whith_lang){
                    if(!empty($string[$try_whith_lang])){
                        if(defined('AK_TRANSLATION_DEBUG') && AK_TRANSLATION_DEBUG){
                            return 'namespace: "'.$controller.'", original: "'.$original_string.'": '.$string[$try_whith_lang];
                        } else {
                            return $string[$try_whith_lang];
                        }
                    }
                }
            }
            if(defined('AK_TRANSLATION_DEBUG') && AK_TRANSLATION_DEBUG){
                return 'namespace: "'.$controller.'", original: "'.$original_string.'": '.@$string[$try_whith_lang];
            } else {
                return @$string[$try_whith_lang];
            }
        }

        if(isset($controller) && !isset($framework_dictionary[$controller.'_dictionary'])) { // && is_file(AkConfig::getDir('app').DS.'locales'.DS.$controller.DS.$lang.'.php')){
            $framework_dictionary[$controller.'_dictionary'] = call_user_func_array(array($locale_manager_class,'getDictionary'),array($lang,$controller));
        }

        if(isset($controller) && isset($framework_dictionary[$controller.'_dictionary'][$string])){
            $string = !empty($framework_dictionary[$controller.'_dictionary'][$string])?$framework_dictionary[$controller.'_dictionary'][$string]:$string;
        }else {
            $string = !empty($framework_dictionary[$string]) ? $framework_dictionary[$string] : $string;
        }

        if(isset($args) && is_array($args)){
            $string = @str_replace(array_keys($args), array_values($args),$string);
        }
        /**
        * @todo Prepare for multiple locales by inspecting AK_DEFAULT_LOCALE
        */
        if(defined('AK_TRANSLATION_DEBUG') && AK_TRANSLATION_DEBUG){
            return 'namespace: "'.$controller.'", original: "'.$original_string.'": '.$string;
        } else {
            return $string;
        }
    }

    /**
    * Translate strings from a language to another language.
    *
    * @access public
    * @static
    * @param    string    $string    The string to be translated.
    * @param    string/array    $target_language    A string containing the
    *           target language or an array containing 0 => from, 1 => to.
    * @return string The untranslated string.
    */
    static function translate($string, $target_language, $namespace = false) {
        $from = is_array($target_language) ? $target_language[0] : 'en' ;
        $to = is_array($target_language) ? $target_language[1] : $target_language ;

        if($from != 'en'){
            $string = Ak::untranslate($string, $from, $namespace);
        }

        $dictionary = AkLocaleManager::getDictionary($to, $namespace);
        return !empty($dictionary[$string]) ? $dictionary[$string] : $string;
    }

    /**
    * Untranslate strings from a locale to english.
    *
    * @access public
    * @static
    * @param    string    $string    The string to be untranslated.
    * @param    string    $current_language    A string containing the current language.
    * @return string The untranslated string.
    */
    static function untranslate($string, $current_language, $namespace = false) {
        $dictionary = AkLocaleManager::getDictionary($current_language, $namespace);
        $untranslated_string = array_search($string, $dictionary);
        return $untranslated_string ? $untranslated_string : $string;
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
    static function locale($locale_setting, $locale = null) {
        static $settings;

        // We initiate the locale settings
        Ak::t('Akelos');

        $locale = empty($locale) ? (defined('AK_LOCALE') ? AK_LOCALE : (Ak::t('Akelos') && Ak::locale($locale_setting))) : $locale;

        if (empty($settings[$locale])) {
            if(func_num_args() != 3){ // First time we ask for something using this locale so we will load locale details
                $requested_locale = $locale;
                if(@include(AK_CONFIG_DIR.DS.'locales'.DS.Ak::sanitize_include($requested_locale,'high').'.php')){
                    $locale = !empty($locale) && is_array($locale) ? $locale : array();
                    Ak::locale(null, $requested_locale, $locale);
                    return Ak::locale($locale_setting, $requested_locale);
                }
            }else{
                $settings[$locale] = func_get_arg(2);
                if(isset($settings[$locale]['charset'])){
                    defined('AK_CHARSET') ? null : (define('AK_CHARSET',$settings[$locale]['charset']) && @ini_set('default_charset', AK_CHARSET));
                }
            }
        }

        return isset($settings[$locale][$locale_setting]) ? $settings[$locale][$locale_setting] : false;
    }


    static function lang($set_language = null) {
        static $lang;
        $lang = empty($set_language) ? (empty($lang) ? AK_FRAMEWORK_LANGUAGE : $lang) : $set_language;
        return $lang;
    }


    static function get_url_locale($set_locale = null) {
        static $locale;
        if(!empty($locale)){
            return $locale;
        }
        $locale = empty($set_locale) ? '' : $set_locale;
        return $locale;
    }



    static function langs() {
        static $langs;
        if(!empty($langs)){
            return $langs;
        }
        $lang = Ak::lang();
        if(defined('AK_APP_LOCALES')){
            $langs = array_diff(explode(',',AK_APP_LOCALES.','),array(''));
        }
        $langs = empty($langs) ? array($lang) : $langs;
        return $langs;
    }

    static function base_lang() {
        return array_shift(Ak::langs());
    }



    static function dir($path, $options = array()) {
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
                    if ($file != "." && $file != ".." && $file != '.svn' && $file != '.git'){
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


    static function file_put_contents($file_name, $content, $options = array()) {

        $default_options = array(
        'ftp' => defined('AK_UPLOAD_FILES_USING_FTP') && AK_UPLOAD_FILES_USING_FTP,
        'base_path' => strstr($file_name, AK_TMP_DIR) ?  AK_TMP_DIR : AK_BASE_DIR,
        );
        $options = array_merge($default_options, $options);

        $file_name = trim(str_replace($options['base_path'], '',$file_name),DS);

        if($options['ftp']){
            $file_name = trim(str_replace(array(DS,'//'),array('/','/'),$file_name),'/');
            if(!AkFtp::is_dir(dirname($file_name))){
                AkFtp::make_dir(dirname($file_name));
            }

            return AkFtp::put_contents($file_name, $content);
        }else{
            $base_path = (AK_WIN&&empty($options['base_path'])?'':$options['base_path'].DS);
            if(!is_dir(dirname($base_path.$file_name))){
                Ak::make_dir(dirname($base_path.$file_name), $options);
            }

            if(!$result = file_put_contents($base_path.$file_name, $content)){
                if(!empty($content)){
                    trigger_error(Ak::t("Could not write to file: %file_name. Please change file/dir permissions or enable FTP file handling on your Akelos application.", array('%file_name' => '"'.$base_path.$file_name.'"')),  E_USER_ERROR);
                }
            }
            return $result;
        }
    }


    static function file_get_contents($file_name, $options = array()) {
        $default_options = array(
        'ftp' => defined('AK_READ_FILES_USING_FTP') && AK_READ_FILES_USING_FTP,
        'base_path' => strstr($file_name, AK_TMP_DIR) ?  AK_TMP_DIR : AK_BASE_DIR,
        );
        $options = array_merge($default_options, $options);

        $file_name = trim(str_replace($options['base_path'], '',$file_name),DS);

        if($options['ftp']){
            $file_name = trim(str_replace(array(DS,'//'),array('/','/'),$file_name),'/');
            return AkFtp::get_contents($file_name);
        }else{
            $base_path = (AK_WIN && empty($options['base_path']) ? '' : $options['base_path'] . DS);
            return file_get_contents($base_path.$file_name);
        }
    }

    /**
     * @todo Optimize this code (dirty add-on to log command line interpreter results)
     */
    static function file_add_contents($file_name, $content, $options = array()) {
        $original_content = @Ak::file_get_contents($file_name, $options);
        return Ak::file_put_contents($file_name, $original_content.$content, $options);
    }

    static function file_delete($file_name, $options = array()) {
        $default_options = array(
        'ftp' => defined('AK_DELETE_FILES_USING_FTP') && AK_DELETE_FILES_USING_FTP,
        'base_path' => strstr($file_name, AK_TMP_DIR) ?  AK_TMP_DIR : AK_BASE_DIR,
        );
        $options = array_merge($default_options, $options);

        $file_name = trim(str_replace($options['base_path'], '',$file_name),DS);
        $base_path = (AK_WIN&&empty($options['base_path'])?'':$options['base_path'].DS);
        if($options['ftp']){
            $file_name = trim(str_replace(array(DS,'//'),array('/','/'),$file_name),'/');
            return AkFtp::delete($file_name, true);
        }elseif (file_exists($base_path.$file_name)){
            return unlink($base_path.$file_name);
        }
        return false;
    }

    static function directory_delete($dir_name, $options = array()) {
        $default_options = array(
        'ftp' => defined('AK_DELETE_FILES_USING_FTP') && AK_DELETE_FILES_USING_FTP,
        'base_path' => strstr($dir_name, AK_TMP_DIR) ?  AK_TMP_DIR : AK_BASE_DIR,
        );
        $options = array_merge($default_options, $options);

        $sucess = true;
        $dir_name = Ak::getRestrictedPath($dir_name, $options);

        if(empty($dir_name)){
            return false;
        }

        if($options['ftp']){
            return AkFtp::delete($dir_name);
        }else{
            $base_path = (AK_WIN&&empty($options['base_path'])?'':$options['base_path'].DS);
            $items = glob($base_path.$dir_name."/*");
            $hidden_items = glob($base_path.$dir_name."/.*");
            $fs_items = $items || $hidden_items ? array_merge((array)$items, (array)$hidden_items) : false;
            if($fs_items){
                $items_to_delete = array('directories'=>array(), 'files'=>array());
                foreach($fs_items as $fs_item) {
                    if($fs_item[strlen($fs_item)-1] != '.'){
                        $items_to_delete[ (is_dir($fs_item) ? 'directories' : 'files') ][] = $fs_item;
                    }
                }
                foreach ($items_to_delete['files'] as $file){
                    Ak::file_delete($file, $options);
                }
                foreach ($items_to_delete['directories'] as $directory){
                    $sucess = $sucess ? Ak::directory_delete($directory, $options) : $sucess;
                }
            }
            return $sucess ? @rmdir($base_path.$dir_name) : $sucess;
        }
    }

    static function make_dir($path, $options = array()) {

        $default_options = array(
        'ftp' => defined('AK_UPLOAD_FILES_USING_FTP') && AK_UPLOAD_FILES_USING_FTP,
        'base_path' => AK_BASE_DIR
        );

        $options = array_merge($default_options, $options);

        if(!is_dir($options['base_path']) && !Ak::make_dir($options['base_path'], array('base_path' => dirname($options['base_path'])))){
            trigger_error(Ak::t('Base path %path must exist in order to use it as base_path in Ak::make_dir()', array('%path' => $options['base_path'])), E_USER_ERROR);
        }

        $path = trim(str_replace($options['base_path'], '',$path),DS);

        if($options['ftp']){
            $path = trim(str_replace(array(DS,'//'),array('/','/'),$path),'/');
            return AkFtp::make_dir($path);
        }else{
            $base_path = (AK_WIN&&empty($options['base_path'])?'':$options['base_path'].DS);
            $path = rtrim($base_path.$path, DS);

            if (!file_exists($path)){
                Ak::make_dir(dirname($path), $options);
                return mkdir($path);
            }else{
                return true;
            }
        }
        return false;
    }

    static function rmdir_tree($directory) {
        $files = glob($directory.'*', GLOB_MARK);
        foreach($files as $file){
            if(substr($file, -1) == DS){
                Ak::rmdir_tree($file);
            } else{
                unlink($file);
            }
        }
        if (is_dir($directory)){
            rmdir($directory);
        }
    }

    /**
    * This static method will copy recursively all the files or directories from one
    * path within an Akelos application to another.
    *
    * It uses current installation settings, so it can perform copies via the filesystem or via FTP
    */
    static function copy($origin, $target, $options = array()) {
        $default_options = array(
        'ftp' => defined('AK_UPLOAD_FILES_USING_FTP') && AK_UPLOAD_FILES_USING_FTP,
        'base_path' => strstr($origin, AK_TMP_DIR) ?  AK_TMP_DIR : AK_BASE_DIR,
        );
        $options = array_merge($default_options, $options);

        $sucess = true;

        $origin = Ak::getRestrictedPath($origin, $options);
        $target = Ak::getRestrictedPath($target, $options);

        if(empty($origin) || empty($target)){
            return false;
        }

        $destination = str_replace($origin, $target, $origin);
        $base_path = (AK_WIN&&empty($options['base_path'])?'':$options['base_path'].DS);
        if(is_file($base_path.$origin)){
            return Ak::file_put_contents($base_path.$destination, Ak::file_get_contents($base_path.$origin, $options), $options);
        }
        Ak::make_dir($base_path.$destination);
        if($fs_items = glob($base_path.$origin."/*")){
            $items_to_copy = array('directories'=>array(), 'files'=>array());
            foreach($fs_items as $fs_item) {
                $items_to_copy[ (is_dir($fs_item) ? 'directories' : 'files') ][] = $fs_item;
            }
            foreach ($items_to_copy['files'] as $file){
                $destination = str_replace($origin, $target, $file);
                $sucess = $sucess ? Ak::file_put_contents($destination, Ak::file_get_contents($file, $options), $options) : $sucess;
            }
            foreach ($items_to_copy['directories'] as $directory){
                $destination = str_replace($origin, $target, $directory);
                $sucess = $sucess ? Ak::copy($directory, $destination, $options) : $sucess;
            }
        }
        return $sucess;
    }

    /**
     * Returns a path restricting it to a base location
     *
     * This is used by Akelos to prevent functions namespaced under Ak
     * from writing out of the Akelos base directory for security reasons.
     */
    static function getRestrictedPath($path, $options = array()) {
        if(!empty($options['skip_path_restriction'])) return $path;
        $default_options = array(
        'ftp' => false,
        'base_path' => strstr($path, AK_TMP_DIR) ?  AK_TMP_DIR : AK_BASE_DIR,
        );
        $options = array_merge($default_options, $options);

        $path = str_replace('..','', rtrim($path,'\\/. '));
        $path = trim(str_replace($options['base_path'], '',$path),DS);

        if($options['ftp']){
            $path = trim(str_replace(array(DS,'//'),array('/','/'), $path),'/');
        }

        return $path;
    }


    /**
     * Perform a web request
     *
     * @param string $url URL we are going to request.
     * @param array $options Options for current request.
     *  Options are:
     * * referer: URL that will be set as referer url. Default is current url
     * * params: Parameter for the request. Can be an array of key=>values or a url params string like key=value&key2=value2
     * * method: In case params are given the will be requested using get method by default. Specify post if get is not what you need.
     * @return string
     */
    static function url_get_contents($url, $options = array()) {
        $Client = new AkHttpClient();
        $method = empty($options['method']) ? 'get' : strtolower($options['method']);
        if(empty($method) || !in_array($method, array('get','post','put','delete'))){
            trigger_error(Ak::t('Invalid HTTP method %method', array('%method'=>$options['method'])), E_USER_ERROR);
        }
        //print_r($options);
        return $Client->$method($url, $options);
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
    static function trace($text = null, $line = null, $file = null, $method = null, $escape_html_entities = true) {
        static $counter = 0;
        if(AK_PRODUCTION_MODE){
            return;
        }
        $html_entities_function = $escape_html_entities ? 'htmlentities' : 'trim';
        list($default_file, $default_line, $default_method) = Ak::getLastFileAndLineAndMethod();
        $default_method = is_bool($text) || empty($text)  ? 'var_dump' : $default_method;
        $line = is_null($line) ? $default_line : $line;
        $file = is_null($file) ? $default_file : $file;
        $method = is_null($method) ? $default_method : $method;

        if(AK_CLI){
            $text = Ak::dump($text, 'print_r');
        }elseif (!empty($text) && !is_scalar($text)){
            $rand = Ak::randomString();
            $formatted = '';
            $methods = array('print_r', 'var_dump', 'var_export');
            foreach ($methods as $method){
                $pre_style = 'display:none;';
                if(defined('AK_TRACE_DUMP_METHOD')){
                    if(AK_TRACE_DUMP_METHOD == $method){
                        $pre_style = '';
                    }
                }elseif ($method == 'print_r'){
                    $pre_style = '';
                }
                $element_id = $method.'_'.$rand;
                $formatted .= "<div style='margin:10px;'><a href='javascript:void(0);' onclick='e_$element_id = document.getElementById(\"$element_id\"); e_$element_id.style.display = (e_$element_id.style.display == \"none\"?\"block\":\"none\");' title='Set the constant AK_TRACE_DUMP_METHOD to your favourite default method'>$method</a><br />".
                '<pre style="'.$pre_style.'" id="'.$element_id.'">'.$html_entities_function(Ak::dump($text, $method)).'</pre></div>';
            }
            $text = $formatted;
        }elseif (is_bool($text) || empty($text)){
            $text = '<pre style="margin:10px;">'.$html_entities_function(Ak::dump($text, $default_method)).'</pre>';
        }elseif (is_scalar($text)){
            $text = '<pre style="margin:10px;">'.$html_entities_function($text).'</pre>';
        }

        if(!isset($text)){
            $counter++;
            $text = '';
        }else {
            $text = AK_CLI?'---> '.$text : ($text);
        }

        $include_file_and_line = strlen(trim($file.$line)) > 0;

        if($include_file_and_line){
            echo AK_CLI?"----------------\n$file ($line):\n $text\n----------------\n":"<div style='background-color:#fff;margin:10px;color:#000;font-family:sans-serif;border:3px solid #fc0;font-size:12px;'><div style='background-color:#ffc;padding:10px;color:#000;font-family:sans-serif;'>$file <span style='font-weight:bold'>$line</span></div>".$text."</div>\n";
        }else{
            echo AK_CLI?"----------------\n $text\n----------------\n":"<div style='background-color:#fff;margin:10px;color:#000;font-family:sans-serif;border:1px solid #ccc;font-size:12px;'>".$text."</div>\n";
        }
    }

    /**
     * Returns a string representation of one of these PHP methods var_dump, var_export, or print_r
     */
    static function dump($var, $method = null, $max_length = null) {
        $method = empty($method) ? (defined('AK_TRACE_DUMP_METHOD') ? AK_TRACE_DUMP_METHOD : 'var_dump') : $method;
        $methods = array('var_dump', 'var_export', 'print_r');
        if(!in_array($method, $methods)){
            trigger_error(Ak::t('Invalid dump method, valid options are %methods', array('%methods'=>join(", ", $methods))), E_USER_ERROR);
            return false;
        }
        ob_start();
        if(is_object($var)){
            !method_exists($var, '__toString') ? $method($var) : print($var);
        }else{
            $method($var);
        }

        $contents = ob_get_contents();
        $max_length = defined('AK_DUMP_MAX_LENGTH') ? AK_DUMP_MAX_LENGTH : 10000000;
        $result = $max_length ? substr($contents, 0, $max_length) : $contents;
        if($contents != $result){
            $result .= ' ...dump truncated at max length of '.$max_length.' chars define AK_DUMP_MAX_LENGTH to false or to a larger number';
        }
        ob_end_clean();
        return $result;
    }

    static function getLastFileAndLineAndMethod($only_app = false, $start_level = 1) {
        $backtrace = debug_backtrace();
        if(!$only_app){
            return array(@$backtrace[$start_level]['file'], @$backtrace[$start_level]['line'], @$backtrace[$start_level]['function']);
        }else{
            for($i = $start_level-1; $i <= count($backtrace) - 1; $i++){
                if(isset($backtrace[$i]["line"])){
                    if(strstr($backtrace[$i]["file"], AK_COMPILED_VIEWS_DIR) || strstr($backtrace[$i]["file"], AkConfig::getDir('app'))){
                        return array($backtrace[$i]["file"], $backtrace[$i]["line"], $backtrace[$i]["function"]);
                    }
                }
            }
        }
    }

    static function getFileAndNumberTextForError($levels = 0) {
        list($file,$line,$method) = Ak::getLastFileAndLineAndMethod(false, $levels+1);
        return Ak::t('In %file line %line', array('%file' => $file, '%line' => $line));
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
    static function debug ($data, $_functions=0) {
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
                        } elseif (stristr($type, "function")) {
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
    static function get_object_info($object, $include_inherited_info = false) {
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
    static function get_this_object_methods($object) {
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
    static function get_this_object_attributes($object) {
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



    static function &getLogger($namespace = AK_ENVIRONMENT) {
        static $Logger = array();
        if(empty($Logger[$namespace])){
            $logger_options = AkConfig::getOption($namespace.'_logger_options', array());
            $logger_class = AkConfig::getOption('logger', 'AkLogger');
            $logger_class_for_namespace = AkConfig::getOption($namespace.'_logger', $logger_class);
            if(is_string($logger_class_for_namespace)){
                $Logger[$namespace] = new $logger_class_for_namespace(array_merge($logger_options, array('namespace' => $namespace)));
            }else{
                $Logger[$namespace] = $logger_class_for_namespace;
            }
        }
        return $Logger[$namespace];
    }

    static function get_constants() {
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
    static function time() {
        return time()+(defined('AK_TIME_DIFERENCE') ? AK_TIME_DIFERENCE*3600 : 0);
    }

    static function gmt_time() {
        return Ak::time()+(AK_TIME_DIFERENCE_FROM_GMT*3600);
    }


    /**
    * Gets a timestamp for input date provided in one of this formats: "year-month-day hour:min:sec", "year-month-day", "hour:min:sec"
    */
    static function getTimestamp($iso_date_or_hour = null) {
        if(empty($iso_date_or_hour)){
            return Ak::time();
        }
        if (!preg_match("/^
            ([0-9]{4})[-\/\.]? # year
            ([0-9]{1,2})[-\/\.]? # month
            ([0-9]{1,2})[ -]? # day
            (
                ([0-9]{1,2}):? # hour
                ([0-9]{2}):? # minute
                ([0-9\.]{0,4}) # seconds
            )?/x", ($iso_date_or_hour), $rr)){
        if (preg_match("|^(([0-9]{1,2}):?([0-9]{1,2}):?([0-9\.]{1,4}))?|", ($iso_date_or_hour), $rr)){
            return empty($rr[0]) ? Ak::time() : mktime($rr[2],$rr[3],$rr[4]);
        }
            }else{
                if($rr[1]>=2038 || $rr[1]<=1970){
                    require_once(AK_CONTRIB_DIR.DS.'adodb'.DS.'adodb-time.inc.php');
                    return isset($rr[5]) ? adodb_mktime($rr[5],$rr[6],(int)$rr[7],$rr[2],$rr[3],$rr[1]) : adodb_mktime(0,0,0,$rr[2],$rr[3],$rr[1]);
                }else{
                    return isset($rr[5]) ? mktime($rr[5],$rr[6],(int)$rr[7],$rr[2],$rr[3],$rr[1]) : mktime(0,0,0,$rr[2],$rr[3],$rr[1]);
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
    static function getDate($timestamp = null, $format = null) {
        $timestamp = empty($timestamp) ? Ak::time() : $timestamp;
        $use_adodb = $timestamp <= -3600 || $timestamp >= 2147468400;
        if($use_adodb){
            require_once(AK_CONTRIB_DIR.DS.'adodb'.DS.'adodb-time.inc.php');
        }
        if(empty($format)){
            return $use_adodb ? adodb_date('Y-m-d H:i:s', $timestamp) : date('Y-m-d H:i:s', $timestamp);
        }elseif (!empty($format)){
            return $use_adodb ? adodb_date($format, $timestamp) : date($format, $timestamp);
        }
        trigger_error(Ak::t('You must supply a valid UNIX timestamp. You can get the timestamp by calling Ak::getTimestamp("2006-09-27 20:45:57")'));
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
    static function mail ($from, $to, $subject, $body, $additional_headers = array()) {
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

        return $mail_connector->send($recipients, $headers, $body) == true;
    }


    /**
     * Add a profile message that can be displayed after executing the script
     *
     * You can add benchmark markers by calling
     *
     *    Ak::profile('Searching for books');
     *
     * To display the results you need to call
     *
     *     Ak::profile(true);
     *
     * You might also find handy adding this to your application controller.
     *
     *     class ApplicationController extends BaseActionController
     *     {
     *         static function __construct(){
     *             $this->afterFilter('_displayBenchmark');
     *             parent::__construct();
     *         }
     *         static function _displayBenchmark(){
     *             Ak::profile(true);
     *         }
     *     }
     *
     * IMPORTANT NOTE: You must define AK_ENABLE_PROFILER to true for this to work.
    */
    static function profile($message = '') {
        if(AK_ENABLE_PROFILER){
            if(!$ProfileTimer = $Timer = Ak::getStaticVar('ProfileTimer')){
                require_once 'Benchmark/Timer.php';
                $ProfileTimer = new Benchmark_Timer();
                $ProfileTimer->start();
                Ak::setStaticVar('ProfileTimer', $ProfileTimer);
            }elseif($message === true){
                $ProfileTimer->display();
            }else {
                $ProfileTimer->setMarker($message);
            }
        }
    }


    /**
    * Gets the size of given element. Counts arrays, returns numbers, string length or executes size() method on given object
    */
    static function size($element) {
        if(is_array($element)){
            return count($element);
        }elseif (is_numeric($element) && !is_string($element)){
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

    static function select(&$source_array) {
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

    static function collect($source_array, $key_index, $value_index = null) {
        $value_index = empty($value_index) ? $key_index : $value_index;
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

    static function valuesAt($source_array, $keys){
        $values = array();
        $args = array_slice(func_get_args(),1);
        $args = count($args) == 1 ? Ak::toArray($args[0]) : $args;
        foreach ($keys as $k){
            if(isset($source_array[$k])){
                $values[] = $source_array[$k];
            }else{
                $values[] = null;
            }
        }
        return $values;
    }

    static function delete($source_array, $attributes_to_delete_from_array) {
        $resulting_array = (array)$source_array;
        $args = array_slice(func_get_args(),1);
        $args = count($args) == 1 ? Ak::toArray($args[0]) : $args;
        foreach ($args as $arg){
            unset($resulting_array[$arg]);
        }
        return $resulting_array;
    }

    static function deleteAndGetValue(&$source_array, $attributes_to_discard_from_array) {
        $discarded_items = array();
        $args = array_slice(func_get_args(),1);
        $args = count($args) == 1 ? Ak::toArray($args[0]) : $args;
        $multiple = count($args) > 1;
        foreach ($args as $arg){
            if(isset($source_array[$arg])){
                $value = $source_array[$arg];
                unset($source_array[$arg]);
                if(!$multiple){
                    return $value;
                }
                $discarded_items[$arg] = $value;
            }
        }
        return empty($discarded_items) ? ($multiple ? array() : null) : $discarded_items;
    }

    static function &singleton($class_name, &$arguments) {
        static $instances;
        if(!isset($instances[$class_name])) {
            if(is_object($arguments)){
                $instances[$class_name] = new $class_name($arguments);
            }else{
                if(Ak::size($arguments) > 0){
                    eval("\$instances[\$class_name] = new \$class_name(".var_export($arguments, true)."); ");
                }else{
                    $instances[$class_name] = new $class_name();
                }
            }
            $instances[$class_name]->__singleton_id = md5(microtime().rand(1000,9000));
        }
        return $instances[$class_name];
    }


    static function encrypt($data, $key = null) {
        $key = empty($key) ? md5(AK_SESSION_NAME) : $key;
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

    static function decrypt($encrypted_data, $key = null) {
        $key = empty($key) ? md5(AK_SESSION_NAME) : $key;
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


    static function blowfishEncrypt($data, $key = null) {
        $key = empty($key) ? md5(AK_SESSION_NAME) : $key;
        $key = substr($key,0,56);
        require_once(AK_CONTRIB_DIR.DS.'pear'.DS.'Crypt'.DS.'Blowfish.php');
        $Blowfish = Ak::singleton('Crypt_Blowfish', $key);
        $Blowfish->setKey($key);
        return $Blowfish->encrypt(base64_encode($data));
    }

    static function blowfishDecrypt($encrypted_data, $key = null) {
        $key = empty($key) ? md5(AK_SESSION_NAME) : $key;
        $key = substr($key,0,56);
        require_once(AK_CONTRIB_DIR.DS.'pear'.DS.'Crypt'.DS.'Blowfish.php');
        $Blowfish = Ak::singleton('Crypt_Blowfish', $key);
        $Blowfish->setKey($key);
        return base64_decode($Blowfish->decrypt($encrypted_data));
    }


    static function randomString($max_length = 8) {
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


    static function compress($data, $format = 'gzip') {
        $key = Ak::randomString(15);
        $compressed_file = AK_TMP_DIR.DS.'d'.$key;
        $uncompressed_file = AK_TMP_DIR.DS.'s'.$key;
        if(Ak::file_put_contents($uncompressed_file, $data, array('base_path'=>AK_TMP_DIR)) !== false){
            $compressed = gzopen($compressed_file,'w9');
            $uncompressed = fopen($uncompressed_file, 'rb');
            while(!feof($uncompressed)){
                $string = fread($uncompressed, 1024*512);
                gzwrite($compressed, $string, strlen($string));
            }
            fclose($uncompressed);
            gzclose($compressed);
        }else{
            trigger_error(Ak::t('Could not write to temporary directory for generating compressed file using Ak::compress(). Please provide write access to %dirname', array('%dirname'=>AK_TMP_DIR)), E_USER_ERROR);
        }
        $result = Ak::file_get_contents($compressed_file, array('base_path'=>AK_TMP_DIR));
        Ak::file_delete($compressed_file, array('base_path'=>AK_TMP_DIR));
        Ak::file_delete($uncompressed_file, array('base_path'=>AK_TMP_DIR));
        return $result;
    }

    static function uncompress($compressed_data, $format = 'gzip') {
        $key = Ak::randomString(15);
        $compressed_file = AK_TMP_DIR.DS.'s'.$key;
        $uncompressed_file = AK_TMP_DIR.DS.'d'.$key;

        if(Ak::file_put_contents($compressed_file, $compressed_data, array('base_path'=>AK_TMP_DIR)) !== false){
            $compressed = gzopen($compressed_file, "r");
            $uncompressed = fopen($uncompressed_file, "w");
            while(!gzeof($compressed)){
                $string = gzread($compressed, 4096);
                fwrite($uncompressed, $string, strlen($string));
            }
            gzclose($compressed);
            fclose($uncompressed);
        }else{
            trigger_error(Ak::t('Could not write to temporary directory for generating uncompressing file using Ak::uncompress(). Please provide write access to %dirname', array('%dirname'=>AK_TMP_DIR)), E_USER_ERROR);
        }
        $result = Ak::file_get_contents($uncompressed_file, array('base_path'=>AK_TMP_DIR));
        Ak::file_delete($uncompressed_file, array('base_path'=>AK_TMP_DIR));
        Ak::file_delete($compressed_file, array('base_path'=>AK_TMP_DIR));
        return $result;
    }


    /**
     * Gets an array or a comma separated list of models. Then it includes its
     * respective files and returns an array of available models.
     *
     * @return array available models
     */
    static function import() {
        $args = func_get_args();
        $args = is_array($args[0]) ? $args[0] : (func_num_args() > 1 ? $args : Ak::stringToArray($args[0]));
        $models = array();
        foreach ($args as $arg){
            $model_name = AkInflector::camelize($arg);
            if (class_exists($model_name)){
                $models[] = $model_name;
                continue;
            }
            $model = AkInflector::toModelFilename($model_name);
            if (file_exists($model)){
                $models[] = $model_name;
                include_once($model);
                continue;
            }
            // Shouldn't we trigger an user-error?: Unknown Model or could not find the Model
        }

        return $models;
    }

    static function import_mailer() {
        $args = func_get_args();
        return call_user_func_array(array('Ak','import'),$args);
    }

    static function uses() {
        $args = func_get_args();
        return call_user_func_array(array('Ak','import'),$args);
    }

    static function stringToArray($string) {
        $args = $string;
        if(count($args) == 1 && !is_array($args)){
        (array)$args = array_unique(array_map('trim',array_diff(explode(',',strtr($args.',',';|-',',,,')),array(''))));
        }
        return $args;
    }


    static function toArray() {
        $args = func_get_args();
        return is_array($args[0]) ? $args[0] : (func_num_args() === 1 ? Ak::stringToArray($args[0]) : $args);
    }

    /**
     * Returns an array including only the elements with provided keys.
     *
     * This is useful to limit the parameters of an array used by a method.
     *
     * This utility can be used for modifying arrays which is useful for securing record creation/updating.
     *
     * If you have this code on a controller
     *
     *     $this->user->setAttributes($this->params['user']);
     *
     * and your users table has a column named is_admin. All it would take to a malicious user is to modify the page html to add the need field and gain admin privileges.
     *
     * You could avoid by using the new Ak::pick method which will return and array with desired keys.
     *
     *     $this->user->setAttributes(Ak::pick('name,email', $this->params['user']));
     *
     */
    static function pick($keys, $source_array) {
        $result = array();
        foreach (Ak::toArray($keys) as $k){
            $result[$k] = isset($source_array[$k]) ? $source_array[$k] : null;
        }
        return $result;
    }

    /**
     * Gets a copy of the first element of an array. Similar to array_shift but it does not modify the original array
     */
    static function first() {
        $args = func_get_args();
        $arr = array_slice(is_array($args[0]) ? $args[0] : $args , 0);
        return array_shift($arr);
    }

    /**
     * Gets a copy of the last element of an array. Similar to array_pop but it does not modify the original array
     */
    static function last() {
        $args = func_get_args();
        $arr = array_slice(is_array($args[0]) ? $args[0] : $args , -1);
        return array_shift($arr);
    }

    /**
     * Includes PHP functions that are not available on current PHP version
     */
    static function compat($function_name) {
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
    static function convert() {
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
        if ($options['from'] == $options['to']) {
            return $options['source'];
        }
        $options['class_prefix'] = empty($options['class_prefix']) && empty($options['path']) ? 'Ak' : $options['class_prefix'];
        $options['path'] = rtrim(empty($options['path']) ? AK_ACTIVE_SUPPORT_DIR.DS.'converters' : $options['path'], DS."\t ");

        $converter_file_name = AkInflector::underscore($options['from']).'_to_'.AkInflector::underscore($options['to']);
        $converter_class_name = $options['class_prefix'].AkInflector::camelize($converter_file_name);
        if(!class_exists($converter_class_name)){
            $file_name = $options['path'].DS.$converter_file_name.'.php';
            if(!file_exists($file_name)){
                if(defined('AK_REMOTE_CONVERTER_URI')){
                    $result = AkRemoteConverter::convert($options['from'], $options['to'], $options['source']);
                    if($result !== false){
                        return $result;
                    }
                }
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
        return $converter->convert((array)$options);
    }


    /**
     * Converts given string to UTF-8
     *
     * @param string $text
     * @param string $input_string_encoding
     * @return string UTF-8 encoded string
     */
    static function utf8($text, $input_string_encoding = null) {
        $input_string_encoding = empty($input_string_encoding) ? Ak::encoding() : $input_string_encoding;
        $Charset = Ak::singleton('AkCharset',$text);
        return $Charset->recodeString($text,'UTF-8',$input_string_encoding);
    }

    static function recode($text, $output_string_encoding = null, $input_string_encoding = null, $recoding_engine = null) {
        $input_string_encoding = empty($input_string_encoding) ? Ak::encoding() : $input_string_encoding;
        $Charset = Ak::singleton('AkCharset',$text);
        return $Charset->recodeString($text,$output_string_encoding,$input_string_encoding, $recoding_engine);
    }

    static function encoding() {
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
    static function userEncoding() {
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
    static function strlen_utf8($str) {
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
     * 
     * @deprecated
     */
    static function toJson($php_value) {
        return json_encode($php_value);
    }

    /**
     * Converts a JSON representation string into a PHP value.
     * 
     * @deprecated
     */
    static function fromJson($json_string) {
        return json_decode($json_string);
    }

    static function &memory_cache($key, &$value) {
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
            $memory[$key] = $value;
        }

        return $value;
    }

    static function getStatusKey($element) {
        $element = clone($element);
        if(isset($element->___status_key)){
            unset($element->___status_key);
        }
        return md5(serialize($element));
    }

    static function logObjectForModifications(&$object) {
        $object->___status_key = empty($object->___status_key) ? Ak::getStatusKey($object) : $object->___status_key;
        return $object->___status_key;
    }

    static function resetObjectModificationsWacther(&$object) {
        unset($object->___status_key);
    }

    static function objectHasBeenModified(&$object) {
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

    static function &call_user_func_array($function_name, $parameters) {
        Ak::deprecateWarning('Ak::call_user_func_array() is deprecated and will be removed from Akelos in future releases. Please use PHP\'s native call_user_func_array() function instead.');
        $result = call_user_func_array($function_name, $parameters);
        return $result;
    }


    static function &array_sort_by($array,  $key = null, $direction = 'asc') {
        $array_copy = $sorted_array = array();
        foreach (array_keys($array) as $k) {
            $array_copy[$k] = $array[$k][$key];
        }

        natcasesort($array_copy);
        if(strtolower($direction) == 'desc'){
            $array_copy = array_reverse($array_copy, true);
        }

        foreach (array_keys($array_copy) as $k){
            $sorted_array[$k] = $array[$k];
        }

        return $sorted_array;
    }

    static function mime_content_type($file) {
        static $mime_types;
        empty($mime_types) ? include AK_ACTIVE_SUPPORT_DIR.DS.'utils'.DS.'mime_types.php' : null;
        $file_extension = substr($file,strrpos($file,'.')+1);
        return !empty($mime_types[$file_extension]) ? $mime_types[$file_extension] : false;
    }

    static function stream($path, $buffer_size = 4096) {
        ob_implicit_flush();
        $len = empty($buffer_size) ? 4096 : $buffer_size;
        $fp = fopen($path, "rb");
        while (!feof($fp)) {
            echo fread($fp, $len);
        }
    }

    static function _nextPermutation($p, $size) {
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
    static function permute($array, $join_with = false) {
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
    static function uuid() {

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


    static function test($test_case_name, $use_sessions = false) {
        ak_test($test_case_name, $use_sessions);
    }

    /**
     * Use this function for securing includes. This way you can prevent file inclusion attacks
     */
    static function sanitize_include($include, $mode = 'normal') {
        $rules = array(
        'paranoid' => '/([^A-Z^a-z^0-9^_^-^ ]+)/',
        'high' => '/([^A-Z^a-z^0-9^_^-^ ^\/^\\\^:]+)/',
        'normal' => '/([^A-Z^a-z^0-9^_^-^ ^\.^\/^\\\]+)/'
        );
        $mode = array_key_exists($mode,$rules) ? $mode : 'normal';
        return preg_replace($rules[$mode],'',$include);
    }

    /**
     * Returns a PHP Object from an API resource
     *
     */
    static function client_api($resource, $options = array()) {
        $default_options = array(
        'protocol' => 'xml_rpc',
        'build' => true
        );
        $options = array_merge($default_options, $options);

        $Client = new AkActionWebServiceClient($options['protocol']);
        $Client->init($resource, $options);
        return $Client;
    }


    /**
     * Cross PHP version replacement for html_entity_decode. Emulates PHP5 behaviour on PHP4 on UTF-8 entities
     */
    static function html_entity_decode($html, $translation_table_or_quote_style = null) {
        return html_entity_decode($html,empty($translation_table_or_quote_style) ? ENT_QUOTES : $translation_table_or_quote_style,'UTF-8');
    }

    /**
    * Loads the plugins found at app/vendor/plugins
    */
    static function &loadPlugins() {
        $PluginManager = new AkPluginLoader();
        $PluginManager->loadPlugins();
        return $PluginManager;
    }

    static function setStaticVar($name,&$value) {
        $refhack = Ak::_staticVar($name,$value);
        return $refhack;
    }

    /**
    * Strategy for unifying in-function static vars used mainly for performance improvements framework-wide.
    *
    * Before we had
    *
    *     class A{
    *       static function b($var){
    *         static $chache;
    *         if(!isset($cache[$var])){
    *           $cache[$var] = some_heavy_function($var);
    *         }
    *         return $cache[$var];
    *       }
    *     }
    *
    * Now imagine we want to create an application server which handles multiple requests on a single instantiation, with the showcased implementation this is not possible as we can't reset $cache, unless we hack badly every single method that uses this strategy.
    *
    * We can refresh this static values the new Ak::getStaticVar method. So from previous example we will have to replace
    *
    *     static $chache;
    */
    static function &getStaticVar($name) {
        $refhack = Ak::_staticVar($name);
        return $refhack;
    }

    static function &unsetStaticVar($name) {
        $null = null;
        $refhack = Ak::_staticVar($name, $null, true);
        return $refhack;
    }

    static function &_staticVar($name, &$value = null, $destruct = false) {
        static $_memory;
        if(!constant('AK_CAN_FORK') || (!$pid = getmypid())){
            $pid = 0;
        }

        $null = null;
        $true = true;
        $false = false;
        $return = $null;
        if ($value === null && $destruct === false) {
            /**
             * GET mode
             */
            if (isset($_memory[$pid][$name])) {
                $return = $_memory[$pid][$name];
            }
        } else if ($value !== null) {
            /**
             * SET mode
             */
            if (is_string($name)) {
                $_memory[$pid][$name] = $value;
                $return = $true;
            } else {
                $return = $false;
            }

        } else if ($destruct === true) {
            if ($name !== null) {
                $value = isset($_memory[$pid][$name])?$_memory[$pid][$name]:$null;
                if (is_object($value) && method_exists($value,'__destruct')) {
                    $value->__destruct();
                }
                unset($value);
                unset($_memory[$pid][$name]);
            } else {
                foreach ($_memory[$pid] as $name => $value) {
                    Ak::unsetStaticVar($name);
                }
            }
        }
        return $return;
    }

    /**
     *
     * @param array $options
     * @param array $default_options
     * @param array $available_options
     * @param boolean $walk_keys
     */
    static function parseOptions(&$options, $default_options = array(), $parameters = array(), $walk_keys=false) {
        if ($walk_keys) {
            foreach ($options as $key=>$value) {
                if (!is_array($value)) {
                    unset($options[$key]);
                    $options[$value] = $default_options;
                } else {
                    Ak::parseOptions($value, $default_options, $parameters);
                    $options[$key] = $value;
                }
            }
            return;
        }

        $options = array_merge($default_options, $options);
        foreach($options as $key => $value) {
            if(isset($parameters['available_options'])) {
                if (!isset($parameters['available_options'][$key])) {
                    continue;
                }
            }
            $options[$key] = $value;

        }
    }

    /**
     * Returns YAML settings from config/$namespace.yml
     */
    static function getSettings($namespace, $raise_error_if_config_file_not_found = true, $environment = AK_ENVIRONMENT) {
        static $_config;
        if ($raise_error_if_config_file_not_found && !in_array($environment,Ak::toArray(AK_AVAILABLE_ENVIRONMENTS))) {
            trigger_error('The environment '.$environment.' is not allowed. Allowed environments: '.AK_AVAILABLE_ENVIRONMENTS, E_USER_ERROR);
            return false;
        }
        if (!isset($_config)) {
            $_config = new AkConfig();
        }
        return $_config->get($namespace, $environment, $raise_error_if_config_file_not_found);
    }

    static function getSetting($namespace, $variable, $default_value = null) {
        if($settings = Ak::getSettings($namespace)){
            return isset($settings[$variable]) ? $settings[$variable] : $default_value;
        }
        return $default_value;
    }

    static function _parseSettingsConstants($settingsStr) {
        return preg_replace_callback('/\$\{(AK_.*?)\}/',array('Ak','getConstant'),$settingsStr);
    }

    static function getConstant($name) {
        return defined($name[1])?constant($name[1]):'';
    }

    /**
     * Get a models a model instance. Including and instantiating the model for us.
     *
     * This kinds mimics the ideal (new Model())->find() wich does not exist on PHP yet.
     *
     * On Akelos we can do Ak::get('Model')->find();
     */
    static function get($model_name, $attributes = array()) {
        $model_name = Ak::first(Ak::import($model_name));
        if(!empty($model_name)){
            return new $model_name($attributes);
        }
    }

    /**
    * PHP modulo % returns the dividend which is not the expected result on
    * Math operations where the divisor is expected.
    *
    * For example PHP will return -5%7 = -5 when expected was 2
    */
    static function modulo($a, $n) {
        $n = abs($n);
        return $n===0 ? null : $a-$n*floor($a/$n);
    }

    /**
     * Akelos version of pcntl_fork wich prevents forked processes from killing the database connection.
     *
     * See http://dev.mysql.com/doc/refman/5.0/en/gone-away.html
     *
     * This is done by closing the connection before forking and reconnecting on the child & parent process.
     *
     * @return Same as pcntl_fork (PID of the children to the parent, 0 to the children process and -1 if fails).
     */
    static function pcntl_fork() {
        $db = Ak::db();
        $can_connect = (isset($db->connection) && method_exists($db->connection, 'connect'));
        // Disconnect on the parent so we we don't have a zombie connection once the child closes the reused connection
        $can_connect && $db->connection->close();
        $pid = pcntl_fork();
        // Connecting on the child process
        $can_connect && $db->connection->connect();
        // Reconect on the parent
        $pid > 0 && $can_connect && $db->connection->connect();
        return $pid;
    }


    /**
     * Getting the temporary directory
    */
    static function get_tmp_dir_name() {
        if(!defined('AK_TMP_DIR')){
            if(defined('AK_BASE_DIR')){
                $tmp_dir = AK_BASE_DIR.DS.'tmp'.DS.AK_ENVIRONMENT.DS.(AK_CLI?'console':'web');
                if(is_writable($tmp_dir)){
                    return $tmp_dir;
                }
            }
            if(!function_exists('sys_get_temp_dir')){
                $dir = empty($_ENV['TMP']) ? (empty($_ENV['TMPDIR']) ? (empty($_ENV['TEMP']) ? false : $_ENV['TEMP']) : $_ENV['TMPDIR']) : $_ENV['TMP'];
                if(empty($dir) && $fn = tempnam(md5(rand()),'')){
                    $dir = dirname($fn);
                    unlink($fn);
                }
            }else{
                $dir = sys_get_temp_dir();
            }
            if(empty($dir)){
                trigger_error('Could not find a path for temporary files. Please define AK_TMP_DIR in your config.php', E_USER_ERROR);
            }
            $dir = rtrim(realpath($dir), DS).DS.'ak_'.md5(AK_BASE_DIR);
            if(!is_dir($dir)){
                mkdir($dir);
            }
            return $dir;
        }
        return AK_TMP_DIR;
    }

    static function registerAutoloader($autoloader) {
        spl_autoload_unregister('akelos_autoload');
        spl_autoload_register($autoloader);
        spl_autoload_register('akelos_autoload');
    }

    /**
    * Unsets circular reference children that are not freed from memory
    * when calling unset() or when the parent object is garbage collected.
    *
    * @see http://paul-m-jones.com/?p=262
    * @see http://bugs.php.net/bug.php?id=33595
    */
    static function unsetCircularReferences(&$Object) {
        // We can't use get_class_vars as it does not include runtime assigned attributes
        foreach (array_keys((array)$Object) as $attribute){
            if(isset($Object->$attribute)){
                unset($Object->$attribute);
            }
        }
    }
}



/**
 * Procedural functions
 */


function translate($string, $args = null, $controller = null)
{
    return Ak::t($string, $args, $controller);
}


/**
 * @deprecated
 */
function ak_get_tmp_dir_name()
{
    Ak::deprecateWarning('ak_get_tmp_dir_name is deprecated. Please use Ak::get_tmp_dir_name()');
    return Ak::get_tmp_dir_name();
}

function ak_test($test_case_name, $use_sessions = false, $prevent_double_test_running = true, $custom_reporter = false)
{
    static $ran_tests = array();
    if(!isset($ran_tests[$test_case_name]) || !$prevent_double_test_running){
        if(!defined('ALL_TESTS_CALL')){
            $use_sessions ? @session_start() : null;
            $test = new $test_case_name();
            if(empty($custom_reporter)){
                if (defined('AK_CLI') && AK_CLI || TextReporter::inCli() || (defined('AK_CONSOLE_MODE') && AK_CONSOLE_MODE) || (defined('AK_WEB_REQUEST') && !AK_WEB_REQUEST)) {
                    $test->run(new TextReporter());
                }else{
                    $test->run(new HtmlReporter());
                }
            }else{
                $test->run(new $custom_reporter());
            }
        }
        $ran_tests[$test_case_name] = true;
    }
}

function ak_test_case($test_case_name, $show_enviroment_flags = true)
{
    $test_cases = (array)Ak::getStaticVar('ak_test_cases');
    $test_cases[] = $test_case_name;
    Ak::setStaticVar('ak_test_cases', $test_cases);
    $levels = count(debug_backtrace());
    if ($levels == 1 || ($levels == 2 && isset($_ENV['SCRIPT_NAME']) && $_ENV['SCRIPT_NAME'] == 'dummy.php')) {
        if($show_enviroment_flags){
            echo "(".AK_ENVIRONMENT." environment) Error reporting set to: ".AkConfig::getErrorReportingLevelDescription()."\n";
        }
        ak_test($test_case_name);
    }
}

function ak_compat($function_name)
{
    if(!function_exists($function_name)){
        require_once(AK_CONTRIB_DIR.DS.'pear'.DS.'PHP'.DS.'Compat'.DS.'Function'.DS.$function_name.'.php');
    }
}


/**
 * This function sets a constant and returns it's value. If constant has been already defined it
 * will reutrn its original value.
 *
 * Returns null in case the constant does not exist
 *
 * @param string $name
 * @param mixed $value
 */
function ak_define($name, $value = null)
{
    $name = strtoupper($name);
    $name = substr($name,0,3) == 'AK_' ? $name : 'AK_'.$name;
    return  defined($name) ? constant($name) : (is_null($value) ? null : (define($name, $value) ? $value : null));
}


