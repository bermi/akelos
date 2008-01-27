<?php

class Post extends ActiveRecord
{
    var $has_many = 'comments';
    var $habtm = 'tags';
    
    function validate()
    {
        if ($this->comments_count<0){
            $this->addError('comments_count','can\'t be negative');
        }
    }
}

?>