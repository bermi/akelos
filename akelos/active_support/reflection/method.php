<?php

class AkReflectionMethod extends AkReflectionFunction
{
    protected
    $_definition,
    $_docBlock;

    public
    $properties = array();

    public function __construct($method_definition) {
        parent::__construct($method_definition);
    }

    public function getVisibility() {
        return isset($this->_definition['visibility']) ? $this->_definition['visibility'] : false;
    }

    public function isStatic() {
        return isset($this->_definition['static']) ? $this->_definition['static'] : false;
    }
}

