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
    * @return void
    */
    function init($options = null, $cache_type = AK_CACHE_HANDLER)
    {
        $options = is_int($options) ? array('lifeTime'=>$options) : (is_array($options) ? $options : array());

        switch ($cache_type) {
            case 1:
                $this->cache_enabled = true;
                if(!class_exists('Cache_Lite')){
                    require_once(AK_CONTRIB_DIR.'/pear/Cache_Lite/Lite.php');
                }
                if(!isset($options['cacheDir'])){
                    if(!is_dir(AK_CACHE_DIR)){
                        Ak::make_dir(AK_CACHE_DIR, array('base_path'=>AK_TMP_DIR));
                    }
                    $options['cacheDir'] = AK_CACHE_DIR.DS;
                }
                $this->_driverInstance =& new Cache_Lite($options);
                break;
            case 2:
                $this->cache_enabled = true;
                require_once(AK_LIB_DIR.'/AkCache/AkAdodbCache.php');
                $this->_driverInstance =& new AkAdodbCache();
                $this->_driverInstance->init($options);
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
