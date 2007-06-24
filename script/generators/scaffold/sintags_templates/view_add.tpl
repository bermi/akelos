<div id="sidebar">
  <h1>_{Tasks}:</h1>
  <ul>
    <li><?php  echo '<%='?> link_to _('Back to overview'), :action => 'listing' %></li>
  </ul> 
</div>

<div id="content">
  <h1>_{<?php  echo AkInflector::humanize($plural_name)?>}</h1>
  
  <?php  echo '<%='?> start_form_tag :action => 'add' %>

    <div class="form">
      <h2>_{Creating <?php  echo AkInflector::humanize($singular_name)?>}</h2>
      <?php  echo '<%='?>  render :partial => 'form' %>
    </div>

    <div id="operations">
      <?php  echo '<%='?> save %> <?php  echo '<%='?> cancel %>
    </div>

  </form>
</div>
