<?php

class Tag extends AkActiveRecord 
{
    var $habtm = array(
        'files' => array(
            'join_table' => 'taggings',
            'join_class_name' => 'Tagging'
        )
    );
    
    var $has_many = 'taggings';
}

?>