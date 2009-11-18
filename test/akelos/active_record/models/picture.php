<?php

class Picture extends ActiveRecord
{
    public $has_one = array(
    'main_thumbnail' => array('dependent' => true,'class_name' => 'Thumbnail', 'foreign_key'=>'photo_id', 'conditions'=>"owner = 'Picture'", 'order'=>"id DESC"),
    );
    public $belongsTo = array(
    'property',
    'landlord');
}

