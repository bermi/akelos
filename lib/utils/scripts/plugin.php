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
require_once(AK_LIB_DIR.DS.'AkPlugin.php');
require_once(AK_LIB_DIR.DS.'AkPlugin/AkPluginManager.php');
defined('AK_SKIP_DB_CONNECTION') && AK_SKIP_DB_CONNECTION ? ($dsn='') : Ak::db(&$dsn);

$ak_app_dir = AK_APP_DIR;
$script_name = array_shift($argv);
$command = strtolower(array_shift($argv));
array_unshift($argv, $script_name);
$_SERVER['argv'] = $argv;

$available_commands = array('list', 'sources', 'source', 'unsource', 'discover', 'install', 'update', 'remove', 'info');

if(!in_array($command, $available_commands)){
    echo <<<BANNER
Usage: {$script_name} command [OPTIONS]

Akelos plugin manager.

COMMANDS

  discover   Discover plugin repositories.
  list       List available plugins.
  install    Install plugin(s) from known repositories or URLs.
  update     Update installed plugins.
  remove     Uninstall plugins.
  source     Add a plugin source repository.
  unsource   Remove a plugin repository.
  sources    List currently configured plugin repositories.


EXAMPLES

  Install a plugin:
    {$script_name} install acts_as_versioned
    
  Install a plugin from a subversion URL:
    {$script_name} install http://svn.akelos.org/plugins/acts_as_versioned

  Install a plugin and add a svn:externals entry to app/vendor/plugins
    {$script_name} install -x acts_as_versioned

  List all available plugins:
    {$script_name} list

  List plugins in the specified repository:
    {$script_name} list --source=http://svn.akelos.org/plugins/

  Discover and prompt to add new repositories:
    {$script_name} discover

  Discover new repositories but just list them, don't add anything:
    {$script_name} discover -l

  Add a new repository to the source list:
    {$script_name} source http://svn.akelos.org/plugins/

  Remove a repository from the source list:
    {$script_name} unsource http://svn.akelos.org/plugins/

  Show currently configured repositories:
    {$script_name} sources

BANNER;
    exit;
}


set_time_limit(0);
error_reporting(E_ALL);

require_once (AK_VENDOR_DIR.DS.'pear'.DS.'Console'.DS.'Getargs.php');
function get_console_options_for($description, $console_configuration)
{
    global $script_name, $argv;

    $args =& Console_Getargs::factory($console_configuration);
    if (PEAR::isError($args)) {

        $replacements = array(
        '-p --parameters values(1-...)' => 'install  plugin_name,URL  ...',
        'Usage: '.basename(__FILE__) =>"Usage: $script_name",
        '[param1] ' => 'plugin_name PLUGIN_URL'
        );
        echo "\n$description\n".str_repeat('-', strlen($description)+1)."\n";
        if ($args->getCode() === CONSOLE_GETARGS_ERROR_USER) {
            echo str_replace(array_keys($replacements), array_values($replacements),
            Console_Getargs::getHelp($console_configuration, null, $args->getMessage()))."\n";
        } else if ($args->getCode() === CONSOLE_GETARGS_HELP) {
            echo str_replace(array_keys($replacements), array_values($replacements),
            @Console_Getargs::getHelp($console_configuration))."\n";
        }
        exit;
    }
    return $args->getValues();
}


$PluginManager = new AkPluginManager();



/**
 * List available plugins.
 */
if($command == 'list') {

    $options = get_console_options_for('List available plugins.', array(
    'source'    => array('short' => 's', 'desc' =>  "Use the specified plugin repositories. --source URL1 URL2", 'max'=> -1, 'min'=> 1),
    'local'     => array('short' => 'l', 'desc' =>  "List locally installed plugins.", 'max' => 0),
    'remote'    => array('short' => 'r', 'desc' =>  "List remotely available plugins. This is the default behavior", 'max' => 0)
    ));

    if(isset($options['local']) && isset($options['remote'])){
        die("Local and remote arguments can not be used simultaneously\n");
    }
    if(!empty($options['source'])){
        $PluginManager->tmp_repositories = Ak::toArray($options['source']);
    }
    $installed_plugins = $PluginManager->getInstalledPlugins();
    if(isset($options['local'])){
        $plugins_dir = $ak_app_dir.DS.'vendor'.DS.'plugins';
        if(empty($installed_plugins)){
            die("There are not plugins intalled at $plugins_dir\n");
        }else{
            echo "Plugins installed at  $plugins_dir:\n\n";
            foreach ($installed_plugins as $plugin){
                echo " * ".$plugin." (".rtrim($PluginManager->getRepositoryForPlugin($plugin),'/')."/$plugin)\n";
            }
            die("\n");
        }
    }else{
        $plugins = $PluginManager->getPlugins(true);
        if(empty($plugins)){
            die("Could not find remote plugins\n");
        }else{
            $repsositories = array();
            foreach ($plugins as $plugin => $repository){
                if(empty($repsositories[$repository])){
                    $repsositories[$repository] = array();
                }
                if(in_array($plugin, $installed_plugins)){
                    array_unshift($repsositories[$repository], '[INSTALLED] '.$plugin);
                }else{
                    $repsositories[$repository][] = $plugin;
                }
            }
            foreach ($repsositories as $repsository=>$plugins){
                echo "Plugins available at $repository:\n";
                echo join(", ",$plugins)."\n\n";
            }
        }
    }
    die();
}



/**
 * List configured plugin repositories.
 */
if($command == 'sources') {
    $options = get_console_options_for('List configured plugin repositories.', array(
    'check'     => array('short' => 'c', 'desc' =>  "Report status of repository.", 'max' => 0)
    ));
    $repositories = $PluginManager->getAvailableRepositories(true);

    foreach ($repositories as $repository){
        $checked = isset($options['check']) && !Ak::url_get_contents($repository, array('timeout'=>5)) ? ' [Connection timeout].' : '';
        echo " * $repository$checked\n";
    }
    die();
}




/**
 * Adds a repository to the default search list.
 */
if($command == 'source') {
    array_shift($argv);
    $options = Ak::toArray($argv);

    if(empty($options)){
        die("You need to provide at least one repository to add to the default search list.\n");
    }

    foreach ($options as $repository){
        if(Ak::url_get_contents($repository, array('timeout'=>10))){
            $PluginManager->addRepository($repository);
            echo "Added: $repository\n";
        }else{
            echo "Not added: Connection error for repository $repository.\n";
        }
    }
    die();
}




/**
 * Removes a repository to the default search list.
 */
if($command == 'unsource') {
    array_shift($argv);
    $options = Ak::toArray($argv);

    if(empty($options)){
        die("You need to provide at least one repository to remove from the default search list.\n");
    }

    foreach ($options as $repository){
        $PluginManager->removeRepository($repository);
        echo "Removed: $repository\n";
    }
    die();
}




/**
 * Discover repositories referenced on a page.
 */
if($command == 'discover') {

    $options = get_console_options_for('Discover repositories referenced on a page.', array(
    'source'     => array('short' => 's', 'desc' =>  "Use the specified plugin repositories instead of the default.", 'max' => 1),
    'list'     => array('short' => 'l', 'desc' =>  "List but don't prompt or add discovered repositories.", 'max' => 0),
    'no-prompt'     => array('short' => 'n', 'desc' =>  "Add all new repositories without prompting.", 'max' => 0)
    ));

    if(!empty($options['source'])){
        $PluginManager->respository_discovery_page = $options['source'];
    }

    $repositories = $PluginManager->getDiscoveredRepositories();
    $default = 'Y';

    foreach ($repositories as $repository){
        echo "* $repository";
        if(!empty($options['list'])){
            echo "\n";
        }else{
            echo $default == 'Y' ? "[Y/n]:" : "[y/N]:";

            $key = trim(strtolower(fgetc(STDIN)));

            if((empty($key) && $default == 'N') || $key == 'n'){
                echo "Skipped $repository.\n";
                $default = 'N';
            }elseif((empty($key) && $default == 'Y') || $key == 'y'){
                if(Ak::url_get_contents($repository, array('timeout'=>10))){
                    $PluginManager->addRepository($repository);
                    echo "Added $repository.\n";
                }else{
                    echo "Not added: Connection error for repository $repository.\n";
                }
                $default = 'Y';
            }

            if(!empty($key)){
                fgetc(STDIN);
            }
        }

    }
    die();
}




/**
 * Install plugins.
 */
if($command == 'install') {

    $options = get_console_options_for('Install one or more plugins.', array(
    CONSOLE_GETARGS_PARAMS => array('short' => 'p', 'desc' =>  "You can specify plugin names as given in 'plugin list' output or absolute URLs to a plugin repository.", 'max' => -1, 'min' => 1),
    'externals'     => array('short' => 'x', 'desc' =>  "Use svn:externals to grab the plugin. Enables plugin updates and plugin versioning.", 'max' => 0),
    'checkout'     => array('short' => 'o', 'desc' =>  "Use svn checkout to grab the plugin. Enables updating but does not add a svn:externals entry.", 'max' => 0),
    'revision'     => array('short' => 'r', 'desc' =>  "Checks out the given revision from subversion. Ignored if subversion is not used.", 'max' => 1, 'min' => 1),
    'force'     => array('short' => 'f', 'desc' =>  "Reinstalls a plugin if it's already installed.", 'max' => 0),
    ));

    if(empty($options['parameters'])){
        die("You must supply at least one plugin name or plugin URL to install.\n");
    }

    $best = $PluginManager->guessBestInstallMethod($options);
    if($best == 'http' && (!empty($options['externals']) ||  !empty($options['checkout']))){
        die("Cannot install using subversion because `svn' cannot be found in your PATH\n");
    }elseif ($best == 'export' && !empty($options['externals'])){
        die("Cannot install using externals because this project is not under subversion.");
    }elseif ($best == 'export' && !empty($options['checkout'])){
        die("Cannot install using checkout because this project is not under subversion.");
    }

    $plugins = Ak::toArray($options['parameters']);

    foreach ($plugins as $plugin){
        $repository = null;
        $plugin_name = basename($plugin);
        if($plugin_name != $plugin){
            $repository = preg_replace('/\/?'.$plugin_name.'$/', '', trim($plugin));
        }
        if(!is_dir($plugin)){
            if (!@$PluginManager->getRepositoryForPlugin($plugin_name, $repository)){
                is_null($repository) ? $repository = AK_PLUGINS_MAIN_REPOSITORY : null;
                echo "\nPlugin $plugin_name not found @ ".$repository.".\n";
                continue;
            }
        }
        echo "\nInstalling $plugin\n";
        $PluginManager->installPlugin($plugin_name, $repository, $options);
    }

    echo "Done.\n";
    die();
}

/**
 * Update plugins.
 */
if($command == 'update') {
    $options = get_console_options_for('Update installed plugins.', array(
    'externals'     => array('short' => 'x', 'desc' =>  "Use svn:externals to grab the plugin. Enables plugin updates and plugin versioning.", 'max' => 0),
    'checkout'     => array('short' => 'o', 'desc' =>  "Use svn checkout to grab the plugin. Enables updating but does not add a svn:externals entry.", 'max' => 0),
    ));

    $best = $PluginManager->guessBestInstallMethod($options);
    if($best == 'http' && (!empty($options['externals']) ||  !empty($options['checkout']))){
        die("Cannot install using subversion because `svn' cannot be found in your PATH\n");
    }elseif ($best == 'export' && !empty($options['externals'])){
        die("Cannot install using externals because this project is not under subversion.");
    }elseif ($best == 'export' && !empty($options['checkout'])){
        die("Cannot install using checkout because this project is not under subversion.");
    }
    $installed_plugins = $PluginManager->getInstalledPlugins();
    foreach ($installed_plugins as $plugin){
        $repository_for_plugin = $PluginManager->getRepositoryForPlugin($plugin);
        echo "Updating $plugin from $repository_for_plugin.\n";
        $PluginManager->updatePlugin($plugin,$repository_for_plugin,$options);
    }
    echo "Done.\n";
    die();
}


/**
 * Remove plugins.
 */
if($command == 'remove') {

    $options = get_console_options_for('Remove plugins.', array(
    CONSOLE_GETARGS_PARAMS => array('short' => 'p', 'desc' =>  "You can specify plugin names as given in 'plugin list' output or absolute URLs to a plugin repository.", 'max' => -1, 'min' => 1)));

    if(empty($options['parameters'])){
        echo "You must supply at least one plugin name or plugin URL to uninstall.\n";
        echo "\nInstalled Plugins: ";
        echo join(', ',$PluginManager->getInstalledPlugins()).'.';
        die();
    }

    $plugins = Ak::toArray($options['parameters']);

    foreach ($plugins as $plugin){
        $plugin_name = basename($plugin);
        echo "\nUninstalling $plugin\n";
        $PluginManager->uninstallPlugin($plugin_name);
    }

    echo "Done.\n";
    die();
}




/**
 * Shows plugin info at plugin_path/ABOUT.
 */
if($command == 'info') {

    $options = get_console_options_for('Remove plugins.', array(
    CONSOLE_GETARGS_PARAMS => array('short' => 'p', 'desc' =>  "Plugin names as given in 'plugin list' output or absolute URL to a plugin repository.", 'max' => 1, 'min' => 1)));

    if(empty($options['parameters'])){
        die("You must supply a plugins name or plugin URL.\n");
    }

    $plugin = $options['parameters'];
    $plugin_name = basename($plugin);
    if($plugin_name != $plugin){
        $repository = preg_replace('/\/?'.$plugin_name.'$/', '', trim($plugin));
    }else {
        $repository = $PluginManager->getRepositoryForPlugin($plugin_name);
    }

    $about = Ak::url_get_contents(rtrim($repository,'/').'/'.$plugin_name.'/ABOUT', array('timeout'=>10));
    echo empty($about) ? "Could not get plugin information." : $about;

    die("\n");
}

?>