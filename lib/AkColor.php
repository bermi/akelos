<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2006, Akelos Media, S.L.  & Bermi Ferrer Martinez |
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
 * @package AkelosFramework
 * @subpackage Utils
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

require_once(AK_VENDOR_DIR.DS.'pear'.DS.'Image'.DS.'Color.php');

class AkColor extends Image_Color
{
    function rgbToHex()
    {
        $rgb = func_get_args();
        $hex = '';
        foreach (count($rgb) == 1 ? $rgb[0] : $rgb as $color){
            $color = dechex($color);
            $hex .= strlen($color) == 2 ? $color : $color.$color;
        }
        return $hex;
    }

    function hexToRgb($hex_color)
    {
        $hex_color = strtolower(trim($hex_color,'#;&Hh'));
        return array_map('hexdec',explode('.',wordwrap($hex_color, ceil(strlen($hex_color)/3),'.',1)));
    }

    function getOpositeHex($hex_color)
    {
        $rgb = AkColor::hexToRgb($hex_color);
        foreach ($rgb as $k=>$color){
            $rgb[$k] = (255-$color < 0 ? 0 : 255-$color);
        }
        return AkColor::rgbToHex($rgb);
    }

    function getRandomHex()
    {
        return AkColor::rgbToHex(rand(0,255),rand(0,255),rand(0,255));
    }
}

?>
