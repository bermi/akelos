<div id="sidebar">
  <h1>_{Tasks}:</h1>
  <ul>
    <li><?='<?= '?>$url_helper->link_to($text_helper->translate('Back to overview'), array('action' => 'listing'))?></li>
  </ul> 
</div>

<div id="content">
  <h1>_{<?=AkInflector::humanize($plural_name)?>}</h1>
  
  <?='<?='?> $form_tag_helper->start_form_tag(array('action'=>'add')) ?>

  <div class="form">
    <h2>_{Creating <?=AkInflector::humanize($singular_name)?>}</h2>
    <?='<?= '?> $controller->renderPartial('form') ?>
  </div>

  <div id="operations">
    <?='<?='.$helper_var_name?>->save() ?> <?='<?= '.$helper_var_name?>->cancel()?>
  </div>

  <?='<?='?> $form_tag_helper->end_form_tag() ?>
</div>
