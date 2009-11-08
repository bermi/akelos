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

class AkHtmlToSmartypants
{
    public function convert()
    {
        require_once(AK_VENDOR_DIR.DS.'TextParsers'.DS.'smartypants.php');
        $Smartypants = new SmartyPantsTypographer_Parser();
        return $Smartypants->transform($this->source);
    }
}

?>
