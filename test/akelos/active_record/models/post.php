<?php

class Post extends ActiveRecord
{
    public $has_many = 'comments';
    public $habtm = 'tags,users';

    public function validate() {
        if ($this->comments_count<0){
            $this->addError('comments_count','can\'t be negative');
        }
    }
}

