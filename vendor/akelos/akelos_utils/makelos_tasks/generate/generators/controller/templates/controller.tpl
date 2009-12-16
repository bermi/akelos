<?php echo '<?php'; ?>


class <?php  echo $class_name?>Controller extends ApplicationController
{<?php   if(!empty($options['scaffold'])) : ?>
    public $scaffold = '<?php  echo AkInflector::singularize($class_name)?>';
<?php 
endif; 

if(!empty($actions)) :?>
<?php   foreach ($actions as $action) : ?>

    public function <?php echo $action?> () {
    }
<?php endforeach; ?>
<?php endif; ?>
}

