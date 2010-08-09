<?php

require_once(dirname(__FILE__).'/../config.php');

class HasManySpecifications_TestCase extends ActiveRecordUnitTest
{
    /**
     * @hasMany    pictures, :dependent => 'destroy'
     * @var ActiveRecord
     */
    public $Property;

    /**
     * @belongsTo  property
     * @var ActiveRecord
     */
    public $Picture;
    public function setUp() {
        $this->installAndIncludeModels(array('Property','Picture', 'Thumbnail'));
    }

    public function test_deletion_from_collection_should_destroy_the_active_record() {
        $Property = new Property(array('description'=>'This is a Property'));
        $Picture = $Property->picture->create(array('title'=>'Front'));
        $this->assertTrue($Property->save());

        $this->assertTrue($Picture instanceof AkActiveRecord);

        $Property->picture->delete($Picture);

        $StoredProperty = $this->Property->find('first');
        $this->assertEqual($Property->getId(), $StoredProperty->getId());
        $this->assertFalse($this->Picture->find('first', array('default' => false)));
    }

    public function test_destroying_should_cascade() {
        $Property = new Property(array('description'=>'This is a Property'));
        $Picture = $Property->picture->create(array('title'=>'Front'));

        $Property->destroy();

        $this->assertFalse($this->Property->find('first', array('default' => false)));
        $this->assertFalse($this->Picture->find('first', array('default' => false)));
    }
}

ak_test_case('HasManySpecifications_TestCase');

