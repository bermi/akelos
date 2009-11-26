<?php

class Comment extends AkActiveRecord
{
    public $belongs_to = 'post';
}

