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
 * @subpackage Cache
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */


// ---- Required Files ---- //
require_once(AK_LIB_DIR.DS.'Ak.php');
require_once(AK_LIB_DIR.DS.'AkObject.php');


/**
* Easy to use class for caching data using a database as
* container or the file system.
*
* Akelos Framework provides an easy to use functionality for
* caching data using a database as container or the file
* system.
*
* By default the cache container is defined in the following
* line
*
* <code>define ('AK_CACHE_HANDLER', 1);</code>
*
* in the ''config/config.php'' file
*
* Possible values are:
*
* - 0: No cache at all
* - 1: File based cache using the folder defined at AK_CACHE_DIR or the system /tmp dir
* - 2: Database based cache. This one has a performance penalty, but works on most servers
*
* Here is a small code spinet of how this works.
* <code>
* // First we include the cache class and
* // create a cache instance
* include_once(AK_LIB_DIR.'/AkCache.php');
* $Cache =& new AkCache();
*
* // Now we define some details for this cache
* $seconds = 3600; // seconds of life for this cache
* $cache_id = 'unique identifier for accesing this cache element';
*
* // Now we call the $Cache constructor (ALA AkFramework)
* $Cache->init($seconds);
*
* // If the data is not cached, we catch it now
* // if it was on cache, $data will hold its content
* if (!$data = $Cache->get($cache_id)) {
* $data = some_heavy_function_that_takes_too_many_time_or_resources();
* $Cache->save($data);
* }
*
* // Now you can use data no matter from where did it came from
* echo $data;
* </code>
*
* This class uses the
* [http://pear.php.net/manual/en/package.caching.cache-lite.php
* pear Cache_Lite] as driver for file based cache.
* In fact you can access an instance of Cache_Lite by
* accesing $Cache->_driverInstance.
*
* @author Bermi Ferrer <bermi at akelos dot com>
* @copyright Copyright (c) 2002-2005, Akelos Media, S.L. http://www.akelos.org
* @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
* @since 0.1
* @version $Revision 0.1 $
*/
class AkCache extends AkObject
{

    /**
    * Handles an instance of current Cache driver
    *
    * @access private
    * @var object $_driverInstance
    */
    var $_driverInstance = NULL;

    /**
    * Ecnables / Disables caching
    *
    * @access public
    * @var boolean true
    */
    var $cache_enabled = true;
    
    
    /**
     * Instantiates and configures the AkCache store.
     * 
     * If $options == NULL the configuration will be taken from the constants:
     * 
     * AK_CACHE_HANDLER and AK_CACHE_OPTIONS
     * 
     * if $options is of type string/int the $options parameter will be considered
     * as the AK_CACHE_HANDLER_* Type (AK_CACHE_HANDLER_PEAR,AK_CACHE_HANDLER_ADODB,AK_CACHE_HANDLER_MEMCACHE)
     * 
     * if $options is an array of format:
     * 
     *   array('file'=>array('cacheDir'=>'/tmp'))
     *   
     *   or
     * 
     *   array(AK_CACHE_HANDLER_PEAR=>array('cacheDir'=>'/tmp'))
     * 
     *  the first key will be used as the AK_CACHE_HANDLER_* Type
     *  and the array as the config options
     * 
     * Default behaviour is calling the method with the $options == null parameter:
     * 
     * AkCache::lookupStore()
     * 
     * Calling it with:
     * 
     * AkCache::lookupStore(true)
     * 
     * will return the configured $cache_store
     *
     * @param mixed $options
     * @return mixed   false if no cache could be configured or AkCache instance
     */
    function &lookupStore($options = null)
    {
        static $cache_store;
        $false = false;
        if ($options === true && !empty($cache_store)) {
            return $cache_store;
        } else if (is_array($options) && 
                   isset($options['enabled']) && $options['enabled']==true &&
                   isset($options['handler']) &&
                   isset($options['handler']['type'])) {
            $type = $options['handler']['type'];
            $options = isset($options['handler']['options'])?$options['handler']['options']:array();
        } else if (is_string($options) || is_int($options)) {
            $type = $options;
            $options = array();
        } else {
            return $false;
        }
        $cache_store = new AkCache();
        $cache_store->init($options,$type);
        if ($cache_store->cache_enabled) {
            return $cache_store;
        }
        return $false;
    }
    
    function expandCacheKey($key, $namespace = null)
    {
        $expanded_cache_key = $namespace != null? $namespace : '';
        if (isset($_ENV['AK_CACHE_ID'])) {
            $expanded_cache_key .= DS . $_ENV['AK_CACHE_ID'];
        } else if (isset($_ENV['AK_APP_VERSION'])) {
            $expanded_cache_key .= DS . $_ENV['AK_APP_VERSION'];
        }
        
        if (is_object($key) && method_exists($key,'cacheKey')) {
            $expanded_cache_key .= DS . $key->cacheKey();
        } else if (is_array($key)) {
            foreach ($key as $idx => $v) {
                $expanded_cache_key .= DS . $idx.'='.$v;
            }
        } else {
            $expanded_cache_key .= DS . $key;
        }
        $regex = '|'.DS.'+|';
        $expanded_cache_key = preg_replace($regex,DS, $expanded_cache_key);
        $expanded_cache_key = rtrim($expanded_cache_key,DS);
        return $expanded_cache_key;
    }
    
    /**
    * Class constructor (ALA Akelos Framework)
    *
    * This method loads an instance of selected driver in order to
    * use it class wide.
    *
    * @access public
    * @param    mixed    $options    You can pass a number specifying the second for
    * the cache to expire or an array with the
    * following options:
    *
    * <code>
    * $options = array(
    * //This options are valid for both cache contains (database and file based)
    * 'lifeTime' => cache lifetime in seconds
    * (int),
    * 'memoryCaching' => enable / disable memory caching (boolean),
    * 'automaticSerialization' => enable / disable automatic serialization (boolean)
    *
    * //This options are for file based cache
    * 'cacheDir' => directory where to put the cache files (string),
    * 'caching' => enable / disable caching (boolean),
    * 'fileLocking' => enable / disable fileLocking (boolean),
    * 'writeControl' => enable / disable write control (boolean),
    * 'readControl' => enable / disable read control (boolean),
    * 'readControlType' => type of read control
    * 'crc32', 'md5', 'strlen' (string),
    * 'pearErrorMode' => pear error mode (when raiseError is called) (cf PEAR doc) (int),
    * 'onlyMemoryCaching' => enable / disable only memory caching (boolean),
    * 'memoryCachingLimit' => max nbr of records to store into memory caching (int),
    * 'fileNameProtection' => enable / disable automatic file name protection (boolean),
    * 'automaticCleaningFactor' => distable / tune automatic cleaning process (int)
    * 'hashedDirectoryLevel' => level of the hashed directory system (int)
    * );
    * </code>
    * @param    integer    $cache_type    The default value is set by defining the constant AK_CACHE_HANDLER in the following line
    *
    * <code>define ('AK_CACHE_HANDLER', 1);</code>
    *
    * in the ''config/config.php'' file
    *
    * Possible values are:
    *
    * - 0: No cache at all
    * - 1: File based cache using the folder defined at AK_CACHE_DIR or the system /tmp dir
    * - 2: Database based cache. This one has a performance penalty, but works on most servers
    * - 3: Memcached - The fastest option
    * @return void
    */
    function init($options = null, $cache_type = null)
    {
        $options = is_int($options) ? array('lifeTime'=>$options) : (is_array($options) ? $options : array());

        switch ($cache_type) {
            case 1:
                $this->cache_enabled = true;
                if(!class_exists('Cache_Lite')){
                    require_once(AK_CONTRIB_DIR.'/pear/Cache_Lite/Lite.php');
                }
                if(!isset($options['cacheDir'])){
                    $options['cacheDir'] = AK_CACHE_DIR.DS;
                } else {
                    $options['cacheDir'].=DS;
                }
                 if(!is_dir($options['cacheDir'])){
                    Ak::make_dir($options['cacheDir'], array('base_path'=>dirname($options['cacheDir'])));
                }
                $this->_driverInstance =& new Cache_Lite($options);
                break;
            case 2:
                require_once(AK_LIB_DIR.'/AkCache/AkAdodbCache.php');
                $this->_driverInstance =& new AkAdodbCache();
                $res = $this->_driverInstance->init($options);
                $this->cache_enabled = $res;
                break;
            case 3:
                require_once(AK_LIB_DIR.'/AkCache/AkMemcache.php');
                $this->_driverInstance =& new AkMemcache();
                $res = $this->_driverInstance->init($options);
                $this->cache_enabled = $res;
                break;
            default:
                $this->cache_enabled = false;
                break;
        }
    }


    /**
    * Test if a cache is available and (if yes) return it
    *
    * @access public
    * @param    string    $id    Cache id
    * @param    string    $group    Name of the cache group.
    * @return mixed Data of the cache (or false if no cache available)
    */
    function get($id, $group = 'default')
    {
        return $this->cache_enabled ? $this->_driverInstance->get($id, $group) : false;
    }


    /**
    * Save some data in the cache
    *
    * @access public
    * @param    string    $data    Data to put in cache
    * @param    string    $id    Cache id
    * @param    string    $group    Name of the cache group
    * @return boolean True if no problem
    */
    function save($data, $id = null, $group = 'default')
    {
        return $this->cache_enabled ? $this->_driverInstance->save($data, $id, $group) : true;
    }


    /**
    * Remove a cache item
    *
    * @access public
    * @param    string    $id    Cache id
    * @param    string    $group    Name of the cache group
    * @return boolean True if no problem
    */
    function remove($id, $group = 'default')
    {
        return $this->cache_enabled ? $this->_driverInstance->remove($id, $group) : true;
    }


    /**
    * Clean the cache
    *
    * If no group is specified all cache items will be destroyed
    * else only cache items of the specified group will be
    * destroyed
    *
    * @access public
    * @param    string    $group    Name of the cache group.
    * If no group is specified all cache items will be
    * destroyed else only cache items of the specified
    * group will be destroyed
    * @param    string    $mode    Flush cache mode. Options are:
    *
    * - old
    * - ingroup
    * - notingroup
    * @return boolean True if no problem
    */
    function clean($group = false, $mode = 'ingroup')
    {
        return $this->cache_enabled ? $this->_driverInstance->clean($group, $mode) : true;
    }

}

?>
