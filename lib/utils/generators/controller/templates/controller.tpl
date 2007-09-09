<?php  echo '<?php'?>


class <?php  echo $class_name?>Controller extends ApplicationController
{
<?php   if(!empty($options['scaffold'])) :?>
  var $scaffold = '<?php  echo AkInflector::singularize($class_name)?>';
<?php endif; ?>
<?php   if(!empty($actions)) :?>
<?php   foreach ($actions as $action) : ?>

    function <?php echo $action?> ()
    {
    }
<?php endforeach; ?>
<?php endif; ?>
}

?>
