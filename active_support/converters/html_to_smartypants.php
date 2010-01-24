<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

class AkHtmlToSmartypants
{
    public function convert() {
        require_once(AK_CONTRIB_DIR.DS.'TextParsers'.DS.'smartypants.php');
        $Smartypants = new SmartyPantsTypographer_Parser();
        return $Smartypants->transform($this->source);
    }
}

