<?php

class Property extends ActiveRecord
{
    public $hasMany = array(
    'pictures' => array('dependent' => 'destroy'),
    'panoramas');
    public $hasAndBelongsToMany = array('property_types' => array('unique'=>true)); // Unique still needs to be implemented
}

?>