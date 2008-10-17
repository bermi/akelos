<?php
require_once(AK_LIB_DIR.DS.DS.'AkType.php');
require_once(AK_LIB_DIR.DS.'AkInstaller.php');
require_once(AK_LIB_DIR.DS.'AkReflection'.DS.'AkReflectionFile.php');


class AkPluginInstaller extends AkInstaller
{
    var $auto_install_files = true;
    var $auto_install_extensions = true;
    var $auto_remove_extensions = true;
    
    var $plugin_name;
    
    var $extension_points = array('BaseActiveRecord'=>'base_active_record.php',
                                  'BaseActionController'=>'base_action_controller.php');

    var $_plugin_definition;
    
    function AkPluginInstaller($db_connection = null, $plugin_name = '')
    {
        $this->AkInstaller($db_connection);
        $this->plugin_name = $plugin_name;
    }
    function _runInstallerMethod($method_prefix, $version, $options = array(), $version_number = null)
    {
        $auto_install_extensions = (isset($this->auto_install_extensions) && $this->auto_install_extensions === true);
        $auto_install_files = (isset($this->auto_install_files) && $this->auto_install_files === true);
        
        $auto_remove_extensions = (isset($this->auto_remove_extensions) && $this->auto_remove_extensions === true);
        
        
        $method_name = $method_prefix.'_'.$version;
        $method_exists = method_exists($this, $method_name);
        
        if(!$method_exists){
            return false;
        }

        $version_number = empty($version_number) ? ($method_prefix=='down' ? $version-1 : $version) : $version_number;

        $this->transactionStart();
        switch ($method_prefix) {
            case 'up':
                $depdencies_ok = $this->_checkInstallDependencies();
                
                break;
            case 'down':
            case 'uninstall':
                $depdencies_ok = $this->_checkUninstallDependencies();
                break;
            default:
                $depdencies_ok = true;
        }
        
        if ($depdencies_ok === false) {
            $this->transactionFail();
            return $depdencies_ok;
        }
        if ($method_prefix == 'up') {
            if ($auto_install_files) {
                $res = $this->installFiles();
                if ($res === false) {
                    $this->transactionFail();
                    return $res;
                }
            }
            if ($auto_install_extensions) {
                $res = $this->autoInstallExtensions();
                if ($res === false) {
                    $this->transactionFail();
                    return $res;
                }
            }
        }
        if ($method_prefix == 'down' || $method_prefix == 'uninstall') {
            if ($auto_remove_extensions) {
                $this->removeExtensions();
            }
            $this->_removeDependency();
        }
        if($this->$method_name($options) === false){
            $this->transactionFail();
        }
        $success = !$this->transactionHasFailed();
        $this->transactionComplete();
        if($success){
            $this->setInstalledVersion($version_number, $options);
        }
        return $success;
    }
    

    function installFiles($installer_dir = 'installer', $source_dir = 'files')
    {
        $basePath = AK_APP_PLUGINS_DIR.DS.$this->plugin_name.DS.$installer_dir.DS.$source_dir;
        $this->files = Ak::dir($basePath, array('recurse'=> true));
        empty($this->options['force']) ? $this->_checkForCollisions($this->files,$basePath) : null;
        $this->_copyFiles($this->files, $basePath, $basePath);
    }
    
    function _copyFiles($directory_structure, $base_path = null, $src_path = null)
    {
        
        foreach ($directory_structure as $k=>$node){
            $path = $base_path.DS.$node;
            if(is_dir($path)){
                echo 'Creating dir '.$path."\n";
                $this->_makeDir($path, $src_path);
            }elseif(is_file($path)){
                echo 'Creating file '.$path."\n";
                $this->_copyFile($path, $src_path);
            }elseif(is_array($node)){
                foreach ($node as $dir=>$items){
                    $path = $base_path.DS.$dir;
                    if(is_dir($path)){
                        echo 'Creating dir '.$path."\n";
                        $this->_makeDir($path, $src_path);
                        $this->_copyFiles($items, $path, $src_path);
                    }
                }
            }
        }
    }

    function _makeDir($path, $base_path)
    {
        $dir = str_replace($base_path, AK_BASE_DIR,$path);
        if(!is_dir($dir)){
            mkdir($dir);
        }
    }
    function _removeDependency()
    {
        if (!empty($this->dependencies)) {
            $this->dependencies = Ak::toArray($this->dependencies);
            
            foreach ($this->dependencies as $dependency) {
                $dependencyFile = AK_PLUGINS_DIR.DS.$dependency.DS.'dependent_plugins';
                if (($fileExists = file_exists($dependencyFile))) {
                    $dependendPlugins = file($dependencyFile);
                } else {
                    $dependendPlugins = array();
                }
                $dependendPlugins = array_diff($dependendPlugins,array($this->plugin_name));
                if (!empty($dependendPlugins)) {
                    Ak::file_put_contents($dependencyFile,implode("\n",$dependendPlugins));
                } else if ($fileExists) {
                    unlink($dependencyFile);
                }
            }
        }
    }
    function _copyFile($path, $base_path)
    {
        $destination_file = str_replace($base_path, AK_BASE_DIR,$path);
        copy($path, $destination_file);
        $source_file_mode =  fileperms($path);
        $target_file_mode =  fileperms($destination_file);
        if($source_file_mode != $target_file_mode){
            chmod($destination_file,$source_file_mode);
        }
    }
    
    function _checkForCollisions(&$directory_structure, $base_path = null)
    {
        foreach ($directory_structure as $k=>$node){
            if(!empty($this->skip_all)){
                return ;
            }
            $path = str_replace($base_path, AK_BASE_DIR, $base_path.DS.$node);
            if(is_file($path)){
                $message = Ak::t('File %file exists.', array('%file'=>$path));
                $user_response = AkInstaller::promptUserVar($message."\n d (overwrite mine), i (keep mine), a (abort), O (overwrite all), K (keep all)", 'i');
                if($user_response == 'i'){
                    unset($directory_structure[$k]);
                }    elseif($user_response == 'O'){
                    return false;
                }    elseif($user_response == 'K'){
                    $directory_structure = array();
                    return false;
                }elseif($user_response != 'd'){
                    echo "\nAborting\n";
                    exit;
                }
            }elseif(is_array($node)){
                foreach ($node as $dir=>$items){
                    $path = $base_path.DS.$dir;
                    if(is_dir($path)){
                        if($this->_checkForCollisions($directory_structure[$k][$dir], $path) === false){
                            $this->skip_all = true;
                            return;
                        }
                    }
                }
            }
        }
    }
    

    function _addMethodToClass($class,$name,$path,$methodString, $pluginName)
    {
        $targetReflection = new AkReflectionFile($path);
        $classes = $targetReflection->getClasses();
        foreach($classes as $c) {
            $method = $c->getMethod($name);
            if ($method!==false) {
                echo "Method $name already exists on class $class in file $path\n";
                return false;
            }
        }
        $contents = @Ak::file_get_contents($path);
       
            return (Ak::file_put_contents($path, preg_replace('|class '.$class.'(.*?)\n.*?{|i',"class $class\\1
{
/** AUTOMATED START: $pluginName::$name */
$methodString
/** AUTOMATED END: $pluginName::$name */
",$contents))>0?true:'Could not write to '.$path);
        
    }
    function _removeMethodFromClass($path,$name,$pluginName)
    {
        return Ak::file_put_contents($path, preg_replace("|(\n[^\n]*?/\*\* AUTOMATED START: $pluginName::$name \*/.*?/\*\* AUTOMATED END: $pluginName::$name \*/\n)|s","",Ak::file_get_contents($path)));
    }
    function autoInstallExtensions()
    {
        $path = AK_APP_PLUGINS_DIR.DS.$this->plugin_name.DS.'extensions';
        $extensionFiles = Ak::dir($path,array('recurse'=>true));
        foreach($extensionFiles as $extensionFile) {
            $this->installExtensions('extensions'.DS.$extensionFile);
        }
    }
    function installExtensions($fromFile,$pluginIdentifier = null)
    {
        if (substr($fromFile,0,5) == 'file:') {
            $fromFile = substr($fromFile,5);
        } else {
            $basePath = AK_APP_VENDOR_DIR.DS.'plugins'.DS.$this->plugin_name.DS;
            $fromFile = $basePath.DS.$fromFile;
        }
        if ($pluginIdentifier==null) {
            $pluginIdentifier = AkInflector::camelize($this->plugin_name);
        }
        $reflection = new AkReflectionFile($fromFile);
        unset($reflection->tokens);
        $classes = $reflection->getClasses();
        foreach ($classes as $class) {
            $install = $class->getTag('ExtensionPoint');
            if ($install!==false) {
                $methods = $class->getMethods();
                $installAll = true;
            } else {
                $installAll = false;
                $methods = $class->getMethods(array('tags'=>array('ExtensionPoint'=>'.*')));
            }
            foreach ($methods as $method) {
                if ($installAll) {
                    $class = $install;
                    $methodAlias = $method->getName();
                } else {
                    $installAs = $method->getTag('ExtensionPoint');
                    $parts=split('::',$installAs);
                    $class = $parts[0];
                    if (!isset($parts[1])) {
                        $methodAlias = $method->getName();
                    } else {
                        $methodAlias = $parts[1];
                    }
                }
                $class = trim($class);
                $methodAlias = trim($methodAlias);
                $method->setTag('Plugin',$pluginIdentifier);
                if (isset($this->extension_points[$class])) {
                    $path = AK_APP_DIR.DS.$this->extension_points[$class];
                    $this->_addMethodToClass($class,$methodAlias,$path,$method->toString(4,$methodAlias),$pluginIdentifier);
                }
            }
        }
    }
    function _checkUninstallDependencies()
    {
        $dependencyFile = AK_PLUGINS_DIR.DS.$this->plugin_name.DS.'dependent_plugins';
        
        if (file_exists($dependencyFile)) {
            $dependendPlugins = file($dependencyFile);
            if (!empty($dependendPlugins)) {
                if (empty($this->options['force'])) {
                    echo "\n";
                    echo Ak::t("The following %plugin %dependent depend on the plugin you are about to uninstall.",array('%plugin'=>AkT('plugin','quantify('.count($dependendPlugins).')'),'%dependent'=>AkT($dependendPlugins,'toSentence')));
                    echo Ak::t("Please uninstall the dependent %plugin first.",array('%plugin'=>AkT('plugin','quantify('.count($dependendPlugins).')')));
                    echo "\n";
                    $this->transactionFail();
                    die();
                } else {
                    echo "\n";
                    echo Ak::t("The following %plugin %dependent depend on the plugin you are about to uninstall.",array('%plugin'=>AkT('plugin','quantify('.count($dependendPlugins).')'),'%dependent'=>AkT($dependendPlugins,'toSentence')));
                    echo "\n";
                    $uninstall = AkInstaller::promptUserVar(
        'Are you sure you want to continue uninstalling (Answer with Yes)? The other plugins will malfunction.', array('default'=>'N'));
                    if ($uninstall != 'Yes') {
                        echo Ak::t('Uninstall cancelled.');
                        echo "\n";
                        $this->transactionFail();
                        die();
                    } else {
                        return true;
                    }
                }
            }
            return true;
        }

    }
    function _checkInstallDependencies()
    {
        if (isset($this->php_min_version)) {
            if(version_compare(PHP_VERSION,$this->php_min_version,'<')) {
                trigger_error(Ak::t("This plugin requires at least php version: %version", array('%version'=>$this->php_min_version)), E_USER_ERROR);
            }
        }

        if (isset($this->php_max_version)) {
            if(version_compare(PHP_VERSION,$this->php_max_version,'>')) {
                trigger_error("This plugin runs only on php version <= %version", array('%version'=>$this->php_min_version), E_USER_ERROR);
            }
        }
        if (isset($this->dependencies)) {
            $this->dependencies = Ak::toArray($this->dependencies);
            $pluginManager = new AkPluginManager();
            $plugins = $pluginManager->getInstalledPlugins();
            $missing = array();
            foreach ($this->dependencies as $dependency) {
                if (!in_array($dependency,$plugins)) {
                    $missing[] = $dependency;
                }
            }
            if (!empty($missing)) {
                echo "\n";
                $params = array('plugin'=>AkT('plugin','quantify('.count($missing).')'),'missing'=>AkT($missing,'toSentence'));
                echo Ak::t("This plugin depends on the %plugin %missing. Please install the missing plugins first.",$params);
                echo "\n";
                return false;
            } else {
                /**
                 * register the dependent plugins
                 */
                foreach ($this->dependencies as $dependency) {
                    $dependencyFile = AK_PLUGINS_DIR.DS.$dependency.DS.'dependent_plugins';
                    if (($fileExists = file_exists($dependencyFile))) {
                        $dependendPlugins = file($dependencyFile);
                    } else {
                        $dependendPlugins = array();
                    }
                    if (!in_array($this->plugin_name,$dependendPlugins)) {
                        $dependendPlugins[] = $this->plugin_name;
                        
                    }
                    if (!empty($dependendPlugins)) {
                        Ak::file_put_contents($dependencyFile,implode("\n",$dependendPlugins));
                    } else if ($fileExists) {
                        unlink($dependencyFile);
                    }
                    
                }
            }
        }
        return true;
    }
    function removeExtensions($pluginIdentifier = null)
    {
        
        if ($pluginIdentifier == null) {
            $pluginIdentifier = AkInflector::camelize($this->plugin_name);
        }

        foreach ($this->extension_points as $targetClass=>$baseFile) {
            $file = AK_APP_DIR.DS.$baseFile;
            $reflection = new AkReflectionFile($file);
            $classes = $reflection->getClasses();
            foreach ($classes as $class) {
                $methods = $class->getMethods(array('tags'=>array('Plugin'=>$pluginIdentifier)));
                foreach ($methods as $method) {
                    $this->_removeMethodFromClass($file,$method->getName(),$pluginIdentifier);
                }
            }
        }
    }

}
?>