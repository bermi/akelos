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


require_once(AK_LIB_DIR.'/Ak.php');


/**
* Dabase cache driver for the AkCache class
* 
* @author Bermi Ferrer <bermi at akelos dot com>
* @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
* @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
*/
class AkAdodbCache
{

    /**
    * Handles an instance of current database conection using
    * AdoBD
    *
    * @see setDb
    * @access private
    * @var object $_db
    */
    var $_db = NULL;

    /**
    * Timestamp of the last valid cache
    *
    * @see setRefreshTime
    * @access private
    * @var integer $_refreshTime
    */
    var $_refreshTime = NULL;

    /**
    * Cache lifetime (in seconds)
    *
    * @see setLifeTime
    * @access private
    * @var integer $_lifeTime
    */
    var $_lifeTime = 3600;

    /**
    * Enable / Disable "Memory Caching"
    *
    * NB : There is no lifetime for memory caching !
    *
    * @see setMemoryCaching
    * @access private
    * @var boolean $_memoryCaching
    */
    var $_memoryCaching = false;

    /**
    * Memory caching container array
    *
    * @access private
    * @var array $_memoryCachingArray
    */
    var $_memoryCachingArray = array();

    /**
    * Enable / disable automatic serialization
    *
    * It can be used to save directly datas which aren't strings
    * (but it's slower)
    *
    * @see setAutomaticSerialization
    * @access private
    * @var boolean $_automaticSerialization
    */
    var $_automaticSerialization = false;

    /**
    * $this->_db setter
    *
    * Use this method to set $this->_db value
    *
    * @access public
    * @see get$db
    * @param    object    $db    Handles an instance of current database conection
    * using AdoBD
    * @return void
    */
    function setDb($db)
    {
        $this->_db = $db;

    }

    /**
    * $this->_refreshTime setter
    *
    * Use this method to set $this->_refreshTime value
    *
    * @access public
    * @see get$refreshTime
    * @param    integer    $refresh_time    Timestamp of the last valid cache
    * @return void
    */
    function setRefreshTime($refresh_time)
    {
        $this->_refreshTime = $refresh_time;

    }

    /**
    * $this->_lifeTime setter
    *
    * Use this method to set $this->_lifeTime value
    *
    * @access public
    * @see get$lifeTime
    * @param    integer    $life_time    Cache lifetime (in seconds)
    * @return void
    */
    function setLifeTime($life_time = 3600)
    {
        $this->_lifeTime = $life_time;
        $this->setRefreshTime(time() - $this->_lifeTime);
    }

    /**
    * $this->_memoryCaching setter
    *
    * Use this method to set $this->_memoryCaching value
    *
    * @access public
    * @see get$memoryCaching
    * @param    boolean    $memory_caching    Enable / Disable "Memory Caching"
    *
    * NB : There is no lifetime for memory caching !
    * @return void
    */
    function setMemoryCaching($memory_caching = false)
    {
        $this->_memoryCaching = (bool)$memory_caching;

    }

    /**
    * $this->_automaticSerialization setter
    *
    * Use this method to set $this->_automaticSerialization value
    *
    * @access public
    * @see get$automaticSerialization
    * @param    boolean    $automatic_serialization    Enable / disable automatic serialization
    * @return void
    */
    function setAutomaticSerialization($automatic_serialization = false)
    {
        $this->_automaticSerialization = (bool)$automatic_serialization;

    }

    /**
    * Class constructor (ALA Akelos Framework)
    *
    * @access public
    * @param    array    $options    
    * <code>
    * $options = array(
    * //This options are valid for both cache contains (database and file based)
    * 'lifeTime' => cache lifetime in seconds (int),
    * 'memoryCaching' => enable / disable memory caching (boolean),
    * 'automaticSerialization' => enable / disable automatic serialization (boolean)
    * );
    * </code>
    * @return void
    */
    function init($options = array())
    {
        $this->_db =& Ak::db();

        $available_options = array('memoryCaching', 'lifeTime', 'automaticSerialization');
        foreach($options as $key => $value) {
            if(in_array($key, $available_options)) {
                $property = '_'.$key;
                $this->$property = $value;
            }
        }
        $this->_refreshTime = time() - $this->_lifeTime;
        return $this->_db?true:false;
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
        $this->_id = $id;
        $this->_group = $group;
        $cache_hash = md5($this->_id).'_'.md5($this->_group);

        if(isset($this->_memoryCachingArray[$cache_hash])){
            return $this->_memoryCachingArray[$cache_hash];
        }

        $query_result = $this->_db->selectValue('
            SELECT cache_data 
            FROM cache 
            WHERE id = '.$this->_db->quote_string($cache_hash).' 
            AND cache_group = '.$this->_db->quote_string($this->_group).' 
            AND expire > '.$this->_db->quote_datetime($this->_refreshTime)
            );
            if (!$query_result) return false;

            $data = $this->_db->unescape_blob($query_result);

            if($this->_automaticSerialization == true){
                $data = unserialize($data);
            }

            if($this->_memoryCaching){
                $this->_memoryCachingArray[$cache_hash] = $data;
            }

            return $data;
    }

    /**
    * Save some data in the cache
    *
    * @access public
    * @param    string    $data    Data to put in cache
    * @param    string    $id    Cache id. By default it will use the Id specified
    * when calling $this->get
    * @param    string    $group    Name of the cache group. By default it will use
    * the group specified when calling $this->get
    * @return boolean True if no problem
    */
    function save($data, $id = null, $group = null)
    {
        $this->_id = isset($id) ? $id : $this->_id;
        $this->_group = isset($group) ? $group : $this->_group;

        $cache_hash = md5($this->_id).'_'.md5($this->_group);

        if($this->_automaticSerialization == true){
            $data = serialize($data);
        }
        // TODO replace with AkDbAdapter statement
        $ret = $this->_db->connection->Replace(
        'cache', array(
        'id'=>$this->_db->quote_string($cache_hash),
        'cache_data'=>$this->_db->quote_string($this->_db->escape_blob($data)),
        'cache_group'=>$this->_db->quote_string($this->_group),
        'expire'=>$this->_db->quote_datetime(time() + $this->_lifeTime)),
        'id');

        if(!$ret){
            return false;
        }else{
            if($this->_memoryCaching){
                $this->_memoryCachingArray[$cache_hash] = $data;
            }
            return true;
        }
    }

    /**
    * Remove a cache item from the database
    *
    * @access public
    * @param    string    $id    Cache id
    * @param    string    $group    Name of the cache group
    * @return boolean True if no problem
    */
    function remove($id, $group = 'default')
    {
        $cache_hash = md5($id).'_'.md5($group);

        if (isset($this->_memoryCachingArray[$cache_hash])) {
            unset($this->_memoryCachingArray[$cache_hash]);
        }
        return (bool)$this->_db->delete('DELETE FROM cache WHERE id = '.$this->_db->quote_string($cache_hash));
    }

    /**
    * Clean the cache
    *
    * If no group is specified all cache items  will be removed
    * from the database else only cache items of the specified
    * group will be destroyed
    *
    * @access public
    * @param    string    $group    If no group is specified all cache items  will be
    * removed from the database else only cache items
    * of the specified group will be destroyed
    * @param    string    $mode    Flush cache mode. Options are:
    *
    * - old
    * - ingroup
    * - notingroup
    * @return boolean True if no problem
    */
    function clean($group = false, $mode = 'ingroup')
    {
        switch ($mode) {
            case 'ingroup':
                return (bool)$this->_db->delete('DELETE FROM cache WHERE cache_group = '.$this->_db->quote_string($group));
            case 'notingroup':
                return (bool)$this->_db->delete('DELETE FROM cache WHERE cache_group NOT LIKE '.$this->_db->quote_string($group));
            case 'old':
                return (bool)$this->_db->delete('DELETE FROM cache WHERE expire < '.$this->_db->quote_datetime(time()));
            default:
                return true;
        }
    }

}


?>
