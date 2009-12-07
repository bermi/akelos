<?php

class Cc extends ActiveRecord
{
    public $hasOne = array('dd' => array('foreign_key'=>'mycc_id'));
}

