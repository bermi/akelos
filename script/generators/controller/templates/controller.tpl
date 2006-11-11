<?='<?php'?>


class <?=$class_name?>Controller extends ApplicationController
{
<? if(!empty($options['scaffold'])) :?>
  var $scaffold = '<?=AkInflector::singularize($class_name)?>';
<?endif;?>
<? foreach ($actions as $action) : ?>

    function <?=$action?> ()
    {
    }
<?endforeach;?>
}

?>
