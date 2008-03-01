<?php

defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);
require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

class test_AkHasMany_Specs extends AkUnitTest
{
    /**
     * @hasMany    pictures, :dependent => 'destroy'
     * @var ActiveRecord
     */
    var $Property;
    
    /**
     * @belongsTo  property
     * @var ActiveRecord
     */
    var $Picture;
    function setUp()
    {
        $this->installAndIncludeModels(array('Property','Picture'));
    }
    
    function testDeletionFromCollectionShouldDestroyTheActiveRecord()
    {
        $Property = new Property(array('description'=>'This is a Property'));
        $Picture = $Property->picture->create(array('title'=>'Front'));
        $this->assertTrue($Property->save());
        $this->assertTrue($Picture instanceof AkActiveRecord);        
        
        $Property->picture->delete($Picture);
        
        $this->assertEqual($Property->getId(),$this->Property->find('first')->getId());
        $this->assertFalse($this->Picture->find('first'));
    }
    
    function testDestroyingShouldCascade()
    {
        $Property = new Property(array('description'=>'This is a Property'));
        $Picture = $Property->picture->create(array('title'=>'Front'));

        $Property->destroy();

        $this->assertFalse($this->Property->find('first'));
        $this->assertFalse($this->Picture->find('first'));
    }
}

ak_test('test_AkHasMany_Specs',true);

?>