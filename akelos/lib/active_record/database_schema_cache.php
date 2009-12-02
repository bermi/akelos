<?php

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+

/**
 * @package ActiveRecord
 * @subpackage DatabaseReflection
 * @author Arno Schneider <arno a.t bermilabs c.om>
 * @author Bermi Ferrer <bermi a.t bermilabs c.om>
 * @copyright Copyright (c) 2002-2009, The Akelos Team http://www.akelos.org
 */

class AkDbSchemaCache
{
    static function shouldRefresh($set = null)
    {
        static $refresh;
        if(!isset($refresh)){
            $refresh = !AK_ACTIVE_RECORD_CACHE_DATABASE_SCHEMA;
        }
        $refresh = is_null($set) ? $refresh : $set;
        return $refresh;
    }

    static function getCacheFileName($environment = AK_ENVIRONMENT)
    {
        return AkDbSchemaCache::getCacheDir().DS.$environment.'.serialized';
    }

    static function getCacheDir()
    {
        $cache_dir  = AK_TMP_DIR.DS.'ak_config';
        return $cache_dir.DS.'cache'.DS.'activerecord';
    }

    static function clear($table, $environment = AK_ENVIRONMENT)
    {
        AkDbSchemaCache::config($table, null, $environment, true);
        AkDbSchemaCache::config('database_table_internals_'.$table, null, $environment, true);
        AkDbSchemaCache::updateCacheFileAfterExecution($environment);
        if(AK_LOG_EVENTS){
            $Logger = Ak::getLogger();
            $Logger->message('Clearing database settings cache for '.$table);
        }
    }

    static function clearAll()
    {
        if(AK_LOG_EVENTS){
            $Logger = Ak::getLogger();
            $Logger->message('Clearing all database settings from cache');
        }
        Ak::rmdir_tree(AkDbSchemaCache::getCacheDir());
    }

    static function get($key, $environment = AK_ENVIRONMENT)
    {
        return AkDbSchemaCache::config($key, null, $environment, false);
    }

    static function set($key, $value, $environment = AK_ENVIRONMENT)
    {
        AkDbSchemaCache::updateCacheFileAfterExecution($environment);
        return AkDbSchemaCache::config($key, $value, $environment, !is_null($value));
    }

    static function updateCacheFileAfterExecution($environment = null)
    {
        static $called = false, $_environment;
        if($called == false && !AkDbSchemaCache::shouldRefresh()){
            register_shutdown_function(array('AkDbSchemaCache','updateCacheFileAfterExecution'));
            $called =  !empty($environment) ? $environment : AK_ENVIRONMENT;
        }elseif(empty($environment)){
            $config = AkDbSchemaCache::config(null, null, $called);
            $file_name = AkDbSchemaCache::getCacheFileName($called);

            /**
            * @todo On PHP5 var_export requires objects that implement the __set_state magic method.
            *       As see on stangelanda at arrowquick dot benchmarks at comhttp://php.net/var_export
            *       serialize works faster without opcode caches. We should do our benchmarks with
            *       var_export VS serialize using APC once we fix the __set_state magic on phpAdoDB
            */
            if(AK_LOG_EVENTS){
                $Logger = Ak::getLogger();
            }
            if(!AK_CLI) {
                if(AK_LOG_EVENTS){
                    $Logger->message('Updating database settings on '.$file_name);
                }

                Ak::file_put_contents($file_name, serialize($config), array('base_path'=> AK_TMP_DIR));
                //file_put_contents($file_name, serialize($config));

            } else if(AK_LOG_EVENTS){
                $Logger->message('Skipping writing of cache file: '.$file_name);
            }
        }
    }

    static function config($key = null, $value = null, $environment = AK_ENVIRONMENT, $unset = false)
    {
        if(AkDbSchemaCache::shouldRefresh()){
            return false;
        }
        static $config;
        if(!isset($config[$environment])){
            $file_name = AkDbSchemaCache::getCacheFileName($environment);
            $config[$environment] = file_exists($file_name) ? unserialize(file_get_contents($file_name)) : array();
            if(AK_LOG_EVENTS){
                $Logger = Ak::getLogger();
                $Logger->message('Loading cached database settings');
            }
        }
        if(!is_null($key)){
            if(!is_null($value)){
                $config[$environment][$key] = $value;
            }elseif($unset){
                unset($config[$environment][$key]);
            }
            return isset($config[$environment][$key]) ? $config[$environment][$key] : false;
        }
        return $config[$environment];
    }
}

