<?php

require_once(dirname(__FILE__).'/../config.php');
require_once(dirname(__FILE__).'/../lib/template_unit_test.php');

class TemplatePaths_TestCase extends TemplateUnitTest
{
    public function testSettingLayoutToFalseMeansYouDontWantALayout() {
        $this->createViewTemplate('index.html');
        
        $this->createTemplate('layouts/application.tpl');
        $controller = $this->createControllerFor('index');
        $controller->layout = false;         
        $this->expectRender(array('index.html'));
        $controller->defaultRender();
    }
    
    public function testPickApplicationLayoutIfWeDontHaveAControllerLayout() {
        $this->createViewTemplate('index.html');
        $this->createTemplate('layouts/application.tpl');
        $controller = $this->createControllerFor('index');
        
        $this->expectRender(array('index.html',AkConfig::getDir('views').DS.'layouts/application.tpl'));
        $controller->defaultRender();
    }

    public function testDontPickAnyLayoutIfNoneIsPresent() {
        $this->createViewTemplate('index.html');
        $controller = $this->createControllerFor('index');
        
        $this->expectRender(array('index.html'));
        $controller->defaultRender();
    }

    public function testPickControllerLayoutIfPresent() {
        $this->createViewTemplate('index.html');
        $this->createTemplate('layouts/template_paths.tpl');
        $controller = $this->createControllerFor('index');
        
        $this->expectRender(array('index.html',AkConfig::getDir('views').DS.'layouts/template_paths.tpl'));
        $controller->defaultRender();
    }

    public function testPickExplicitlySetLayout() {
        $this->createViewTemplate('index.html');
        $this->createTemplate('render_tests/my_layout.tpl');
        $controller = $this->createControllerFor('index');
        $controller->setLayout('render_tests/my_layout');
        
        $this->expectRender(array('index.html',AkConfig::getDir('views').DS.'render_tests/my_layout.tpl'));
        $controller->defaultRender();
    }

    public function testPickALayoutUsingADefinedMethod() {
        $this->createViewTemplate('index.html');
        $this->createTemplate('layouts/picked_from_method.tpl');
        $controller = $this->createControllerFor('index');
        $controller->setLayout('my_layout_picker');
        
        $this->expectRender(array('index.html',AkConfig::getDir('views').DS.'layouts/picked_from_method.tpl'));
        $controller->defaultRender();
    }

    public function testPickALayoutUsingAnObject() {
        $this->createViewTemplate('index.html');
        $this->createTemplate('layouts/picked_from_method.tpl');
        $controller = $this->createControllerFor('index');
        $controller->setLayout(array($controller,'my_layout_picker'));
        
        $this->expectRender(array('index.html',AkConfig::getDir('views').DS.'layouts/picked_from_method.tpl'));
        $controller->defaultRender();
    }

    public function testPickLayoutIfActionameMatches() {
        $this->createViewTemplate('index.html');
        $this->createTemplate('layouts/application.tpl');
        $controller = $this->createControllerFor('index');
        $controller->setLayout('application',array('only'=>'index'));
        
        $this->expectRender(array('index.html',AkConfig::getDir('views').DS.'layouts/application.tpl'));
        $controller->defaultRender();
    }
 
    public function testPickLayoutUnlessActionameMatches() {
        $this->createViewTemplate('index.html');
        $this->createTemplate('layouts/application.tpl');
        $controller = $this->createControllerFor('index');
        $controller->setLayout('application',array('except'=>'index'));
        
        $this->expectRender(array('index.html'));
        $controller->defaultRender();
    }

    public function testPickFormatAccordingToRespondTo() {
        $this->createViewTemplate('index.xml');
        $controller = $this->createControllerFor('index','xml');
        
        $this->expectRender(array('index.xml'));
        $controller->defaultRender();
    }

    public function testPickAlternativeHtmlTemplateFileWithoutTheHtmlExtension() {
        $this->createViewTemplate('index');
        $controller = $this->createControllerFor('index');
        $this->expectRender(array('index.html'));
        $controller->defaultRender();
    }
}

ak_test_case('TemplatePaths_TestCase');

