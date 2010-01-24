<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

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

