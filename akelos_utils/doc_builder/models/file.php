<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

class File extends AkActiveRecord
{
    public $has_many = array('methods');
    public $belongs_to = array('component', 'category');

    public function validate()
    {
        $this->validatesUniquenessOf('path');
    }
}

