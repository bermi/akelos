<?php

if(!defined('AK_BASE_DIR') && !defined('AK_FRAMEWORK_DIR'))
{
    define('AK_FRAMEWORK_DIR', realpath(dirname(__FILE__).'/../../'));
    if(is_dir(AK_FRAMEWORK_DIR.DIRECTORY_SEPARATOR.'app_layout'))
    {
        define('AK_BASE_DIR', AK_FRAMEWORK_DIR.DIRECTORY_SEPARATOR.'app_layout');
    }
}

require_once(dirname(__FILE__).'/../shared/config/config.php');

AkConfig::setOption('testing_url', 'http://akelos.tests/akelos');
AkConfig::setOption('action_controller.session', array("key" => "_myapp_session", "secret" => "c1ef4792-42c5-b484-819e-16750c71cddb"));

AkUnitTestSuite::checkIfTestingWebserverIsAccesible(array('base_path' => dirname(__FILE__)));
AkConfig::setOption('memcached_enabled', AkMemcache::isServerUp());

if(AK_WEB_REQUEST && !(AK_REMOTE_IP == '127.0.0.1' || AK_REMOTE_IP == '::1')){
    die('Web tests can only be called from localhost(127.0.0.1), you can change this beahviour in '.__FILE__);
}