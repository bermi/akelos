<?php

class ExtendedPost extends ActiveRecord
{
    var $has_many = 'extended_comments';
    
    function validate()
    {
        if ($this->comments_count<0){
            $this->addError('comments_count','can\'t be negative');
        }
    }
}

?>