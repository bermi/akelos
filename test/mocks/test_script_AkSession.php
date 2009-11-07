<?php

define ('AK_DEBUG', 0);
define ('AK_SESSION_HANDLER', 1);

if(isset($_GET['expire'])){
    define('AK_SESSION_EXPIRE', (int)$_GET['expire']);
}

define('AK_ENVIRONMENT', 'testing');

defined('AK_TEST_DIR') ? null : define('AK_TEST_DIR', str_replace(DIRECTORY_SEPARATOR.'mocks'.DIRECTORY_SEPARATOR.'test_script_AkDbSession.php','',__FILE__));
defined('AK_APP_DIR') ? null : define('AK_APP_DIR', AK_TEST_DIR.DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'app');

define('AK_SITE_URL_SUFFIX',str_replace(array(join(DIRECTORY_SEPARATOR,array_diff((array)@explode(DIRECTORY_SEPARATOR,AK_TEST_DIR),
(array)@explode('/',@$_SERVER['REQUEST_URI']))),DIRECTORY_SEPARATOR),array('','/'),AK_TEST_DIR));

include('..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'config.php');

require_once(AK_LIB_DIR.DS.'Ak.php');
require_once(AK_LIB_DIR.DS.'AkInflector.php');
Ak::db();

require_once(AK_LIB_DIR.DS.'AkSession.php');

$session_handler = isset($_GET['handler'])?$_GET['handler']:null;

$session_settings = Ak::getSettings('sessions',false);
if ($session_handler !== null) {
    $session_settings['handler']['type'] = (int)$session_handler;
}
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



?>