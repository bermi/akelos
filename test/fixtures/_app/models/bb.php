<?php

class Bb extends ActiveRecord
{
    public $belongsTo = array('aa');
    public $habtm = array('ccs');
    public $serialize = array('languages','other');
}

