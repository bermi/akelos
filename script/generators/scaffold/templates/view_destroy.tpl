<div id="sidebar">
  <h1>_{Tasks}:</h1>
  <ul>
    <li><?='<?= '?>$url_helper->link_to($text_helper->translate('Back to overview'), array('action' => 'listing'))?></li>
    <li><?='<?= '?>$url_helper->link_to($text_helper->translate('Show this <?=AkInflector::humanize($singular_name)?>'), array('action' => 'show', 'id'=>$<?=$singular_name?>->getId()))?></li>
  </ul> 
</div>


<div id="content">
  <h1>_{<?=AkInflector::humanize($plural_name)?>}</h1>

  <p>_{Are you sure you want to delete this <?=AkInflector::humanize($singular_name)?>?}</p>
  <?='<?='?> $form_tag_helper->start_form_tag(array('action' => 'destroy', 'id' => $<?=$singular_name ?>->getId())) ?>
  <?='<?= '.$helper_var_name?>->confirm_delete() ?>
  <?='<?='?> $form_tag_helper->end_form_tag() ?>
</div>
