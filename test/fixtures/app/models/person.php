<?php

class Person extends ActiveRecord
{
    var $has_one = 'account';
}

?>