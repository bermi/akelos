<?php

$Installer = new AkInstaller();
$Installer->copyFilesIntoApp(dirname(__FILE__).DS.'website_files', array('relative_url' => $Installer->promptUserVar("Relative url path for serving images on CSS files\n    (ie. /public /akelos/public or /)\n hit enter if your application is served from the base of hostname\n", '/')));

echo "\nYou can now browse your documentation from localhost at: \n\n    ".AK_SITE_URL."/docs\n\nPlease configure access settings by editing docs_controller.php\n";