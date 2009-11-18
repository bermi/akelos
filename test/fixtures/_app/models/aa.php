<?php

class Aa extends ActiveRecord
{
    public $hasMany = array('bbs'=>array('handler_name'=>'babies'));
}

