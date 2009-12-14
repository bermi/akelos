<?php

$Installer = new AkInstaller();
$Installer->copyFilesIntoApp(dirname(__FILE__).DS.'website_files');

echo "\nYou can now browse your documentation from localhost at: \n\n    ".AK_SITE_URL."/docs\n\nPlease configure access settings by editing docs_controller.php\n";