<?php

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+

/**
 * @package ActiveSupport
 * @subpackage Converters
 * @author Bermi Ferrer <bermi a.t bermilabs c.om>
 */

class AkMarkdownToHtml
{
    public function convert()
    {
        return $this->source = preg_replace("/([ \n\t]+)/",' ', TextHelper::markdown($this->source));
    }
}

