<?php  echo '<?php'?>

require_once(AK_BASE_DIR.DS.'app'.DS.'installers'.<?php 
echo !empty($module_preffix) ? "DS.'".trim($module_preffix,DS)."'." : ''
?>DS.substr(strrchr(__FILE__, DS), 1));

?>