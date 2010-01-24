<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

class AkWildcardSegment extends AkDynamicSegment
{
    public function isCompulsory() {
        return $this->default === COMPULSORY || $this->expectsExactSize();    
    }

    public function getRegEx() {
        $optional_switch = $this->isOptional() ? '?': '';
        $multiplier = ($size = $this->expectsExactSize()) ? '{'. $size .'}' : '+';
        return "(?P<$this->name>(?:$this->delimiter{$this->getInnerRegEx()})$multiplier)$optional_switch";
    }

    public function extractValueFromUrl($url_part) {
        $url_part = substr($url_part,1); // the first char is the delimiter
        return explode('/',$url_part);
    }

    private function expectsExactSize() {
        return is_int($this->default) ? $this->default : false;
    }

    protected function generateUrlFor($value) {
        return $this->delimiter.join('/',$value);
    }

    protected function fulfillsRequirement($values) {
        if (!$this->hasRequirement()) return true;
        if (($size = $this->expectsExactSize()) && count($values) != $size) return false;
        $regex = "@^{$this->getInnerRegEx()}$@";
        foreach ($values as $value){
            if (!(bool) preg_match($regex,$value)) return false;
        }
        return true;
    }
}
