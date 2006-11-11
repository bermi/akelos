<div id="sidebar">
  <h1>_{Tasks}:</h1>
  <ul>
    <li><?='<?= '?>$url_helper->link_to($text_helper->translate('Back to overview'), array('action' => 'listing'))?></li>
    <li><?='<?= '?>$url_helper->link_to($text_helper->translate('Show this <?=AkInflector::humanize($singular_name)?>'), array('action' => 'show', 'id'=>$<?=$singular_name?>->getId()))?></li>
  </ul> 
</div>

<div id="content">
  <h1>_{<?=AkInflector::humanize($plural_name)?>}</h1>

  <?='<?='?> $form_tag_helper->start_form_tag(array('action'=>'edit', 'id' => $<?=$singular_name?>->getId())) ?>

  <div class="form">
    <h2>_{Editing <?=AkInflector::humanize($singular_name)?>}</h2>
    <?='<?= '?> $controller->renderPartial('form') ?>
  </div>

  <div id="operations">
    <?='<?='.$helper_var_name?>->save() ?> <?='<?= '.$helper_var_name?>->cancel()?>
  </div>

  <?='<?='?> $form_tag_helper->end_form_tag() ?>
</div>
