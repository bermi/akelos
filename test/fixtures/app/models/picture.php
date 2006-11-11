<?php

class Picture extends ActiveRecord
{
    var $has_one = array(
    'main_thumbnail' => array('dependent' => true,'class_name' => 'Thumbnail', 'foreign_key'=>'photo_id', 'conditions'=>"owner = 'Picture'", 'order'=>"id DESC"),
    );
    var $belongsTo = array(
    'property'=>array('dependent'=>'destroy'),
    'landlord');
}

?>