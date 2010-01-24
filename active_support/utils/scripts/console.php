<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

if(defined('AK_CONSOLE_MODE')){

    require_once(AK_ACTIVE_SUPPORT_DIR.DS.'base.php');
    require_once AK_CONTRIB_DIR.DS.'iphp'.DS.'iphp.php';
    iphp::main(array(
    'require'       => __FILE__,
    'prompt_header' => "Akelos PHP Framework iphp console\n"
    ));

}else{

    define('AK_CONSOLE_MODE', true);
    defined('DS')           || define('DS', DIRECTORY_SEPARATOR);
    defined('AK_BASE_DIR')  || define('AK_BASE_DIR', str_replace(DS.'akelos'.DS.'active_support'.DS.'utils'.DS.'scripts'.DS.'console.php','',__FILE__));

    $_app_config_file = AK_BASE_DIR.DS.'config'.DS.'config.php';

    if(file_exists($_app_config_file)){
        include(AK_BASE_DIR.DS.'config'.DS.'config.php');
    }else{
        include(AK_BASE_DIR.DS.'test'.DS.'shared'.DS.'config'.DS.'config.php');
    }
    defined('AK_ENVIRONMENT')           || define('AK_ENVIRONMENT', 'testing');

    require_once(AK_ACTIVE_SUPPORT_DIR.DS.'base.php');

}
