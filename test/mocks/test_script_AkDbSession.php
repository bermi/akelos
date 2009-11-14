<?php

define ('AK_SESSION_HANDLER', 1);

if(isset($_GET['expire'])){
    define('AK_SESSION_EXPIRE', (int)$_GET['expire']);
}

require_once(dirname(__FILE__).'/../fixtures/config/config.php');

Ak::db();

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
