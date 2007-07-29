<?php

defined('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION') ? null : define('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION', false);
defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);

require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

class test_AkActiveRecord_type_casting extends  AkUnitTest
{
    function test_start()
    {
        $this->installAndIncludeModels(array('Tag'));
    }

    // Ticket #21
    function test_should_store_zero_strings_as_intergers()
    {
        $Tag = new Tag(array('name'=>'Ticket #21'));
        $this->assertTrue($Tag->save());
        $this->assertEqual($Tag->get('score'), 100);
        
        $Tag->setAttributes(array('score' => '0'));
        $this->assertTrue($Tag->save());
        
        $Tag =& $Tag->find($Tag->id);
        $this->assertIdentical($Tag->get('score'), 0);
        
    }
}

ak_test('test_AkActiveRecord_type_casting',true);

?>
