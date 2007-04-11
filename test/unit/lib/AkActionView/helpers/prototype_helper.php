<?php

require_once('_HelpersUnitTester.php');
require_once(AK_LIB_DIR.DS.'AkActionView'.DS.'helpers'.DS.'prototype_helper.php');
require_once(AK_LIB_DIR.DS.'AkActionController.php');
require_once(AK_LIB_DIR.DS.'AkRequest.php');

class PrototypeHelperTests extends HelpersUnitTester
{
    function test_setup()
    {
        $Controller = &new AkActionController();
        $Controller->Request =& new AkRequest();
        $Controller->controller_name = 'test';
        $Controller->instantiateHelpers();
        
        $this->PrototypeHelper =& $Controller->prototype_helper;
    }
    
    /**
     * @todo Add all the tests for prototype helper
     */
    
    function test_submit_to_remote()
    {
        $this->assertEqual("<input name=\"More beer!\" onclick=\"new Ajax.Updater('empty_bottle', 'http://www.example.com/', {parameters:Form.serialize(this.form)}); return false;\" type=\"button\" value=\"1000000\" />",

        $this->PrototypeHelper->submit_to_remote("More beer!", "1000000", array('update' => 'empty_bottle','url'=>'http://www.example.com/')));
    }
}

Ak::test('PrototypeHelperTests');

?>