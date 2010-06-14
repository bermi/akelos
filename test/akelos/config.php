<?php

require_once(dirname(__FILE__).'/../shared/config/config.php');

AkConfig::setOption('testing_url', 'http://akelos.tests/akelos');
AkConfig::setOption('action_controller.session', array("key" => "_myapp_session", "secret" => "c1ef4792-42c5-b484-819e-16750c71cddb"));

AkUnitTestSuite::checkIfTestingWebserverIsAccesible(array('base_path' => dirname(__FILE__)));
AkConfig::setOption('memcached_enabled', AkMemcache::isServerUp());


if(AK_WEB_REQUEST && AK_REMOTE_IP != '127.0.0.1'){
    die('Web tests can only be called from localhost(127.0.0.1), you can change this beahviour in '.__FILE__);
}