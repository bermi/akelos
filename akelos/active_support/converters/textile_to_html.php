<?php

class AkTextileToHtml
{
    public function convert() {
        require_once(AK_ACTION_PACK_DIR.DS.'helpers'.DS.'text_helper.php');
        return TextHelper::textilize($this->source);
    }
}

