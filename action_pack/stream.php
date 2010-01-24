<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

class AkStream
{
    public $buffer_size;
    public $path;
    
    public function __construct($path, $buffer_size = 4096) {
        $this->buffer_size = empty($buffer_size) ? 4096 : $buffer_size;
        $this->path = $path;
    }

    public function stream() {
        Ak::stream($this->path, $this->buffer_size);
    }    
}

