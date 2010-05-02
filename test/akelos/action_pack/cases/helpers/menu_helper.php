<?php

require_once(dirname(__FILE__).'/../helpers.php');

class MenuHelper_TestCase extends HelperUnitTest
{
    public function setUp()
    {
        $this->controller = new AkActionController();
        $this->controller->Request = new MockAkRequest($this);
        $this->controller->controller_name = 'test';
        
        $this->menu_helper = $this->controller->menu_helper;
    }
    
    public function test_should_get_controller_methods()
    {
        $controller_file_name = 'authentication_controller.php';
        require_once(AkConfig::getDir('controllers').DS.$controller_file_name);
        $this->assertTrue(in_array('authenticate',$this->menu_helper->_get_this_class_methods('AuthenticationController')));
    }

    public function test_should_get_all_controllers_with_their_actions()
    {
        $available_controllers = (array)AkFileSystem::dir(AkConfig::getDir('controllers'), array('dirs'=>false));
        $got = $this->menu_helper->_get_default_full_menu();
        foreach ($available_controllers as $controller_filename){
            $controller_name = str_replace('_controller.php','',$controller_filename);
            $this->assertTrue(isset($got[$controller_name]));
        }
        $this->assertTrue(in_array('authenticate',$got['authentication']));
    }
    
    public function tests_menu_for_controllers()
    {
        $this->assertEqual(
            $this->menu_helper->menu_for_controllers(array('advertiser' => array('buy', 'partial_in_template'), 'locale_detection' => 'session', 'page' => 'setup')),
            file_get_contents(HelperUnitTest::getFixturesDir().DS.'menu_helper_limit.txt')
        );

        $this->assertEqual(
            $this->menu_helper->menu_for_controllers(array('unavailable' => array('foo', 'bar'))),
            '<div id="menu"></div>'
        );

        $this->assertEqual(
            $this->menu_helper->menu_for_controllers(array('unavailable' => array('foo', 'bar')), "custom_menu_id"),
            '<div id="custom_menu_id"></div>'
        );

        $this->assertEqual(
            $this->menu_helper->menu_for_controllers(array('advertiser' => array('buy', 'partial_in_template'), 'locale_detection' => 'session', 'page' => 'setup'), 'menu', 'current', ''),
            file_get_contents(HelperUnitTest::getFixturesDir().DS.'menu_helper_limit_no_title_tag.txt')
        );

        $this->assertEqual(
            $this->menu_helper->menu_for_controllers(array('advertiser' => array('buy', 'partial_in_template'), 'locale_detection' => 'session', 'page' => 'setup'), 'menu', 'current', 'p'),
            file_get_contents(HelperUnitTest::getFixturesDir().DS.'menu_helper_limit_title_tag_p.txt')
        );

        $this->controller->controller_name = 'Advertiser';

        $this->assertEqual(
            $this->menu_helper->menu_for_controllers(array('advertiser' => array('buy', 'partial_in_template'), 'locale_detection' => 'session', 'page' => 'setup')),
            file_get_contents(HelperUnitTest::getFixturesDir().DS.'menu_helper_limit_current.txt')
        );

        $this->assertEqual(
            $this->menu_helper->menu_for_controllers(array('advertiser' => array('buy', 'partial_in_template'), 'locale_detection' => 'session', 'page' => 'setup'), 'menu', 'selected'),
            file_get_contents(HelperUnitTest::getFixturesDir().DS.'menu_helper_limit_current_not_default.txt')
        );

        $this->assertEqual(
            $this->menu_helper->menu_for_controllers(array('advertiser' => array('buy', 'partial_in_template'), 'locale_detection' => 'session', 'page' => 'setup'), 'menu', 'selected', ''),
            file_get_contents(HelperUnitTest::getFixturesDir().DS.'menu_helper_limit_current_not_default_no_title.txt')
        );
        
    }
}

ak_test_case('MenuHelper_TestCase');

