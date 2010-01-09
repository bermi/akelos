<?php

class SegmentDoesNotMatchParameterException extends RouteDoesNotMatchParametersException 
{ }


abstract class AkSegment 
{
    public     $name;
    protected  $delimiter;

    static protected $DEFAULT_REQUIREMENT='[^/.]+';  //default requirement matches all but stops on dashes

    public function __construct($name,$delimiter) {
        $this->name        = $name;
        $this->delimiter   = $delimiter;
    }

    abstract public function isCompulsory();

    public function isOptional() {
        return !$this->isCompulsory();
    }

    public function isOmitable() {
        return false;
    }

    public function __toString() {
        return $this->getRegEx();
    }

    abstract public function getRegEx();
    abstract public function generateUrlFromValue($value,$omit_optional_segments);

}

