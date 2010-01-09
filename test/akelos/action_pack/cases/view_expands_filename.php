<?php

require_once(dirname(__FILE__).'/../config.php');
require_once(dirname(__FILE__).'/../lib/template_unit_test.php');

class ViewExpandsFilenames_TestCase extends TemplateUnitTest  
{
    public function setUp() {
        
    }
    
    public function testViewAcceptsOtherFormats() {
        $View = $this->createView();
        $this->createViewTemplate('index.xml');

        $this->expectsOnRender('tpl','index.xml.tpl');
        $View->renderFile('index.xml',true,array());
    }
    
    public function testViewsHandlesHtmlExtension() {
        $View = $this->createView();
        $this->createViewTemplate('index.html');

        $this->expectsOnRender('html.tpl','index.html.tpl');
        $View->renderFile('index.html',true,array());
    }

    public function testViewShouldAddHtmlExtensionIfAnHtmlTemplateExists() {
        $View = $this->createView();
        $this->createViewTemplate('index.html');

        $this->expectsOnRender('html.tpl','index.html.tpl');
        $View->renderFile('index',true,array());
    }
    
    public function testForCompatibilityViewDoesntAddHtmlExtensionIfTemplateWouldNotExist() {
        $View = $this->createView();
        $this->createViewTemplate('index');

        $this->expectsOnRender('tpl','index.tpl');
        $View->renderFile('index',true,array());
    }
    
    public function testForCompatibilityViewRemovesHtmlExtensionIfNecessary() {
        $View = $this->createView();
        $this->createViewTemplate('index');

        $this->expectsOnRender('tpl','index.tpl');
        $View->renderFile('index.html',true,array());
    }
    
    /* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */
    
    /**
     * @return AkActionView
     */
    public function createView() {
        $View = new AkActionView(AkConfig::getDir('views').DS.$this->controller_name);
        $View->registerTemplateHandler('tpl','AkPhpTemplateHandler');
        return $this->Template = $View;
    }

    public function expectsOnRender($handler_extension,$view_file) {
        $file_name = AkConfig::getDir('views').DS.$this->controller_name.DS.$view_file;
        $this->assertEqual($this->Template->renderTemplate($handler_extension, null, $file_name), 'Dummy');
    }
}

ak_test_case('ViewExpandsFilenames_TestCase');
