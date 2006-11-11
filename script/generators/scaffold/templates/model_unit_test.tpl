<?='<?php'?>

require_once('..'.DIRECTORY_SEPARATOR.'config.php');

require_once(AK_LIB_DIR.DS.'AkActiveRecord.php');
require_once(AK_MODELS_DIR.DS.'<?=$singular_name?>.php');

class Test_of_<?=$model_name?> extends UnitTestCase
{
    var $fixtures = '<?=$plural_name?>';
    
    // Replace this with your real tests.
    function test_implement_tests_please()
    {
        $this->assertTrue(false,'Unit test for <?=$model_name?> not implemented');
    }
    
}

if(!defined("ALL_TESTS_CALL")){
	$test = &new Test_of_<?=$model_name?>();
	$test->run(new HtmlReporter());
}


?>
