<?php

// Core tests need to run without installing a new app
$_config_path_candidate = realpath(dirname(__FILE__).'/../../../../app_layout/config');
if($_config_path_candidate){
    define('AK_CONFIG_DIR', $_config_path_candidate);
}
unset($_config_path_candidate);

define('AK_ENABLE_URL_REWRITE',     false);
define('AK_URL_REWRITE_ENABLED',    false);

if(isset($_GET['custom_routes'])){
    define('AK_ROUTES_MAPPING_FILE', dirname(__FILE__).'/../'.str_replace('.','',$_GET['custom_routes']).'_routes.php');
}

define ('AK_SESSION_HANDLER', 1);

if(isset($_GET['expire'])){
    define('AK_SESSION_EXPIRE', (int)$_GET['expire']);
}

require_once(dirname(__FILE__).'/../config.php');


Ak::db();
AkDbSession::install();

$AkDbSession = new AkDbSession();
$AkDbSession->session_life = AK_SESSION_EXPIRE;
session_set_save_handler (
array($AkDbSession, '_open'),
array($AkDbSession, '_close'),
array($AkDbSession, '_read'),
array($AkDbSession, '_write'),
array($AkDbSession, '_destroy'),
array($AkDbSession, '_gc')
);

session_start();

if(isset($_GET['key']) && isset($_GET['value'])){
    $_SESSION[$_GET['key']] = $_GET['value'];
}elseif (isset($_GET['key'])){
    if(isset($_SESSION[$_GET['key']])){
        echo $_SESSION[$_GET['key']];
    }else{
        echo 'value not found';
    }
}

if(isset($_GET['unset'])){
    unset($_SESSION[$_GET['unset']]);
}


if(isset($_GET['open_check'])){
    echo session_id();
}

if(isset($_GET['destroy_check'])){
    session_destroy();
}

