<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

class AkVariableSegment extends AkDynamicSegment
{
    public function getRegEx() {
        $optional_switch = $this->isOptional() ? '?': '';
        return "(?:[$this->delimiter](?P<$this->name>{$this->getInnerRegEx()}))$optional_switch";
    }

    public function extractValueFromUrl($url_part) {
        return $url_part;
    }

    protected function generateUrlFor($value) {
        return $this->delimiter.$value;
    }

    protected function fulfillsRequirement($value) {
        if (!$this->hasRequirement()) return true;
        
        $regex = "@^{$this->getInnerRegEx()}$@";
        return (bool) preg_match($regex,$value);
    }
}

