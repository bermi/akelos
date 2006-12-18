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
* @subpackage AkImage
* @author Bermi Ferrer <bermi a.t akelos c.om>
* @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
* @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
*/

require_once(AK_LIB_DIR.DS.'AkImage.php');

class AkImageColorScheme extends AkObject
{
    var $number_of_colors = 12;
    var $calculate_negatives = true;
    var $minimum_hits_for_negative = 20;
    var $Image;
    var $_tmp_file;
    var $_frequentColors = array();

    function setImage($image_path)
    {
        $this->Image =& new AkImage($image_path);
        $this->Image->transform('resize',array('size'=>'24x24'));
        $this->_tmp_file = AK_CACHE_DIR.DS.'__AkImageColorScheme_'.Ak::randomString(32).'.jpg';
        $this->Image->save($this->_tmp_file);
    }

    function __destruct()
    {
        if(file_exists($this->_tmp_file)){
            @Ak::file_delete($this->_tmp_file);
        }
    }

    function getColorScheme($number_of_colors = null)
    {
        $colors = array();
        if($image = @imagecreatefromjpeg($this->_tmp_file)){
            $imgage_width = $this->Image->Transform->new_x;
            $imgage_height = $this->Image->Transform->new_y;
            $inverted_colors = array();
            for ($y=0; $y < $imgage_height; $y++){
                for ($x=0; $x < $imgage_width; $x++){
                    $index = imagecolorat($image, $x, $y);
                    $image_colors = imagecolorsforindex($image, $index);
                    $hex = '';
                    foreach ($image_colors as $color=>$value){
                        $image_colors[$color] = intval((($image_colors[$color])+15)/32)*32;
                        $image_colors[$color] = $image_colors[$color] >= 256 ? 240 : $image_colors[$color];
                        $hex .= substr('0'.dechex($image_colors[$color]), -2);
                    }
                    $hex = substr($hex, 0, 6);
                    if(strlen($hex) == 6){
                        $colors[$hex] = empty($colors[$hex]) ? 1 : $colors[$hex]+1;
                        $this->_addToFrequentColors($hex);
                        if($this->calculate_negatives && $colors[$hex] > $this->minimum_hits_for_negative){
                            $negative = $this->_getNegativeAsHex($image_colors['red'], $image_colors['green'], $image_colors['blue']);
                            $colors[$negative] = empty($colors[$negative]) ? 1 : $colors[$negative]+1;
                            $this->_addToFrequentColors($negative);
                        }
                    }
                }
            }
        }

        return $this->_getColorsFromCounterColorArray($colors, $number_of_colors);
    }

    function _getColorsFromCounterColorArray($colors_array, $number_of_colors = null)
    {
        $number_of_colors = empty($number_of_colors) ? $this->number_of_colors : $number_of_colors;
        asort($colors_array);
        $colors_array = array_slice(array_unique(array_keys(array_reverse($colors_array, true))), 0, $number_of_colors);
        natsort($colors_array);
        return $colors_array;
    }

    function _getNegativeAsHex($red, $green, $blue)
    {
        $rgb = $red*0.15 + $green*0.5 + $blue * 0.35;
        return $this->_rgbToHex(array(255-$rgb, 255-$rgb, 255-$rgb));
    }

    function _addToFrequentColors($hex_color)
    {
        $this->_frequentColors[$hex_color] = empty($this->_frequentColors[$hex_color]) ? 1 : $this->_frequentColors[$hex_color]+1;
    }

    function resetFrequentColors()
    {
        $this->_frequentColors = array();
    }

    function getFrequentColors($number_of_colors = null)
    {
        return $this->_getColorsFromCounterColorArray($this->_frequentColors, $number_of_colors);
    }

    function _rgbToHex($rgb)
    {
        $r = str_pad(dechex($rgb[0]), 2, '0', STR_PAD_LEFT);
        $g = str_pad(dechex($rgb[1]), 2, '0', STR_PAD_LEFT);
        $b = str_pad(dechex($rgb[2]), 2, '0', STR_PAD_LEFT);
        return $r.$g.$b;
    }
}

?>