<?php

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
* $SessionHandler = AkSession::initHandler();
*
* </code>
*
* @author Bermi Ferrer <bermi at akelos com>
* @author Arno Schneider <arno at bermilabs com>
* @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
* @since 0.9
* @version $Revision 0.9 $
*/
class AkSession
{
    /**
    * Session driver
    *
    * Stores session data using cache handlers
    *
    * @access protected
    * @var object $_driverInstance
    */
    public $_driverInstance;

    public $sessions_enabled;
    /**
    * Original session value for avoiding hitting the cache system in case nothing has changed
    *
    * @access private
    * @var string $_db
    */
    public $_original_sess_value = '';

    static function &initHandler()
    {
        $settings = Ak::getSettings('sessions', false);
        $SessionHandler = AkSession::lookupStore($settings);
        return $SessionHandler;
    }

    static function &lookupStore($options = null)
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
        $session_store->init($options, $type);
        if ($session_store->sessions_enabled) {
            return $session_store;
        }
        return $false;
    }

    public function init($options = array(),$type = null)
    {
        $options = is_int($options) ? array('lifeTime'=>$options) : (is_array($options) ? $options : array());

        switch ($type) {
            case 1:
                $sessionpath = session_save_path();
                $this->sessions_enabled = false; // Use PHP session handling
                break;
            case 2:
                $this->_driverInstance = new AkAdodbCache();
                $res = $this->_driverInstance->init($options);
                $this->sessions_enabled = $res;
                break;
            case 3:
                $this->_driverInstance = new AkMemcache();
                $res = $this->_driverInstance->init($options);
                $this->sessions_enabled = $res;
                break;
            default:
                $this->sessions_enabled = false;
                break;
        }
        if ($this->sessions_enabled) {
             $this->sessionLife = $options['lifeTime'];
             session_set_save_handler (
             array($this, '_open'),
             array($this, '_close'),
             array($this, '_read'),
             array($this, '_write'),
             array($this, '_destroy'),
             array($this, '_gc')
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
    public function setSessionLife($sessionLife)
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
    public function _open()
    {
        return true;
    }

    /**
    * Session close handler
    *
    * @access protected
    * @return boolean
    */
    public function _close()
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
    public function _read($id)
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
    public function _write($id, $data)
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
    public function _destroy($id)
    {
        return (bool)$this->_driverInstance->remove($id,'AK_SESSIONS');
    }

    /**
    * Session garbage collection handler
    *
    * @access protected
    * @return boolean
    */
    public function _gc()
    {
        return (bool)$this->_driverInstance->clean('AK_SESSIONS','old');
    }
}
