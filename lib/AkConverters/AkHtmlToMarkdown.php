<?php

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
 * @package ActiveSupport
 * @subpackage Converters
 * @author Bermi Ferrer <bermi a.t bermilabs c.om>
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
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

?>
