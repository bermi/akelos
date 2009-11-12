<?php

/**
 * This file and the lib/constants.php file perform most part of Akelos
 * environment guessing.
 *
 * You can retrieve a list of current settings by running Ak::get_constants();
 *
 * If you're running a high load site you might want to fine tune this options
 * according to your environment. If you set the options implicitly you might
 * gain in performance but loose in flexibility when moving to a different
 * environment.
 *
 * If you need to customize the framework default settings or specify
 * internationalization options, edit the files at config/environments/*
 */

defined('AK_PHP5')                      || define('AK_PHP5', version_compare(PHP_VERSION, '5', '>=') == 1 ? true : false);
defined('AK_PHP53')                     || define('AK_PHP53', version_compare(PHP_VERSION, '5.3', '>=') == 1 ? true : false);
defined('AK_PHP6')                      || define('AK_PHP6', version_compare(PHP_VERSION, '6', '>=') == 1 ? true : false);

defined('AK_CONFIG_DIR')                || define('AK_CONFIG_DIR', AK_BASE_DIR.DS.'config');

defined('AK_CACHE_HANDLER_PEAR')        || define('AK_CACHE_HANDLER_PEAR',1);
defined('AK_CACHE_HANDLER_ADODB')       || define('AK_CACHE_HANDLER_ADODB',2);
defined('AK_CACHE_HANDLER_MEMCACHE')    || define('AK_CACHE_HANDLER_MEMCACHE',3);

// If you need to customize the framework default settings or specify internationalization options,
// edit the files config/testing.php, config/development.php, config/production.php
if(AK_ENVIRONMENT != 'setup'){
    require_once(AK_CONFIG_DIR.DS.'environments'.DS.AK_ENVIRONMENT.'.php');
}

defined('AK_CACHE_HANDLER')                 || define('AK_CACHE_HANDLER', AK_CACHE_HANDLER_PEAR);

if (!defined('AK_TEST_DATABASE_ON')) {
    defined('AK_DEFAULT_DATABASE_PROFILE')  || define('AK_DEFAULT_DATABASE_PROFILE', AK_ENVIRONMENT);
}

// Locale settings ( you must create a file at /config/locales/ using en.php as departure point)
// Please be aware that your charset needs to be UTF-8 in order to edit the locales files
// auto will enable all the locales at config/locales/ dir
defined('AK_AVAILABLE_LOCALES')         || define('AK_AVAILABLE_LOCALES', 'auto');
defined('AK_AVAILABLE_ENVIRONMENTS')    || define('AK_AVAILABLE_ENVIRONMENTS',"setup,testing,development,production,staging");
// Set these constants in order to allow only these locales on web requests
// defined('AK_ACTIVE_RECORD_DEFAULT_LOCALES') || define('AK_ACTIVE_RECORD_DEFAULT_LOCALES','en,es');
// defined('AK_APP_LOCALES') || define('AK_APP_LOCALES','en,es');
// defined('AK_PUBLIC_LOCALES') || define('AK_PUBLIC_LOCALES','en,es');
// defined('AK_URL_REWRITE_ENABLED') || define('AK_URL_REWRITE_ENABLED', true);

defined('AK_TIME_DIFFERENCE')           || define('AK_TIME_DIFFERENCE', 0); // Time difference from the webserver

defined('AK_CLI')                       || define('AK_CLI', php_sapi_name() == 'cli');
defined('AK_WEB_REQUEST')               || define('AK_WEB_REQUEST', !empty($_SERVER['REQUEST_URI']));
defined('AK_REQUEST_URI')               || define('AK_REQUEST_URI', isset($_SERVER['REQUEST_URI']) ?
$_SERVER['REQUEST_URI'] :
$_SERVER['PHP_SELF'] .'?'.(isset($_SERVER['argv']) ? $_SERVER['argv'][0] : $_SERVER['QUERY_STRING']));

defined('AK_DEBUG')                 || define('AK_DEBUG', AK_ENVIRONMENT == 'production' ? 0 : 1);

defined('AK_APP_DIR')               || define('AK_APP_DIR', AK_BASE_DIR.DS.'app');
defined('AK_APIS_DIR')              || define('AK_APIS_DIR', AK_APP_DIR.DS.'apis');
defined('AK_MODELS_DIR')            || define('AK_MODELS_DIR', AK_APP_DIR.DS.'models');
defined('AK_CONTROLLERS_DIR')       || define('AK_CONTROLLERS_DIR', AK_APP_DIR.DS.'controllers');
defined('AK_VIEWS_DIR')             || define('AK_VIEWS_DIR', AK_APP_DIR.DS.'views');
defined('AK_HELPERS_DIR')           || define('AK_HELPERS_DIR', AK_APP_DIR.DS.'helpers');
defined('AK_PUBLIC_DIR')            || define('AK_PUBLIC_DIR', AK_BASE_DIR.DS.'public');
defined('AK_TEST_DIR')              || define('AK_TEST_DIR', AK_BASE_DIR.DS.'test');
defined('AK_SCRIPT_DIR')            || define('AK_SCRIPT_DIR',AK_BASE_DIR.DS.'script');
defined('AK_APP_VENDOR_DIR')        || define('AK_APP_VENDOR_DIR',AK_APP_DIR.DS.'vendor');
defined('AK_APP_PLUGINS_DIR')       || define('AK_APP_PLUGINS_DIR',AK_APP_VENDOR_DIR.DS.'plugins');
defined('AK_APP_INSTALLERS_DIR')    || define('AK_APP_INSTALLERS_DIR', AK_APP_DIR.DS.'installers');

defined('AK_PLUGINS_DIR')           || define('AK_PLUGINS_DIR', AkConfig::getDir('app').DS.'vendor'.DS.'plugins');
defined('AK_PLUGINS')               || define('AK_PLUGINS', 'auto');

defined('AK_TMP_DIR')               || define('AK_TMP_DIR', Ak::get_tmp_dir_name());
defined('AK_COMPILED_VIEWS_DIR')    || define('AK_COMPILED_VIEWS_DIR', AK_TMP_DIR.DS.'views');
defined('AK_CACHE_DIR')             || define('AK_CACHE_DIR', AK_TMP_DIR.DS.'cache');

defined('AK_DEFAULT_LAYOUT')        || define('AK_DEFAULT_LAYOUT', 'application');

defined('AK_CONTRIB_DIR')           || define('AK_CONTRIB_DIR',AK_FRAMEWORK_DIR.DS.'vendor');
defined('AK_VENDOR_DIR')            || define('AK_VENDOR_DIR', AK_CONTRIB_DIR);
defined('AK_DOCS_DIR')              || define('AK_DOCS_DIR',AK_FRAMEWORK_DIR.DS.'docs');

defined('AK_CONFIG_INCLUDED')       || define('AK_CONFIG_INCLUDED',true);
defined('AK_FW')                    || define('AK_FW',true);

if(AK_ENVIRONMENT != 'setup'){
    defined('AK_UPLOAD_FILES_USING_FTP')    || define('AK_UPLOAD_FILES_USING_FTP', !empty($ftp_settings));
    defined('AK_READ_FILES_USING_FTP')      || define('AK_READ_FILES_USING_FTP', false);
    defined('AK_DELETE_FILES_USING_FTP')    || define('AK_DELETE_FILES_USING_FTP', !empty($ftp_settings));
    defined('AK_FTP_AUTO_DISCONNECT')       || define('AK_FTP_AUTO_DISCONNECT', !empty($ftp_settings));

    if(!empty($ftp_settings)){
        defined('AK_FTP_PATH')              || define('AK_FTP_PATH', $ftp_settings);
        unset($ftp_settings);
    }
}


if(!AK_CLI && AK_WEB_REQUEST){

    if (!defined('AK_SITE_URL_SUFFIX')){
        $__ak_site_url_suffix_userdir = substr(AK_REQUEST_URI,1,1) == '~' ? substr(AK_REQUEST_URI, 0, strpos(AK_REQUEST_URI, '/', 1)) : '';
        $__ak_site_url_suffix = str_replace(array(join(DS,array_diff((array)@explode(DS,AK_BASE_DIR), (array)@explode('/',AK_REQUEST_URI))), DS,'//'), array('','/','/'), AK_BASE_DIR);
        define('AK_SITE_URL_SUFFIX', $__ak_site_url_suffix_userdir.$__ak_site_url_suffix);
        unset($__ak_site_url_suffix_userdir, $__ak_site_url_suffix);
    }
    defined('AK_AUTOMATIC_SSL_DETECTION')   || define('AK_AUTOMATIC_SSL_DETECTION', 1);
    defined('AK_PROTOCOL')                  || define('AK_PROTOCOL',isset($_SERVER['HTTPS']) ? 'https://' : 'http://');
    defined('AK_HOST')                      || define('AK_HOST', $_SERVER['SERVER_NAME'] == 'localhost' ?
    // Will force to IP4 for localhost until IP6 is supported by helpers
    ($_SERVER['SERVER_ADDR'] == '::1' ? '127.0.0.1' : $_SERVER['SERVER_ADDR']) :
    $_SERVER['SERVER_NAME']);
    defined('AK_REMOTE_IP')                 || define('AK_REMOTE_IP',preg_replace('/,.*/','',((!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : (!empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : (!empty($_ENV['HTTP_X_FORWARDED_FOR']) ? $_ENV['HTTP_X_FORWARDED_FOR'] : (empty($_ENV['REMOTE_ADDR']) ? false : $_ENV['REMOTE_ADDR']))))));
    defined('AK_SERVER_STANDARD_PORT')      || define('AK_SERVER_STANDARD_PORT', AK_PROTOCOL == 'https://' ? '443' : '80');

    $_ak_port = ($_SERVER['SERVER_PORT'] != AK_SERVER_STANDARD_PORT)
    ? (empty($_SERVER['SERVER_PORT']) ? '' : ':'.$_SERVER['SERVER_PORT']) : '';

    if(isset($_SERVER['HTTP_HOST']) && strstr($_SERVER['HTTP_HOST'],':')){
        $_ak_port = substr($_SERVER['HTTP_HOST'], strpos($_SERVER['HTTP_HOST'],':'));
    }

    $_ak_suffix = '';
    if(defined('AK_SITE_HTPSS_URL_SUFFIX') && isset($_SERVER['HTTPS'])){
        $_ak_suffix = AK_SITE_HTPSS_URL_SUFFIX;
        $_ak_port = strstr(AK_SITE_HTPSS_URL_SUFFIX,':') ? '' : $_ak_port;
    }elseif(defined('AK_SITE_URL_SUFFIX') && AK_SITE_URL_SUFFIX != ''){
        $_ak_suffix = AK_SITE_URL_SUFFIX;
        $_ak_port = strstr(AK_SITE_URL_SUFFIX,':') ? '' : $_ak_port;
    }

    if(!defined('AK_SITE_URL')){
        defined('AK_SITE_URL') || define('AK_SITE_URL', trim(AK_PROTOCOL.AK_HOST, '/').$_ak_port.$_ak_suffix);
        defined('AK_URL')       || define('AK_URL', AK_SITE_URL);
    }else{
        if(AK_AUTOMATIC_SSL_DETECTION){
            defined('AK_URL')   || define('AK_URL', str_replace(array('https://','http://'),AK_PROTOCOL, AK_SITE_URL).$_ak_port.$_ak_suffix);
        }else{
            defined('AK_URL')   || define('AK_URL', AK_SITE_URL.$_ak_port.$_ak_suffix);
        }
    }

    defined('AK_CURRENT_URL')           || define('AK_CURRENT_URL', substr(AK_SITE_URL,0,strlen($_ak_suffix)*-1).AK_REQUEST_URI);
    defined('AK_SERVER_PORT')           || define('AK_SERVER_PORT', empty($_ak_port) ? AK_SERVER_STANDARD_PORT : trim($_ak_port,':'));

    unset($_ak_suffix, $_ak_port);
    defined('AK_COOKIE_DOMAIN')                 || define('AK_COOKIE_DOMAIN', AK_HOST);
    defined('AK_INSECURE_APP_DIRECTORY_LAYOUT') || define('AK_INSECURE_APP_DIRECTORY_LAYOUT', false);

    if(!defined('AK_ASSET_URL_PREFIX')){
        defined('AK_ASSET_URL_PREFIX')  || define('AK_ASSET_URL_PREFIX', AK_INSECURE_APP_DIRECTORY_LAYOUT ? AK_SITE_URL_SUFFIX.str_replace(array(AK_BASE_DIR,'\\','//'),array('','/','/'), AK_PUBLIC_DIR) : AK_SITE_URL_SUFFIX);
    }

}else{
    defined('AK_PROTOCOL')          || define('AK_PROTOCOL','http://');
    defined('AK_HOST')              || define('AK_HOST', 'localhost');
    defined('AK_REMOTE_IP')         || define('AK_REMOTE_IP','127.0.0.1');
    defined('AK_SITE_URL')          || define('AK_SITE_URL', 'http://localhost');
    defined('AK_URL')               || define('AK_URL', 'http://localhost/');
    defined('AK_CURRENT_URL')       || define('AK_CURRENT_URL', 'http://localhost/');
    defined('AK_COOKIE_DOMAIN')     || define('AK_COOKIE_DOMAIN', AK_HOST);
    defined('AK_ASSET_URL_PREFIX')  || define('AK_ASSET_URL_PREFIX', '');
}

defined('AK_CALLED_FROM_LOCALHOST')                     || define('AK_CALLED_FROM_LOCALHOST', AK_REMOTE_IP == '127.0.0.1');
defined('AK_SESSION_HANDLER')                           || define('AK_SESSION_HANDLER', 0);
defined('AK_SESSION_EXPIRE')                            || define('AK_SESSION_EXPIRE', 600);
defined('AK_SESSION_NAME')                              || define('AK_SESSION_NAME', 'AK_'.substr(md5(AK_HOST.AK_APP_DIR),0,6));
defined('AK_DESKTOP')                                   || define('AK_DESKTOP', AK_SITE_URL == 'http://akelos');
defined('AK_ASSET_HOST')                                || define('AK_ASSET_HOST','');
defined('AK_DEV_MODE')                                  || define('AK_DEV_MODE', AK_ENVIRONMENT == 'development');
defined('AK_TEST_MODE')                                 || define('AK_TEST_MODE', AK_ENVIRONMENT == 'testing');
defined('AK_PRODUCTION_MODE')                           || define('AK_PRODUCTION_MODE', AK_ENVIRONMENT == 'production');
defined('AK_AUTOMATICALLY_UPDATE_LANGUAGE_FILES')       || define('AK_AUTOMATICALLY_UPDATE_LANGUAGE_FILES', AK_DEV_MODE);
defined('AK_ENABLE_PROFILER')                           || define('AK_ENABLE_PROFILER', false);
defined('AK_PROFILER_GET_MEMORY')                       || define('AK_PROFILER_GET_MEMORY',false);
defined('AK_MODE_DISPLAY')                              || define('AK_MODE_DISPLAY', 1);
defined('AK_MODE_MAIL')                                 || define('AK_MODE_MAIL', 2);
defined('AK_MODE_FILE')                                 || define('AK_MODE_FILE', 4);
defined('AK_MODE_DATABASE')                             || define('AK_MODE_DATABASE', 8);
defined('AK_MODE_DIE')                                  || define('AK_MODE_DIE', 16);
defined('AK_LOG_DIR')                                   || define('AK_LOG_DIR', AK_BASE_DIR.DS.'log');
defined('AK_LOG_EVENTS')                                || define('AK_LOG_EVENTS', false);
defined('AK_ROUTES_MAPPING_FILE')                       || define('AK_ROUTES_MAPPING_FILE', AK_CONFIG_DIR.DS.'routes.php');
defined('AK_OS')                                        || define('AK_OS', (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'WINDOWS' : 'UNIX'));
defined('AK_CAN_FORK')                                  || define('AK_CAN_FORK', function_exists('pcntl_fork'));
defined('AK_CHARSET')                                   || define('AK_CHARSET', 'UTF-8');
defined('AK_ACTION_CONTROLLER_DEFAULT_REQUEST_TYPE')    || define('AK_ACTION_CONTROLLER_DEFAULT_REQUEST_TYPE', 'web_request');
defined('AK_ACTION_CONTROLLER_DEFAULT_ACTION')          || define('AK_ACTION_CONTROLLER_DEFAULT_ACTION', 'index');
defined('AK_BEEP_ON_ERRORS_WHEN_TESTING')               || define('AK_BEEP_ON_ERRORS_WHEN_TESTING', false);
defined('AK_FRAMEWORK_LANGUAGE')                        || define('AK_FRAMEWORK_LANGUAGE', 'en');
defined('AK_DEV_MODE')                                  || define('AK_DEV_MODE', false);
defined('AK_AUTOMATIC_CONFIG_VARS_ENCRYPTION')          || define('AK_AUTOMATIC_CONFIG_VARS_ENCRYPTION', false);
defined('AK_VERBOSE_INSTALLER')                         || define('AK_VERBOSE_INSTALLER', AK_DEV_MODE);
defined('AK_HIGH_LOAD_MODE')                            || define('AK_HIGH_LOAD_MODE', false);
defined('AK_AUTOMATIC_SESSION_START')                   || define('AK_AUTOMATIC_SESSION_START', !AK_HIGH_LOAD_MODE);
defined('AK_APP_NAME')                                  || define('AK_APP_NAME', 'Application');
defined('JAVASCRIPT_DEFAULT_SOURCES')                   || define('JAVASCRIPT_DEFAULT_SOURCES','prototype,event_selectors,scriptaculous');
defined('AK_DATE_HELPER_DEFAULT_PREFIX')                || define('AK_DATE_HELPER_DEFAULT_PREFIX', 'date');
defined('AK_JAVASCRIPT_PATH')                           || define('AK_JAVASCRIPT_PATH', AK_PUBLIC_DIR.DS.'javascripts');
defined('AK_DEFAULT_LOCALE_NAMESPACE')                  || define('AK_DEFAULT_LOCALE_NAMESPACE', null);



// Akelos args is a short way to call functions that is only intended for fast prototyping
defined('AK_ENABLE_AKELOS_ARGS') || define('AK_ENABLE_AKELOS_ARGS', false);
// Use setColumnName if available when using set('column_name', $value);
defined('AK_ACTIVE_RECORD_INTERNATIONALIZE_MODELS_BY_DEFAULT')  || define('AK_ACTIVE_RECORD_INTERNATIONALIZE_MODELS_BY_DEFAULT', true);
defined('AK_ACTIVE_RECORD_ENABLE_AUTOMATIC_SETTERS_AND_GETTERS')|| define('AK_ACTIVE_RECORD_ENABLE_AUTOMATIC_SETTERS_AND_GETTERS', false);
defined('AK_ACTIVE_RECORD_ENABLE_CALLBACK_SETTERS')             || define('AK_ACTIVE_RECORD_ENABLE_CALLBACK_SETTERS', AK_ACTIVE_RECORD_ENABLE_AUTOMATIC_SETTERS_AND_GETTERS);
defined('AK_ACTIVE_RECORD_ENABLE_CALLBACK_GETTERS')             || define('AK_ACTIVE_RECORD_ENABLE_CALLBACK_GETTERS', AK_ACTIVE_RECORD_ENABLE_AUTOMATIC_SETTERS_AND_GETTERS);

defined('AK_ACTIVE_RECORD_ENABLE_PERSISTENCE')                  || define('AK_ACTIVE_RECORD_ENABLE_PERSISTENCE', AK_ENVIRONMENT != 'testing');
defined('AK_ACTIVE_RECORD_CACHE_DATABASE_SCHEMA')               || define('AK_ACTIVE_RECORD_CACHE_DATABASE_SCHEMA', AK_ACTIVE_RECORD_ENABLE_PERSISTENCE && AK_ENVIRONMENT != 'development');
defined('AK_ACTIVE_RECORD_CACHE_DATABASE_SCHEMA_LIFE')          || define('AK_ACTIVE_RECORD_CACHE_DATABASE_SCHEMA_LIFE', 300);
defined('AK_ACTIVE_RECORD_VALIDATE_TABLE_NAMES')                || define('AK_ACTIVE_RECORD_VALIDATE_TABLE_NAMES', true);
defined('AK_ACTIVE_RECORD_SKIP_SETTING_ACTIVE_RECORD_DEFAULTS') || define('AK_ACTIVE_RECORD_SKIP_SETTING_ACTIVE_RECORD_DEFAULTS', false);
defined('AK_NOT_EMPTY_REGULAR_EXPRESSION')                      || define('AK_NOT_EMPTY_REGULAR_EXPRESSION','/.+/');
defined('AK_EMAIL_REGULAR_EXPRESSION')                          || define('AK_EMAIL_REGULAR_EXPRESSION',"/^([a-z0-9_\-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([a-z0-9\-]+\.)+))([a-z]{2,4}|[0-9]{1,3})(\]?)$/i");
defined('AK_NUMBER_REGULAR_EXPRESSION')                         || define('AK_NUMBER_REGULAR_EXPRESSION',"/^[0-9]+$/");
defined('AK_PHONE_REGULAR_EXPRESSION')                          || define('AK_PHONE_REGULAR_EXPRESSION',"/^([\+]?[(]?[\+]?[ ]?[0-9]{2,3}[)]?[ ]?)?[0-9 ()\-]{4,25}$/");
defined('AK_DATE_REGULAR_EXPRESSION')                           || define('AK_DATE_REGULAR_EXPRESSION',"/^(([0-9]{1,2}(\-|\/|\.| )[0-9]{1,2}(\-|\/|\.| )[0-9]{2,4})|([0-9]{2,4}(\-|\/|\.| )[0-9]{1,2}(\-|\/|\.| )[0-9]{1,2})){1}$/");
defined('AK_IP4_REGULAR_EXPRESSION')                            || define('AK_IP4_REGULAR_EXPRESSION',"/^((25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])\.){3}(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])$/");
defined('AK_POST_CODE_REGULAR_EXPRESSION')                      || define('AK_POST_CODE_REGULAR_EXPRESSION',"/^[0-9A-Za-z  -]{2,9}$/");

defined('AK_HAS_AND_BELONGS_TO_MANY_CREATE_JOIN_MODEL_CLASSES') || define('AK_HAS_AND_BELONGS_TO_MANY_CREATE_JOIN_MODEL_CLASSES' ,true);
defined('AK_HAS_AND_BELONGS_TO_MANY_JOIN_CLASS_EXTENDS')        || define('AK_HAS_AND_BELONGS_TO_MANY_JOIN_CLASS_EXTENDS' , 'ActiveRecord');

defined('AK_DEFAULT_TEMPLATE_ENGINE')                           || define('AK_DEFAULT_TEMPLATE_ENGINE', 'AkSintags');
defined('AK_TEMPLATE_SECURITY_CHECK')                           || define('AK_TEMPLATE_SECURITY_CHECK', true);
defined('AK_PHP_CODE_SANITIZER_FOR_TEMPLATE_HANDLER')           || define('AK_PHP_CODE_SANITIZER_FOR_TEMPLATE_HANDLER', 'AkPhpCodeSanitizer');

@ini_set("arg_separator.output","&");
@ini_set("include_path",(AK_LIB_DIR.PATH_SEPARATOR.AK_MODELS_DIR.PATH_SEPARATOR.AK_CONTRIB_DIR.DS.'pear'.PATH_SEPARATOR.ini_get("include_path")));
@ini_set("session.name", AK_SESSION_NAME);
$ADODB_CACHE_DIR = AK_CACHE_DIR;

//AK_CLI || AK_PRODUCTION_MODE || require_once(AK_LIB_DIR.DS.'AkDevelopmentErrorHandler.php');
