<div id="sidebar">
  <h1>_{Tasks}:</h1>
  <ul>
    <li><?php  echo '<%='?> link_to _('Create new <?php  echo AkInflector::humanize($singular_name)?>'), :action => 'add' %></li>
  </ul> 
</div>

<div id="content">
  <h1>_{<?php  echo AkInflector::humanize($plural_name)?>}</h1>

  {?<?php  echo $plural_name?>}
  <div class="listing">
  <table cellspacing="0" summary="_{Listing available <?php  echo AkInflector::humanize($plural_name)?>}">

  <tr>
    <?php  echo '<?php  '?>$content_columns = array_keys($<?php  echo $model_name?>->getContentColumns()); ?>
    {loop content_columns}
        <th scope="col"><?php  echo '<%='?> sortable_link content_column %></th>
    {end}
    <th colspan="3" scope="col"><span class="auraltext">_{Item actions}</span></th>
  </tr>

  {loop <?php  echo $plural_name?>}
    <tr {?<?php  echo $singular_name?>_odd_position}class="odd"{end}>
    {loop content_columns}
      <td class="field"><?php  echo '<?php '?>echo $<?php  echo $singular_name?>->get($content_column) ?></td>
    {end}
      <td class="operation"><?php  echo '<%='?> link_to_show <?php  echo $singular_name?> %></td>
      <td class="operation"><?php  echo '<%='?> link_to_edit <?php  echo $singular_name?> %></td>
      <td class="operation"><?php  echo '<%='?> link_to_destroy <?php  echo $singular_name?> %></td>    
    </tr>
  {end}
   </table>
  </div>
  {end}
  
  {?<?php  echo $singular_name?>_pages.links}
      <div id="<?php  echo $model_name?>Pagination">
      <div id="paginationHeader"><?php  echo '<?php  echo '?>translate('Showing page %page of %number_of_pages',array('%page'=>$<?php  echo $singular_name?>_pages->getCurrentPage(),'%number_of_pages'=>$<?php  echo $singular_name?>_pages->pages))?></div>
      {<?php  echo $singular_name?>_pages.links?}
      </div>
  {end}

</div>