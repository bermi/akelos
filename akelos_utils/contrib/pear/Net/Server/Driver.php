<?PHP
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2002 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Stephan Schmidt <schst@php.net>                             |
// +----------------------------------------------------------------------+
//
//    $Id: Driver.php,v 1.8 2004/08/18 20:51:20 schst Exp $

/**
 * Base class for all drivers
 *
 * @category    Networking
 * @package     Net_Server
 * @author      Stephan Schmidt <schst@php.net>
 */

/**
 * uses PEAR's error handling and the destructors
 */
require_once 'PEAR.php';
 
/**
 * Base class for all drivers
 *
 * This class provides methods, can be used by all
 * server implementations.
 *
 * @category    Networking
 * @package     Net_Server
 * @author      Stephan Schmidt <schst@php.net>
 */
class Net_Server_Driver extends PEAR {
   /**
    * port to listen
    * @access private
    * @var    integer        $port
    */
    var $port = 10000;

   /**
    * domain to bind to
    * @access private
    * @var    string    $domain
    */
    var $domain = "localhost";

   /**
    * all file descriptors are stored here
    * @access private
    * @var    array    $clientFD
    */
    var $clientFD = array();

   /**
    * maximum amount of clients
    * @access private
    * @var    integer    $maxClients
    */
    var $maxClients = -1;

   /**
    * buffer size for socket_read
    * @access private
    * @var    integer    $readBufferSize
    */
    var $readBufferSize = 128;
    
   /**
    * end character for socket_read
    * @access private
    * @var    integer    $readEndCharacter
    */
    var $readEndCharacter = "\n";
    
   /**
    * maximum of backlog in queue
    * @access private
    * @var    integer    $maxQueue
    */
    var $maxQueue = 500;

   /**
    * debug mode
    * @access private
    * @var    boolean    $_debug
    */
    var $_debug = true;
    
   /**
    * debug mode, normally only text is needed, as servers should not be run in a browser
    * @access private
    * @var    string    $_debugMode
    */
    var $_debugMode = "text";

   /**
    * debug destination (filename or stdout)
    * @access private
    * @var    string    $_debugDest
    */
    var $_debugDest = "stdout";

   /**
    * empty array, used for socket_select
    * @access private
    * @var    array    $null
    */
    var $null = array();
    
   /**
    * needed to store client information
    * @access private
    * @var    array    $clientInfo
    */
    var $clientInfo = array();

  /**
    * constructor _MUST_ not be called directly
    *
    * Please use the Net_Server::create() method instead
    * that can be called statically and will return a server
    * of the specified type.
    *
    * @access   private
    * @param    string   $domain      domain to bind to
    * @param    integer  $port        port to listen to
	* @see      Net_Server::create()
    */
    function Net_Server_Driver($domain = "localhost", $port = 10000)
    {
        $this->PEAR();
        
        $this->domain = $domain;
        $this->port   = $port;
    }

   /**
    * destructor
	*
	* Will shutdown the server if the script is abborted.
    *
    * @access   private
    */
    function _Net_Server_Driver()
    {
        $this->shutdown();
    }

   /**
    * Set debug mode
    *
	* Debug information can either be written
	* to a logfile or standard out (= the console).
	*
	* To disable debugging, pass false as first parameter.
	*
    * @access   public
    * @param    mixed    $debug   [text|html|false]
    * @param    string   $dest    destination of debug message (stdout to output or filename if log should be written)
    */
    function setDebugMode($debug, $dest = "stdout")
    {
        if ($debug === false) {
            $this->_debug = false;
            return true;
        }
        
        $this->_debug     = true;
        $this->_debugMode = $debug;
        $this->_debugDest = $dest;
    }

   /**
    * read from a socket
    *
    * @access   private
    * @param    integer         $clientId    internal id of the client to read from
    * @return   string|boolean  $data        data that was read
    */
    function readFromSocket($clientId = 0)
    {
        //    start with empty string
        $data = '';
    
        //    read data from socket
        while(true) {
            $buf = socket_read($this->clientFD[$clientId], $this->readBufferSize);
            $data .= $buf;

            if (trim($buf) === '') {
                break;
            }
            
            if ($this->readEndCharacter != null) {
                $endString    =    substr($buf, - strlen($this->readEndCharacter));
                if ($endString == $this->readEndCharacter) {
                    break;
                }
            } else {
                /**
                 * readEndCharacter is set to null => autodetect
                 */
                if( strlen( $buf ) < $this->readBufferSize ) {
                    break;
                }
            }
        }

        if ($buf === false) {
            $this->_sendDebugMessage("Could not read from client ".$clientId." (".$this->getLastSocketError($this->clientFD[$clientId]).").");
            return false;
        }
        if ((string)$data === '') {
            return false;
        }
        return $data;
    }

   /**
    * send a debug message
	*
	* Debug messages will be either sent to standard out
	* or written to a logfile, depending on debug settings.
    *
    * @access public
    * @param  string    $msg    message to debug
	* @see    setDebugMode()
	* @todo   remove underscore from method name as the method now is public.
    */
    function _sendDebugMessage($msg)
    {
        if (!$this->_debug) {
            return false;
        }

        $msg    =    date("Y-m-d H:i:s", time()) . " " . $msg;    
            
        switch($this->_debugMode) {
            case    "text":
                $msg    =    $msg."\n";
                break;
            case    "html":
                $msg    =    htmlspecialchars($msg) . "<br />\n";
                break;
        }

        if ($this->_debugDest == "stdout" || empty($this->_debugDest)) {
            echo    $msg;
            flush();
            return true;
        }
        
        error_log($msg, 3, $this->_debugDest);
        return true;
    }

   /**
    * register a callback object
	*
	* The callback object is the actual server,
	* that contains the logic to process the events
	* triggered by the driver class.
	*
	* The best way to create a callback object is to
	* extend the Net_Server_Handler class.
    *
    * @access public
    * @param  object    $object     callback object
	* @see    Net_Server_Handler
    */
    function setCallbackObject(&$object)
    {
        $this->callbackObj = &$object;
        if (method_exists($this->callbackObj,'setServerReference')) {
            $this->callbackObj->setServerReference($this);
        }
    }
    
   /**
    * return string for last socket error
    *
    * @access   public
    * @return string    $error    last error
    */
    function getLastSocketError(&$fd)
    {
        if(!is_resource($fd)) {
            return '';
        }
        $lastError    =    socket_last_error($fd);
        return 'Msg: ' . socket_strerror($lastError) . ' / Code: '.$lastError;
    }
}
?>