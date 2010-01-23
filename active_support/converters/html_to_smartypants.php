<?php

class AkHtmlToSmartypants
{
    public function convert() {
        require_once(AK_CONTRIB_DIR.DS.'TextParsers'.DS.'smartypants.php');
        $Smartypants = new SmartyPantsTypographer_Parser();
        return $Smartypants->transform($this->source);
    }
}

