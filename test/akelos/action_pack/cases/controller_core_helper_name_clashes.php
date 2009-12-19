<?php

require_once(dirname(__FILE__).'/../config.php');


class TextController extends AkActionController{
}

class Controller_core_helper_name_clashes_TestCase extends AkWebTestCase
{
    public function test_setup() {
        $TestSetup = new AkUnitTest();
        $TestSetup->rebaseAppPaths();
        $TestSetup->installAndIncludeModels(array('Text' => 'id, name'));
    }

    public function __destruct() {
        $TestSetup = new AkUnitTest();
        $TestSetup->dropTables('all');
    }

    public function test_should_instantiate_controller_model_and_respect_core_text_helper() {
        $TextController = new TextController();
        $TextController->instantiateIncludedModelClasses();
        $this->assertTrue($TextController->Text instanceof AkActiveRecord);
        $this->assertTrue($TextController->text_helper instanceof AkTextHelper);
        $this->assertTrue($TextController->ak_text_helper instanceof AkTextHelper);
    }
}

ak_test_case('Controller_core_helper_name_clashes_TestCase');

