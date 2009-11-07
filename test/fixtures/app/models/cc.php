<?php

class Cc extends ActiveRecord
{
    var $hasOne = array('dd' => array('foreign_key'=>'mycc_id'));
    //var $habtm = array('bb');
}

?>