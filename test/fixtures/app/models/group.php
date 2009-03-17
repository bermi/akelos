<?php

class Group extends ActiveRecord
{
    var $habtm = 'users';
    var $has_many = 'locations';
}

?>