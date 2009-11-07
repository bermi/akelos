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
//    $Id: Server.php,v 1.12 2004/07/17 10:36:17 schst Exp $

/**
 * PHP socket server base class
 *
 * @category    Networking
 * @package     Net_Server
 * @version     0.11 alpha
 * @author      Stephan Schmidt <schst@php.net>
 * @license     PHP License
 */

/**
 * uses PEAR's error handling and the destructors
 */
require_once 'PEAR.php';

/**
 * driver file does not exist
 */
define('NET_SERVER_ERROR_UNKNOWN_DRIVER', 51);

/**
 * driver file does not contain class
 */
define('NET_SERVER_ERROR_DRIVER_CORRUPT', 52);

/**
 * feature is not supported
 */
define('NET_SERVER_ERROR_NOT_SUPPORTED', 53);

/**
 * needs PCNTL extension
 */
define('NET_SERVER_ERROR_PCNTL_REQUIRED', 54);

/**
 * PHP socket server base class
 *
 * This class must only be used to create a new server using
 * the static method 'create()'.
 *
 * To handle the events that happen while the server is running
 * you have to create a new class that handles all events.
 *
 * <code>
 * require_once 'myHandler.php';
 * require_once 'Net/Server.php';
 *
 * $server = &Net_Server::create('fork', 'localhost', 9090);
 *
 * $handler = &new myHandler;
 *
 * $server->setCallbackObject($handler);
 *
 * $server->start();
 * </code>
 *
 * See Server/Handler.php for a baseclass that you can
 * use to implement new handlers.
 *
 * @category    Networking
 * @package     Net_Server
 * @version 0.11 alpha
 * @author  Stephan Schmidt <schst@php.net>
 */
class Net_Server {

   /**
    * Create a new server
    *
    * Currently two types of servers are supported:
    * - 'sequential', creates a server where one process handles all request from all clients sequentially
    * - 'fork', creates a server where a new process is forked for each client that connects to the server. This only works on *NIX
    *
	* This method will either return a server object or a PEAR error if the server
	* type does not exist.
	*
    * @access public
    * @static
    * @param  string    $type   type of the server
    * @param  string    $host   hostname
    * @param  integer   $port   port
	* @return object Net_Server_Driver  server object of the desired type
	* @throws object PEAR_Error
    */
    function &create($type, $host, $port)
    {
        if (!function_exists('socket_create')) {
            return PEAR::raiseError('Sockets extension not available.');
        }

        $type       =   ucfirst(strtolower($type));
        $driverFile =   'Net/Server/Driver/' . $type . '.php';
        $className  =   'Net_Server_Driver_' . $type;
        
        if (!include_once $driverFile) {
            return PEAR::raiseError('Unknown server type', NET_SERVER_ERROR_UNKNOWN_DRIVER);
        }
        
        if (!class_exists($className)) {
            return PEAR::raiseError('Driver file is corrupt.', NET_SERVER_ERROR_DRIVER_CORRUPT);
        }

        $server = &new $className($host, $port);
        return $server;
    }
}
?>