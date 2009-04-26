<?php

class Aa extends ActiveRecord
{
    var $hasMany = array('bbs'=>array('handler_name'=>'babies'));
}

?>