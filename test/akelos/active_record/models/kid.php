<?php

class Kid extends ActiveRecord
{
    public $hasMany = 'Activities';
    public $belongsTo = 'Father';
}
