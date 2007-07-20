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

if(!defined('AK_DBSESSION_CLASS_INCLUDED')){ define('AK_DBSESSION_CLASS_INCLUDED',true); // Class overriding trick


require_once(AK_LIB_DIR.DS.'Ak.php');
require_once(AK_LIB_DIR.DS.'AkObject.php');


/**
* Database based session.
*
* This class enables saving sessions into a database. This can
* be usefull for multiple server sites, and to have more
* control over sessions.
*
* <code>
*
* require_once(AK_LIB_DIR.'/AkDbSession.php');
*
* $AkDbSession = new AkDbSession();
* $AkDbSession->sessionLife = AK_SESSION_EXPIRE;
* session_set_save_handler (
* array(&$AkDbSession, '_open'),
* array(&$AkDbSession, '_close'),
* array(&$AkDbSession, '_read'),
* array(&$AkDbSession, '_write'),
* array(&$AkDbSession, '_destroy'),
* array(&$AkDbSession, '_gc')
* );
*
* </code>
*
* @author Bermi Ferrer <bermi at akelos com>
* @copyright Copyright (c) 2002-2005, Akelos Media, S.L. http://www.akelos.org
* @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
* @since 0.1
* @version $Revision 0.1 $
*/
class AkDbSession extends AkObject
{
    // {{{ properties


    // --- Public properties --- //


    /**
    * Secconds for the session to expire.
    *
    * @see setSessionLife
    * @access public
    * @var integer $sessionLife
    */
    var $sessionLife = AK_SESSION_EXPIRE;


    // --- Protected properties --- //


    /**
    * Database instance handler
    *
    * Stores a reference to an ADODB database instance.
    *
    * @access protected
    * @var object $_db
    */
    var $_db;

    // }}}


    /**
    * Original session value for avoiding hitting the database in case nothing has changed
    *
    * @access private
    * @var string $_db
    */
    var $_original_sess_value = '';

    // }}}


    // ------ CLASS METHODS ------ //



    // ---- Setters ---- //


    // {{{ setSessionLife()

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

    // }}}


    // ---- Protected methods ---- //


    // {{{ _open()

    /**
    * Session open handler
    *
    * @access protected
    * @return boolean
    */
    function _open()
    {
        $this->_db =& Ak::db();
        return true;
    }

    // }}}
    // {{{ _close()

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

    // }}}
    // {{{ _read()

    /**
    * Session read handler
    *
    * @access protected
    * @param    string    $id    Session Id
    * @return string
    */
    function _read($id)
    {
        $query_result = $this->_db->Execute("SELECT value FROM sessions WHERE id = ".$this->_db->qstr($id));
        if(!$query_result && AK_DEBUG){
            trigger_error($this->_db->ErrorMsg(), E_USER_NOTICE);
        }else{
            $this->_original_sess_value = (string)$query_result->fields[0];
            return $this->_original_sess_value;
        }
        return '';
    }

    // }}}
    // {{{ _write()

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
        // We don't want to hit the db if nothing has changed
        if($this->_original_sess_value != $data){
            $ret = $this->_db->Replace('sessions', array('id'=>$this->_db->qstr($id),'expire'=>$this->_db->DBTimeStamp(time()),'value'=>$this->_db->qstr($data)), 'id');
            if($ret == 0){
                return false;
            }else{
                return true;
            }
        }else {
            return true;
        }
    }

    // }}}
    // {{{ _destroy()

    /**
    * Session destroy handler
    *
    * @access protected
    * @param    string    $id    
    * @return boolean
    */
    function _destroy($id)
    {
        if(!$this->_db->Execute('DELETE FROM sessions WHERE id = '.$this->_db->qstr($id)) && AK_DEBUG){
            trigger_error($this->_db->ErrorMsg(), E_USER_NOTICE);
        }
        return (bool)@$this->_db->Affected_Rows();
    }

    // }}}
    // {{{ _gc()

    /**
    * Session garbage collection handler
    *
    * @access protected
    * @return boolean
    */
    function _gc()
    {
        if(!$this->_db->Execute('DELETE FROM sessions WHERE expire < '.$this->_db->DBTimeStamp(time()-$this->sessionLife)) && AK_DEBUG){
            trigger_error($this->_db->ErrorMsg(), E_USER_NOTICE);
        }
        return (bool)$this->_db->Affected_Rows();
    }

    // }}}


}

}// End of if(!defined('AK_DBSESSION_CLASS_INCLUDED')){

?>
