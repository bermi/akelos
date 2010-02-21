<h1>_{Editing <?php echo AkInflector::humanize($singular_name); ?>}</h1>

<?php  echo '<%='?> render :partial => 'form' %>

<?php  echo '<%='?> link_to 'Show', <?php echo $singular_name; ?> %> |
<?php  echo '<%='?> link_to 'Back', <?php echo $plural_name; ?>_path() %>
