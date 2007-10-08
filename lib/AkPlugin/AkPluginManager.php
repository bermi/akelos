<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2006, Akelos Media, S.L.  & Bermi Ferrer Martinez |
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+


/**
 * Plugin manager
 * 
 * @package Plugins
 * @subpackage Manager
 * @author Bermi Ferrer <bermi a.t akelos c.om> 2007
 * @copyright Copyright (c) 2002-2007, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

@set_time_limit(0);
@ini_set('memory_limit', -1);

require_once(AK_LIB_DIR.DS.'AkPlugin.php');

defined('AK_PLUGINS_MAIN_REPOSITORY') ? null : define('AK_PLUGINS_MAIN_REPOSITORY', 'http://svn.akelos.org/plugins');
defined('AK_PLUGINS_REPOSITORY_DISCOVERY_PAGE') ? null : define('AK_PLUGINS_REPOSITORY_DISCOVERY_PAGE', 'http://wiki.akelos.org/plugins');

/**
 * Plugin manager
 * 
 * @package Plugins
 * @subpackage Manager
 * @author Bermi Ferrer <bermi a.t akelos c.om> 2007
 * @copyright Copyright (c) 2002-2007, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */
class AkPluginManager extends AkObject
{

    /**
     * Main repository, must be an Apache mod_svn interface to subversion. Defaults to AK_PLUGINS_MAIN_REPOSITORY.
     * @var    string
     * @access public
     */
    var $main_repository = AK_PLUGINS_MAIN_REPOSITORY;

    /**
     * Repository discovery page.
     * 
     * A wiki page containing links to repositories. Links on that wiki page 
     * must link to an http:// protocol (no SSL yet) and end in plugins.
     * Defaults to  AK_PLUGINS_REPOSITORY_DISCOVERY_PAGE
     * @var    string
     * @access public
     */
    var $respository_discovery_page = AK_PLUGINS_REPOSITORY_DISCOVERY_PAGE;



    /**
     * Gets a list of available repositories.
     * 
     * @param  boolean $force_reload Forces reloading, useful for testing and when running as an application server.
     * @return array   List of repository URLs
     * @access public 
     */
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



    /**
     * Ads a repository to the know repositories list.
     * 
     * @param  string $repository_path  An Apache mod_svn interface to subversion.
     * @return void  
     * @access public
     */
    function addRepository($repository_path)
    {
        Ak::file_add_contents($this->_getRepositoriesConfigPath(), $repository_path."\n");
    }



    /**
     * Removes a repository to the know repositories list.
     * 
     * @param  string $repository_path  An Apache mod_svn interface to subversion.
     * @return boolean Returns false if the repository was not available
     * @access public 
     */
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



    /**
     * Gets a list of available plugins.
     * 
     * Goes through each trusted plugin server and retrieves the name of the 
     * folders (plugins) on the repository path.
     * 
     * @param  boolean $force_update If it is not set to true, it will only check remote sources once per hour
     * @return array   Returns an array containing "plugin_name" => "repository URL"
     * @access public 
     */
    function getPlugins($force_update = false)
    {
        if(!$force_update || !is_file($this->_getRepositoriesCahePath()) || filemtime($this->_getRepositoriesCahePath()) > 3600){
            if(!$this->_updateRemotePluginsList()){
                return array();
            }
        }

        return Ak::convert('yaml', 'array', Ak::file_get_contents($this->_getRepositoriesCahePath()));
    }



    /**
     * Retrieves a list of installed plugins
     * 
     * @return array  Returns an array with the plugins available at AK_PLUGINS_DIR
     * @access public
     */
    function getInstalledPlugins()
    {
        $Loader = new AkPluginLoader();
        return $Loader->getAvailablePlugins();
    }



    /**
     * Installs a plugin
     * 
     * Install a plugin from a remote resource.
     * 
     * Plugins can have an Akelos installer at located at "plugin_name/installer/plugin_name_installer.php"
     * If the installer is available, it will run the "PluginNameInstaller::install()" method, which will trigger
     * all the up_* methods for the installer.
     * 
     * @param  string  $plugin_name Plugin name
     * @param  unknown $repository   An Apache mod_svn interface to subversion. If not provided it will use a trusted repository.
     * @return mixed Returns false if the plugin can't be found.
     * @access public 
     */
    function installPlugin($plugin_name, $repository = null)
    {
        $plugin_name = Ak::sanitize_include($plugin_name, 'high');
        $repository = $this->_getRepositoryForPlugin($plugin_name, $repository);
        $this->_copyRemoteDir(rtrim($repository, '/').'/'.$plugin_name.'/', AK_PLUGINS_DIR);
        $this->_runInstaller($plugin_name, 'install');
    }


    /**
     * Updates a plugin if there changes.
     * 
     * This method is the same as install, but can you can avoid an updates if 
     * you keep a CHANGELOG file for your plugin it will only perform the update
     * if the CHANGELOG has hanged from last version
     * 
     * @param  string  $plugin_name Plugin name
     * @param  string $repository   An Apache mod_svn interface to subversion. If not provided it will use a trusted repository.
     * @return mixed Returns false if the plugin can't be found, true if is already updated and null when there is an update
     * @access public 
     */
    function updatePlugin($plugin_name, $repository = null)
    {
        $plugin_name = Ak::sanitize_include($plugin_name, 'high');
        $repository = $this->_getRepositoryForPlugin($plugin_name, $repository);

        if(is_file(AK_PLUGINS_DIR.DS.$plugin_name.DS.'CHANGELOG') &&
        md5(Ak::url_get_contents(rtrim($repository, '/').'/'.$plugin_name.'/CHANGELOG')) == md5_file(AK_PLUGINS_DIR.DS.$plugin_name.DS.'CHANGELOG')){
            return false;
        }

        return $this->installPlugin($plugin_name, $repository);
    }


    /**
     * Uninstalls an existing plugin
     * 
     * Plugins can have an Akelos installer at located at "plugin_name/installer/plugin_name_installer.php"
     * If the installer is available, it will run the "PluginNameInstaller::uninstall()" method, which will trigger
     * all the down_* methods for the installer.
     * 
     * @param  string  $plugin_name Plugin name
     * @return void  
     * @access public
     */
    function uninstallPlugin($plugin_name)
    {
        $plugin_name = Ak::sanitize_include($plugin_name, 'high');
        $this->_runInstaller($plugin_name, 'uninstall');
        Ak::directory_delete(AK_PLUGINS_DIR.DS.$plugin_name);
    }


    /**
     * Gets a list of repositories available at the web page defined by AK_PLUGINS_REPOSITORY_DISCOVERY_PAGE (http://wiki.akelos.org/plugins by default)
     * 
     * @return array An array of non trusted repositories available at http://wiki.akelos.org/plugins
     * @access public 
     */
    function getDiscoveredRepositories()
    {
        return array_diff($this->_getRepositoriesFromRemotePage(), $this->getAvailableRepositories(true));
    }


    /**
     * Returns the repository for a given $plugin_name
     * 
     * @param  string  $plugin_name     The name of the plugin
     * @param  string  $repository  If a repository name is provided it will check for the plugin name existance.
     * @return mixed Repository URL or false if plugin can't be found   
     * @access private
     */
    function _getRepositoryForPlugin($plugin_name, $repository = null)
    {
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
        return $repository;
    }

    /**
     * Runs the plugin installer/uninstaller if available
     * 
     * Plugins can have an Akelos installer at located at "plugin_name/installer/plugin_name_installer.php"
     * If the installer is available, it will run the "PluginNameInstaller::install/uninstall()" method, which will trigger
     * all the up/down_* methods for the installer.
     * 
     * @param  string  $plugin_name     The name of the plugin
     * @param  string  $install_or_uninstall What to do, options are install or uninstall
     * @return void   
     * @access private
     */
    function _runInstaller($plugin_name, $install_or_uninstall = 'install')
    {
        $plugin_dir = AK_PLUGINS_DIR.DS.$plugin_name;
        if(file_exists($plugin_dir.DS.'installer'.DS.$plugin_name.'_installer.php')){
            require_once($plugin_dir.DS.'installer'.DS.$plugin_name.'_installer.php');
            $class_name = AkInflector::camelize($plugin_name.'_installer');
            if(class_exists($class_name)){
                $Installer =& new $class_name();
                $Installer->warn_if_same_version = false;
                $Installer->$install_or_uninstall();
            }
        }
    }


    /**
     * Retrieves the URL's from the AK_PLUGINS_REPOSITORY_DISCOVERY_PAGE (http://wiki.akelos.org/plugins by default)
     * 
     * Plugins in that page must follow this convention:
     * 
     *  * Only http:// protocol. No https:// or svn:// support yet
     *  * The URL must en in plugins to be fetched automatically
     * 
     * @return array   An array of existing repository URLs
     * @access private
     */
    function _getRepositoriesFromRemotePage()
    {

        $repositories = array();
        if(preg_match_all('/href="(http:\/\/(?!wiki\.akelos\.org)[^"]*plugins)/', Ak::url_get_contents($this->respository_discovery_page), $matches)){
            $repositories = array_unique($matches[1]);
        }
        return $repositories;
    }

    /**
     * Copy recursively a remote svn dir into a local path.
     * 
     * Downloads recursively the contents of remote directories from a mod_svn Apache subversion interface to a local destination.
     * 
     * File or directory permissions are not copied, so you will need to use installers to fix it if required.
     * 
     * @param  string  $source      An Apache mod_svn interface to subversion URL.
     * @param  string  $destination Destination directory
     * @return void   
     * @access private
     */
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



    /**
     * Copies a remote file into a local destination
     * 
     * @param  string $source      Source URL
     * @param  string  $destination Destination directory
     * @return void   
     * @access private
     */
    function _copyRemoteFile($source, $destination)
    {
        Ak::file_put_contents($destination, Ak::url_get_contents($source));
    }



    /**
     * Performs an update of available cached plugins.
     * 
     * @return boolean   
     * @access private
     */
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



    /**
     * Modifies $plugins_list adding the plugins available at $repository
     * 
     * @param  string $repository    Repository URL
     * @param  array   $plugins_list Plugins list in the format 'plugin_name' => 'repository'
     * @return void   
     * @access private
     */
    function _addAvailablePlugins_($repository, &$plugins_list)
    {
        list($directories) = $this->_parseRemoteAndGetDirectoriesAndFiles($repository);
        foreach ($directories as $plugin){
            if(empty($plugins_list[$plugin])){
                $plugins_list[$plugin] = $repository;
            }
        }
    }



    /**
     * Parses a remote Apache svn web page and returns a list of available files and directories
     * 
     * @param  string $remote_path Repository URL
     * @return array   an array like array($directories, $files). Use list($directories, $files) = $this->_parseRemoteAndGetDirectoriesAndFiles($remote_path) for getting the results of this method
     * @access private
     */
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



    /**
     * Trusted repositories location
     * 
     * By default trusted repositories are located at config/plugin_repositories.txt
     * 
     * @return string  Trusted repositories  path
     * @access private
     */
    function _getRepositoriesConfigPath()
    {
        return AK_CONFIG_DIR.DS.'plugin_repositories.txt';
    }



    /**
     * Cached informations about available plugins
     * 
     * @return string  Plugin information cache path. By default AK_TMP_DIR.DS.'plugin_repositories.yaml'
     * @access private
     */
    function _getRepositoriesCahePath()
    {
        return AK_TMP_DIR.DS.'plugin_repositories.yaml';
    }
}

?>
