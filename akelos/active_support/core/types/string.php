<?php

class AkString extends AkType
{
    public function at($pos) {
        return @$this->value{$pos};
    }
    
    public function from($pos) {
        return @substr($this->value,$pos);
    }
    
    public function to($pos) {
        return @substr($this->value,0,$pos);
    }
    
    public function first($number = 1) {
        return @substr($this->value,0,$number);
    }
    
    public function last($number = 1) {
        return @substr($this->value,$number>$this->length()?-$this->length():-$number);
    }
    
    public function startsWith($string) {
        return $this->first(strlen($string))==$string;
    }
    
    public function endsWith($string) {
        return $this->last(strlen($string))==$string;
    }
    
    public function pluralize($dictionary = null) {
        return AkInflector::pluralize($this->value,null,$dictionary);
    }
    
    public function singularize($dictionary = null) {
        return AkInflector::singularize($this->value,null,$dictionary);
    }
    
    public function humanize() {
        return AkInflector::humanize($this->value);
    }
    
    public function titleize() {
        return AkInflector::titleize($this->value);
    }
    
    public function tableize() {
        return AkInflector::tableize($this->value);
    }
    
    public function length() {
        if (function_exists('mb_strlen')) {
            return mb_strlen($this->value);
        } else {
            return strlen($this->value);
        }
    }
}

