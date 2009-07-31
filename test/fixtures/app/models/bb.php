<?php

class Bb extends ActiveRecord
{
    var $belongsTo = array('aa');
    var $habtm = array('ccs');
    var $serialize = array('languages','other');
}

?>