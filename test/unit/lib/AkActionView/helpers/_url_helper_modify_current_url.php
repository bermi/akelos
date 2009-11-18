<?php

require_once(dirname(__FILE__).'/../../../../fixtures/config/config.php');

class UrlHelper_modify_current_url_TestCase extends AkTestApplication
{
    public function __construct()
    {
        parent::__construct();
        $this->rebaseAppPaths();
    }

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

ak_test_case('UrlHelper_modify_current_url_TestCase');