<?php echo '<?php'; ?>


class <?php  echo $class_name; ?>Generator extends AkelosGenerator
{
    public $command_values = array('destination_path','(array)attribute2_is_array');
    
    public function hasCollisions() {
        $this->collisions = array(); // Add collisions to this array
        if(is_dir($this->destination_path)){
            $this->collisions[] = Ak::t('%path already exists', array('%path' => $this->destination_path));
        }
        return count($this->collisions) > 0;
    }

    public function generate() {
        
        $this->assignVarToTemplate('variables', 'Got value');
        $this->save(
                $this->destination_path.DS.'generated_template.txt', 
                $this->render('template')); // Will render template/template.tpl
    }
}

