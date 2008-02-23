<?php

defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);
require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

class test_HasOne_cascading_destroy extends AkUnitTest
{
    #thumbnail belongsTo picture
    /**
     * hasOne    main_thumbnail, dependent => true
     * @var ActiveRecord
     */
    var $Picture;
    function setUp()
    {
        $this->installAndIncludeModels(array('Picture','Thumbnail'));
        $Picture =& $this->Picture->create(array('title'=>'This is not a picture'));
        $Picture->main_thumbnail->create(array('caption'=>'It cant have a thumbnail'));
    }
         
    function test_ensure_we_have_the_setup_right()
    {
        $Picture =& $this->Picture->find('first',array('include'=>'main_thumbnail'));
        $this->assertEqual(1,$Picture->main_thumbnail->photo_id);
        
        $Thumb =& $this->Thumbnail->find('first');
        $this->assertEqual(1,$Thumb->photo_id);
        #var_dump($this->Picture->_db->select('SELECT * FROM thumbnails'));
    }
    
    function test_should_destroy_the_belonging_thumbnail()
    {
        $Picture =& $this->Picture->find('first',array('include'=>'main_thumbnail'));
        $Picture->destroy();
        
        $this->assertFalse($this->Thumbnail->find('first'));
    }
    
    function test_should_destroy_the_thumbnail_even_when_not_loaded()
    {
        $Picture =& $this->Picture->find('first');
        $Picture->destroy();
        
        $this->assertFalse($this->Thumbnail->find('first'),'Issue #125');
    }

}

ak_test('test_HasOne_cascading_destroy',true);

?>