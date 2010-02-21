<?php foreach($attributes as $attribute) : ?>
<p>
  <b>_{<?php echo AkTextHelper::humanize($attribute['name']); ?>}:</b>
  {\<?php  echo $singular_name.'.'.$attribute['name']; ?>}
</p>

<?php endforeach; ?>

<?php  echo '<%='?> link_to 'Edit', edit_<?php  echo $singular_name; ?>_path(<?php  echo $singular_name; ?>) %> |
<?php  echo '<%='?> link_to 'Back', <?php  echo $plural_name; ?>_path() %>
