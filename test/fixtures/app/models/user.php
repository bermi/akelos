<?php

class User extends ActiveRecord
{
    var $habtm = 'groups,posts';
    var $serialize = array('preferences');
}

?>