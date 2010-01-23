<?php

class File extends AkActiveRecord
{
    public $has_many = array('methods');
    public $belongs_to = array('component', 'category');

    public function validate()
    {
        $this->validatesUniquenessOf('path');
    }
}

