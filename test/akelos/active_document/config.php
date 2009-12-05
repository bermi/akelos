<?php

require_once(dirname(__FILE__).'/../config.php');

class ActiveDocumentUnitTest extends AkUnitTest
{
    public function __construct()
    {
        AkConfig::setDir('suite', dirname(__FILE__));
        $this->rebaseAppPaths();
    }

    public function __destruct()
    {
        parent::__destruct();
    }
}

AkConfig::setOption('document_connections', array(
array('type' => 'mongo_db')
));