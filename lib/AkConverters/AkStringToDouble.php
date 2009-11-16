<?php

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+

/**
 * @package ActiveSupport
 * @subpackage Converters
 * @author Bermi Ferrer <bermi a.t bermilabs c.om>
 */

class AkStringToDouble
{
    public function convert()
    {
        return doubleval($this->source);
    }
}


