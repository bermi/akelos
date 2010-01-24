<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

array_shift($argv);
$options = $argv;

$installer = array_shift($options);
if(preg_match('/:{2}|\//', $installer)){
    $installer_class_name = AkInflector::camelize(AkInflector::demodulize($installer)).'Installer';
}else{
    $installer_class_name = AkInflector::camelize($installer).'Installer';
}

$command = count($options) > 0 ? array_shift($options) : 'usage';

$installer = str_replace('::','/',$installer);
$file = AkConfig::getDir('app').DS.'installers'.DS.rtrim(join('/',array_map(array('AkInflector','underscore'), explode('/',$installer.'/'))),'/').'_installer.php';


function ak_print_available_installers($files, $preffix = '')
{
    foreach($files as $k => $file){
        if(is_string($file)){
            if(preg_match('/(.*)_installer\.php$/', $file, $match)){
                echo ' * '.$preffix.$match[1]."\n";
            }
        }else{
            ak_print_available_installers($file, $k.'::');
        }
    }
    echo "\n";
}

if($installer_class_name == 'Installer'){
    $files = Ak::dir(AkConfig::getDir('app').DS.'installers', array('recurse' => true));
    if(empty($files)){
        echo Ak::t("\n  Could not find installers at %dir  \n", array('%dir'=>AkConfig::getDir('app').DS.'installers'));
    }else{
        echo Ak::t("\n  You must supply a valid installer name like : \n");
        echo Ak::t("\n  > ./script/migrate my_installer_name install\n\n");
        echo Ak::t("  Available installers are:  \n\n");
        ak_print_available_installers($files);
    }
}elseif(!file_exists($file)){
    echo Ak::t("\n\n  Could not locate the installer file %file\n\n",array('%file'=>$file));
}else{
    require_once($file);
    if(!class_exists($installer_class_name)){
        echo Ak::t("\n\n  Could not find load the installer. Class doesn't exists\n\n");
    }else{
        $installer = new $installer_class_name();
        if(!method_exists($installer,$command)){
            echo Ak::t("\n\n  Could not find the method %method for the installer %installer\n\n",
            array('%method'=>$command,'%installer'=>$installer_class_name));
        }else{
            $installer->$command($options);
        }
    }
}

echo "\n";

