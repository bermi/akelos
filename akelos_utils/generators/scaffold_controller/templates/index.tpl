<h1>_{Listing <?php echo AkInflector::humanize($plural_name); ?>}</h1>

<table>
  <tr>
<?php foreach($attributes as $attribute) : ?>
    <th>_{<?php echo AkInflector::humanize($attribute['name']); ?>}</th>
<?php endforeach; ?>
    <th></th>
    <th></th>
    <th></th>
  </tr>

{loop <?php echo $plural_name; ?>}
  <tr>
<?php foreach($attributes as $attribute) : ?>
    <td>{<?php echo $singular_name; ?>.<?php echo $attribute['name']; ?>}</td>
<?php endforeach; ?>
    <td><?php  echo '<%='?> link_to 'Show', <?php echo $singular_name; ?> %></td>
    <td><?php  echo '<%='?> link_to 'Edit', edit_<?php echo $singular_name; ?>_path(<?php echo $singular_name; ?>) %></td>
    <td><?php  echo '<%='?> link_to 'Destroy', <?php echo $singular_name; ?>, {:confirm => t('Are you sure?'), :method => 'delete'} %></td>
  </tr>
{end}
</table>

<br />

<?php  echo '<%='?> link_to 'New <?php echo $singular_name; ?>', add_<?php echo $singular_name; ?>_path() %>
