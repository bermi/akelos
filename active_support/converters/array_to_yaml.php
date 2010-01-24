<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

class AkArrayToYaml
{
    public function convert() {
        require_once(AK_CONTRIB_DIR.DS.'TextParsers'.DS.'spyc.php');
        return Spyc::YAMLDump($this->source);
    }
}

