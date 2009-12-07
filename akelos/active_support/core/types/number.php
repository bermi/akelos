<?php

class AkNumber extends AkType
{
    public $_default_date_format = 'Y-m-d H:i:s';

    public function years() {
        return $this->year();
    }
    
    public function year() {
        return new AkNumber(365*24*60*60*$this->value);
    }
    
    public function months() {
        return $this->month();
    }
    
    public function month() {
        return new AkNumber(30*24*60*60*$this->value);
    }
    
    public function weeks() {
        return $this->week();
    }
    
    public function fortnights() {
        return $this->fortnight();
    }
    
    public function fortnight() {
        return new AkNumber(14*24*60*60*$this->value);
    }
    
    public function week() {
        return new AkNumber(7*24*60*60*$this->value);
    }
    
    public function second() {
        return new AkNumber($this->value);
    }
    
    public function ordinalize() {
        return AkInflector::ordinalize($this->value);
    }
    
    public function seconds() {
        return $this->second();
    }
    
    public function minutes() {
        return $this->minute();
    }
    
    public function minute() {
        return new AkNumber(60*$this->value);
    }
    
    public function hours() {
        return $this->hour();
    }
    
    public function hour() {
        return new AkNumber(60*60*$this->value);
    }
    
    public function days() {
        return $this->day();
    }
    
    public function day() {
        return new AkNumber(24*60*60*$this->value);
    }
    
    public function ago() {
        return new AkTime(time()-$this->value);
    }
    
    public function fromNow() {
        return new AkTime(time()+$this->value);
    }
    
    public function until($date_string) {
        $val = strtotime($date_string)-$this->value;
        return new AkTime($val);
    }
    
    public function since($date_string) {
        return new AkTime(strtotime($date_string)+$this->value);
    }
    
    public function toDate() {
        return date($this->_default_date_format,$this->value);
    }
        
    public function bytes() {
        return $this->byte();
    }
    
    public function byte() {
        return new AkNumber($this->value);
    }
    
    public function kilobytes() {
        return $this->kilobyte();
    }
    public function kilobyte() {
        return new AkNumber(1024*$this->value);
    }
    public function megabytes() {
        return $this->megabyte();
    }
    
    public function megabyte() {
        $val = $this->kilobyte();
        $val = $val->getValue();
        return new AkNumber(1024*$val);
    }
    
    public function gigabytes() {
        return $this->gigabyte();
    }
    
    public function gigabyte() {
        $val = $this->megabyte();
        $val = $val->getValue();
        return new AkNumber(1024*$val);
    }
    public function terabytes() {
        return $this->terabyte();
    }
    
    public function terabyte() {
        $val = $this->gigabyte();
        $val = $val->getValue();
        return new AkNumber(1024*$val);
    }
    
    public function petabytes() {
        return $this->petabyte();
    }
    public function petabyte() {
        $val = $this->terabyte();
        $val = $val->getValue();
        return new AkNumber(1024*$val);
    }
    
    public function exabytes() {
        return $this->exabyte();
    }
    
    public function exabyte() {
        $val = $this->exabyte();
        $val = $val->getValue();
        return new AkNumber(1024*$val);
    }
    
    public function now() {
        return new AkNumber(time());
    }
    
    public function quantify($item) {
        if ($this->value==1) {
            return $this->value.' '.Ak::t(AkInflector::singularize($item));
        } else {
            return $this->value.' '.Ak::t(AkInflector::pluralize($item));
        }
    }
}

