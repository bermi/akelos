<?php

class AkDbSchemaCache
{
    function doRefresh($set = null)
    {
        static $refresh = false;
        if ($set === false) {
            $refresh = false;
        } else if ($set === true) {
            $refresh = true;
        }
        return $refresh;
    }
    function _generateCacheFileName($table, $environment = AK_ENVIRONMENT)
    {
        $namespace = Ak::sanitize_include($table, 'high');
        $cacheDir = AK_CONFIG_DIR;
        if (defined('AK_CONFIG_CACHE_TMP') && AK_CONFIG_CACHE_TMP) {
            $cacheDir  = AK_TMP_DIR.DS.'ak_config';
        }
        $cacheFile = $cacheDir.DS.'cache'.DS.'activerecord'.DS.$environment.DS.$table.'.php';
        return $cacheFile;
    }
    
    function clear($table, $environment = AK_ENVIRONMENT)
    {
        
        $modelName = AkInflector::singularize(AkInflector::classify($table));
        $cacheFileName = AkDbSchemaCache::_generateCacheFileName($modelName, $environment);
        //echo "Cleaning cache: $cacheFileName\n";
        if (file_exists($cacheFileName)) {
            @unlink($cacheFileName);
        }
        AkDbSchemaCache::_get($modelName,$environment,false,false);
        $tableName = AkInflector::tableize($table);
        $databaseInternalsFileName = AkDbSchemaCache::_generateCacheFileName('database_table_internals_'.$tableName);
        //echo "Cleaning cache: $databaseInternalsFileName\n";
        if (file_exists($databaseInternalsFileName)) {
            @unlink($databaseInternalsFileName);
        }
        
        AkDbSchemaCache::_get('database_table_internals_'.$tableName,$environment,false,false);
    }
    
    function clearAll($environment = AK_ENVIRONMENT)
    {
        $dummy = AkDbSchemaCache::_generateCacheFileName('dummy', $environment);
        $dir = dirname($dummy);
        $files = Ak::dir($dir);
        foreach ($files as $file) {
            if (is_file($dir.DS.$file)) {
                @unlink($dir.DS.$file);
            }
        }
    }
    
    function getAvailableTables($environment = AK_ENVIRONMENT)
    {
        return AkDbSchemaCache::_get('available_tables', $environment);
    }
    
    function setAvailableTables($tables, $environment = AK_ENVIRONMENT)
    {
        AkDbSchemaCache::_set('available_tables',$tables, $environment);
    }
    function setModelColumnSettings($model, $config, $environment = AK_ENVIRONMENT)
    {
        return AkDbSchemaCache::_set($model, $config, $environment, false, true);
        
    }
    function setDbTableInternals($table, $internals, $environment = AK_ENVIRONMENT)
    {
        return AkDbSchemaCache::_set('database_table_internals_'.$table, $internals, $environment);
    }
    function getDbTableInternals($table, $environment = AK_ENVIRONMENT)
    {
        return AkDbSchemaCache::_get('database_table_internals_'.$table,$environment);
    }
    function getColumnsSettings($environment = AK_ENVIRONMENT)
    {
        return AkDbSchemaCache::_get(true,$environment);
        
    }
    function getModelColumnSettings($model, $environment = AK_ENVIRONMENT)
    {
        return AkDbSchemaCache::_get($model, $environment, false, null, true);
    }
    function &_get($type, $environment = AK_ENVIRONMENT, $uncached = false, $set = null, $var_export = false)
    {
        $false = false;
        if (AkDbSchemaCache::doRefresh() && $set === null) return $false;
        $null = null;
        static $configs = array();
        if ($set !== null) {
            if (!isset($configs[$environment])) {
                $configs[$environment] = array();
            }
            if ($set === false) {
                unset($configs[$environment][$type]);
            } else {
                $configs[$environment][$type] = $set;
            }
            return $null;
        }
        
        if ($type === true) {
            return isset($configs[$environment]) ? $configs[$environment] : array();
        }
        if (!$uncached && isset($configs[$environment]) && isset($configs[$environment][$type])) {
            return $configs[$environment][$type];
        }
        if ($uncached || !($config = AkDbSchemaCache::_readCache($type, $environment, false, $var_export))) {
            return $false;
        }
        if (!isset($configs[$environment])) {
            $configs[$environment] = array($type=>$config);
        } else {
            $configs[$environment][$type] = $config;
        }
        return $configs[$environment][$type];
    }
    
    function _readCache($table, $environment = AK_ENVIRONMENT, $force = false, $var_export = false)
    {
        $cacheFileName = AkDbSchemaCache::_generateCacheFileName($table,$environment);
        if (file_exists($cacheFileName)) {
            if ($var_export === false) {
                $config = unserialize(file_get_contents($cacheFileName));
            } else {
                $config = include $cacheFileName;
            }
        } else {
            $config = false;
        }
        return $config;
    }
    
    function _set($type, $config, $environment = AK_ENVIRONMENT, $force = false, $var_export = false)
    {
        if ($var_export === false) {
            $cache = serialize($config);
        } else {
            $cacheStr = var_export($config,true);
            $cache = <<<EOF
<?php
\$cache = $cacheStr;
return \$cache;
?>
EOF;
        }
        $cacheFileName = AkDbSchemaCache::_generateCacheFileName($type,$environment);
        $cacheDir = dirname($cacheFileName);
        
        if (!file_exists($cacheDir)) {
            $oldumask = umask();
            umask(0);
            $res = @mkdir($cacheDir,0777,true);
            if (!$res) {
                trigger_error(Ak::t('Could not create config cache dir %dir',array('%dir'=>$cacheDir)),E_USER_ERROR);
            }
            umask($oldumask);
        }
        $fh = fopen($cacheFileName,'w+');
        if ($fh) {
            fputs($fh,$cache);
            fclose($fh);
            @chmod($cacheFileName,0777);
        } else {
            trigger_error(Ak::t('Could not create dbschema cache file %file',array('%file'=>$cacheFileName)),E_USER_ERROR);
        }
        AkDbSchemaCache::_get($type, $environment, false, $config);
    }

}
?>