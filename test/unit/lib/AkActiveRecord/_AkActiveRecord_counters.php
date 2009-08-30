<?php

defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);
require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

class test_AkActiveRecord_counters extends  AkUnitTest
{

    public function setUp()
    {
        $this->installAndIncludeModels(array('Post','Comment'));
        $Post = $this->Post->create(array('title'=>'A Title','body'=>'and a body'));
        $this->PostId = $Post->getId();
    }


    public function test_counter_should_be_default_zero()
    {
        $Post = $this->Post->find($this->PostId);
        $counter = $Post->comments_count;

        $this->assertEqual($counter,0);
        $this->assertNotNull($counter); // !
    }

    public function test_should_increment_counter_by_one()
    {
        $this->assertTrue($this->Post->incrementCounter('comments_count',$this->PostId));

        $Post = $this->Post->find($this->PostId);
        $counter = $Post->comments_count;

        $this->assertEqual($counter,1);
    }

    public function test_should_increment_counter_by_delta()
    {
        $this->assertTrue($this->Post->incrementCounter('comments_count',$this->PostId,50));

        $Post = $this->Post->find($this->PostId);
        $counter = $Post->comments_count;

        $this->assertEqual($counter,50);
    }

    public function test_should_increment_counter_multiple_times()
    {
        $this->assertTrue($this->Post->incrementCounter('comments_count',$this->PostId));
        $this->assertTrue($this->Post->incrementCounter('comments_count',$this->PostId));
        $this->assertTrue($this->Post->incrementCounter('comments_count',$this->PostId));

        $Post = $this->Post->find($this->PostId);
        $counter = $Post->comments_count;

        $this->assertEqual($counter,3);
    }

    public function test_should_decrement_counter_by_one()
    {
        $this->assertTrue($this->Post->decrementCounter('comments_count',$this->PostId));

        $Post = $this->Post->find($this->PostId);
        $counter = $Post->comments_count;

        $this->assertEqual($counter,-1);
    }

    public function test_should_decrement_counter_by_delta()
    {
        $this->assertTrue($this->Post->decrementCounter('comments_count',$this->PostId,50));

        $Post = $this->Post->find($this->PostId);
        $counter = $Post->comments_count;

        $this->assertEqual($counter,-50);
    }

    public function test_should_decrement_counter_multiple_times()
    {
        $this->assertTrue($this->Post->decrementCounter('comments_count',$this->PostId));
        $this->assertTrue($this->Post->decrementCounter('comments_count',$this->PostId));
        $this->assertTrue($this->Post->decrementCounter('comments_count',$this->PostId));

        $Post = $this->Post->find($this->PostId);
        $counter = $Post->comments_count;

        $this->assertEqual($counter,-3);
    }





}
ak_test('test_AkActiveRecord_counters', true);

?>