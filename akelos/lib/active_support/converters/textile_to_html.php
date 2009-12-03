<?php

class AkTextileToHtml
{
    public function convert()
    {
        require_once(AK_LIB_DIR.DS.'action_pack'.DS.'helpers'.DS.'text_helper.php');
        return TextHelper::textilize($this->source);
    }
}

