<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

class AkPluginInstaller extends AkInstaller
{
    public
    $auto_install_files         = true,
    $auto_install_extensions    = true,
    $auto_remove_extensions     = true,
    $plugin_name,
    $extension_points           = array(
    'BaseActiveRecord'      => 'models/base_active_record.php',
    'BaseActionController'  => 'controllers/base_action_controller.php'
    );

    protected
    $_plugin_definition;

    public function __construct($db_connection = null, $plugin_name = '') {
        parent::__construct($db_connection);
        $this->plugin_name = $plugin_name;
    }

    public function installFiles($installer_dir = 'installer', $source_dir = 'files') {
        $this->copyFilesIntoApp($this->app_plugins_dir.DS.$this->plugin_name.DS.$installer_dir.DS.$source_dir);
    }

    public function autoInstallExtensions() {
        $path = $this->app_plugins_dir.DS.$this->plugin_name.DS.'extensions';
        $extensionFiles = AkFileSystem::dir($path,array('recurse'=>true, 'skip_path_restriction'=>true));
        foreach($extensionFiles as $extensionFile) {
            $this->installExtensions('extensions'.DS.$extensionFile);
        }
    }

    public function installExtensions($fromFile, $pluginIdentifier = null) {
        if (substr($fromFile,0,5) == 'file:') {
            $fromFile = trim(substr($fromFile,5));
        } else {
            $basePath = $this->app_vendor_dir.DS.'plugins'.DS.$this->plugin_name.DS;
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
                    $parts = explode('::',$installAs);
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
                    $path = $this->app_app_dir.DS.$this->extension_points[$class];
                    $this->_addMethodToClass($class,$methodAlias,$path,$method->toString(4,$methodAlias),$pluginIdentifier);
                }
            }
        }
    }

    public function removeExtensions($pluginIdentifier = null) {
        if ($pluginIdentifier == null) {
            $pluginIdentifier = AkInflector::camelize($this->plugin_name);
        }

        foreach ($this->extension_points as $targetClass=>$baseFile) {
            $file = $this->app_app_dir.DS.$baseFile;
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

    protected function _removeDependency() {
        if (!empty($this->dependencies)) {
            $this->dependencies = Ak::toArray($this->dependencies);

            foreach ($this->dependencies as $dependency) {
                $dependencyFile = $this->app_plugins_dir.DS.$dependency.DS.'dependent_plugins';
                if (($fileExists = file_exists($dependencyFile))) {
                    $dependendPlugins = file($dependencyFile);
                } else {
                    $dependendPlugins = array();
                }
                $dependendPlugins = array_diff($dependendPlugins,array($this->plugin_name));
                if (!empty($dependendPlugins)) {
                    AkFileSystem::file_put_contents($dependencyFile,implode("\n",$dependendPlugins), array('skip_path_restriction'=>true));
                } else if ($fileExists) {
                    unlink($dependencyFile);
                }
            }
        }
    }

    protected function _runInstallerMethod($method_prefix, $version, $options = array(), $version_number = null) {
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

    protected function _checkUninstallDependencies() {
        $dependencyFile = $this->app_plugins_dir.DS.$this->plugin_name.DS.'dependent_plugins';

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
                    $uninstall = AkConsole::promptUserVar(
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

    protected function _checkInstallDependencies() {
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
                    $dependencyFile = $this->app_plugins_dir.DS.$dependency.DS.'dependent_plugins';
                    if (($fileExists = file_exists($dependencyFile))) {
                        $dependendPlugins = file($dependencyFile);
                    } else {
                        $dependendPlugins = array();
                    }
                    if (!in_array($this->plugin_name,$dependendPlugins)) {
                        $dependendPlugins[] = $this->plugin_name;

                    }
                    if (!empty($dependendPlugins)) {
                        AkFileSystem::file_put_contents($dependencyFile,implode("\n",$dependendPlugins), array('skip_path_restriction'=>true));
                    } else if ($fileExists) {
                        unlink($dependencyFile);
                    }

                }
            }
        }
        return true;
    }

    protected function _addMethodToClass($class,$name,$path,$methodString, $pluginName) {
        $targetReflection = new AkReflectionFile($path);
        $classes = $targetReflection->getClasses();
        foreach($classes as $c) {
            $method = $c->getMethod($name);
            if ($method !== false) {
                echo "Method $name already exists on class $class in file $path\n";
                return false;
            }
        }
        $contents = file_exists($path) ? AkFileSystem::file_get_contents($path, array('skip_path_restriction'=>true)) : '';

        return (AkFileSystem::file_put_contents($path, preg_replace('|class '.$class.'(.*?)\n.*?{|i',"class $class\\1
{
/** AUTOMATED START: $pluginName::$name */
$methodString
/** AUTOMATED END: $pluginName::$name */
",$contents), array('skip_path_restriction'=>true))>0?true:'Could not write to '.$path);

    }

    protected function _removeMethodFromClass($path,$name,$pluginName) {
        return AkFileSystem::file_put_contents($path, preg_replace("|(\n[^\n]*?/\*\* AUTOMATED START: $pluginName::$name \*/.*?/\*\* AUTOMATED END: $pluginName::$name \*/\n)|s","",AkFileSystem::file_get_contents($path)), array('skip_path_restriction'=>true));
    }
}

