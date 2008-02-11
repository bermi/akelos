<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2006, Akelos Media, S.L.  & Bermi Ferrer Martinez |
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
 * @package ActiveSupport
 * @subpackage Scripts
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

error_reporting(defined('AK_ERROR_REPORTING_ON_SCRIPTS') ? AK_ERROR_REPORTING_ON_SCRIPTS : 0);
require_once(AK_LIB_DIR.DS.'Ak.php');
require_once(AK_LIB_DIR.DS.'AkObject.php');
require_once(AK_LIB_DIR.DS.'AkInflector.php');
defined('AK_SKIP_DB_CONNECTION') && AK_SKIP_DB_CONNECTION ? ($dsn='') : Ak::db(&$dsn);
array_shift($argv);
$options = $argv;

require_once(AK_LIB_DIR.DS.'AkInstaller.php');
require_once(AK_LIB_DIR.DS.'utils'.DS.'generators'.DS.'AkelosGenerator.php');

$installer = array_shift($options);
$installer_class_name = AkInflector::camelize(AkInflector::demodulize($installer)).'Installer';
$command = count($options) > 0 ? array_shift($options) : 'usage';

$installer = str_replace('::','/',$installer);
$file = AK_APP_DIR.DS.'installers'.DS.rtrim(join('/',array_map(array('AkInflector','underscore'), explode('/',$installer.'/'))),'/').'_installer.php';


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
    $files = Ak::dir(AK_APP_DIR.DS.'installers', array('recurse' => true));
    if(empty($files)){
        echo Ak::t("\n  Could not find installers at %dir  \n", array('%dir'=>AK_APP_DIR.DS.'installers'));
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

?>