<?php

require_once(dirname(__FILE__).'/../config.php');

class ActiveRecordUnitTest extends AkUnitTest
{
    public $skip_fixtures = true;
    public function __construct() {
        AkConfig::setDir('suite', dirname(__FILE__));
        $this->rebaseAppPaths();
        AkUnitTestSuite::cleanupTmpDir();
    }

    public function __destruct() {
        parent::__destruct();
        AkUnitTestSuite::cleanupTmpDir();
        $this->dropTables('all');
    }
}

