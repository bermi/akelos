<?php

class PropertyType extends ActiveRecord
{
    var $hasAndBelongsToMany = array('properties' => array('unique'=>true));
}

?>