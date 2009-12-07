<?php

require_once(AK_CONTRIB_DIR.DS.'pear'.DS.'Image'.DS.'Color.php');

class AkColor extends Image_Color
{
    static function rgbToHex() {
        $rgb = func_get_args();
        $hex = '';
        foreach (count($rgb) == 1 ? $rgb[0] : $rgb as $color){
            $color = dechex($color);
            $hex .= strlen($color) == 2 ? $color : $color.$color;
        }
        return $hex;
    }

    static function hexToRgb($hex_color) {
        $hex_color = strtolower(trim($hex_color,'#;&Hh'));
        return array_map('hexdec',explode('.',wordwrap($hex_color, ceil(strlen($hex_color)/3),'.',1)));
    }

    static function getOpositeHex($hex_color) {
        $rgb = AkColor::hexToRgb($hex_color);
        foreach ($rgb as $k=>$color){
            $rgb[$k] = (255-$color < 0 ? 0 : 255-$color);
        }
        return AkColor::rgbToHex($rgb);
    }

    static function getRandomHex() {
        return AkColor::rgbToHex(rand(0,255),rand(0,255),rand(0,255));
    }
}

