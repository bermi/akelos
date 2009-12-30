<?php

class TaskGenerator extends AkelosGenerator
{
    public $command_values = array('task_name');
    public $destination_path;
    
    public function hasCollisions() {
        $this->collisions = array();
        $this->namespace = AkInflector::underscore(Ak::first(explode(':', $this->task_name.':')));
        $this->task_name = AkInflector::underscore(Ak::last(explode(':', ':'.$this->task_name)));
        $this->destination_path = AK_TASKS_DIR.DS.$this->namespace;
        if(file_exists($this->destination_path.DS.$this->task_name.'.task.php')){
            $this->collisions[] = Ak::t('%path already exists', array('%path' => $this->destination_path.DS.$this->task_name.'.task.php'));
        }
        return count($this->collisions) > 0;
    }

    public function generate() {
        $destination_path = AK_TASKS_DIR.DS.$this->namespace;
        $this->assignVarToTemplate('namespace',  $this->namespace);
        $this->assignVarToTemplate('task_name',  $this->task_name);
        
        $old_file = '';
        if(file_exists($destination_path.DS.'makefile.php')){
            $old_file = file_get_contents($destination_path.DS.'makefile.php');
        }
        
        $this->save($destination_path.DS.'makefile.php', $this->render('makefile')."\n".str_replace('<?php', '', $old_file));
        $this->save($destination_path.DS.$this->task_name.'.task.php', $this->render('task'));
        $this->save($destination_path.DS.$this->task_name.'.autocompletion.php', $this->render('autocompletion'));
    }
}
