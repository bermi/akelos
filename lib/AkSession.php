<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2006, Akelos Media, S.L.  & Bermi Ferrer Martinez |
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
 * @package ActionController
 * @subpackage Sessions
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

if(!defined('AK_SESSION_CLASS_INCLUDED')){ define('AK_SESSION_CLASS_INCLUDED',true); // Class overriding trick


require_once(AK_LIB_DIR.DS.'Ak.php');
require_once(AK_LIB_DIR.DS.'AkObject.php');


/**
* Memcache based session.
*
* This class enables saving sessions into a database or memcache.
* see: config/DEFAULT-DB-sessions.yml or  config/DEFAULT-MEMCACHE-sessions.yml
*
* This can
* be usefull for multiple server sites, and to have more
* control over sessions.
*
* <code>
*
* require_once(AK_LIB_DIR.DS.'AkSession.php');
* $SessionHandler = &AkSession::initHandler();
*
* </code>
*
* @author Bermi Ferrer <bermi at akelos com>
* @author Arno Schneider <arno at bermilabs com>
* @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
* @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
* @since 0.9
* @version $Revision 0.9 $
*/
class AkSession extends AkObject
{

    /**
    * Session driver
    *
    * Stores session data using cache handlers
    *
    * @access protected
    * @var object $_driverInstance
    */
    var $_driverInstance;

    var $sessions_enabled;
    /**
    * Original session value for avoiding hitting the cache system in case nothing has changed
    *
    * @access private
    * @var string $_db
    */
    var $_original_sess_value = '';

    function initHandler()
    {
        $settings = Ak::getSettings('sessions', false);
        $SessionHandler = &AkSession::lookupStore($settings);
        return $SessionHandler;
    }

    function &lookupStore($options = null)
    {
        static $session_store;
        $false = false;
        if ($options === true && !empty($session_store)) {
            return $session_store;
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
        $session_store = new AkSession();
        $session_store->init($options,$type);
        if ($session_store->sessions_enabled) {
            return $session_store;
        }
        return $false;
    }

    function init($options = array(),$type = null)
    {
        $options = is_int($options) ? array('lifeTime'=>$options) : (is_array($options) ? $options : array());

        switch ($type) {
            case 1:
                $this->sessions_enabled = false;
                if(isset($options['save_path'])) {
                    session_save_path($options['save_path']);
                }
                break;
            case 2:
                require_once(AK_LIB_DIR.'/AkCache/AkAdodbCache.php');
                $this->_driverInstance =& new AkAdodbCache();
                $res = $this->_driverInstance->init($options);
                $this->sessions_enabled = $res;
                break;
            case 3:
                require_once(AK_LIB_DIR.'/AkCache/AkMemcache.php');
                $this->_driverInstance =& new AkMemcache();
                $res = $this->_driverInstance->init($options);
                $this->sessions_enabled = $res;
                break;
            case 4:
                require_once(AK_LIB_DIR.'/AkSession/AkCookieSession.php');
                $this->sessions_enabled = false;
                $AkCookieSession = new AkCookieSession();
                session_set_save_handler (
                array(&$AkCookieSession, '_open'),
                array(&$AkCookieSession, '_close'),
                array(&$AkCookieSession, '_read'),
                array(&$AkCookieSession, '_write'),
                array(&$AkCookieSession, '_destroy'),
                array(&$AkCookieSession, '_gc')
                );
                return;
            default:
                $this->sessions_enabled = false;
                break;
        }
        if ($this->sessions_enabled) {
             $this->sessionLife = $options['lifeTime'];
             session_set_save_handler (
             array(&$this, '_open'),
             array(&$this, '_close'),
             array(&$this, '_read'),
             array(&$this, '_write'),
             array(&$this, '_destroy'),
             array(&$this, '_gc')
             );

        }
    }
    /**
    * $this->sessionLife setter
    *
    * Use this method to set $this->sessionLife value
    *
    * @access public
    * @see get$sessionLife
    * @param    integer    $sessionLife    Secconds for the session to expire.
    * @return bool Returns true if $this->sessionLife has been set
    * correctly.
    */
    function setSessionLife($sessionLife)
    {
        $this->sessionLife = $sessionLife;

    }

    // ---- Protected methods ---- //

    /**
    * Session open handler
    *
    * @access protected
    * @return boolean
    */
    function _open()
    {
        return true;
    }

    /**
    * Session close handler
    *
    * @access protected
    * @return boolean
    */
    function _close()
    {
        /**
        * @todo Get from cached vars last time garbage collection was made to avoid hitting db
        * on every request
        */
        $this->_gc();
        return true;
    }

    /**
    * Session read handler
    *
    * @access protected
    * @param    string    $id    Session Id
    * @return string
    */
    function _read($id)
    {
        $result = $this->_driverInstance->get($id,'AK_SESSIONS');
        return is_null($result) ? '' : (string)$result;
    }

    /**
    * Session write handler
    *
    * @access protected
    * @param    string    $id
    * @param    string    $data
    * @return boolean
    */
    function _write($id, $data)
    {
        // We don't want to hit the cache handler if nothing has changed
        if($this->_original_sess_value != $data){
            $ret = $this->_driverInstance->save($data, $id,'AK_SESSIONS');
            if(!$ret){
                return false;
            }else{
                return true;
            }
        }else {
            return true;
        }
    }

    /**
    * Session destroy handler
    *
    * @access protected
    * @param    string    $id
    * @return boolean
    */
    function _destroy($id)
    {
        return (bool)$this->_driverInstance->remove($id,'AK_SESSIONS');
    }

    /**
    * Session garbage collection handler
    *
    * @access protected
    * @return boolean
    */
    function _gc()
    {
        return (bool)$this->_driverInstance->clean('AK_SESSIONS','old');
    }


}

}
// END OF AK_SESSION_CLASS_INCLUDED
?>
