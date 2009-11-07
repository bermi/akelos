<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2006, Akelos Media, S.L.  & Bermi Ferrer Martinez |
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
 * @package ActiveSupport
 * @subpackage Converters
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */
class AkHtmlToRtf
{
    public $font_face = 0;
    public $font_size = 24;

    // Convert special characters to ASCII
    public function escapeCharacter($character)
    {
        $escaped = '';
        if(ord($character) >= 0x00 && ord($character) < 0x20){
            $escaped = "\\'".dechex(ord($character));
        }

        if ((ord($character) >= 0x20 && ord($character) < 0x80) || ord($character) == 0x09 || ord($character) == 0x0A){
            $escaped = $character;
        }

        if (ord($character) >= 0x80 and ord($character) < 0xFF){
            $escaped = "\\'".dechex(ord($character));
        }

        switch(ord($character)) {
            case 0x5C:
            case 0x7B:
            case 0x7D:
            $escaped = "\\".$character;
            break;
        }

        return $escaped;
    }

    public function specialCharacters($text)
    {
        $text_buffer = '';
        for($i = 0; $i < strlen($text); $i++){
            $text_buffer .= $this->escapeCharacter($text[$i]);
        }
        return $text_buffer;
    }

    public function convert()
    {
        $this->source = str_replace(
        array('<ul>','<UL>','<ol>','<OL>','</ul>','</UL>','</ol>','</OL>'),
        '',
        $this->source
        );
        $this->source = $this->specialCharacters($this->source);
        
        $rules = array(
        "/<LI>(.*?)<\/LI>/mi"=> "\\f3\\'B7\\tab\\f{$this->font_face} \\1\\par",
        "/<P>(.*?)<\/P>/mi" => "\\1\\par ",
        "/<STRONG>(.*?)<\/STRONG>/mi" => "\\b \\1\\b0 ",
        "/<B>(.*?)<\/B>/mi" => "\\b \\1\\b0 ",
        "/<EM>(.*?)<\/EM>/mi" => "\\i \\1\\i0 ",
        "/<U>(.*?)<\/U>/mi" => "\\ul \\1\\ul0 ",
        "/<STRIKE>(.*?)<\/STRIKE>/mi" => "\\strike \\1\\strike0 ",
        "/<SUB>(.*?)<\/SUB>/mi" => "{\\sub \\1}",
        "/<SUP>(.*?)<\/SUP>/mi" => "{\\super \\1}",
        "/<H1>(.*?)<\/H1>/mi" => "\\fs48\\b \\1\\b0\\fs{$this->font_size}\\par ",
        "/<H2>(.*?)<\/H2>/mi" => "\\fs36\\b \\1\\b0\\fs{$this->font_size}\\par ",
        "/<H3>(.*?)<\/H3>/mi" => "\\fs27\\b \\1\\b0\\fs{$this->font_size}\\par ",
        "/<HR(.*?)>/i" => "\\brdrb\\brdrs\\brdrw30\\brsp20 \\pard\\par ",
        "/<BR(.*?)>/i" => "\\par ",
        "/<TAB(.*?)>/i" => "\\tab ",
        "/\\n/" => "\\par "
        );
        
        return preg_replace(array_keys($rules),array_values($rules), $this->source);

    }

}

?>
