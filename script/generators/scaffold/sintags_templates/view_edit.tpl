<div id="sidebar">
  <h1>_{Tasks}:</h1>
  <ul>
    <li><?php  echo '<%='?> link_to _'Back to overview', :action => 'listing' %></li>
    <li><?php  echo '<%='?> link_to _'Show this <?php  echo AkInflector::humanize($singular_name)?>', :action => 'show', :id => <?php  echo $singular_name?>.id %></li>
  </ul> 
</div>

<div id="content">
  <h1>_{<?php  echo AkInflector::humanize($plural_name)?>}</h1>

  <?php  echo '<%='?> start_form_tag :action => 'edit', :id => <?php  echo $singular_name?>.id %>

  <div class="form">
    <h2>_{Editing <?php  echo AkInflector::humanize($singular_name)?>}</h2>
    <?php  echo '<%='?> render :partial => 'form' %>
  </div>

  <div id="operations">
    <?php  echo '<%='?> save %> <?php  echo '<%='?> cancel %>
  </div>

  </form>
</div>
