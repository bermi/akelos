<?php

class AkStringToArray
{
    public function convert() {
        return Ak::toArray($this->source);
    }
}

