<?php
class AkNumber extends AkType
{

    var $_default_date_format = 'Y-m-d H:i:s';

    
    function years()
    {
        return $this->year();
    }
    function year()
    {
        return new AkNumber(365*24*60*60*$this->value);
    }
    function months()
    {
        return $this->month();
    }
    function month()
    {
        return new AkNumber(30*24*60*60*$this->value);
    }
    function weeks()
    {
        return $this->week();
    }
    
    function fortnights()
    {
        return $this->fortnight();
    }
    
    function fortnight()
    {
        return new AkNumber(14*24*60*60*$this->value);
    }
    
    function week()
    {
        return new AkNumber(7*24*60*60*$this->value);
    }
    function second()
    {
        return new AkNumber($this->value);
    }
    function ordinalize()
    {
        return AkInflector::ordinalize($this->value);
    }
    function seconds()
    {
        return $this->second();
    }
    function minutes()
    {
        return $this->minute();
    }
    function minute()
    {
        return new AkNumber(60*$this->value);
    }
    function hours()
    {
        return $this->hour();
    }
    function hour()
    {
        return new AkNumber(60*60*$this->value);
    }
    function days()
    {
        return $this->day();
    }
    function day()
    {
        return new AkNumber(24*60*60*$this->value);
    }
    function ago()
    {
        return new AkTime(time()-$this->value);
    }
    function fromNow()
    {
        return new AkTime(time()+$this->value);
    }
    function until($date_string)
    {
        $val = strtotime($date_string)-$this->value;
        return new AkTime($val);
    }
    function since($date_string)
    {
        return new AkTime(strtotime($date_string)+$this->value);
    }
    function toDate()
    {
        return date($this->_default_date_format,$this->value);
    }
    
    
    function bytes()
    {
        return $this->byte();
    }
    function byte()
    {
        return new AkNumber($this->value);
    }
    function kilobytes()
    {
        return $this->kilobyte();
    }
    function kilobyte()
    {
        return new AkNumber(1024*$this->value);
    }
    function megabytes()
    {
        return $this->megabyte();
    }
    function megabyte()
    {
        $val = $this->kilobyte();
        $val = $val->getValue();
        return new AkNumber(1024*$val);
    }
    function gigabytes()
    {
        return $this->gigabyte();
    }
    function gigabyte()
    {
        $val = $this->megabyte();
        $val = $val->getValue();
        return new AkNumber(1024*$val);
    }
    function terabytes()
    {
        return $this->terabyte();
    }
    function terabyte()
    {
        $val = $this->gigabyte();
        $val = $val->getValue();
        return new AkNumber(1024*$val);
    }
    function petabytes()
    {
        return $this->petabyte();
    }
    function petabyte()
    {
        $val = $this->terabyte();
        $val = $val->getValue();
        return new AkNumber(1024*$val);
    }
    function exabytes()
    {
        return $this->exabyte();
    }
    function exabyte()
    {
        $val = $this->exabyte();
        $val = $val->getValue();
        return new AkNumber(1024*$val);
    }
    function now()
    {
        return new AkNumber(time());
    }
    
    function quantify($item)
    {
        if ($this->value==1) {
            return $this->value.' '.Ak::t(AkInflector::singularize($item));
        } else {
            return $this->value.' '.Ak::t(AkInflector::pluralize($item));
        }
    }
    
}
?>