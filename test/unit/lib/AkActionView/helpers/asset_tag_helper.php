<?php
require_once('_HelpersUnitTester.php');
require_once(AK_LIB_DIR.DS.'AkActionView'.DS.'helpers'.DS.'asset_tag_helper.php');
require_once(AK_LIB_DIR.DS.'AkActionController.php');
require_once(AK_LIB_DIR.DS.'AkRequest.php');

ak_generate_mock('AkRequest');

class AssetTagHelperTests extends HelpersUnitTester
{
    function setUp()
    {
        $this->testing_url_path = $this->testing_url_path == '/' ? '' : $this->testing_url_path;
        $this->controller = &new MockAkActionController($this);
        $this->controller->asset_host = AK_ASSET_HOST;
        $this->controller->setReturnValue('urlFor','/url/for/test');
        $this->asset_tag_helper = new AssetTagHelper();
        $this->asset_tag_helper->setController($this->controller);
    }

    function test_auto_discovery_link_tag()
    {
        $this->assertEqual(
        $this->asset_tag_helper->auto_discovery_link_tag(),
        '<link href="/url/for/test" rel="alternate" title="RSS" type="application/rss+xml" />'
        );
        $this->assertEqual(
        $this->asset_tag_helper->auto_discovery_link_tag('atom'),
        '<link href="/url/for/test" rel="alternate" title="ATOM" type="application/atom+xml" />'
        );
        $this->assertEqual(
        $this->asset_tag_helper->auto_discovery_link_tag('atom', array(), array('title' => "My Atom")),
        '<link href="/url/for/test" rel="alternate" title="My Atom" type="application/atom+xml" />'
        );
        $this->assertEqual(
        $this->asset_tag_helper->auto_discovery_link_tag('atom', 'http://www.example.com', array('title' => "My Atom", 'rel' => 'foo/bar')),
        '<link href="http://www.example.com" rel="foo/bar" title="My Atom" type="application/atom+xml" />'
        );
    }

    function test_javascript_path()
    {
        $this->assertEqual($this->asset_tag_helper->javascript_path('ajax'), $this->testing_url_path.'/javascripts/ajax.js');
        $this->assertEqual($this->asset_tag_helper->javascript_path('superfx.javascript'), $this->testing_url_path.'/javascripts/superfx.javascript');
    }

    function test_javascript_include_tag()
    {
        $this->assertEqual(
        $this->asset_tag_helper->javascript_include_tag('ajax'),
        '<script src="'.$this->testing_url_path.'/javascripts/ajax.js" type="text/javascript"></script>'."\n"
        );
        $this->assertEqual(
        $this->asset_tag_helper->javascript_include_tag('ajax','superfx.javascript'),
        '<script src="'.$this->testing_url_path.'/javascripts/ajax.js" type="text/javascript"></script>'."\n".
        '<script src="'.$this->testing_url_path.'/javascripts/superfx.javascript" type="text/javascript"></script>'."\n"
        );
        $this->assertEqual(
        $this->asset_tag_helper->javascript_include_tag('marquesine',array('charset'=>'iso-8859-1')),
        '<script charset="iso-8859-1" src="'.$this->testing_url_path.'/javascripts/marquesine.js" type="text/javascript"></script>'."\n"
        );

        // Private method, should not be called by external software.
        $defaults = $this->asset_tag_helper->_get_javascript_included_defaults();
        $defaults_count = count($defaults);
        $this->assertTrue($defaults_count > 0);

        $this->asset_tag_helper->register_javascript_include_default('akelos_fx');
        $new_defaults = $this->asset_tag_helper->_get_javascript_included_defaults();
        $new_defaults_count = count($new_defaults);
        $this->assertEqual('akelos_fx',array_pop($new_defaults));
        $this->assertEqual($defaults_count+1,$new_defaults_count);

        $this->asset_tag_helper->register_javascript_include_default('another_fx');

        $this->assertTrue(strstr($this->asset_tag_helper->javascript_include_tag(), '<script src="'.$this->testing_url_path.'/javascripts/another_fx.js" type="text/javascript"></script>'."\n"));

        $this->asset_tag_helper->reset_javascript_include_default();

        $this->assertEqual($defaults_count,count($this->asset_tag_helper->_get_javascript_included_defaults()));
    }

    function test_stylesheet_path()
    {
        $this->assertEqual($this->asset_tag_helper->stylesheet_path('cool'), $this->testing_url_path.'/stylesheets/cool.css');
        $this->assertEqual($this->asset_tag_helper->stylesheet_path('cooler.style'), $this->testing_url_path.'/stylesheets/cooler.style');
    }

    function test_stylesheet_link_tag()
    {
        $this->assertEqual(
        $this->asset_tag_helper->stylesheet_link_tag('cool',array('media'=>'all')),
        '<link href="'.$this->testing_url_path.'/stylesheets/cool.css" media="all" rel="Stylesheet" type="text/css" />'."\n"
        );
        $this->assertEqual(
        $this->asset_tag_helper->stylesheet_link_tag('cool','cooler.style',array('media'=>'all')),
        '<link href="'.$this->testing_url_path.'/stylesheets/cool.css" media="all" rel="Stylesheet" type="text/css" />'."\n".
        '<link href="'.$this->testing_url_path.'/stylesheets/cooler.style" media="all" rel="Stylesheet" type="text/css" />'."\n"
        );
    }

    function test_image_tag()
    {
        $this->assertEqual(
        $this->asset_tag_helper->image_tag('summer_in_toronto',array('size' => '200x1000')),
        '<img alt="Summer in toronto" height="1000" src="'.$this->testing_url_path.'/images/summer_in_toronto.png" width="200" />'
        );
        $this->assertEqual(
        $this->asset_tag_helper->image_tag('summer_in_toronto.jpg',array('alt' => 'akelos alt')),
        '<img alt="akelos alt" src="'.$this->testing_url_path.'/images/summer_in_toronto.jpg" />'
        );
        $this->assertEqual(
        $this->asset_tag_helper->image_tag('http://www.example.com/pic/logo.png'),
        '<img alt="Logo" src="http://www.example.com/pic/logo.png" />'
        );
        $this->assertEqual(
        $this->asset_tag_helper->image_tag('http://www.example.com/pic/logo'),
        '<img alt="" src="http://www.example.com/pic/logo" />'
        );
    }

    function test_image_path()
    {
        $this->assertEqual($this->asset_tag_helper->image_path('photo'),$this->testing_url_path.'/images/photo.png');
        $this->assertEqual($this->asset_tag_helper->image_path('photo.jpg'),$this->testing_url_path.'/images/photo.jpg');
    }

    function test_compute_public_path()
    {
        $this->assertEqual($this->asset_tag_helper->_compute_public_path('test','javascripts','js'), $this->testing_url_path.'/javascripts/test.js');
        $this->assertEqual($this->asset_tag_helper->_compute_public_path('http://www.example.com/logo.png'), 'http://www.example.com/logo.png');
    }

    function test_stylesheet_for_current_controller()
    {
        $controller =& new MockAkActionController($this);
        $controller->asset_host = AK_ASSET_HOST;
        $controller->setReturnValue('urlFor','/url/for/test');
        $controller->setReturnValue('getControllerName','valid_controller');
        $controller->setReturnValue('urlFor','/url/for/test');
        
        $asset_tag_helper =& new AssetTagHelper();
        $asset_tag_helper->setController($controller);

        $this->assertEqual(
        $asset_tag_helper->stylesheet_for_current_controller(),
        '<link href="'.$this->testing_url_path.'/stylesheets/valid_controller.css" media="screen" rel="Stylesheet" type="text/css" />'."\n"
        );


        $controller = &new MockAkActionController($this);
        $controller->asset_host = AK_ASSET_HOST;
        $controller->setReturnValue('urlFor','/url/for/test');
        $asset_tag_helper = new AssetTagHelper();
        $asset_tag_helper->setController($controller);
        $controller->setReturnValue('getControllerName','non_valid_controller');
        $controller->setReturnValue('urlFor','/url/for/test');

        $this->assertEqual($asset_tag_helper->stylesheet_for_current_controller(), '');
    }

    function test_javascript_for_current_controller()
    {
        $controller = &new MockAkActionController($this);
        $controller->asset_host = AK_ASSET_HOST;
        $controller->setReturnValue('urlFor','/url/for/test');
        $asset_tag_helper = new AssetTagHelper();
        $asset_tag_helper->setController($controller);
        $controller->setReturnValue('getControllerName','valid_controller');
        $controller->setReturnValue('urlFor','/url/for/test');

        $this->assertEqual(
        $asset_tag_helper->javascript_for_current_controller(),
        '<script src="'.$this->testing_url_path.'/javascripts/valid_controller.js" type="text/javascript"></script>'."\n"
        );


        $controller = &new MockAkActionController($this);
        $controller->asset_host = AK_ASSET_HOST;
        $controller->setReturnValue('urlFor','/url/for/test');
        $asset_tag_helper = new AssetTagHelper();
        $asset_tag_helper->setController($controller);
        $controller->setReturnValue('getControllerName','non_valid_controller');
        $controller->setReturnValue('urlFor','/url/for/test');

        $this->assertEqual($asset_tag_helper->javascript_for_current_controller(), '');
    }
}

ak_test('AssetTagHelperTests');

?>