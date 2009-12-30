<?php

class ModelGenerator extends  AkelosGenerator
{
    public $command_values = array('class_name','(array)table_columns');

    public function _preloadPaths() {
        $this->class_name = AkInflector::camelize($this->class_name);
        $this->assignVarToTemplate('class_name', $this->class_name);
        $this->table_columns = trim(join(' ', (array)@$this->table_columns));
        $this->assignVarToTemplate('table_columns', $this->table_columns);
        $this->table_name = AkInflector::tableize($this->class_name);
        $this->underscored_model_name = AkInflector::underscore($this->class_name);
        $this->model_path = 'app'.DS.'models'.DS.$this->underscored_model_name.'.php';
        $this->installer_path = 'app'.DS.'installers'.DS.$this->underscored_model_name.'_installer.php';
    }

    public function hasCollisions() {
        $this->_preloadPaths();

        $this->collisions = array();

        if(AkInflector::is_plural($this->class_name)){
            $this->collisions[] = Ak::t('%class_name should be a singular noun',array('%class_name'=>$this->class_name));
        }

        $files = array(
        AkInflector::toModelFilename($this->class_name),
        AK_TEST_DIR.DS.'unit'.DS.'app'.DS.'models'.DS.$this->underscored_model_name.'.php',
        AK_TEST_DIR.DS.'fixtures'.DS.$this->model_path,
        AK_TEST_DIR.DS.'fixtures'.DS.$this->installer_path
        );

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
        'model'=>AkInflector::toModelFilename($this->class_name),
        'unit_test'=>AK_TEST_DIR.DS.'unit'.DS.'app'.DS.'models'.DS.$this->underscored_model_name.'.php',
        'model_fixture.tpl'=>AK_TEST_DIR.DS.'fixtures'.DS.$this->model_path,
        'installer_fixture.tpl'=>AK_TEST_DIR.DS.'fixtures'.DS.$this->installer_path
        );

        foreach ($files as $template=>$file_path){
            $this->save($file_path, $this->render($template));
        }

        $installer_path = AkConfig::getDir('app').DS.'installers'.DS.$this->underscored_model_name.'_installer.php';
        if(!file_exists($installer_path)){
            $this->save($installer_path, $this->render('installer'));
        }

        $unit_test_runner = AK_TEST_DIR.DS.'unit.php';
        if(!file_exists($unit_test_runner)){
            Ak::file_put_contents($unit_test_runner, file_get_contents(AK_FRAMEWORK_DIR.DS.'test'.DS.'app.php'));
        }
    }

    public function cast() {
        $this->_template_vars['class_name'] = AkInflector::camelize($this->class_name);
        $this->_template_vars['table_columns'] = (array)@$this->table_columns;
    }
}
