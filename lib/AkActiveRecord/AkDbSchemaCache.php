<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2008-2009, Bermi Ferrer Martinez                       |
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
 * @package ActiveRecord
 * @subpackage Base
 * @component DbSchemaCache
 * @author Arno Schneider 2008
 * @author Bermi Ferrer 2009
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */
 
class AkDbSchemaCache
{
    function shouldRefresh($set = null)
    {
        static $refresh;
        if(!isset($refresh)){
            $refresh = !AK_ACTIVE_RECORD_CACHE_DATABASE_SCHEMA;
        }
        $refresh = is_null($set) ? $refresh : $set;
        return $refresh;
    }

    function getCacheFileName($environment = AK_ENVIRONMENT)
    {
        return AkDbSchemaCache::getCacheDir().DS.$environment.'.serialized';
    }
    
    function getCacheDir()
    {
        $cache_dir = AK_CONFIG_DIR;
        if (defined('AK_CONFIG_CACHE_TMP') && AK_CONFIG_CACHE_TMP) {
            $cache_dir  = AK_TMP_DIR.DS.'ak_config';
        }
        return $cache_dir.DS.'cache'.DS.'activerecord';
    }
    
    function clear($table, $environment = AK_ENVIRONMENT)
    {
        AkDbSchemaCache::_config($table, null, $environment, true);
        AkDbSchemaCache::_config('database_table_internals_'.$table, null, $environment, true);
        AkDbSchemaCache::_updateCacheFileAfterExecution($environment);
        if(AK_LOG_EVENTS){
            $Logger =& Ak::getLogger();
            $Logger->message('Clearing database settings cache for '.$table);
        }
    }
    
    function clearAll()
    {
        if(AK_LOG_EVENTS){
            $Logger =& Ak::getLogger();
            $Logger->message('Clearing all database settings from cache');
        }
        Ak::directory_delete(AkDbSchemaCache::getCacheDir());
    }
    
    function get($key, $environment = AK_ENVIRONMENT)
    {
        return AkDbSchemaCache::_config($key, null, $environment, false);   
    }
    
    function set($key, $value, $environment = AK_ENVIRONMENT)
    {
        AkDbSchemaCache::_updateCacheFileAfterExecution($environment);
        return AkDbSchemaCache::_config($key, $value, $environment, !is_null($value));
    }
    
    function _updateCacheFileAfterExecution($environment = null)
    {
        static $called = false, $_environment;
        if($called == false && !AkDbSchemaCache::shouldRefresh()){
            register_shutdown_function(array('AkDbSchemaCache','_updateCacheFileAfterExecution'));
            $called =  !empty($environment) ? $environment : AK_ENVIRONMENT;
        }elseif(empty($environment)){
            $config = AkDbSchemaCache::_config(null, null, $called);
            $file_name = AkDbSchemaCache::getCacheFileName($called);
            
            /**
            * @todo On PHP5 var_export requires objects that implement the __set_state magic method.
            *       As see on stangelanda at arrowquick dot benchmarks at comhttp://php.net/var_export
            *       serialize works faster without opcode caches. We should do our benchmarks with 
            *       var_export VS serialize using APC once we fix the __set_state magic on phpAdoDB
            */
            if(AK_LOG_EVENTS){
                    $Logger =& Ak::getLogger();
            }
            if(!AK_CLI) {
                if(AK_LOG_EVENTS){
                    $Logger->message('Updating database settings on '.$file_name);
                }
                Ak::file_put_contents($file_name, serialize($config));
            } else if(AK_LOG_EVENTS){
                $Logger->message('Skipping writing of cache file: '.$file_name);
            }
        }
    }
    
    function _config($key = null, $value = null, $environment = AK_ENVIRONMENT, $unset = false)
    {
        if(AkDbSchemaCache::shouldRefresh()){
            return false;
        }
        static $config;
        if(!isset($config[$environment])){
            $file_name = AkDbSchemaCache::getCacheFileName($environment);
            $config[$environment] = file_exists($file_name) ? unserialize(Ak::file_get_contents($file_name)) : array();
            if(AK_LOG_EVENTS){
                $Logger =& Ak::getLogger();
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

?>