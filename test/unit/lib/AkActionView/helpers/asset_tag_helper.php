<?php

require_once('_HelpersUnitTester.php');
require_once(AK_LIB_DIR.DS.'AkActionView'.DS.'helpers'.DS.'asset_tag_helper.php');


class AssetTagHelperTests extends HelpersUnitTester 
{    
    function test_for_asset_tag_helpers()
    {
        $this->testing_url_path = $this->testing_url_path == '/' ? '' : $this->testing_url_path;
        $Controller = &new MockAkActionController($this);
        $Controller->setReturnValue('urlFor','/url/for/test');
        $asset_tag = new AssetTagHelper();
        $asset_tag->setController($Controller);
        
        $this->assertEqual($asset_tag->_compute_public_path('test','javascripts','js'),$this->testing_url_path.'/javascripts/test.js');
        $this->assertEqual($asset_tag->_compute_public_path('http://www.example.com/logo.png'),'http://www.example.com/logo.png');

        $this->assertEqual($asset_tag->image_path('photo'),$this->testing_url_path.'/images/photo.png');
        $this->assertEqual($asset_tag->image_path('photo.jpg'),$this->testing_url_path.'/images/photo.jpg');

        $this->assertEqual($asset_tag->image_tag('summer_in_toronto',array('size'=>'200x1000')),
        '<img alt="Summer in toronto" height="1000" src="'.$this->testing_url_path.'/images/summer_in_toronto.png" width="200" />');

        $this->assertEqual($asset_tag->stylesheet_path('cool'),$this->testing_url_path.'/stylesheets/cool.css');
        $this->assertEqual($asset_tag->stylesheet_path('cooler.style'),$this->testing_url_path.'/stylesheets/cooler.style');

        $this->assertEqual($asset_tag->stylesheet_link_tag('cool',array('media'=>'all')),
        '<link href="'.$this->testing_url_path.'/stylesheets/cool.css" media="all" rel="Stylesheet" type="text/css" />'."\n");

        $this->assertEqual($asset_tag->stylesheet_link_tag('cool','cooler.style',array('media'=>'all')),
        '<link href="'.$this->testing_url_path.'/stylesheets/cool.css" media="all" rel="Stylesheet" type="text/css" />'."\n".
        '<link href="'.$this->testing_url_path.'/stylesheets/cooler.style" media="all" rel="Stylesheet" type="text/css" />'."\n");


        $this->assertEqual($asset_tag->javascript_path('ajax'),$this->testing_url_path.'/javascripts/ajax.js');
        $this->assertEqual($asset_tag->javascript_path('superfx.javascript'),$this->testing_url_path.'/javascripts/superfx.javascript');

        $this->assertEqual($asset_tag->javascript_include_tag('ajax'),'<script src="'.$this->testing_url_path.'/javascripts/ajax.js" type="text/javascript"></script>'."\n");
        $this->assertEqual($asset_tag->javascript_include_tag('ajax','superfx.javascript'),
        '<script src="'.$this->testing_url_path.'/javascripts/ajax.js" type="text/javascript"></script>'."\n".
        '<script src="'.$this->testing_url_path.'/javascripts/superfx.javascript" type="text/javascript"></script>'."\n");
        $this->assertEqual($asset_tag->javascript_include_tag('marquesine',array('charset'=>'iso-8859-1')),'<script charset="iso-8859-1" src="'.$this->testing_url_path.'/javascripts/marquesine.js" type="text/javascript"></script>'."\n");

        $defaults = $asset_tag->_get_javascript_included_defaults();
        $defaults_count = count($defaults);
        $this->assertTrue($defaults_count > 0);

        $asset_tag->register_javascript_include_default('akelos_fx');
        $new_defaults = $asset_tag->_get_javascript_included_defaults();
        $new_defaults_count = count($new_defaults);
        $this->assertEqual('akelos_fx',array_pop($new_defaults));
        $this->assertEqual($defaults_count+1,$new_defaults_count);

        $asset_tag->register_javascript_include_default('another_fx');

        $this->assertTrue(strstr($asset_tag->javascript_include_tag(),'<script src="'.$this->testing_url_path.'/javascripts/another_fx.js" type="text/javascript"></script>'."\n"));

        $asset_tag->reset_javascript_include_default();

        $this->assertEqual($defaults_count,count($asset_tag->_get_javascript_included_defaults()));

        $this->assertEqual($asset_tag->auto_discovery_link_tag(),'<link href="/url/for/test" rel="alternate" title="RSS" type="application/rss+xml" />');
    }
}

Ak::test('AssetTagHelperTests');

?>