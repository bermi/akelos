<?php  echo '<?php'?>

defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);
require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

// You can execute this test by running 
// ./script/test app/models/<?php  echo AkInflector::underscore($class_name)?>.php

class <?php  echo $model_name?>Test extends  AkUnitTest
{
    function test_setup()
    {
        $this->installAndIncludeModels('<?php  echo $model_name?>');
    }

    function test_<?php  echo $model_name?>()
    {
        $this->assertTrue(false,'Unit test for <?php  echo $model_name?> not implemented');
    }
}

ak_test('<?php  echo $model_name?>Test',true);

?>
