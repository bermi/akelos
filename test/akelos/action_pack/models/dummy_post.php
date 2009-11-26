<?php

class DummyPost extends AkActiveRecord
{
    public $has_many = 'dummy_comments';
}

