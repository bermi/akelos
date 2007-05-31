<?php

// If you need to customize the framework default settings or specify internationalization options,
// edit the files config/testing.php, config/development.php, config/production.php

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


defined('DS') ? null : define('DS', DIRECTORY_SEPARATOR);

ak_define('BASE_DIR', str_replace(DS.'config'.DS.'boot.php','',__FILE__));
ak_define('CONFIG_DIR', AK_BASE_DIR.DS.'config');

// If you need to customize the framework default settings or specify internationalization options,
// edit the files config/testing.php, config/development.php, config/production.php
if(AK_ENVIRONMENT != 'setup'){
    $akdb = $database_settings[strtolower(AK_ENVIRONMENT)];
    $dsn = $akdb['type'] == 'sqlite' ?
    'sqlite://'.urlencode($akdb['database_file']).'/?persist' :
    $akdb['type'].'://'.$akdb['user'].':'.$akdb['password'].'@'.$akdb['host'].
    (empty($akdb['port'])?'':':'.$akdb['port']).
    '/'.$akdb['database_name'].
    (!empty($akdb['options'])?'?'.$akdb['options']:'');

    require_once(AK_CONFIG_DIR.DS.AK_ENVIRONMENT.'.php');
}

unset($environment, $database_settings, $akdb);


// Locale settings ( you must create a file at /config/locales/ using en.php as departure point)
// Please be aware that your charset needs to be UTF-8 in order to edit the locales files
// auto will enable all the locales at config/locales/ dir
ak_define('AVAILABLE_LOCALES', 'auto');


// Set these constants in order to allow only these locales on web requests
// ak_define('ACTIVE_RECORD_DEFAULT_LOCALES','en,es');
// ak_define('APP_LOCALES','en,es');
// ak_define('PUBLIC_LOCALES','en,es');

ak_define('URL_REWRITE_ENABLED', true);

ak_define('TIME_DIFFERENCE', 0); // Time difference from the webserver

// COMMENT THIS LINE IF YOU DONT WANT THE FRAMEWORK TO CONNECT TO THE DATABASE ON EACH REQUEST AUTOMATICALLY
ak_define('WEB_REQUEST_CONNECT_TO_DATABASE_ON_INSTANTIATE', true);

ak_define('CLI', php_sapi_name() == 'cli');
ak_define('WEB_REQUEST', !empty($_SERVER['REQUEST_URI']));
ak_define('REQUEST_URI', isset($_SERVER['REQUEST_URI']) ?
$_SERVER['REQUEST_URI'] :
$_SERVER['PHP_SELF'] .'?'.(isset($_SERVER['argv']) ? $_SERVER['argv'][0] : $_SERVER['QUERY_STRING']));

ak_define('DEBUG', AK_ENVIRONMENT == 'production' ? 0 : 1);

@error_reporting(AK_DEBUG ? E_ALL : 0);

ak_define('CACHE_HANDLER', 2);

ak_define('APP_DIR', AK_BASE_DIR.DS.'app');
ak_define('APIS_DIR', AK_APP_DIR.DS.'apis');
ak_define('MODELS_DIR', AK_APP_DIR.DS.'models');
ak_define('CONTROLLERS_DIR', AK_APP_DIR.DS.'controllers');
ak_define('VIEWS_DIR', AK_APP_DIR.DS.'views');
ak_define('HELPERS_DIR', AK_APP_DIR.DS.'helpers');
ak_define('ELEMENTS_DIR', AK_VIEWS_DIR.DS.'elements');
ak_define('CACHE_DIR',AK_BASE_DIR.DS.'cache');
ak_define('COMPONENTS_DIR',AK_BASE_DIR.DS.'components');
ak_define('PUBLIC_DIR', AK_BASE_DIR.DS.'public');
ak_define('TEST_DIR', AK_BASE_DIR.DS.'test');
ak_define('SCRIPT_DIR',AK_BASE_DIR.DS.'script');

ak_define('DEFAULT_LAYOUT', 'application');

// Paths below this point refer to the Akelos Framework components.
ak_define('FRAMEWORK_DIR', AK_BASE_DIR);
ak_define('CONTRIB_DIR',AK_FRAMEWORK_DIR.DS.'vendor');
ak_define('VENDOR_DIR', AK_CONTRIB_DIR);
ak_define('DOCS_DIR',AK_FRAMEWORK_DIR.DS.'docs');
ak_define('LIB_DIR',AK_FRAMEWORK_DIR.DS.'lib');


ak_define('CONFIG_INCLUDED',true);
ak_define('FW',true);

if(AK_ENVIRONMENT != 'setup'){
    ak_define('UPLOAD_FILES_USING_FTP', !empty($ftp_settings));
    ak_define('READ_FILES_USING_FTP', false);
    ak_define('DELETE_FILES_USING_FTP', !empty($ftp_settings));
    ak_define('FTP_AUTO_DISCONNECT', !empty($ftp_settings));

    if(!empty($ftp_settings)){
        ak_define('FTP_PATH', $ftp_settings);
        unset($ftp_settings);
    }
}

@ini_set("arg_separator.output","&");

@ini_set("session.name","AK_SESSID");
@ini_set("include_path",(AK_LIB_DIR.PATH_SEPARATOR.AK_MODELS_DIR.PATH_SEPARATOR.AK_CONTRIB_DIR.DS.'pear'.PATH_SEPARATOR.ini_get("include_path")));
ak_define('PHP5', version_compare(PHP_VERSION, '5', '>=') == 1 ? true : false);

if(!AK_CLI && AK_WEB_REQUEST){

    ak_define('SITE_URL_SUFFIX',
    '/'.str_replace(array(join(DS,array_diff((array)@explode(DS,AK_BASE_DIR),
    (array)@explode('/',AK_REQUEST_URI))), AK_BASE_DIR, DS),'',AK_BASE_DIR));

    ak_define('AUTOMATIC_SSL_DETECTION', 1);

    ak_define('PROTOCOL',isset($_SERVER['HTTPS']) ? 'https://' : 'http://');
    ak_define('HOST', $_SERVER['SERVER_NAME'] == 'localhost' ? $_SERVER['SERVER_ADDR'] : $_SERVER['SERVER_NAME']);
    ak_define('REMOTE_IP',(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR']);
    $port = ($_SERVER['SERVER_PORT'] != 80 && AK_PROTOCOL == 'http://') ||
    ($_SERVER['SERVER_PORT'] != 443 && AK_PROTOCOL == 'https://')
    ? (empty($_SERVER['SERVER_PORT']) ? '' : ':'.$_SERVER['SERVER_PORT']) : '';


    $suffix = '';
    if(defined('AK_SITE_HTPSS_URL_SUFFIX') && isset($_SERVER['HTTPS'])){
        $suffix = AK_SITE_HTPSS_URL_SUFFIX;
        $port = strstr(AK_SITE_HTPSS_URL_SUFFIX,':') ? '' : $port;
    }elseif(defined('AK_SITE_URL_SUFFIX') && AK_SITE_URL_SUFFIX != ''){
        $suffix = AK_SITE_URL_SUFFIX;
        $port = strstr(AK_SITE_URL_SUFFIX,':') ? '' : $port;
    }
    if(!defined('AK_SITE_URL')){
        ak_define('SITE_URL', trim(AK_PROTOCOL.AK_HOST, '/').$port.$suffix);
        ak_define('URL', AK_SITE_URL);
    }else{
        if(AK_AUTOMATIC_SSL_DETECTION){
            ak_define('URL', str_replace(array('https://','http://'),AK_PROTOCOL, AK_SITE_URL).$port.$suffix);
        }else{
            ak_define('URL', AK_SITE_URL.$port.$suffix);
        }
    }
    ak_define('CURRENT_URL', substr(AK_SITE_URL,0,strlen($suffix)*-1).AK_REQUEST_URI);

    unset($suffix, $port);
    ak_define('COOKIE_DOMAIN', AK_HOST);
    // ini_set('session.cookie_domain', AK_COOKIE_DOMAIN);

}else{
    ak_define('PROTOCOL','http://');
    ak_define('HOST', 'localhost');
    ak_define('REMOTE_IP','127.0.0.1');
    ak_define('SITE_URL', 'http://localhost');
    ak_define('URL', 'http://localhost/');
    ak_define('CURRENT_URL', 'http://localhost/');
    ak_define('COOKIE_DOMAIN', AK_HOST);
}

ak_define('SESSION_HANDLER', 0);
ak_define('SESSION_EXPIRE', 600);
ak_define('SESSION_NAME', 'AK_SESSID');

ak_define('DESKTOP', AK_SITE_URL == 'http://akelos');

ak_define('ASSET_HOST','');

if(!defined('AK_ASSET_URL_PREFIX')){
    ak_define('ASSET_URL_PREFIX',str_replace(array(AK_BASE_DIR,'\\','//'),array('','/','/'), AK_PUBLIC_DIR));
}


ak_define('DEV_MODE', AK_ENVIRONMENT == 'development');
ak_define('AUTOMATICALLY_UPDATE_LANGUAGE_FILES', AK_DEV_MODE);
ak_define('ENABLE_PROFILER', false);
ak_define('PROFILER_GET_MEMORY',false);

$ADODB_CACHE_DIR = AK_CACHE_DIR;

/**
 * Mode types for error reporting and loggin
 */
ak_define('MODE_DISPLAY', 1);
ak_define('MODE_MAIL', 2);
ak_define('MODE_FILE', 4);
ak_define('MODE_DATABASE', 8);
ak_define('MODE_DIE', 16);

ak_define('LOG_EVENTS', false);

ak_define('ROUTES_MAPPING_FILE', AK_CONFIG_DIR.DS.'routes.php');
ak_define('OS', (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'WINDOWS' : 'UNIX'));
ak_define('CHARSET', 'UTF-8');

require_once(AK_LIB_DIR.DS.'Ak.php');

/*
if(!AK_CLI && (AK_DEBUG || AK_ENVIRONMENT == 'setup')){
    include_once(AK_LIB_DIR.DS.'AkDevelopmentErrorHandler.php');
    $__AkDevelopmentErrorHandler = new AkDevelopmentErrorHandler();
    set_error_handler(array(&$__AkDevelopmentErrorHandler, 'raiseError'));
}
*/

ak_define('ACTION_CONTROLLER_DEFAULT_REQUEST_TYPE', 'web_request');
ak_define('ACTION_CONTROLLER_DEFAULT_ACTION', 'index');

require_once(AK_LIB_DIR.DS.'AkActionController.php');

?>
