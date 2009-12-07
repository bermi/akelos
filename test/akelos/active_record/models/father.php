<?php

class Father extends ActiveRecord
{
    public $hasMany = 'Kids';
}
