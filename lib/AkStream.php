<?php

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+

/**
 * @package ActionController
 * @subpackage Base
 * @author Bermi Ferrer <bermi a.t bermilabs c.om>
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