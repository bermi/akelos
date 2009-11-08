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

class AkTextileToHtml
{
    public function convert()
    {
        require_once(AK_LIB_DIR.DS.'AkActionView'.DS.'helpers'.DS.'text_helper.php');
        return TextHelper::textilize($this->source);
    }
}

?>
