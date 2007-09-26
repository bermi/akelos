<?php

class Location extends ActiveRecord
{
    var $acts_as = array('nested_set' => array('scope'=>'owner_id = ?'));
}

?>