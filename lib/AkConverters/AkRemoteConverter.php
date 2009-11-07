<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2006, Akelos Media, S.L.  & Bermi Ferrer Martinez |
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
 * @package ActiveSupport
 * @subpackage Converters
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2007, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */
class AkRemoteConverter
{
    public function convert($from, $to, $data)
    {
        if(!defined('AK_REMOTE_CONVERTER_URI')){
            return false;
        }
        $details = parse_url(AK_REMOTE_CONVERTER_URI);
        if(empty($details['host'])){
            return false;
        }
        $port = empty($details['port']) ? 80 : $details['port'];
        $path = empty($details['path']) ? '' : $details['path'];

        $data = "data=$data";
        if ($fp = fsockopen($details['host'], $port)) {
            fwrite($fp, "POST $path/{$_SERVER['SERVER_NAME']}/{$from}_to_{$to} HTTP/1.1\r\n".
            "Host: webservices.akelos.com\r\nContent-type: application/x-www-form-urlencoded\r\n".
            "User-Agent: Mozilla 4.0\r\nContent-length: ".strlen($data)."\r\nConnection: close\r\n\r\n$data");
            $result = '';
            while (!feof($fp)) {
                $result .= fgets($fp, 1024);
            }
            if(preg_match('/\n\n.*/ms',str_replace("\r\n","\n",$result),$match)){
                $result = explode("\n",trim($match[0]));
                array_pop($result);
                array_shift($result);
                $result = join("\n",$result);
            }
            fclose($fp);
            return $result == 'CONVERTER_NOT_AVAILABLE' ? false : $result;
        }
        return false;
    }
}

?>