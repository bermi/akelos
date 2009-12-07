<?php

class Friend extends AkActiveRecord
{
    public $habtm = array('friends' => array('association_foreign_key' => 'related_id'));
}

