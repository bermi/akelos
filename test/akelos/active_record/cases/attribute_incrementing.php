<?php

require_once(dirname(__FILE__).'/../config.php');

class AttributeIncrementing_TestCase extends ActiveRecordUnitTest
{
    public function setUp() {
        $this->installAndIncludeModels(array('Post'));
    }

    public function test_should_increment_default_null_value_of_numeric_attribute() {
        $Post = new Post(array('title'=>'title of a Post','body'=>'The Body'));

        $this->assertNull($Post->hip_factor);
        $this->assertEqual($Post->incrementAttribute('hip_factor'),1);
    }

    public function test_should_increment_numeric_attribute() {
        $Post = new Post(array('title'=>'title of a Post','body'=>'The Body'));

        $this->assertEqual($Post->incrementAttribute('comments_count'),1);
        $this->assertEqual($Post->incrementAttribute('comments_count'),2);
    }

    public function test_should_decrement_null_value_of_numeric_attribute() {
        $Post = new Post(array('title'=>'title of a Post','body'=>'The Body'));

        $this->assertNull($Post->hip_factor);
        $this->assertEqual($Post->decrementAttribute('hip_factor'),-1);
    }

    public function test_should_decrement_numeric_attribute() {
        $Post = new Post(array('title'=>'title of a Post','body'=>'The Body'));

        $this->assertEqual($Post->decrementAttribute('comments_count'),-1);
        $this->assertEqual($Post->decrementAttribute('comments_count'),-2);
    }

    public function test_should_increment_and_save_numeric_attribute() {
        $Post = $this->Post->create(array('title'=>'title of a Post','body'=>'The Body'));
        $Loaded = $Post->find($Post->getId());
        $this->assertNull($Loaded->hip_factor);

        $Post->incrementAndSaveAttribute('hip_factor');
        $Reloaded = $Post->find($Post->getId());
        $this->assertEqual($Reloaded->hip_factor,1);
    }

    public function test_should_decrement_and_save_numeric_attribute() {
        $Post = $this->Post->create(array('title'=>'title of a Post','body'=>'The Body'));

        $Post->decrementAndSaveAttribute('hip_factor');
        $Reloaded = $Post->find($Post->getId());
        $this->assertEqual($Reloaded->hip_factor,-1);
    }

    public function test_should_not_save_when_invalid() {
        $Post = $this->Post->create(array('title'=>'title of a Post','body'=>'The Body'));

        $this->assertFalse($Post->decrementAndSaveAttribute('comments_count'));

        $Reload = $this->Post->find($Post->getId());
        $this->assertEqual($Reload->comments_count,0);
    }

}

ak_test_case('AttributeIncrementing_TestCase');