<!DOCTYPE html>
<html>
<head>
  <title><?php echo $controller_class_name; ?>: {action}</title>
  <?php  echo '<%='?> stylesheet_link_tag 'scaffold' %>
  <?php  echo '<%='?> javascript_include_tag %>
  <?php  echo '<%='?> csrf_meta_tag %>
</head>
<body>

<?php  echo '<%='?> flash %>

{content_for_layout}

</body>
</html>
