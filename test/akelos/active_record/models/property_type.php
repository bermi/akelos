<?php

class PropertyType extends ActiveRecord
{
    public $hasAndBelongsToMany = array('properties' => array('unique'=>true));
}

