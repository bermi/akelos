<?php

class AkStaticSegment extends AkSegment
{
    public function isCompulsory() {
        return true;
    }

    public function getRegEx() {
        return preg_quote($this->delimiter,'@').$this->name;    
    }

    public function generateUrlFromValue($value,$omit_optional_segments) {
        return $this->delimiter.$this->name;    
    }
}

