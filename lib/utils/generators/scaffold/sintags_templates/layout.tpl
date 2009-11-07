<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
 <head>
  <title><%= translate ('<?php  echo  $controller_human_name ?>', {},'layout') %>: <?php  echo '<?php  echo '?> $text_helper->translate($controller->getActionName(),array(),'layout');?></title>
  <meta http-equiv="Content-type" content="text/html; charset=utf-8" />
  <?php  echo '<%='?> stylesheet_link_tag 'scaffold' %>
 </head>
 <body>
 {?flash-notice}<div class="flash_notice">{flash-notice}</div>{end}
  {content_for_layout}
 </body>
</html>