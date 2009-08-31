<?php

require_once('_HelpersUnitTester.php');
require_once(AK_LIB_DIR.DS.'AkActionView'.DS.'helpers'.DS.'capture_helper.php');
require_once(AK_LIB_DIR.DS.'AkActionView.php');
require_once(AK_LIB_DIR.DS.'AkActionController.php');
require_once(AK_LIB_DIR.DS.'AkRequest.php');

ak_generate_mock('AkRequest');


class CaptureHelperTests extends HelpersUnitTester
{
    public function setUp()
    {
        $this->controller = new AkActionController();
        $this->controller->Request = new MockAkRequest($this);
        $this->controller->controller_name = 'test';
        $this->controller->instantiateHelpers();

        $this->capture_helper =& $this->controller->capture_helper;
    }

    public function test_begin_without_label()
    {
        $str = "test CaptureHelper #1";
        $this->capture_helper->begin();
        echo $str;
        $this->assertEqual($this->capture_helper->end(), $str);
        $globals = AkActionView::_getGlobals();
        $this->assertEqual(isset($globals['content_for_']) ? $globals['content_for_'] : null, null);
    }

    public function test_begin_with_label()
    {
        $str = "test CaptureHelper #2";
        $this->capture_helper->begin('test_2');
        echo $str;
        $this->assertEqual($this->capture_helper->end(), $str);
        $globals = AkActionView::_getGlobals();
        $this->assertEqual($globals['content_for_test_2'], $str);
    }

    public function test_content_for()
    {
        $str = "test CaptureHelper content_for";
        $this->capture_helper->content_for('content_for');
        echo $str;
        $this->assertEqual($this->capture_helper->end(), $str);
        $globals = AkActionView::_getGlobals();
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

        $globals = AkActionView::_getGlobals();
        $this->assertEqual($globals['content_for_concatenation'], 'AB');
    }
}


ak_test('CaptureHelperTests');

?>