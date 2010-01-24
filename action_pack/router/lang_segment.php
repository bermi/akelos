<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

class AkLangSegment extends AkVariableSegment 
{
    public function __construct($name,$delimiter,$default=null,$requirement=null) {
        if (!$requirement){
            $requirement = '('.join('|',$this->availableLocales()).')';  
        }
        parent::__construct($name,$delimiter,$default,$requirement);
    }

    public function isOmitable() {
        return true;
    }

    private function availableLocales() {
        return Ak::langs();
    }

}

