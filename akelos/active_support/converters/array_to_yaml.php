<?php

class AkArrayToYaml
{
    public function convert() {
        require_once(AK_CONTRIB_DIR.DS.'TextParsers'.DS.'spyc.php');
        return Spyc::YAMLDump($this->source);
    }
}

