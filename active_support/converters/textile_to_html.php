<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

class AkTextileToHtml
{
    public function convert() {
        require_once(AK_ACTION_PACK_DIR.DS.'helpers'.DS.'text_helper.php');
        return AkTextHelper::textilize($this->source);
    }
}

