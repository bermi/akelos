<?php echo '<?php'; ?>


class <?php  echo $class_name; ?>Generator extends AkelosGenerator
{
    public $command_values = array('attribute1','(array)attribute2_is_array');
    
    public function hasCollisions() {
        $this->collisions = array(); // Add collisions to this array
        return count($this->collisions) > 0;
    }

    public function generate() {
        
        $this->assignVarToTemplate('variables', 'Got value');
        $this->save(
                AkConfig::getDir('app').DS.'generated_template.txt', 
                $this->render('template')); // Will render template/template.tpl
    }
}

