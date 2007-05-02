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

class AkHtmlToTextile
{
    function convert()
    {
        ak_compat('str_ireplace');
        $this->source = trim(preg_replace("/(>[ \n\t]+<)/",'> <', $this->source));
        return trim($this->detextilize($this->source));
    }

    function detextilize($html)
    {
        $replacements =
        array("\r"=>"\n","\t"=>" ","  "=>' ' , "<br />" => "\n", "<br>" => "\n",
        "<table>" => "", "</table>" => "", "<tr>" => "", "</tr>" => "|\n", "<td>" => "|",
        "</td>" => "", "<th>" => "|_.", "</th>" => '');

        $html = str_ireplace(array_keys($replacements), array_values($replacements), $html);
        $html = preg_replace('/<img(?!.*\/>)([^>]*)>/Us','<img$1 />',$html);
        $html = preg_replace('/<img\s*([^>]*)/>/Usi','<img $1>image hack</img>',$html);

        $valid_tags = array('p','ol','ul','li','i','b','em','strong','span','a','h[1-6]',
        'u','del','sup','sub','blockquote','img');
                
        foreach($valid_tags as $tag){
            $html = preg_replace_callback("/\s*<(".$tag.")\s*([^>]*)>(.*)<\/\\1>/Usi",
            array(&$this, 'replaceTag'), $html);
        }
        
        $html = $this->convertList($html);

        return Ak::html_entity_decode($html);
    }

    function convertList($html)
    {
        $is_list = false;

        $html = preg_split("/(<.*>)/U", $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        foreach($html as $html_line){

            if ($is_list == false && preg_match('/<ol/',$html_line)){
                $html_line = ' ';
                $is_list = 'o';
            } else if (preg_match('/<\/ol/',$html_line)){
                $html_line = ' ';
                $is_list = false;
            } else if ($is_list == false && preg_match('/<ul/',$html_line)){
                $html_line = '';
                $is_list = 'u';
            } else if (preg_match('/<\/ul/',$html_line)){
                $html_line = '';
                $is_list = false;
            } else if ($is_list == 'o'){
                $html_line = preg_replace('/<li.*>/U','# ', $html_line);
            } else if ($is_list == 'u'){
                $html_line = preg_replace('/<li.*>/U','* ', $html_line);
            }
            $glyph_out[] = str_replace("</li>","\n", $html_line);
        }

        $html = implode('', $glyph_out);

        return preg_replace('/^\t* *p\. /m','',$html);
    }

    function replaceTag($html_tag_matches)
    {
        list($all, $tag, $attributes, $content) = $html_tag_matches;
        
        $attributes = $this->getAttributesAsArray($attributes);

        $delimiters = array(
        'em'=>'_',
        'i'=>'__',
        'b'=>'**',
        'strong'=>'*',
        'code'=>'@',
        'cite'=>'??',
        'del'=>'-',
        'ins'=>'+',
        'sup'=>'^',
        'sub'=>'~',
        'span'=>'%');

        $block_tags = array('p','h1','h2','h3','h4','h5','h6');

        if(isset($delimiters[$tag])) {
            return $delimiters[$tag].$this->addAttributes($attributes).$content.$delimiters[$tag];
        } elseif($tag == 'blockquote') {
            return 'bq.'.$this->addAttributes($attributes).' '.$content;
        } elseif(in_array($tag,$block_tags)) {
            return $tag.$this->addAttributes($attributes).'. '.$content."\n\n";
        } elseif ($tag == 'a' && isset($attributes['href'])) {
            return '"'.$content.((isset($attributes['title'])) ? ' ('.$attributes['title'].')' : '').'":'.$attributes['href'];
        } elseif ($tag == 'img' && isset($attributes['src'])) {
            return '!'.$attributes['src'].(isset($attributes['alt'])?'('.$attributes['alt'].')':'').'!';
        } else {
            return $all;
        }
    }


    function addAttributes($attributes)
    {
        $result = '';
        $delimiters = array('class'=>array('(',')'),'lang'=>array('[',']'),'id'=>array('[',']'),'style'=>array('{','}',';'),'cite'=>array(':',''));
        foreach($attributes as $name=>$value){
            $value = isset($delimiters[$name][2]) ? trim($value, $delimiters[$name][2]) : $value;
            $result .= isset($delimiters[$name]) ? $delimiters[$name][0].$value.$delimiters[$name][1] : '';
        }
        return $result;
    }


    function getAttributesAsArray($attributes_string)
    {
        $attributes = array();
        if(preg_match_all('/(\w+)[\s]*\=[\s]*
        (
            (?=")"([^"]+)"  # double quoted attributes
        |
            (?=\')\'([^\']+)\' # single quoted attributes  
        |
            (?=[^\'"])([^ \>]+) # unquoted attributes  
    )/xs',
        $attributes_string, $matches)){
            foreach ($matches[1] as $k=>$attribute){
                $attributes[$attribute] = empty($matches[3][$k]) ? (empty($matches[4][$k]) ? (empty($matches[5][$k]) ? '' : $matches[5][$k]) : $matches[4][$k]) : $matches[3][$k];
            }
        }
        return $attributes;
    }
}

?>