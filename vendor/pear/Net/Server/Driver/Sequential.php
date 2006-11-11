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
//    $Id: Sequential.php,v 1.8 2004/08/18 20:48:53 schst Exp $

/**
 * Sequential server class.
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
 * Sequential server class.
 *
 * This class will handles all connection in one server process.
 * This allows you to build servers, where communication between
 * the clients is easy. The drawback is that clients are served
 * sequentially (hence the name). If you send large blocks of data
 * to a client, the others will have to wait.
 * For servers where communication between clients is not needed,
 * use Net_Server_Fork instead.
 *
 * Events that can be handled:
 *   - onStart
 *   - onConnect
 *   - onConnectionRefused
 *   - onClose
 *   - onReceiveData
 *   - onShutdown
 *
 * @category    Networking
 * @package     Net_Server
 * @author      Stephan Schmidt <schst@php.net>
 */
 class Net_Server_Driver_Sequential extends Net_Server_Driver
{
   /**
    * amount of clients
    * @var    integer        $clients
    */
    var $clients = 0;

   /**
    * set maximum amount of simultaneous connections
    *
    * @access   public
    * @param    int    $maxClients
    */
    function setMaxClients($maxClients)
    {
        $this->maxClients = $maxClients;
    }

   /**
    * start the server
    *
    * @access   public
    */
    function start()
    {
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

        //    this allows the shutdown function to check whether the server is already shut down
        $GLOBALS["_Net_Server_Status"]    =    "running";

        if (method_exists($this->callbackObj, "onStart")) {
            $this->callbackObj->onStart();
        }

        while (true)
        {
            $readFDs    =    array();
            array_push($readFDs, $this->initFD);

            //    fetch all clients that are awaiting connections
            for ($i = 0; $i < count($this->clientFD); $i++) {
                if (isset($this->clientFD[$i]))
                    array_push($readFDs, $this->clientFD[$i]);
            }

            //    block and wait for data or new connection
            $ready    =    @socket_select($readFDs, $this->null, $this->null, NULL);

            if ($ready === false) {
                $this->_sendDebugMessage("socket_select failed.");
                $this->shutdown();
            }
            
            //    check for new connection
            if (in_array($this->initFD, $readFDs)) {
                $newClient    =    $this->acceptConnection($this->initFD);

                //    check for maximum amount of connections
                if ($this->maxClients > 0) {
                    if ($this->clients > $this->maxClients) {
                        $this->_sendDebugMessage("Too many connections.");
                        
                        if (method_exists($this->callbackObj, "onConnectionRefused")) {
                            $this->callbackObj->onConnectionRefused($newClient);
                        }

                        $this->closeConnection($newClient);
                    }
                }

                if (--$ready <= 0) {
                    continue;
                }
            }

            //    check all clients for incoming data
            for($i = 0; $i < count($this->clientFD); $i++) {
                if (!isset($this->clientFD[$i])) {
                    continue;
                }

                if (in_array($this->clientFD[$i], $readFDs)) {
                    $data    =    $this->readFromSocket($i);
                    
                    //    empty data => connection was closed
                    if ($data === false) {
                        $this->_sendDebugMessage("Connection closed by peer");
                        $this->closeConnection($i);
                    }
                    else {
                        $this->_sendDebugMessage("Received ".trim($data)." from ".$i);

                        if (method_exists($this->callbackObj, "onReceiveData")) {
                            $this->callbackObj->onReceiveData($i, $data);
                        }
                    }
                }
            }
        }
    }

   /**
    * accept a new connection
    *
    * @access   private
    * @param    resource    &$socket    socket that received the new connection
    * @return   int         $clientID   internal ID of the client
    */
    function acceptConnection(&$socket)
    {
        for($i = 0 ; $i <= count($this->clientFD); $i++) {
            if (!isset($this->clientFD[$i]) || $this->clientFD[$i] == NULL) {
                $this->clientFD[$i]    =    socket_accept($socket);
                socket_setopt($this->clientFD[$i], SOL_SOCKET, SO_REUSEADDR, 1);
                $peer_host    =    "";
                $peer_port    =    "";
                socket_getpeername($this->clientFD[$i], $peer_host, $peer_port);
                $this->clientInfo[$i]    =    array(
                                                    "host"        =>    $peer_host,
                                                    "port"        =>    $peer_port,
                                                    "connectOn"    =>    time()
                                               );
                $this->clients++;

                $this->_sendDebugMessage("New connection (".$i.") from ".$peer_host." on port ".$peer_port);

                if (method_exists($this->callbackObj, "onConnect")) {
                    $this->callbackObj->onConnect($i);
                }
                return $i;
            }
        }
    }

   /**
    * check, whether a client is still connected
    *
    * @access   public
    * @param    integer    $id         client id
    * @return   boolean    $connected  true if client is connected, false otherwise
    */
    function isConnected($id)
    {
        if (!isset($this->clientFD[$id])) {
            return false;
        }
        return true;    
    }

   /**
    * get current amount of clients
    *
    * @access   public
    * @return int    $clients    amount of clients
    */
    function getClients()
    {
        return $this->clients;
    }

   /**
    * send data to a client
    *
    * @access   public
    * @param    int        $clientId    ID of the client
    * @param    string    $data        data to send
    * @param    boolean    $debugData    flag to indicate whether data that is written to socket should also be sent as debug message
    */
    function sendData($clientId, $data, $debugData = true)
    {
        if (!isset($this->clientFD[$clientId]) || $this->clientFD[$clientId] == NULL) {
            return $this->raiseError("Client does not exist.");
        }

        if ($debugData) {
            $this->_sendDebugMessage("sending: \"" . $data . "\" to: $clientId" );
        }
        if (!@socket_write($this->clientFD[$clientId], $data)) {
            $this->_sendDebugMessage("Could not write '".$data."' client ".$clientId." (".$this->getLastSocketError($this->clientFD[$clientId]).").");
        }
        return true;
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
        if (!empty($exclude) && !is_array($exclude)) {
            $exclude    =    array($exclude);
        }

        for($i = 0; $i < count($this->clientFD); $i++) {
            if (isset($this->clientFD[$i]) && $this->clientFD[$i] != NULL && !in_array($i, $exclude)) {
                if (!@socket_write($this->clientFD[$i], $data)) {
                    $this->_sendDebugMessage("Could not write '".$data."' client ".$i." (".$this->getLastSocketError($this->clientFD[$i]).").");
                }
            }
        }
    }

   /**
    * get current information about a client
    *
    * @access   public
    * @param    int        $clientId    ID of the client
    * @return array    $info        information about the client
    */
    function getClientInfo($clientId)
    {
        if (!isset($this->clientFD[$clientId]) || $this->clientFD[$clientId] == NULL) {
            return $this->raiseError("Client does not exist.");
        }
        return $this->clientInfo[$clientId];
    }
    
   /**
    * close connection to a client
    *
    * @access   public
    * @param    int    $clientID    internal ID of the client
    */
    function closeConnection($id = 0)
    {
        if (!isset($this->clientFD[$id])) {
            return $this->raiseError( "Connection already has been closed." );
        }

        if (method_exists($this->callbackObj, "onClose")) {
            $this->callbackObj->onClose($id);
        }

        $this->_sendDebugMessage("Closed connection (".$id.") from ".$this->clientInfo[$id]["host"]." on port ".$this->clientInfo[$id]["port"]);

		@socket_shutdown($this->clientFD[$id], 2);
        @socket_close($this->clientFD[$id]);
        $this->clientFD[$id]    =    NULL;
        unset($this->clientInfo[$id]);
        $this->clients--;
    }

   /**
    * shutdown server
    *
    * @access   public
    */
    function shutDown()
    {
        if (method_exists($this->callbackObj, "onShutdown")) {
            $this->callbackObj->onShutdown();
        }

        $maxFD    =    count($this->clientFD);
        for($i = 0; $i < $maxFD; $i++) {
            $this->closeConnection($i);
        }

        @socket_shutdown($this->initFD, 2);
        @socket_close($this->initFD);

        $this->_sendDebugMessage("Shutdown server.");
        exit();
    }
}
?>