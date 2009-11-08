<?php

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
 * @package ActiveSupport
 * @subpackage Config
 * @author Arno Schneider
 * @author Bermi Ferrer
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

/**
 * Config Reader
 * 
 * Provides access to config files stored in:
 * 
 * AK_APP_DIR/config/*.yml
 *
 * = Structure of a config file
 * 
 * A config file contains configuration directives for all
 * configured environments (development,testing,production).
 * 
 * A config file can have a default configuration section, which will 
 * be the base for all other environments. That means if a default configuration
 * directive is not overwritten in an environment, the default directive is active.
 * 
 * Example:
 * 
 * <code>
 * default:
 *          log:
 *              file:   /tmp/debug.log
 *              level:  verbose
 * 
 * development:
 *          log:
 *              file:   /tmp/development.log
 * 
 * testing:
 *          log:
 *              file:   /tmp/testing.log
 * 
 * production:
 *          log:
 *              file:   /tmp/production.log
 *              level:  error
 * </code>
 * 
 * The above example sets a log level of "verbose" as the default.
 * The default log file is in "/tmp/debug.log".
 * 
 * The environments development and testing overwrite the default log file.
 * 
 * The production environment overwrites as well the log file and the log level.
 * 
 * The Log level for development will be "verbose" (inherited from default).
 * The log level for testing will be "verbose" (inherited from default).
 * The log level for production will be "error" (overwritten the default level).
 * 
 * 
 * = Accessing configuration files
 * 
 * The format of the config files is YAML.
 * The convention is that a yaml file in:
 * 
 * AK_APP_DIR/config/myconfig.yml
 * 
 * can be accessed via:
 * 
 *      $config = new AkConfig();
 *      $config->get('myconfig'); // loads myconfig.yml and section "AK_ENVIRONMENT"
 * 
 * By default the configuration for the environment defined in AK_ENVIRONMENT will be loaded.
 * 
 * By providing the desired environment in the get call you can change that:
 * 
 *      $config = new AkConfig();
 *      $config->get('myconfig','production'); // loads myconfig.yml and section production
 *
 * = Config caching
 * 
 * The AkConfig class caches php representations of the yaml files inside:
 * 
 *      AK_TMP_DIR/ak_config/cache/$environment/$config.yml
 * 
 * As soon as the modification time of a yaml-config file changes, the cache is invalidated
 * and will be regenerated.
 */

class AkConfig
{
    const CONFIG_DIR = AK_CONFIG_DIR;

    static function getConstant($name)
    {
        return defined($name[1]) ? constant($name[1]) : '';
    }

    static function parseSettingsConstants($settingsStr)
    {
        return preg_replace_callback('/\$\{(AK_.*?)\}/',array('AkConfig','getConstant'),$settingsStr);
    }

    public function &get($namespace, $environment = AK_ENVIRONMENT, $raise_error_if_config_file_not_found = true, $uncached = false)
    {
        static $_configs = array();
        if (!$uncached && isset($_configs[$namespace]) && isset($_configs[$namespace][$environment])) {
            return $_configs[$namespace][$environment];
        }
        if ($uncached || !($config = $this->readCache($namespace, $environment))) {
            $config = $this->readConfig($namespace, $environment, $raise_error_if_config_file_not_found);
        }
        if (!isset($_configs[$namespace])) {
            $_configs[$namespace] = array($environment=>$config);
        } else {
            $_configs[$namespace][$environment] = $config;
        }
        return $_configs[$namespace][$environment];
    }

    public function readConfig($namespace, $environment = AK_ENVIRONMENT, $raise_error_if_config_file_not_found = true)
    {
        $yaml_file_name = $this->_generateConfigFileName($namespace);
        if (!is_file($yaml_file_name)){
            if($raise_error_if_config_file_not_found){
                die(Ak::t('Could not find %namespace settings file in %path.', array('%namespace'=>$namespace, '%path'=>$yaml_file_name))."\n");
            }
            return false;
        }

        require_once(AK_VENDOR_DIR.DS.'TextParsers'.DS.'spyc.php');
        $content = file_get_contents($yaml_file_name);
        $content = self::parseSettingsConstants($content);
        $config = Spyc::YAMLLoad($content);

        if (!is_array($config)){
            return false;
        }

        $default = isset($config['default'])?$config['default']:array();

        $configs = array();

        unset($config['default']);
        $environments = array_keys($config);
        $default_environments = array('testing','development','production');
        $environments = array_merge($default_environments, $environments);

        foreach($environments as $env) {
            $envConfig = $this->_merge($default, isset($config[$env])?$config[$env]:array());
            $this->writeCache($envConfig,$namespace,$env,$this->_useWriteCache($environment));
            $configs[$env] = $envConfig;
        }

        return isset($configs[$environment]) ? $configs[$environment] : (in_array($environments, $environments) ? $default : false);
    }

    static function generateCacheFileName($namespace, $environment = AK_ENVIRONMENT)
    {
        $namespace = Ak::sanitize_include($namespace, 'high');
        $cacheDir  = AK_TMP_DIR.DS.'ak_config';
        $cacheFile = $cacheDir.DS.'cache'.DS.$environment.DS.$namespace.'.php';
        return $cacheFile;
    }

    public function readCache($namespace, $environment = AK_ENVIRONMENT, $force = false)
    {
        if ((AK_CLI && AK_ENVIRONMENT!='testing') || (!$force && !$this->_useReadCache($environment))){
            return false;
        }
        $cacheFileName = $this->generateCacheFileName($namespace,$environment);
        if ($this->_checkCacheValidity($namespace, $environment)) {
            $config = include $cacheFileName;
        } else {
            $config = false;
        }
        return $config;
    }

    public function writeCache($config, $namespace, $environment = AK_ENVIRONMENT, $force = false)
    {
        if ((AK_CLI && AK_ENVIRONMENT != 'testing') || AK_ENVIRONMENT == 'setup' || (!$force &&!$this->_useWriteCache($environment))){
            return false;
        }

        $var_export = var_export($config, true);
        $cache = <<<CACHE
<?php
/**
 * Auto-generated config cache from $namespace in environment $environment
 */
\$config = $var_export;
return \$config;
?>
CACHE;
        $cacheFileName = $this->generateCacheFileName($namespace,$environment);
        $cacheDir = dirname($cacheFileName);

        if (!file_exists($cacheDir)) {
            $oldumask = umask();
            umask(0);
            $res = @mkdir($cacheDir, 0777, true);
            if (!$res) {
                trigger_error(Ak::t('Could not create config cache dir %dir',array('%dir'=>$cacheDir)),E_USER_ERROR);
            }
            umask($oldumask);
        }
        $fh = fopen($cacheFileName,'w+');
        if ($fh) {
            fputs($fh,$cache);
            fclose($fh);
            @chmod($cacheFileName, 0777);
        } else {
            trigger_error(Ak::t('Could not create config cache file %file',array('%file'=>$cacheFileName)),E_USER_ERROR);
        }
        $this->_setCacheValidity($namespace,$environment);
        return true;
    }


    protected function _useReadCache($environment = AK_ENVIRONMENT)
    {
        if(AK_CLI && AK_ENVIRONMENT != 'testing') return false;
        switch ($environment) {
            case 'development':
            case 'testing':
                return false;
                break;
            default:
                return true;

        }
    }

    protected function _useWriteCache($environment = AK_ENVIRONMENT)
    {
        if(AK_CLI && AK_ENVIRONMENT != 'testing') return false;
        switch ($environment) {
            case 'setup':
                return false;
                break;
            case 'development':
            case 'testing':
                return true;
                break;
            default:
                return true;

        }
    }

    protected function _checkCacheValidity($namespace,$environment)
    {
        $cacheFilename = $this->generateCacheFileName($namespace,$environment);
        $configFilename = $this->_generateConfigFileName($namespace);

        $cacheMtime = file_exists($cacheFilename) ? filemtime($cacheFilename): 1;
        $configMtime = file_exists($cacheFilename) ? filemtime($configFilename) : 2;
        return $cacheMtime == $configMtime;
    }

    protected function _setCacheValidity($namespace, $environment)
    {
        $cacheFilename = $this->generateCacheFileName($namespace,$environment);
        $configFilename = $this->_generateConfigFileName($namespace);
        if(!touch($cacheFilename, filemtime($configFilename))){
            echo "Error touching file $cacheFilename, check your permissions!\n";
        }
    }



    protected function _generateConfigFileName($namespace)
    {
        $namespace = Ak::sanitize_include($namespace, 'high');
        $yaml_file_name = self::CONFIG_DIR.DS.$namespace.'.yml';
        return $yaml_file_name;
    }

    protected function _merge($default,$env)
    {
        if (is_array($default)) {
            foreach($default as $key => $value) {
                if (!is_array($value)) {
                    $env[$key] = isset($env[$key]) ? $env[$key] : $value;
                } else {
                    $env[$key] = $this->_merge($value, isset($env[$key])?$env[$key] : array());
                }
            }
        } else {
            $env = empty($env) ? $default : $env;
        }
        return $env;
    }
}

