<?php

class GeneratorGenerator extends AkelosGenerator
{
    public $command_values = array('generator_name');
    public $destination_path;
    
    public function hasCollisions() {
        $this->collisions = array();
        $this->generator_name = AkInflector::underscore($this->generator_name);
        $this->class_name = AK_APP_LIB_DIR.DS.'generators'.DS.$this->generator_name;
        $this->destination_path = AK_APP_LIB_DIR.DS.'generators'.DS.$this->generator_name;
        if(is_dir($this->destination_path)){
            $this->collisions[] = Ak::t('%path already exists', array('%path' => $this->destination_path));
        }
        return count($this->collisions) > 0;
    }

    public function generate() {
        $destination_path = AK_APP_LIB_DIR.DS.'generators'.DS.$this->generator_name;
        $this->assignVarToTemplate('class_name', AkInflector::camelize($this->generator_name));
        $this->save($destination_path.DS.$this->generator_name.'_generator.php', $this->render('generator'));
        $this->save($destination_path.DS.'templates'.DS.'template.tpl', $this->render('template'));
        $this->save($destination_path.DS.'USAGE', $this->render('usage'));
    }
}
