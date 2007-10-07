<?php

@set_time_limit(0);
@ini_set('memory_limit', -1);

defined('AK_PLUGINS_DIR') ? null : define('AK_PLUGINS_DIR', AK_APP_DIR.DS.'vendor'.DS.'plugins');
defined('AK_PLUGINS') ? null : define('AK_PLUGINS', 'auto');

class AkPluginManager extends AkObject
{
    var $main_repository = 'http://svn.akelos.org/plugins';

    var $respository_discovery_page = 'http://wiki.akelos.org/plugins';



    function getAvailableRepositories($force_reload = false)
    {
        if($force_reload || empty($this->repositories)){
            $this->repositories = array($this->main_repository);
            if(file_exists($this->_getRepositoriesConfigPath())){
                $repository_candidates = array_diff(array_map('trim', explode("\n",Ak::file_get_contents($this->_getRepositoriesConfigPath()))), array(''));
                if(!empty($repository_candidates)){
                    foreach ($repository_candidates as $repository_candidate){
                        if(strlen($repository_candidate) > 0 && $repository_candidate[0] != '#' && strstr($repository_candidate,'plugins')){
                            $this->repositories[] = $repository_candidate;
                        }
                    }
                }
            }
        }
        return $this->repositories;
    }



    function addRepository($repository_path)
    {
        Ak::file_add_contents($this->_getRepositoriesConfigPath(), $repository_path."\n");
    }



    function removeRepository($repository_path)
    {
        if(file_exists($this->_getRepositoriesConfigPath())){
            $repositories = Ak::file_get_contents($this->_getRepositoriesConfigPath());
            if(!strstr($repositories, $repository_path)){
                return false;
            }
            $repositories = str_replace(array($repository_path, "\r", "\n\n"), array('', "\n", "\n"), $repositories);
            Ak::file_put_contents($this->_getRepositoriesConfigPath(), $repositories);
        }
    }



    function getPlugins($force_update = false)
    {
        if(!$force_update || !is_file($this->_getRepositoriesCahePath())){
            if(!$this->_updateRemotePluginsList()){
                return array();
            }
        }

        return Ak::convert('yaml', 'array', Ak::file_get_contents($this->_getRepositoriesCahePath()));
    }



    function getInstalledPlugins()
    {
        return array_shift(Ak::dir(AK_PLUGINS_DIR, array('files' => 'false')));
    }



    function installPlugin($plugin_name, $repository = null)
    {
        $plugin_name = Ak::sanitize_include($plugin_name, 'high');
        if(empty($repository)){
            $available_plugins = $this->getPlugins();
        }else{
            $available_plugins = array();
            $this->_addAvailablePlugins_($repository, &$available_plugins);
        }

        if(empty($available_plugins[$plugin_name])){
            trigger_error(Ak::t('Could not find %plugin_name plugin', array('%plugin_name' => $plugin_name)), E_USER_NOTICE);
            return false;
        }elseif (empty($repository)){
            $repository = $available_plugins[$plugin_name];
        }

        $this->_copyRemoteDir(rtrim($repository, '/').'/'.$plugin_name.'/', AK_PLUGINS_DIR);

        $this->_runInstaller($plugin_name, 'install');
    }



    function _runInstaller($plugin_name, $install_or_uninstall = 'install')
    {
        $plugin_dir = AK_PLUGINS_DIR.DS.$plugin_name;
        if(file_exists($plugin_dir.DS.'installer'.DS.$plugin_name.'_installer.php')){
            require_once($plugin_dir.DS.'installer'.DS.$plugin_name.'_installer.php');
            $class_name = AkInflector::camelize($plugin_name.'_installer');
            if(class_exists($class_name)){
                $Installer =& new $class_name();
                $Installer->$install_or_uninstall();
            }
        }
    }



    function updatePlugin($plugin_name, $repository = null)
    {
        return $this->installPlugin($plugin_name, $repository);
    }




    function uninstallPlugin($plugin_name)
    {
        $plugin_name = Ak::sanitize_include($plugin_name, 'high');
        $this->_runInstaller($plugin_name, 'uninstall');
        Ak::directory_delete(AK_PLUGINS_DIR.DS.$plugin_name);
    }



    function _copyRemoteDir($source, $destination)
    {
        $dir_name = trim(substr($source, strrpos(rtrim($source, '/'), '/')),'/');
        Ak::make_dir($destination.DS.$dir_name);

        list($directories, $files) = $this->_parseRemoteAndGetDirectoriesAndFiles($source);

        foreach ($files as $file){
            $this->_copyRemoteFile($source.$file, $destination.DS.$dir_name.DS.$file);
        }

        foreach ($directories as $directory){
            $this->_copyRemoteDir($source.$directory.'/', $destination.DS.$dir_name);
        }
    }



    function _copyRemoteFile($source, $destination)
    {
        Ak::file_put_contents($destination, Ak::url_get_contents($source));
    }



    function _updateRemotePluginsList()
    {
        $new_plugins = array();
        foreach ($this->getAvailableRepositories() as $repository){
            $this->_addAvailablePlugins_($repository, $new_plugins);
        }
        if(empty($new_plugins)){
            trigger_error(Ak::t('Could not fetch remote plugins from one of these repositories: %repositories', array('%repositories' => "\n".join("\n", $this->getAvailableRepositories()))), E_USER_NOTICE);
            return false;
        }
        return Ak::file_put_contents($this->_getRepositoriesCahePath(), Ak::convert('array', 'yaml', $new_plugins));
    }



    function _addAvailablePlugins_($repository, &$plugins_list)
    {
        list($directories) = $this->_parseRemoteAndGetDirectoriesAndFiles($repository);
        foreach ($directories as $plugin){
            if(empty($plugins_list[$plugin])){
                $plugins_list[$plugin] = $repository;
            }
        }
    }



    function _parseRemoteAndGetDirectoriesAndFiles($remote_path)
    {
        $directories = $files = array();
        $remote_contents = Ak::url_get_contents(rtrim($remote_path, '/').'/');

        if(preg_match_all('/href="([A-Za-z\-_0-9]+)\/"/', $remote_contents, $matches)){
            foreach ($matches[1] as $directory){
                $directories[] = trim($directory);
            }
        }
        if(preg_match_all('/href="(\.?[A-Za-z\-_0-9\.]+)"/', $remote_contents, $matches)){
            foreach ($matches[1] as $file){
                $files[] = trim($file);
            }
        }
        return array($directories, $files);
    }



    function _getRepositoriesConfigPath()
    {
        return AK_CONFIG_DIR.DS.'plugin_repositories.txt';
    }



    function _getRepositoriesCahePath()
    {
        return AK_TMP_DIR.DS.'plugin_repositories.yaml';
    }
}

?>
