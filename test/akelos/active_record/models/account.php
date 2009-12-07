<?php

class Account extends ActiveRecord
{
    public $belongs_to = 'person';
}

