<?php  echo '<?php'?>


class <?php  echo $class_name?>Controller extends ApplicationController
{
<?php   if(!empty($options['scaffold'])) :?>
  var $scaffold = '<?php  echo AkInflector::singularize($class_name)?>';
<?endif;?>
<?php   foreach ($actions as $action) : ?>

    function <?php  echo $action?> ()
    {
    }
<?endforeach;?>
}

?>
