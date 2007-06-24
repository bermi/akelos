<div id="sidebar">
  <h1>_{Tasks}:</h1>
  <ul>
    <li><?php  echo '<%='?> link_to _'Back to overview', :action => 'listing' %></li>
    <li><?php  echo '<%='?> link_to _'Show this <?php  echo AkInflector::humanize($singular_name)?>', :action => 'show', :id => <?php  echo $singular_name?>.id %></li>
  </ul> 
</div>

<div id="content">
  <h1>_{<?php  echo AkInflector::humanize($plural_name)?>}</h1>

  <p>_{Are you sure you want to delete this <?php  echo AkInflector::humanize($singular_name)?>?}</p>
  <?php  echo '<%='?>  start_form_tag :action => 'destroy', :id => <?php  echo $singular_name ?>.id %>
    <?php  echo '<%='?> confirm_delete %>
  </form>
</div>
