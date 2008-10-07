<?php
class AkString extends AkType
{

    function at($pos)
    {
        return @$this->value{$pos};
    }
    
    function from($pos)
    {
        return @substr($this->value,$pos);
    }
    
    function to($pos)
    {
        return @substr($this->value,0,$pos);
    }
    
    function first($number = 1)
    {
        return @substr($this->value,0,$number);
    }
    
    function last($number = 1)
    {
        return @substr($this->value,$number>$this->length()?-$this->length():-$number);
    }
    
    function startsWith($string)
    {
        return $this->first(strlen($string))==$string;
    }
    
    function endsWith($string)
    {
        return $this->last(strlen($string))==$string;
    }
    
    function pluralize($dictionary = null)
    {
        return AkInflector::pluralize($this->value,null,$dictionary);
    }
    
    function singularize($dictionary = null)
    {
        return AkInflector::singularize($this->value,null,$dictionary);
    }
    
    function humanize()
    {
        return AkInflector::humanize($this->value);
    }
    
    function titleize()
    {
        return AkInflector::titleize($this->value);
    }
    
    function tableize()
    {
        return AkInflector::tableize($this->value);
    }
    
    function length()
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($this->value);
        } else {
            return strlen($this->value);
        }
    }
}

?>