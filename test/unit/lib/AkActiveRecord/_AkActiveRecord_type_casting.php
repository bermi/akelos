<?php

defined('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION') ? null : define('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION', false);
defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);

require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

class test_AkActiveRecord_type_casting extends  AkUnitTest
{
    function test_start()
    {
        $this->installAndIncludeModels(array('Tag','Post'));
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

    // Ticket #36
    function test_should_update_dates_correctly()
    {
        $params = array(
        'title' => 'Hello',
        'body' => 'Hello world!',
        'posted_on(1i)' => '2005',
        'posted_on(2i)' => '6',
        'posted_on(3i)' => '16');
        $Post =& new Post();
        $Post->setAttributes($params);
        $this->assertTrue($Post->save());
        $Post->reload();
        $this->assertEqual($Post->get('posted_on'), '2005-06-16');

    }
}

ak_test('test_AkActiveRecord_type_casting',true);

?>
