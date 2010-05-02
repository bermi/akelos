<?php

abstract class TemplateUnitTest extends ActionPackUnitTest 
{
    protected $controller_name = 'template_paths';

    public function tearDown() {
        $this->deleteCreatedFiles();
        $this->created_files = array();
    }

    /**
     * @var ApplicationController
     */
    private $Controller;

    /**
     * @var AkActionView
     */
    protected $Template;

    private $action_name;

    private $created_files = array();

    public function expectRender($arg_list) {
        foreach ($arg_list as $i=>$args){
            $args           = is_array($args) ? $args : array($args);
            $args[0] = str_replace('/',DS,$args[0]); // at args[0] we must have a string, the template_path
            $this->assertEqual(call_user_func_array(array($this->Template, 'renderFile'), $args), 'Dummy');
        }
    }

    public function assertLayout($expected,$actual=null) {
        $expected = str_replace('/',DS,$expected);
        if (!$actual){
            $actual = $this->Controller->_pickLayout(false,$this->action_name,null);
        }
        $this->assertEqual(AkConfig::getDir('views').DS.$expected, $actual);
    }

    public function assertNoLayout() {
        $this->assertFalse($this->Controller->_pickLayout(false,$this->action_name,null));
    }

    /**
     * @return TemplatePathsController
     */
    public function createControllerFor($action_name, $mime_type='html') {
        $controller_class_name = AkInflector::camelize($this->controller_name).'Controller';
        $controller = new $controller_class_name();

        $Request = $this->createGetRequest($action_name,$mime_type);
        $Response = $this->mock('AkResponse',array('outputResults' => ''));
        $controller->setRequestAndResponse($Request,$Response);
        $controller->Template = new AkActionView(
            $controller->_getTemplateBasePath(),
            array(),
            $controller);
        
        $controller->Template->registerTemplateHandler('tpl','AkPhpTemplateHandler');
        //$this->Template->registerTemplateHandler('html.tpl','AkPhpTemplateHandler');
        $this->Template = $controller->Template;


        $this->action_name = $action_name;
        return $this->Controller = $controller;
    }

    public function createViewTemplate($action_name) {
        $view_for_action = $this->controller_name.DS.$action_name.'.tpl';
        $this->createTemplate($view_for_action);
    }

    public function createTemplate($file_name,$content='Dummy') {
        $file_name = str_replace('/',DS,$file_name);
        $file_name = AkConfig::getDir('views').DS.$file_name;
        $this->assertTrue((boolean)AkFileSystem::file_put_contents($file_name,$content));
        $this->created_files[] = $file_name;
    }

    public function deleteCreatedFiles() {
        foreach ($this->created_files as $file_name){
            $this->assertTrue(AkFileSystem::file_delete($file_name));
        }
    }

    public function createGetRequest($action_name,$format)  {
        $request_method = 'get';
        $controller_name = 'template_paths';

        $Request = $this->mock('AkRequest', array(
        'getMethod'     => $request_method,
        'getFormat'     => $format,
        'getAction'     => $action_name,
        'getController' => $controller_name,
        'getParams'     => array('controller'=>$controller_name,'action'=>$action_name)
        ));

        return $Request;
    }
}