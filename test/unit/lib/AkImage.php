<?php

require_once(dirname(__FILE__).'/../../fixtures/config/config.php');

require_once(AK_LIB_DIR.DS.'AkImage.php');

class Test_of_AkImage extends  AkUnitTest
{   
    function setUp()
    {
        $this->image_path = AK_TEST_DIR.DS.'fixtures'.DS.'public'.DS.'images'.DS.'akelos_framework_logo.png';
    }
    function test_image_save_as()
    {
        $PngImage = new AkImage($this->image_path);
        $this->assertEqual($PngImage->getExtension(), 'png');
        
        $PngImage->save($this->image_path.'.jpg');
        $JpgImage = new AkImage($this->image_path.'.jpg');
        $this->assertEqual($JpgImage->getExtension(), 'jpg');
        
        $PngImage = new AkImage($this->image_path);
        $PngImage->save($this->image_path.'.gif');
        $GifImage = new AkImage($this->image_path.'.gif');
        $this->assertEqual($GifImage->getExtension(), 'gif');
    }   
    
    function test_image_resize()
    {
        $Image = new AkImage();
        $Image->load($this->image_path);
        
        $this->assertEqual($Image->getWidth(), 170);
        $this->assertEqual($Image->getHeight(), 75);
        
        $Image->transform('resize',array('size'=>'50x'));
        $Image->save($this->image_path.'_50x22.jpg');
        
        $Image = new AkImage($this->image_path.'_50x22.jpg');
        $this->assertEqual($Image->getWidth(), 50);
        $this->assertEqual($Image->getHeight(), 22);
        
        
        $Image = new AkImage($this->image_path);
        $Image->transform('resize',array('size'=>'50%'));
        $Image->save($this->image_path.'_85x37.png');
        
        $Image = new AkImage($this->image_path.'_85x37.png');
        $this->assertEqual($Image->getWidth(), 85);
        $this->assertEqual($Image->getHeight(), 37);
                
        
        $Image = new AkImage($this->image_path);
        $Image->transform('resize', array('mode'=>'force','size'=>'300x300'));
        $Image->save($this->image_path.'_300x300.png');
               
        $Image = new AkImage($this->image_path.'_300x300.png');
        $this->assertEqual($Image->getWidth(), 300);
        $this->assertEqual($Image->getHeight(), 300);
        
        
        $Image = new AkImage($this->image_path);
        $Image->transform('resize', array('mode'=>'expand','size'=>'x300'));
        $Image->save($this->image_path.'_x300.png');
               
        $Image = new AkImage($this->image_path.'_x300.png');
        $this->assertEqual($Image->getWidth(), 680);
        $this->assertEqual($Image->getHeight(), 300);
        
        
        
        $Image = new AkImage($this->image_path);
        $Image->transform('resize', array('mode'=>'expand','size'=>'300x300'));
        $Image->save($this->image_path.'_680x300.png');
               
        $Image = new AkImage($this->image_path.'_680x300.png');
        $this->assertEqual($Image->getWidth(), 680);
        $this->assertEqual($Image->getHeight(), 300);
        
        
        $Image = new AkImage($this->image_path);
        $Image->transform('resize', array('mode'=>'expand','size'=>'200%'));
        $Image->save($this->image_path.'_340x150.png');
               
        $Image = new AkImage($this->image_path.'_340x150.png');
        $this->assertEqual($Image->getWidth(), 340);
        $this->assertEqual($Image->getHeight(), 150);
        
    }
}

ak_test('Test_of_AkImage');

?>
