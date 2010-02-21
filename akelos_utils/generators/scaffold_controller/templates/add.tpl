<h1>_{Creating new <?php echo AkInflector::humanize($singular_name); ?>}</h1>

<?php  echo '<%='?> render :partial => 'form' %>

<?php  echo '<%='?> link_to 'Back', <?php echo $plural_name; ?>_path() %>
