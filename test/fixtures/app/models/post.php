<?php

class Post extends ActiveRecord
{
    var $has_many = 'comments';
    var $habtm = 'tags';
}

?>