<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
 <head>
  <title><?php  echo '<?php  echo '?>$text_helper->translate('<?php  echo  $controller_human_name ?>',array(),'layout');?>: <?php  echo '<?php  echo '?> $text_helper->translate($controller->getActionName(),array(),'layout');?></title>
  <meta http-equiv="Content-type" content="text/html; charset=utf-8" />
  <?php  echo '<?php  echo '?> $asset_tag_helper->stylesheet_link_tag('scaffold') ?>
 </head>
 <body>
 {?flash-notice}<div class="flash_notice">{flash-notice}</div>{end}
  <?php  echo '<?php  echo '?> $content_for_layout ?>
 </body>
</html>