<?php

class Tagging extends AkActiveRecord
{
    public $belongs_to = array('file', 'tag');

}

