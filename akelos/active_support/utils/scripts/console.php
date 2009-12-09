<?php

defined('AK_RECODE_UTF8_ON_CONSOLE_TO') || define('AK_RECODE_UTF8_ON_CONSOLE_TO', false);
define('AK_PROMT',fopen("php://stdin","r"));

require_once(AK_ACTIVE_SUPPORT_DIR.DS.'base.php');
require_once AK_CONTRIB_DIR.DS.'iphp'.DS.'iphp.php';
iphp::main(array(
    'require' => AK_ACTIVE_SUPPORT_DIR.DS.'base.php'
));
