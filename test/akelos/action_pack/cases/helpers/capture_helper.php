<?php

require_once(dirname(__FILE__).'/../helpers.php');

class CaptureHelper_TestCase extends HelperUnitTest
{   
    public function setUp()
    {
        $this->controller = new AkActionController();
        $this->controller->Request = new MockAkRequest($this);
        $this->controller->controller_name = 'test';

        $this->capture_helper = $this->controller->capture_helper;
    }

    public function test_begin_without_label()
    {
        $str = "test CaptureHelper #1";
        $this->capture_helper->begin();
        echo $str;
        $this->assertEqual($this->capture_helper->end(), $str);
        $globals = AkActionView::getGlobals();
        $this->assertEqual(isset($globals['content_for_']) ? $globals['content_for_'] : null, null);
    }

    public function test_begin_with_label()
    {
        $str = "test CaptureHelper #2";
        $this->capture_helper->begin('test_2');
        echo $str;
        $this->assertEqual($this->capture_helper->end(), $str);
        $globals = AkActionView::getGlobals();
        $this->assertEqual($globals['content_for_test_2'], $str);
    }

    public function test_content_for()
    {
        $str = "test CaptureHelper content_for";
        $this->capture_helper->content_for('content_for');
        echo $str;
        $this->assertEqual($this->capture_helper->end(), $str);
        $globals = AkActionView::getGlobals();
        $this->assertEqual($globals['content_for_content_for'], $str);
    }

    public function test_concatenation()
    {
        $this->capture_helper->content_for('concatenation');
        echo 'A';
        $this->assertEqual($this->capture_helper->end(), 'A');

        $this->capture_helper->content_for('concatenation');
        echo 'B';
        $this->assertEqual($this->capture_helper->end(), 'B');

        $globals = AkActionView::getGlobals();
        $this->assertEqual($globals['content_for_concatenation'], 'AB');
    }
}


ak_test_case('CaptureHelper_TestCase');