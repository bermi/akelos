<?php

class Panorama extends ActiveRecord
{
    var $has_one = array('thumbnail' => array('dependent' => true,'class_name' => 'Thumbnail', 'foreign_key'=>'photo_id','condition'=>"owner = 'Panorama'"));
    var $belongsTo = 'property';

    function beforeSave()
    {
        if($this->thumbnail->getType() == 'Thumbnail'){
            $this->thumbnail->owner = 'Panorama';
        }
        return true;
    }
}

?>