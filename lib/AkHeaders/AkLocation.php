<?php

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
 * @package ActionController
 * @subpackage Response
 * @author Bermi Ferrer <bermi a.t bermilabs c.om>
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

require_once(AK_LIB_DIR.DS.'Ak.php');

class AkLocation
{
    function redirect($url)
    {
        if (!headers_sent($file_name, $line_number)) {
            header("Location: $url");
            exit;
        } else {
            trigger_error(Ak::t('Headers already sent in %file_name on line %line_number',array('%file_name'=>$file_name,'%line_number'=>$line_number)), E_NOTICE);
            echo "<meta http-equiv=\"refresh\" content=\"0;url=$url\">";
            echo Ak::t('Cannot redirect, for now please click this <a href="%url">link</a> instead',array('%url'=>$url));
            exit;
        }
    }

}

?>