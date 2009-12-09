<?php

define ('AK_SESSION_HANDLER', 1);

if(isset($_GET['expire'])){
    define('AK_SESSION_EXPIRE', (int)$_GET['expire']);
}

require_once(dirname(__FILE__).'/../config.php');

Ak::db();

if(!empty($_GET['construct'])){
    @AkDbSession::install();
    @AkAdodbCache::install();
}elseif (!empty($_GET['destruct'])){
    AkAdodbCache::uninstall();
    AkDbSession::uninstall();
    $Installer = new AkInstaller();
    $Installer->dropTable('akelos_migrations');
}

$session_handler = isset($_GET['handler'])?$_GET['handler']:null;

$session_settings = Ak::getSettings('sessions',false);
if ($session_handler !== null) {
    $session_settings['handler']['type'] = (int)$session_handler;
}
error_reporting(E_STRICT);

$SessionHandler = AkSession::lookupStore($session_settings);

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

