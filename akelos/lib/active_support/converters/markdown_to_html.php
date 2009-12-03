<?php

class AkMarkdownToHtml
{
    public function convert()
    {
        return $this->source = preg_replace("/([ \n\t]+)/",' ', TextHelper::markdown($this->source));
    }
}

