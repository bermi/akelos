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
//    $Id: Fork.php,v 1.9 2004/08/21 21:37:10 schst Exp $

/**
 * Forking server class.
 *
 * @category    Networking
 * @package     Net_Server
 * @author      Stephan Schmidt <schst@php.net>
 */

/**
 * needs the driver base class
 */
require_once 'Net/Server/Driver.php';

/**
 * Forking server class.
 *
 * This class will fork a new process for each connection.
 * This allows you to build servers, where communication between
 * the clients is no issue.
 *
 * Events that can be handled:
 *   - onStart
 *   - onConnect
 *   - onClose
 *   - onReceiveData
 *
 * @category    Networking
 * @package     Net_Server
 * @author      Stephan Schmidt <schst@php.net>
 */
class Net_Server_Driver_Fork extends Net_Server_Driver
{
   /**
    * flag to indicate whether this is the parent
    * @access private
    * @var    boolean
    */
    var $_isParent = true;

   /**
    * set maximum amount of simultaneous connections
    *
    * this is not possible as each client gets its own
    * process
    *
    * @access   public
    * @param    int    $maxClients
    */
    function setMaxClients($maxClients)
    {
        return  $this->raiseError('Not supported.', NET_SERVER_ERROR_NOT_SUPPORTED);
    }

   /**
    * start the server
    *
    * @access   public
    */
    function start()
    {
        if (!function_exists('pcntl_fork')) {
            return $this->raiseError('Needs pcntl extension to fork processes.', NET_SERVER_ERROR_PCNTL_REQUIRED);
        }
    
        $this->initFD    =    @socket_create(AF_INET, SOCK_STREAM, 0);
        if (!$this->initFD) {
            return $this->raiseError("Could not create socket.");
        }

        //    adress may be reused
        socket_setopt($this->initFD, SOL_SOCKET, SO_REUSEADDR, 1);

        //    bind the socket
        if (!@socket_bind($this->initFD, $this->domain, $this->port)) {
            $error = $this->getLastSocketError($this->initFd);
            @socket_close($this->initFD);
            return $this->raiseError("Could not bind socket to ".$this->domain." on port ".$this->port." (".$error.").");
        }

        //    listen on selected port
        if (!@socket_listen($this->initFD, $this->maxQueue)) {
            $error = $this->getLastSocketError($this->initFd);
            @socket_close($this->initFD);
            return $this->raiseError("Could not listen (".$error.").");
        }

        $this->_sendDebugMessage("Listening on port ".$this->port.". Server started at ".date("H:i:s", time()));

        if (method_exists($this->callbackObj, "onStart")) {
            $this->callbackObj->onStart();
        }

        // Dear children, please do not become zombies
        pcntl_signal(SIGCHLD, SIG_IGN);
        
        // wait for incmoning connections
        while (true)
        {
            // new connection
            if(($fd = socket_accept($this->initFD)))
            {
                $pid = pcntl_fork();
                if($pid == -1) {
                    return  $this->raiseError('Could not fork child process.');
                }
                // This is the child => handle the request
                elseif($pid == 0) {
                    // this is not the parent
                    $this->_isParent = false;
                    // store the new file descriptor
                    $this->clientFD[0] = $fd;

                    $peer_host    =    "";
                    $peer_port    =    "";
                    socket_getpeername($this->clientFD[0], $peer_host, $peer_port);
                    $this->clientInfo    =    array(
                                                      "host"        =>    $peer_host,
                                                      "port"        =>    $peer_port,
                                                      "connectOn"   =>    time()
                                                   );
                    $this->_sendDebugMessage("New connection from ".$peer_host." on port ".$peer_port.", new pid: ".getmypid());

                    if (method_exists($this->callbackObj, "onConnect")) {
                        $this->callbackObj->onConnect(0);
                    }

                    $this->serviceRequest();
                    $this->closeConnection();
                    exit;
                }
                else {
                    // the parent process does not have to do anything
                }
                
            }
        }
    }

   /**
    * service the current request
    *
    *
    *
    */
    function serviceRequest()
    {
        while( true )
        {
            $readFDs = array( $this->clientFD[0] );
    
            //    block and wait for data
            $ready    =    @socket_select($readFDs, $this->null, $this->null, null);
    
            if ($ready === false)
            {
                $this->_sendDebugMessage("socket_select() failed.");
                $this->shutdown();
            }
    
            if (in_array($this->clientFD[0], $readFDs))
            {
                $data    =    $this->readFromSocket();
    
                // empty data => connection was closed
                if ($data === false)
                {
                    $this->_sendDebugMessage("Connection closed by peer");
                    $this->closeConnection();
                }
                else
                {
                    $this->_sendDebugMessage("Received ".trim($data)." from ".$this->_getDebugInfo());
    
                    if (method_exists($this->callbackObj, "onReceiveData")) {
                        $this->callbackObj->onReceiveData(0, $data);
                    }
                }
            }
        }
    }
    
   /**
    * check, whether a client is still connected
    *
    * @access   public
    * @return   boolean    $connected  true if client is connected, false otherwise
    */
    function isConnected()
    {
        if (is_resource($this->clientFD[0])) {
            return true;
        }
    }

   /**
    * get current amount of clients
    *
    * not possible with forking
    *
    * @access   public
    * @return PEAR_Error
    */
    function getClients()
    {
        return $this->raiseError('Not implemented');
    }

   /**
    * send data to a client
    *
    * @access   public
    * @param    string    $data        data to send
    * @param    boolean    $debugData    flag to indicate whether data that is written to socket should also be sent as debug message
    */
    function sendData($data, $debugData = true)
    {
        // keep it compatible to Net_Server_Sequential
        if (is_string($debugData)) {
            $data = $debugData;
        }
    
        if (!isset($this->clientFD[0]) || $this->clientFD[0] == null) {
            return $this->raiseError("Client does not exist.");
        }

        if ($debugData) {
            $this->_sendDebugMessage("sending: \"" . $data . "\" to: ".$this->_getDebugInfo() );
        }
        if (!@socket_write($this->clientFD[0], $data)) {
            $this->_sendDebugMessage("Could not write '".$data."' client ".$this->_getDebugInfo()." (".$this->getLastSocketError($this->clientFD[0]).").");
        }
    }

   /**
    * send data to all clients
    *
    * @access   public
    * @param    string    $data        data to send
    * @param    array    $exclude    client ids to exclude
    */
    function broadcastData($data, $exclude = array())
    {
        $this->sendData($data);
    }

   /**
    * get current information about a client
    *
    * @access   public
    * @return array    $info        information about the client
    */
    function getClientInfo()
    {
        if (!is_array($this->clientFD)) {
            return $this->raiseError("Client does not exist.");
        }
        return $this->clientInfo;
    }

   /**
    * close the current connection
    *
    * @access   public
    */
    function closeConnection()
    {
        if (!isset($this->clientFD[0])) {
            return $this->raiseError( "Connection already has been closed." );
        }

        if (method_exists($this->callbackObj, "onClose")) {
            $this->callbackObj->onClose(0);
        }

        $this->_sendDebugMessage("Closed connection from ".$this->_getDebugInfo());

        @socket_shutdown($this->clientFD[0], 2);
        @socket_close($this->clientFD[0]);
        $this->clientFD[0]    =    null;
        $this->clientInfo = null;
        exit();
    }

   /**
    * shutdown server
    *
    * @access   public
    */
    function shutDown()
    {
        $this->closeConnection();
        exit();
    }

   /**
    * get debug information about the process
    *
    * @access private
    * @return string
    */
    function _getDebugInfo()
    {
        return $this->clientInfo['host'].':'.$this->clientInfo['port'].' (pid: '.getmypid().')';
    }
}
?>