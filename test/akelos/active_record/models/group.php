<?php

class Group extends ActiveRecord
{
    public $habtm = 'users';
    public $has_many = 'locations';
}

