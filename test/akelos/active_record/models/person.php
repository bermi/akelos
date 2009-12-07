<?php

class Person extends ActiveRecord
{
    public $has_one = 'account';
}

