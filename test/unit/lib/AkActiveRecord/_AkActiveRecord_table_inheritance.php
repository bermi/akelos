<?php

defined('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION') ? null : define('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION', false);
defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);

require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

class test_AkActiveRecord_table_inheritance extends  AkUnitTest
{
    function test_start()
    {
        //$this->resetFrameworkDatabaseTables();
        $this->installAndIncludeModels(array('Event', 'Concert','OpenHouseMeeting'));
    }


    function test_for_table_inheritance()
    {
        $Event = new Event(array('description'=>'Uncategorized Event'));
        $this->assertTrue($Event->save());
        
        $Concert = new Concert('description->', 'Madonna at Barcelona');
        $this->assertTrue($Concert->save());
        
        $OpenHouseMeeting = new OpenHouseMeeting('description->', 'Networking event at Akelos');
        $this->assertTrue($OpenHouseMeeting->save());
                
        $this->assertEqual($OpenHouseMeeting->get('type'), 'Open house meeting');
        
        $this->assertTrue($OpenHouseMeeting = $Event->findFirstBy('description','Networking event at Akelos'));
        
        $this->assertEqual($OpenHouseMeeting->get('description'), 'Networking event at Akelos');
        
        $this->assertEqual($OpenHouseMeeting->getType(), 'OpenHouseMeeting');
        
    }
}

Ak::test('test_AkActiveRecord_table_inheritance',true);

?>
