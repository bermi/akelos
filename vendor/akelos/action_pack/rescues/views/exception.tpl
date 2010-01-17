<h1><?php 

echo AkTextHelper::h(
    get_class($Exception).(empty($Exception->controller)?'':' in '.$Exception->controller).
    (empty($Exception->params['action'])?'':'#'.$Exception->params['action'])
); 

?></h1>
<p><pre><?php echo AkTextHelper::h($Exception->getMessage()); ?></pre></p>

<?php echo $Template->render(array('file' => '_trace', 'locals' => array('Exception'=>$Exception))); ?>
<?php echo $Template->render(array('file' => '_request_and_response', 'locals' => array('Exception'=>$Exception, 'Request'=>$Request))); ?>
