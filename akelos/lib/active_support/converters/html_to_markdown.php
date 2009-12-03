<?php

class AkHtmlToMarkdown
{
    public function convert()
    {
        require_once(AK_CONTRIB_DIR.DS.'TextParsers'.DS.'html2text.php');
        $Converter = new html2text(true, 0, false);
        return $Converter->load_string($this->source);
    }
}

