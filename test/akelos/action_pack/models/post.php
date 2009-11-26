<?php

class Post extends AkActiveRecord
{
    public $has_many = 'comments';
}

