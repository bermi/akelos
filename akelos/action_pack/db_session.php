<?php

/**
* Database based session.
*
* This class enables saving sessions into a database. This can
* be usefull for multiple server sites, and to have more
* control over sessions.
*
* You'll have to run AkDbSession::install() in order to create the default
* database scheme for strogin sessions.
*
* <code>
*
* $AkDbSession = new AkDbSession();
* $AkDbSession->sessionLife = AK_SESSION_EXPIRE;
* session_set_save_handler (
* array($AkDbSession, '_open'),
* array($AkDbSession, '_close'),
* array($AkDbSession, '_read'),
* array($AkDbSession, '_write'),
* array($AkDbSession, '_destroy'),
* array($AkDbSession, '_gc')
* );
*
* </code>
*
* @author Bermi Ferrer <bermi at akelos com>
* @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
* @since 0.1
* @deprecated use AkSession instead
* @version $Revision 0.1 $
*/
class AkDbSession
{
    /**
    * Secconds for the session to expire.
    *
    * @see setSessionLife
    * @access public
    * @var integer $sessionLife
    */
    public $sessionLife = AK_SESSION_EXPIRE;

    /**
    * Database instance handler
    *
    * Stores a reference to an ADODB database instance.
    *
    * @access protected
    * @var object $_db
    */
    public $_db;

    /**
    * Original session value for avoiding hitting the database in case nothing has changed
    *
    * @access private
    * @var string $_db
    */
    public $_original_sess_value = '';

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
        $this->_db = Ak::db();
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
        $result = @$this->_db->selectValue("SELECT value FROM sessions WHERE id = ".$this->_db->quote_string($id));
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
        // We don't want to hit the db if nothing has changed
        if($this->_original_sess_value != $data){
            /**
            * @todo replace with dbAdapter-method
            */
            $ret = @$this->_db->connection->Replace('sessions', array('id'=>$this->_db->quote_string($id),'expire'=>$this->_db->quote_datetime(time()),'value'=>$this->_db->quote_string($data)), 'id');
            if($ret == 0){
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
        return (bool)@$this->_db->delete('DELETE FROM sessions WHERE id = '.$this->_db->quote_string($id));
    }

    /**
    * Session garbage collection handler
    *
    * @access protected
    * @return boolean
    */
    public function _gc()
    {
        return (bool)@$this->_db->delete('DELETE FROM sessions WHERE expire < '.$this->_db->quote_datetime(time()-$this->sessionLife));

    }

    static function install()
    {
        $db = Ak::db();
        if(!$db->tableExists('sessions')){
            $Installer = new AkInstaller($db);
            $Installer->createTable('sessions', '
                                        id string(32) not null primary key,
                                        expire datetime,
                                        value text'
            ,                           array('timestamp'=>false));
        }
    }

    static function uninstall()
    {
        $db = Ak::db();
        if($db->tableExists('sessions')){
            $Installer = new AkInstaller($db);
            $Installer->dropTable('sessions');
        }
    }
}


