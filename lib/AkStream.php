<?php

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
 * @package ActionController
 * @subpackage Base
 * @author Bermi Ferrer <bermi a.t bermilabs c.om>
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

require_once(AK_LIB_DIR.DS.'Ak.php');

class AkStream
{
    var $buffer_size;
    var $path;
    
    function AkStream($path, $buffer_size = 4096)
    {
        $this->buffer_size = empty($buffer_size) ? 4096 : $buffer_size;
        $this->path = $path;
    }

    function stream()
    {
        Ak::stream($this->path, $this->buffer_size);
    }    
}

?>