<?php

class User extends ActiveRecord
{
    public $habtm = 'groups,posts';
    public $serialize = array('preferences');
}

