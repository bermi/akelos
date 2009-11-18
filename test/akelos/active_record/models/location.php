<?php

class Location extends ActiveRecord
{
    public $acts_as = array('nested_set' => array('scope'=>'owner_id = ?'));
    public $belongs_to = array(
        'group'=>array('dependent'=>'destroy')
        );
}

