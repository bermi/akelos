<div id="sidebar">
  <h1>_{Tasks}:</h1>
  <ul>
    <li><?='<?= '?>$url_helper->link_to($text_helper->translate('Create new <?=AkInflector::humanize($singular_name)?>'), array('action' => 'add'))?></li>
  </ul> 
</div>

<div id="content">
  <h1>_{<?=AkInflector::humanize($plural_name)?>}</h1>

  {?<?=$plural_name?>}
  <div class="listing">
  <table cellspacing="0" summary="_{Listing available <?=AkInflector::humanize($plural_name)?>}">

  <tr>
    <?='<? '?>$content_columns = array_keys($<?=$model_name?>->getContentColumns()); ?>
    {loop content_columns?}
        <th scope="col"><?='<?= '?>$pagination_helper->sortable_link($content_column) ?></th>
    {end}
    <th colspan="3" scope="col"><span class="auraltext">_{Item actions}</span></th>
  </tr>

  {loop <?=$plural_name?>?}
    <tr {?<?=$singular_name?>_odd_position}class="odd"{end}>
    {loop content_columns?}
      <td class="field"><?='<?= '?>$<?=$singular_name?>->get($content_column) ?></td>
    {end}
      <td class="operation"><?='<?= '.$helper_var_name?>->link_to_show($<?=$singular_name?>)?></td>
      <td class="operation"><?='<?= '.$helper_var_name?>->link_to_edit($<?=$singular_name?>)?></td>
      <td class="operation"><?='<?= '.$helper_var_name?>->link_to_destroy($<?=$singular_name?>)?></td>    
    </tr>
  {end}
   </table>
  </div>
  {end}
  
    {?<?=$singular_name?>_pages.links}
        <div id="<?=$model_name?>Pagination">
        <div id="paginationHeader"><?='<?='?>translate('Showing page %page of %number_of_pages',array('%page'=>$<?=$singular_name?>_pages->getCurrentPage(),'%number_of_pages'=>$<?=$singular_name?>_pages->pages))?></div>
        {<?=$singular_name?>_pages.links?}
        </div>
    {end}
  
</div>