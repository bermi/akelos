<?php

require_once(AK_LIB_DIR.DS.'AkUnitTestSuite.php');

class ActionMailerTestSuite extends AkUnitTestSuite {
    public $partial_tests = array('AkActionMailer');
    public $baseDir = '';
    public $title = 'ActionMailer';
}
