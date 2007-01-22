<?php

class File extends AkActiveRecord 
{
    var $habtm = array(
        'tags' => array(
            'join_table' => 'taggings',
            'join_class_name' => 'Tagging'
        )
    );
    
    var $has_many = 'taggings';
}

?>