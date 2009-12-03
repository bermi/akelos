<?php

class AkYamlToArray
{
    public function convert()
    {
        include_once AK_CONTRIB_DIR.DS.'TextParsers'.DS.'spyc.php';
        return Spyc::YAMLLoad($this->source);
    }
}

