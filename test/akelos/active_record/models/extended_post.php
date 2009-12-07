<?php

class ExtendedPost extends ActiveRecord
{
    public $has_many = 'extended_comments';

    public function validate() {
        if ($this->comments_count<0){
            $this->addError('comments_count','can\'t be negative');
        }
    }
}
