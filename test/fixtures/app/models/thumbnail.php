<?php

class Thumbnail extends ActiveRecord
{
    var $belongsTo = array(
    'picture'=>array('primary_key_name'=>'photo_id'),
    'panorama'=>array('primary_key_name'=>'photo_id'),
    );
}

?>