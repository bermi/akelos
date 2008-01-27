<?php

defined('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION') ? null : define('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION', false);
defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);

require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

class Schedule extends ActiveRecord
{
    var $belongs_to = 'event';
    
    //ugly PHP4 hack
    function __construct()
    {
        $this->setModelName('Schedule');
        $this->setTableName('schedules');
        $attributes = (array)func_get_args();
        $this->init($attributes);
     }
}

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
    
    function test_find_should_return_appropriate_models()
    {
        $Events = $this->Event->find('all');
        $expected = array(1 => 'Event', 2 => 'Concert', 3 => 'OpenHouseMeeting');
        foreach ($Events as $event){
            $this->assertEqual($event->getType(),$expected[$event->getId()]);
        }
    }
    
    function test_inheritance_should_lazy_load_right_model()
    {
        $this->installAndIncludeModels(array('Schedule'=>'id,name,event_id'));
        $this->Schedule->create(array('name'=>'to OpenHouseMeeting','event_id'=>3));
        $this->Schedule->create(array('name'=>'to Event','event_id'=>1));
        $this->Schedule->create(array('name'=>'to Concert','event_id'=>2));
        
        $scheds = $this->Schedule->find('all');
        foreach ($scheds as $schedule){
            $schedule->event->load();
        }
        
        $expected = array(1=>'OpenHouseMeeting',2=>'Event',3=>'Concert');
        foreach ($scheds as $schedule){
            $this->assertEqual($schedule->event->getType(),$expected[$schedule->getId()]);
        }
    }
}

ak_test('test_AkActiveRecord_table_inheritance',true);

?>
