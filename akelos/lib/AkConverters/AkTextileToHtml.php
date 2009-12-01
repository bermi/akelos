<?php

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+

/**
 * @package ActiveSupport
 * @subpackage Converters
 * @author Bermi Ferrer <bermi a.t bermilabs c.om>
 */

class AkTextileToHtml
{
    public function convert()
    {
        require_once(AK_LIB_DIR.DS.'action_pack'.DS.'helpers'.DS.'text_helper.php');
        return TextHelper::textilize($this->source);
    }
}

