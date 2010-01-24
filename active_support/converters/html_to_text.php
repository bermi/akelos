<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

class AkHtmlToText
{
    public function convert() {
        require_once(AK_CONTRIB_DIR.DS.'TextParsers'.DS.'html2text.php');
        $Converter = new html2text(true, 0, false);
        $markdown = str_replace('__AK:AMP__','&', $Converter->load_string(str_replace('&','__AK:AMP__', $this->source)));

        require_once(AK_CONTRIB_DIR.DS.'TextParsers'.DS.'smartypants.php');
        $Smartypants = new SmartyPantsTypographer_Parser();
        $markdown = Ak::html_entity_decode(strip_tags($Smartypants->transform($markdown)));

        return str_replace(" \n", "\n",trim($this->_simplifyMarkdown($markdown)));
    }

    public function _simplifyMarkdown($markdown) {
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

