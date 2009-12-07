<?php

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

