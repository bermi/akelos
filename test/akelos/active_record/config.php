<?php

require_once(dirname(__FILE__).'/../config.php');

class ActiveRecordUnitTest extends AkUnitTest
{
    public $skip_fixtures = true;
    public function __construct()
    {
        AkConfig::setDir('suite', dirname(__FILE__));
        $this->rebaseAppPaths();
        @Ak::rmdir_tree(AK_TMP_DIR);
        @mkdir(AK_TMP_DIR);
    }

    public function __destruct()
    {
        parent::__destruct();
        @Ak::rmdir_tree(AK_TMP_DIR);
        @mkdir(AK_TMP_DIR);
        $this->dropTables('all');
    }
}

