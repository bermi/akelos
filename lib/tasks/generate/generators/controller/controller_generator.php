<?php

class ControllerGenerator extends  AkelosGenerator
{
    public $command_values = array('class_name','(array)actions');
    public $scaffold = false;

    public function _preloadPaths() {
        if(!empty($this->class_name_arg)){
            $this->class_name = $this->class_name_arg;
        }

        $this->class_name = $this->controller_name = $this->class_name_arg = str_replace('::', '/', AkInflector::camelize(preg_replace('/_?controller$/i','',$this->class_name)));

        $this->module_path = '';

        // Controller inside module
        if(strstr($this->class_name_arg,'/')){
            $module_parts = substr($this->class_name, 0, strrpos($this->class_name_arg, '/'));
            $this->module_path = join(DS, array_map(array('AkInflector','underscore'), strstr($module_parts, '/') ? explode('/', $module_parts) : array($module_parts))).DS;
            $this->controller_name = substr($this->class_name_arg, strrpos($this->class_name_arg, '/') + 1);
            $this->underscored_controller_name = $this->module_path.AkInflector::underscore($this->controller_name);
            $this->controller_path = 'controllers'.DS.$this->underscored_controller_name.'_controller.php';
            $this->controller_test_path = 'controllers'.DS.$this->underscored_controller_name.'_controller_test.php';
            $this->class_name = str_replace('/', '_', $this->class_name_arg);
        }else{
            $this->underscored_controller_name = AkInflector::underscore($this->class_name);
            $this->controller_path = 'controllers'.DS.$this->underscored_controller_name.'_controller.php';
            $this->controller_test_path = 'controllers'.DS.$this->underscored_controller_name.'_controller_test.php';
        }

        $this->assignVarToTemplate('class_name', $this->class_name);
        $this->assignVarToTemplate('underscored_controller_name', $this->underscored_controller_name);

    }

    public function hasCollisions() {
        $this->collisions = array();
        $this->_preloadPaths();
        $this->actions = empty($this->actions) ? array() : (array)$this->actions;

        $files = array(
        AkConfig::getDir('app').DS.$this->controller_path,
        AkConfig::getDir('test').DS.'functional'.DS.$this->controller_test_path,
        AkConfig::getDir('helpers').DS.$this->underscored_controller_name."_helper.php",
        AkConfig::getDir('test').DS.'unit'.DS.'helpers'.DS.$this->underscored_controller_name."_helper_test.php"
        );
        
        foreach ($this->actions as $action){
            $files[] = AkConfig::getDir('views').DS.$this->module_path.AkInflector::underscore($this->controller_name).DS.$action.'.html.tpl';
        }

        foreach ($files as $file_name){
            if(file_exists($file_name)){
                $this->collisions[] = Ak::t('%file_name file already exists',array('%file_name'=>$file_name));
            }
        }
        return count($this->collisions) > 0;
    }

    public function generate() {
        $this->_preloadPaths();

        $this->save(AkConfig::getDir('app').DS.$this->controller_path, $this->render('controller'));
        $this->save(AkConfig::getDir('helpers').DS.$this->underscored_controller_name."_helper.php", $this->render('helper'));
        $this->save(AkConfig::getDir('test').DS.'functional'.DS.$this->controller_test_path, $this->render('functional_test'));
        $this->save(AkConfig::getDir('test').DS.'unit'.DS.'helpers'.DS.$this->underscored_controller_name."_helper_test.php", $this->render('helper_test'));

        @Ak::make_dir(AkConfig::getDir('views').DS.$this->module_path.AkInflector::underscore($this->controller_name));

        foreach ($this->actions as $action){
            //$this->action = $action;
            $this->assignVarToTemplate('action',$action);
            $this->assignVarToTemplate('path', 'app'.DS.'views'.DS.$this->module_path.AkInflector::underscore($this->controller_name).'/'.$action.'.html.tpl\'');
            $this->save(AkConfig::getDir('views').DS.$this->module_path.AkInflector::underscore($this->controller_name).DS.$action.'.html.tpl', $this->render('view'));
        }
    }
}
