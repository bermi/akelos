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
 * @subpackage Converters
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

class AkHtmlToText
{
    function convert()
    {
        require_once(AK_VENDOR_DIR.DS.'TextParsers'.DS.'html2text.php');
        $Converter = new html2text(true, 0, false);
        $markdown = str_replace('__AK:AMP__','&', $Converter->load_string(str_replace('&','__AK:AMP__', $this->source)));

        require_once(AK_VENDOR_DIR.DS.'TextParsers'.DS.'smartypants.php');
        $Smartypants = new SmartyPantsTypographer_Parser();

        $markdown = html_entity_decode(strip_tags($Smartypants->transform($markdown)),ENT_QUOTES,'UTF-8');

        return trim($this->_simplifyMarkdown($markdown));
    }

    function _simplifyMarkdown($markdown)
    {
        $markdown = trim($markdown);
        if(strstr($markdown,"\n")){
            if(preg_match_all('/([ \t]*[#]{1,2})(.*)(\{#[a-z0-9_]+}|$)+/i',$markdown,$matches)){
                foreach ($matches[0] as $k=>$match){
                    $simple_markdown = trim($matches[2][$k]);
                    if($match[0] == '#'){
                        if($simple_markdown[0] == '#'){ // h3, h4, h5
                            $markdown = str_replace($match,'##'.$simple_markdown,$markdown);
                        }else{
                            $separator = strlen($matches[1][$k]) === 1 ? '=' : '-';
                            $simple_markdown .= "\n".str_repeat($separator,strlen($simple_markdown));
                            $markdown = str_replace($match,$simple_markdown,$markdown);
                        }
                    }
                }
            }
        }
        return $markdown;
    }
}

?>
