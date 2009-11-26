<?php

require_once(dirname(__FILE__).'/../shared/config/config.php');

AkConfig::setOption('testing_url', 'http://akelos.tests/akelos');
AkConfig::setOption('webserver_enabled', @file_get_contents(AkConfig::getOption('testing_url').'/ping.php') == 'pong');
AkConfig::setOption('memcached_enabled', AkMemcache::isServerUp());


if(AK_WEB_REQUEST && AK_REMOTE_IP != '127.0.0.1'){
    die('Web tests can only be called from localhost(127.0.0.1), you can change this beahviour in '.__FILE__);
}