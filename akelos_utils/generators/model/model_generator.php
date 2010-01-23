<?php

class ModelGenerator extends  AkelosGenerator
{
    public $command_values = array('class_name','(array)table_columns');

    public function _preloadPaths() {

        if(!isset($this->active_document)){
            $this->active_document = isset($this->activedocument) || isset($this->ad) || isset($this->ActiveDocument);
        }

        $this->class_name = AkInflector::camelize($this->class_name);
        $this->table_columns = trim(join(' ', (array)@$this->table_columns));

        $this->assignVarToTemplate('table_columns', $this->table_columns);
        $this->table_name = AkInflector::tableize($this->class_name);
        $this->underscored_model_name = AkInflector::underscore($this->class_name);
        $this->model_path = 'app'.DS.'models'.DS.$this->underscored_model_name.'.php';
        $this->installer_path = 'app'.DS.'installers'.DS.$this->underscored_model_name.'_installer.php';
        $this->test_file_name = AkInflector::underscore($this->class_name).'_test.php';

        $this->assignVarToTemplate('class_name', $this->class_name);
        $this->assignVarToTemplate('test_file_name', $this->test_file_name);
        $this->assignVarToTemplate('model_type', $this->active_document ? 'AkActiveDocument' : 'ActiveRecord');
    }

    public function hasCollisions() {
        $this->_preloadPaths();
        $this->collisions = array();
        if(AkInflector::is_plural($this->class_name)){
            $this->collisions[] = Ak::t('%class_name should be a singular noun',array('%class_name'=>$this->class_name));
        }
        $files = array(
        AkInflector::toModelFilename($this->class_name),
        AkConfig::getDir('test').DS.'unit'.DS.$this->test_file_name
        );

        if(!$this->active_document){
            $files[] = AkConfig::getDir('app_installers').DS.$this->underscored_model_name.'_installer.php';
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

        $this->class_name = AkInflector::camelize($this->class_name);

        $files = array(
        'model'     => AkInflector::toModelFilename($this->class_name),
        'unit_test' => AkConfig::getDir('test').DS.'unit'.DS.$this->test_file_name
        );

        if(!$this->active_document){
            $files['installer'] = AkConfig::getDir('app_installers').DS.$this->underscored_model_name.'_installer.php';
        }

        foreach ($files as $template=>$file_path){
            $this->save($file_path, $this->render($template));
        }

    }

    public function cast() {
        $this->_template_vars['class_name'] = AkInflector::camelize($this->class_name);
        $this->_template_vars['table_columns'] = (array)@$this->table_columns;
    }
}

