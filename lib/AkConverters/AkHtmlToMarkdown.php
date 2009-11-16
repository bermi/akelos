<?php

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+

/**
 * @package ActiveSupport
 * @subpackage Converters
 * @author Bermi Ferrer <bermi a.t bermilabs c.om>
 */

class AkHtmlToMarkdown
{
    public function convert()
    {
        require_once(AK_VENDOR_DIR.DS.'TextParsers'.DS.'html2text.php');
        $Converter = new html2text(true, 0, false);
        return $Converter->load_string($this->source);
    }
}

