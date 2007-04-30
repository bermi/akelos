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
        $this->source = trim(preg_replace("/(>[ \n\t]+<)/",'> <', $this->source));
        return trim($this->detextilize($this->source));
    }

    function detextilize($html)
    {
        $replacements =
        array("  "=>' ' , "<br />" => "\n", "<br>" => "\n",
        "<table>" => "", "</table>" => "", "<tr>" => "", "</tr>" => "|\n", "<td>" => "|",
        "</td>" => "", "<th>" => "|_.", "</th>" => "","\n "=>"\n");

        $html = str_ireplace(array_keys($replacements), array_values($replacements), $html);
        $html = preg_replace('/<img(?!.*\/>)([^>]*)>/Us','<img$1 />',$html);

        $valid_tags = array('p','ol','ul','li','i','b','em','strong','span','a','h[1-6]',
        'u','del','sup','sub','blockquote');

        foreach($valid_tags as $tag){
            $html = preg_replace_callback("/\t*<(".$tag.")\s*([^>]*)>(.*)<\/\\1>/Usi",
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

        $non_block_tags = array(
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

        if(isset($non_block_tags[$tag])) {
            return $non_block_tags[$tag].$this->addAttributes($attributes).$content.$non_block_tags[$tag];
        } elseif($tag == 'blockquote') {
            return 'bq.'.$this->addAttributes($attributes).' '.$content;
        } elseif(in_array($tag,$block_tags)) {
            return $tag.$this->addAttributes($attributes).'. '.$content."\n\n";
        } elseif ($tag == 'a') {
            $attribute = $this->getFilteredAttributes($attributes, array('href', 'title'));
            $result = '"'.$content;
            $result.= (isset($attribute['title'])) ? ' ('.$attribute['title'].')' : '';
            $result.= '":'.$attribute['href'];
            return $result;
        } else {
            return $all;
        }
    }


    function getFilteredAttributes($attributes, $ok)
    {
        $result = '';
        foreach($attributes as $attribute) {
            if(in_array($attribute['name'], $ok)) {
                if(!empty($attribute['attribute'])) {
                    $result[$attribute['name']] = $attribute['attribute'];
                }
            }
        }
        return $result;
    }

    function addAttributes($attributes)
    {
        $result = '';
        foreach($attributes as $attribute){
            $result.= ($attribute['name']=='class') ? '('.$attribute['attribute'].')' : '';
            $result.= ($attribute['name']=='id') ? '['.$attribute['attribute'].']' : '';
            $result.= ($attribute['name']=='style') ? '{'.$attribute['attribute'].'}' : '';
            $result.= ($attribute['name']=='cite') ? ':'.$attribute['attribute'] : '';
        }
        return $result;
    }


    function getAttributesAsArray($attributes)
    {
        $result = array();
        $attribute_name = '';
        $mode = 0;

        while (strlen($attributes) != 0){
            $ok = 0;
            switch ($mode) {
                case 0: // name
                if (preg_match('/^([a-z]+)/i', $attributes, $match)) {
                    $attribute_name = $match[1];
                    $ok = $mode = 1;
                    $attributes = preg_replace('/^[a-z]+/i', '', $attributes);
                }
                break;

                case 1: // =
                if (preg_match('/^\s*=\s*/', $attributes)) {
                    $ok = 1;
                    $mode = 2;
                    $attributes = preg_replace('/^\s*=\s*/', '', $attributes);
                    break;
                }
                if (preg_match('/^\s+/', $attributes)) {
                    $ok = 1;
                    $mode = 0;
                    $result[] = array('name'=>$attribute_name,'whole'=>$attribute_name,'attribute'=>$attribute_name);
                    $attributes = preg_replace('/^\s+/', '', $attributes);
                }
                break;

                case 2: // value
                if (preg_match('/^("[^"]*")(\s+|$)/', $attributes, $match)) {
                    $result[]=array('name' =>$attribute_name,'whole'=>$attribute_name.'='.$match[1],
                    'attribute'=>str_replace('"','',$match[1]));
                    $ok = 1;
                    $mode = 0;
                    $attributes = preg_replace('/^"[^"]*"(\s+|$)/', '', $attributes);
                    break;
                }
                if (preg_match("/^('[^']*')(\s+|$)/", $attributes, $match)) {
                    $result[]=array('name' =>$attribute_name,'whole'=>$attribute_name.'='.$match[1],
                    'attribute'=>str_replace("'",'',$match[1]));
                    $ok = 1;
                    $mode = 0;
                    $attributes = preg_replace("/^'[^']*'(\s+|$)/", '', $attributes);
                    break;
                }
                if (preg_match("/^(\w+)(\s+|$)/", $attributes, $match)) {
                    $result[]=
                    array('name'=>$attribute_name,'whole'=>$attribute_name.'="'.$match[1].'"',
                    'attribute'=>$match[1]);
                    $ok = 1;
                    $mode = 0;
                    $attributes = preg_replace("/^\w+(\s+|$)/", '', $attributes);
                }
                break;
            }
            if ($ok == 0){
                $attributes = preg_replace('/^\S*\s*/', '', $attributes);
                $mode = 0;
            }
        }
        if ($mode == 1) {
            $result[] = array ('name'=>$attribute_name,'whole'=>$attribute_name.'="'.$attribute_name.'"','attribute'=>$attribute_name);
        }

        return $result;
    }
}

?>