<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2006, Akelos Media, S.L.  & Bermi Ferrer Martinez |
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
 * @package AkelosFramework
 * @subpackage AkActionController
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

class AkHttpStatusCode
{
	function statusCode($code_number=null, $send=true)
	{
		$codes = array(
		// Information Codes
		100=>'100 Continue',
		101=>'101 Switching Protocols',
		102=>'102 Processing',
		// Success Codes
		200=>'200 OK',
		201=>'201 Created',
		202=>'202 Accepted',
		203=>'203 Non-Authoriative Information',
		204=>'204 No Content',
		205=>'205 Reset Content',
		206=>'206 Partial Content',
		207=>'207 Multi-Status',
		// Redirection Codes
		300=>'300 Multiple Choices',
		301=>'301 Moved Permanently',
		302=>'302 Found',
		303=>'303 See Other',
		304=>'304 Not Modified',
		305=>'305 Use Proxy',
		306=>'306 (Unused)',
		307=>'307 Temporary Redirect',
		// Client Error Codes
		400=>'400 Bad Request',
		401=>'401 Unauthorized',
		402=>'402 Payment Granted',
		403=>'403 Forbidden',
		404=>'404 Not found',
		405=>'405 Method Not Allowed',
		406=>'406 Not Acceptable',
		407=>'407 Proxy Authentication Required',
		408=>'408 Request Time-out',
		409=>'409 Conflict',
		410=>'410 Gone',
		411=>'411 Length Required',
		412=>'412 Precondition Failed',
		413=>'413 Request Entity Too Large',
		414=>'414 Request-URI Too Large',
		415=>'415 Unsupported Media Type',
		416=>'416 Requested range not satisfiable',
		417=>'417 Expectation Failed',
		422=>'422 Unprocessable Entity',
		423=>'423 Locked',
		424=>'424 Failed Dependency',
		// Server Errors
		500=>'500 Internal Server Error',
		501=>'501 Not Implemented',
		502=>'502 Bad Gateway',
		503=>'503 Service Unavailable',
		504=>'504 Gateway Timeout',
		505=>'505 HTTP Version Not Supported',
		507=>'507 Insufficient Storage');
		

		if(isset($code_number) && array_key_exists($code_number,$codes)){
			if($send==true){
				AkHeaders::sendHeader("Status: ".$codes[$code_number]);
			}else{
				AkHeaders::addHeader("Status: ".$codes[$code_number]);
			}
			return true;
		}
		return false;
	}
}

?>