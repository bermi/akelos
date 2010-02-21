<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

class AkActiveRecordToYaml
{
    public function convert() {
        $attributes = array();
        if(is_array($this->source)){
            foreach (array_keys($this->source) as $k){
                if($this->source[$k] instanceof AkBaseModel){
                    $attributes[$this->source[$k]->getId()] = $this->source[$k]->getAttributes();
                }
            }
        } elseif ($this->source instanceof AkBaseModel){
            $attributes[$this->source->getId()] = $this->source->getAttributes();
        }
        require_once(AK_CONTRIB_DIR.DS.'TextParsers'.DS.'spyc.php');
        return Spyc::YAMLDump($attributes);
    }
}

