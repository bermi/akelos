<?php

require_once(AK_LIB_DIR.DS.'AkUnitTest'.DS.'AkTestApplication.php');
require_once(AK_LIB_DIR.DS.'AkActionView'.DS.'helpers'.DS.'url_helper.php');

class Test_AkActionView_helpers_url_helper_modify_current_url extends AkTestApplication
{
    public function test_modify_current_url()
    {
        $this->get('/modify');
        $this->assertTextMatch('index');
        
        $newUrl1=$this->getController()->url_helper->modify_current_url(array('action'=>'test1'));
        $this->assertEqual($newUrl1,'/modify/test1/');
        $this->get($newUrl1);
        $this->assertTextMatch('test1');
        
        $newUrl2=$this->getController()->url_helper->modify_current_url(array('action'=>'testid'));
        $this->assertEqual($newUrl2,'/modify/testid/');
        $this->get($newUrl2);
        $this->assertTextMatch('testid:');
        
        $newUrl3=$this->getController()->url_helper->modify_current_url(array('action'=>'testid','id'=>1));
        $this->assertEqual($newUrl3,'/modify/testid/1/');
        $this->get($newUrl3);
        $this->assertTextMatch('testid:1');
    }
}
?>