<?php

class Property extends ActiveRecord
{
    //var $_dynamicMethods = array('id','description','landlord_id');
    //var $hasOne = 'panorama';
    //var $belongsTo = 'landlord';
    var $hasMany = array(
    'pictures' => array('dependent' => 'destroy'),
    'panoramas');
    var $hasAndBelongsToMany = array('property_types' => array('uniq'=>true));
    //var $hasOne = 'FeaturedPicture';
}

?>