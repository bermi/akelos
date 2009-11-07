<div id="sidebar">
  <h1>_{Tasks}:</h1>
  <ul>
    <li><?php  echo '<%='?> link_to _('Edit this <?php  echo AkInflector::humanize($singular_name)?>'), :action => 'edit', :id => <?php  echo $singular_name?>.id %></li>
    <li><?php  echo '<%='?> link_to _('Back to overview'), :action => 'listing' %></li>
  </ul> 
</div>

<div id="content">
  <h1>_{<?php  echo AkInflector::humanize($plural_name)?>}</h1>

  <div class="show">
    <?php  echo '<?php  '?>$content_columns = array_keys($<?php  echo $model_name?>->getContentColumns()); ?>
    {loop content_columns}
      <label><?php  echo '<%='?> translate( humanize( content_column ) ) %>:</label> <span class="static"><?php  echo '<?php  echo '?> $<?php  echo $singular_name?>->get($content_column) ?></span><br />
    {end}
  </div>
</div>