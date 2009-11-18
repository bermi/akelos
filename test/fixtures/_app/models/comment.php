<?php

class Comment extends ActiveRecord 
{
    public $belongs_to = 'post';
}

?>