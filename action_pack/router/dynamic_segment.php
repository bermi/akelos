<?php

abstract class AkDynamicSegment extends AkSegment
{
    
    public     $default;
    protected  $requirement;

    public function __construct($name,$delimiter,$default=null,$requirement=null) {
        parent::__construct($name,$delimiter);
        $this->default     = $default;
        $this->requirement = $requirement;
    }

    public function isCompulsory() {
        return $this->default === COMPULSORY;
    }

    public function hasRequirement() {
        return $this->requirement ? true : false;
    }

    protected function getInnerRegEx() {
        if ($this->hasRequirement()) return $this->requirement;
        return self::$DEFAULT_REQUIREMENT;
    }

    /**
     * @param mixed $value                  the value we urlize         
     * @param bool $omit_optional_segments  true if optional segments should be supressed
     * @return string|false                 return false if url_part should or can be supressed
     *                                      otherwise return the url_part as a string
     */
    public function generateUrlFromValue($value,$omit_optional_segments) {
        if (is_null($value)){
            if ($this->isCompulsory()) throw new SegmentDoesNotMatchParameterException("Segment {$this->name} is compulsory, but was not set.");
            if ($omit_optional_segments || $this->isOmitable()) return false;
            throw new SegmentDoesNotMatchParameterException("Segment {$this->name} must be set.");
        }else{
            if ($this->default == $value && $omit_optional_segments) return false;
            
            if (!$this->fulfillsRequirement($value)) throw new SegmentDoesNotMatchParameterException("Value {$value} does not fulfill the requirements of {$this->name}.");
            return $this->generateUrlFor($value);
        }
    }

    abstract protected function fulfillsRequirement($value);
    abstract protected function generateUrlFor($value);

    abstract public function extractValueFromUrl($url_part);

}

