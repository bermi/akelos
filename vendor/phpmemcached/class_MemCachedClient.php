<?php
/* MemCached Client
 * Version 1.0.0
 * Copyright 2004, Steve Blinch
 * http://code.blitzaffe.com
 * 
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 * 
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 *****************************************************************************
 *
 * DETAILS
 *
 * This is a MemCached client library used to connect to MemCache daemons
 * (http://www.danga.com/memcached/) to store and retrieved memory-cached data.
 *
 *
 * EXAMPLE
 *
 * //
 * // Simple MemCached client library example
 * //
 * require_once('class_MemCachedClient.php');
 *
 * $hosts = array('127.0.0.1:1234','127.0.0.2:1234');
 * $mc = &new MemCachedClient($hosts);
 *
 * // try to get a value
 * if (!$mc->get("myvalue")) {
 *
 *		// if an error occurred, exit
 *	 	if ($mc->errno==ERR_NO_SOCKET) {
 *	 		die("Could not connect to MemCache daemon\n");
 * 		}
 *
 *		// set a value
 * 		$mc->set("myvalue",1);
 * }
 *
 * // increment a counter
 * $mc->incr('counter');
 * // decrement a counter
 * $mc->decr('counter');
 *
 * // delete a value
 * $mc->delete("myvalue");
 *
 *
 */
define("CONNECT_TIMEOUT",5);

define("ERR_NO_SOCKET",			1);
define("ERR_NEED_KEY",			2);

define("ERR_STORE_FAILED",		4);
define("ERR_DELETE_FAILED",		5);
define("ERR_INCRDECR_FAILED",	6);

define("ERR_COMMAND_ERROR",		7);
define("ERR_CLIENT_ERROR",		8);
define("ERR_SERVER_ERROR",		9);

define("ERR_UNEXPECTED_RESULT",	10);

$GLOBALS["errormessages"] = array(
	ERR_NO_SOCKET			=> "Could not connect to memcached server",
	ERR_UNEXPECTED_RESULT	=> "Received unexpected result from server",
	ERR_STORE_FAILED		=> "Store operation failed",
	ERR_NEED_KEY			=> "Must specify a key for this operation",
	ERR_DELETE_FAILED		=> "Delete operation failed",
	ERR_INCRDECR_FAILED		=> "Increment/decrement operation failed",
	ERR_COMMAND_ERROR		=> "Server did not understand command",
);

class MemCachedClient {
	
    var $errno = null;
    var $socketdebug = false;
	function MemCachedClient($hosts) {
		
		$this->hosts = &$hosts;

		$this->sockets = array();
		foreach ($this->hosts as $k=>$host) {
			$this->sockets[$host] = false;
		}
	}
	
	function get($keys,$forcehost=false) {
		if (!$keys) return $this->_error(ERR_NEED_KEY);
		
		
		//echo "---BEGIN GET---\n";
		
		if (!is_array($keys)) $keys = array($keys);
		
		$results = array();
		
		// group the keys by server so that we can issue ONE multi-key get command for each server
		$groups = array();
		foreach ($keys as $k=>$key) {
			$socket = $this->_get_socket($key,$forcehost);
			isset($groups[$socket])? $groups[$socket].= $key." ": $groups[$socket] = $key." ";
		}
		reset($keys);
		
		//echo "\n\n"; var_dump($groups); echo "\n\n";

		foreach ($groups as $k=>$keygroup) {
			$cmd = "get ".trim($keygroup)."\r\n";
			list($key,) = explode(" ",$keygroup);
			
			if (!$this->_socket_write($key,$cmd,$forcehost)) return false;
			
			$iterations = 0;
			$retrievals = count($keys);
			
			while (true) {
				if ($iterations>$retrievals) break;
				$iterations++;
	
				if (!$value = $this->_socket_readstr($key,1024,$forcehost)) return false;
				//echo "\n[$value]\n";
				$value = trim($value);
				
				if ($value=="END") break;
				if($value == 'ERROR') 
				{
				    $results[$key] = false;
				    break;
				}
				
				@list(,$key,$flags,$bytestotal) = explode(' ',$value);
				
				$bytesread = 0;
				$bytestotal += 2;
				$data = "";
				//echo "[total:$bytestotal]";
				while ($bytesread<$bytestotal) {
					$remaining = $bytestotal-$bytesread;
					$bufsize = $remaining>1024?1024:$remaining;
					
					if (!$buf = $this->_socket_read($key,$bufsize,$forcehost)) return false;
					$data .= $buf;
					
					$bytesread += strlen($buf);
				}

				$data = substr($data,0,strlen($data)-2);
				if ($flags==1) $data = unserialize($data);
				$results[$key] = $data;
			}
		}
		
		if (count($results)<=1) $results = array_shift($results);

		//echo "\n---END GET---\n";
        
		return $results===null?false:$results;
	}
	
	function set($key,$data,$exptime=0,$forcehost=false) {
		return $this->_store("set",$key,$exptime,$data,$forcehost);
	}

	function add($key,$data,$exptime=0,$forcehost=false) {
		return $this->_store("add",$key,$exptime,$data,$forcehost);
	}

	function replace($key,$data,$exptime=0,$forcehost=false) {
		return $this->_store("replace",$key,$exptime,$data,$forcehost);
	}
	
	function lock($key,$fail=true,$max_wait=30) {
		if ($this->wait_lock($key,$max_wait,$fail)) return false;
		$this->incr('__lock__'.$key);
		
		return true;
	}
	
	function unlock($key) {
		$this->decr('__lock__'.$key);
	}
	
	function is_locked($key) {
		$sem = $this->get('__lock__'.$key);
		return $sem>0;
	}

	// wait up to $max_wait tenths of a second for $key to become unlocked;
	// if $fail is TRUE, the operation will fail if the lock cannot be acquired
	// in the specified amount of time; if $fail is FALSE, the lock will be reset
	// and the write forced if it cannot be acquired in the specified amount of time
	function wait_lock($key,$fail=true,$max_wait=30) {
		$iterations = 0;
		while ($this->is_locked($key)) {
			usleep(100000);
			if ($iterations++==$max_wait) {
				if (!$fail) $this->set('__lock__'.$key,0);
				return !$fail;
			}
		}
		return true;
	}

	function delete($key,$time=0,$forcehost=false) {
		if (!$key) return $this->_error(ERR_NEED_KEY);
		
		$time = (int) $time;
		$cmd = "delete $key $time\r\n";
		
		if (!$this->_socket_write($key,$cmd,$forcehost)) return false;
		if (!$res = $this->_socket_readstr($key,1024,$forcehost)) return false;
		$res = trim($res);
		
		switch($res) {
			case "DELETED": 
				return $key;
			case "NOT_FOUND":
				return $this->_error(ERR_DELETE_FAILED);
			default:
				return $this->_get_error($res);
		}
				
	}
	
	function flush_all($host=false) {
		if ($host!==false) $hosts = array($host); else $hosts = $this->hosts;
		
		foreach ($this->hosts as $k=>$host) {
			if (!$this->_socket_write(-1,"flush_all\r\n",$host)) continue;
			if (!$res = $this->_socket_readstr(-1,1024,$host)) continue;
		}
		
		return true;
	}
	
	function disconnect($host=false) {
		if ($host!==false) $hosts = array($host); else $hosts = $this->hosts;
		
		foreach ($this->hosts as $k=>$host) {
			$socket = $this->_get_socket(-1,$host);
			if ($socket) fclose($socket);
		}
		
		return true;
	}
	
	function version($host=false) {
		if ($host!==false) $hosts = array($host); else $hosts = $this->hosts;
		
		$results = array();
		
		foreach ($this->hosts as $k=>$host) {
			if (!$this->_socket_write(-1,"version\r\n",$host)) continue;
			if (!$res = $this->_socket_readstr(-1,1024,$host)) continue;
			list(,$version) = explode(' ',trim($res));
			
			$results[$host] = $version;
		}
		
		return $results;
	}
	
	function incr($key,$value=1,$forcehost=false) {
		return $this->_incrdecr("incr",$key,$value,$forcehost);
	}

	function decr($key,$value=1,$forcehost=false) {
		return $this->_incrdecr("decr",$key,$value,$forcehost);
	}
	
	function stats($host) {
		if (!$this->_socket_write(-1,"stats\r\n",$host)) return false;

		$results = array();
		
		$iterations = 0;
		
		while (true) {
			if ($iterations>500) break; // avoid infinite loops if something goes nuts
			$iterations++;

			if (!$line = $this->_socket_readstr(-1,1024,$host)) return false;
			$line = trim($line);
			if ($line=="END") break;
			
			list(,$name,$value) = explode(' ',$line);
			$results[$name] = $value;
			
		}
		
		return $results;		
	}
	
	function _incrdecr($method,$key,$value,$forcehost=false) {
		if (!$key) return $this->_error(ERR_NEED_KEY);
		
		$value = (int) $value;
		if ($value<1) $value = 1;
		$cmd = "$method $key $value\r\n";
		
		if (!$this->_socket_write($key,$cmd,$forcehost)) return false;
		if (!$res = $this->_socket_readstr($key,1024,$forcehost)) return false;
		$res = trim($res);

		if (is_numeric($res)) {
			return $res;
		} else {
			switch($res) {
				case "NOT_FOUND":
					return $this->_error(ERR_INCRDECR_FAILED);
				default:
					return $this->_get_error($res);
			}
		}
	}
	
	function _store($method,$key,$exptime,$data,$forcehost=false) {
		if (!$key) $key = $this->_generate_key();
		$exptime = (int) $exptime;
		$flags = 0;
		
		if (!is_scalar($data)) {
			$data = serialize($data);
			$flags = 1;
		}
		$len = strlen($data);
		
		$cmd = "$method $key $flags $exptime $len\r\n$data\r\n";
		
		if (!$this->_socket_write($key,$cmd,$forcehost)) return false;
		if (!$res = $this->_socket_readstr($key,1024,$forcehost)) return false;
		$res = trim($res);
		
		switch($res) {
			case "STORED": 
				return $key;
			case "NOT_STORED":
				return $this->_error(ERR_STORE_FAILED);
			default:
				return $this->_get_error($res);
		}
		
	}
	
	// try to write some data to the socket for $key
	function _socket_write($key,$data,$forcehost=false,$attemptnumber=0) {
		//echo "WRITE:[$key|$data]";
		
		// get the appropriate socket
		$socket = $this->_get_socket($key,$forcehost);
		// if no socket was returned, then there are no memcached servers available
		if (!$socket) return $this->_error(ERR_NO_SOCKET);
		
		// try to write our data to the socket
		if (!$byteswritten = fwrite($socket,$data)) {
			// if the data could not be written, then that socket has died (host down, etc.)

			// if we've tried all of the possible hosts, then all memcached servers have
			// gone down - bomb out with an error
			$attemptnumber++;
			if ($attemptnumber>=count($this->hosts)) return $this->_error(ERR_NO_SOCKET);
			
			// mark this socket as dead, and try to get another socket
			$socket = $this->_get_alternate_socket($key,$forcehost);
			
			// try the write again
			return $this->_socket_write($key,$data,$forcehost,$attemptnumber);
		}

		if ($this->socketdebug) echo "WRITE [$key:$data]<br>\n";
		return $byteswritten;
	}
	
	function _socket_read($key,$len=10240,$forcehost=false,$attemptnumber=0) {
		$socket = $this->_get_socket($key,$forcehost);
		if (!$socket) return $this->_error(ERR_NO_SOCKET);
		
		$res = fread($socket,$len);
		if ($res===false) {
			// if the data could not be read due to an error, then that socket has died
			// (host down, etc.)

			// if we've tried all of the possible hosts, then all memcached servers have
			// gone down - bomb out with an error
			$attemptnumber++;
			if ($attemptnumber>=count($this->hosts)) return $this->_error(ERR_NO_SOCKET);
			
			// mark this socket as dead, and try to get another socket
			$socket = $this->_get_alternate_socket($key,$forcehost);
			
			// try the write again
			return $this->_socket_read($key,$len,$forcehost,$attemptnumber);
		}
		//echo "READ:[$key|$res]";
		if ($this->socketdebug) echo "READ [$key:$res]<br>\n";
		return $res;		
	}
	
	function _socket_readstr($key,$len=10240,$forcehost=false,$attemptnumber=0) {
		$socket = $this->_get_socket($key,$forcehost);
		if (!$socket) return $this->_error(ERR_NO_SOCKET);
		
		$res = fgets($socket,$len);
		if ($res===false) {
			// if the data could not be read due to an error, then that socket has died
			// (host down, etc.)

			// if we've tried all of the possible hosts, then all memcached servers have
			// gone down - bomb out with an error
			$attemptnumber++;
			if ($attemptnumber>=count($this->hosts)) return $this->_error(ERR_NO_SOCKET);
			
			// mark this socket as dead, and try to get another socket
			$socket = $this->_get_alternate_socket($key,$forcehost);
			
			// try the write again
			return $this->_socket_readstr($key,$len,$forcehost,$attemptnumber);
		}
		//echo "READ:[$key|$res]";
		if ($this->socketdebug) echo "READ [$key:$data]<br>\n";
		return $res;
	}	
	
	
	// marks the current socket for $key/$forcehost as dead, and then attempts
	// to get a socket connection to another server to take over	
	function _get_alternate_socket($key,$forcehost) {
		$this->_remove_socket($key,$forcehost);
		return $this->_get_socket($key,$forcehost);
	}
	
	function _get_active_sockets() {
		$res = array();
		
		reset($this->sockets);
		foreach ($this->sockets as $host=>$socket) {
			if (!is_array($socket) && !is_null($socket)) $res[$host] = $socket;
		}
		
		return $res;
	}
	
	function _get_hash($key) {
		/*
		if ($this->xh[$key]) return $this->xh[$key];
		$this->globaliterator++;
		$this->xh[$key] = $this->globaliterator;
		return $this->xh[$key];
		*/
		return sprintf("%u",crc32($key));
	}
	
	
	function _remove_socket($key,$forcehost=false) {
		//$host = $this->_get_host($key,$forcehost);

		$hash = $this->_get_hash($key);
		$keys = array_keys($this->sockets);
		$host = $keys[$hash % count($keys)];

		$this->sockets[$host] = NULL;
		
		$livesockets = $this->_get_active_sockets();
		$this->_set_socket_recursive($host,$livesockets,&$this->sockets);
		
		/*
		$oldsocket = $this->sockets[$host];
		$this->sockets[$host] = NULL;
		*/
	}

	function _get_socket_recursive($hash,&$sockets) {
		$keys = array_keys($sockets);
		$host = $keys[$hash % count($keys)];
		
		$socket = &$sockets[$host];
		if (is_array($socket)) {
			return $this->_get_socket_recursive($hash,&$socket);
		} else {
			//echo "$hash = $host<br>";
			$this->_last_host = $host;
			return array($host,$socket);
		}
	}
			
	function _get_socket($key,$forcehost=false,$attempts=0) {
		//$host = $this->_get_host($key,$forcehost);

		$hash = $this->_get_hash($key);
		
		list($host,$socket) = $this->_get_socket_recursive($hash,&$this->sockets);

		if (!$socket) {
			list($hostname,$port) = explode(':',$host);
			$socket = @fsockopen($hostname,$port,$errno,$errstr,CONNECT_TIMEOUT);
			if (!$socket) {
				$attempts++;
				if ($attempts>=count($this->sockets)) {
					return $this->_error(ERR_NO_SOCKET);
				} else {
					$this->_remove_socket($key,$forcehost);
					return $this->_get_socket($key,$forcehost,$attempts);
				}
			}
//			$socket = "OPENSoCK-{$hostname}";
			
			$this->_set_socket($host,$socket);
		}
		
		return $socket;
	}
	
	function _set_socket_recursive($host,$socket,&$sockets) {
		foreach ($sockets as $loophost=>$loopsocket) {
			if (is_array($loopsocket)) {
				$this->_set_socket_recursive($host,$socket,&$sockets[$loophost]);
			} elseif ($loophost==$host) {
				$sockets[$host] = $socket;
			}
		}
	}
	
	function _set_socket($host,$socket) {
		$this->_set_socket_recursive($host,$socket,&$this->sockets);
	}

	function _generate_key() {
		return md5(uniqid(rand(), true)); 
	}
	
	function _get_error($line) {
		if (substr($line,0,6)=="ERROR\n") {
			$this->_error(ERR_COMMAND_ERROR);
		} elseif (substr($line,0,13)=="CLIENT_ERROR ") {
			$this->errno = ERR_CLIENT_ERROR;
			$this->errstr = substr($line,13);
		} elseif (substr($line,0,13)=="SERVER_ERROR ") {
			$this->errno = ERR_SERVER_ERROR;
			$this->errstr = substr($line,13);
		} else {
			$this->_error(ERR_UNEXPECTED_RESULT);
		}
		return false;
	}	
	
	function _error($id) {
		global $errormessages;
		
		$this->errno = $id;
		$this->errstr = $errormessages[$id];
		return false;
	}
	
}
?>