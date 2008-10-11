<?php

class Friend extends AkActiveRecord
{
    var $habtm = array('friends' => array('association_foreign_key' => 'related_id'));
}

?>