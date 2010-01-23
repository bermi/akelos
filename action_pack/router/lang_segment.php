<?php

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

