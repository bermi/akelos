<?php

class Comment extends ActiveRecord 
{
    var $belongs_to = 'post';
}

?>