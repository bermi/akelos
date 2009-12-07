<?php

class Panorama extends ActiveRecord
{
    public $has_one = array('thumbnail' => array('dependent' => true,'class_name' => 'Thumbnail', 'foreign_key'=>'photo_id','condition'=>"owner = 'Panorama'"));
    public $belongsTo = 'property';

    public function beforeSave() {
        if($this->thumbnail->getType() == 'Thumbnail'){
            $this->thumbnail->owner = 'Panorama';
        }
        return true;
    }
}

