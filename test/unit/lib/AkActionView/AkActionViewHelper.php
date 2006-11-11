<?php

defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);

require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

class test_AkActionViewHelper extends  UnitTestCase
{
}


Ak::test('test_AkActionViewHelper',true);

?>
