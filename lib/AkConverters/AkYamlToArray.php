<?php

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+

/**
 * @package ActiveSupport
 * @subpackage Converters
 * @author Bermi Ferrer <bermi a.t bermilabs c.om>
 */

class AkYamlToArray
{
    public function convert()
    {
        include_once AK_VENDOR_DIR.DS.'TextParsers'.DS.'spyc.php';
        return Spyc::YAMLLoad($this->source);
    }
}

