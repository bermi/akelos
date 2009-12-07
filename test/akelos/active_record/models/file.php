<?php

class File extends AkActiveRecord
{
    public $habtm = array(
        'tags' => array(
            'join_table' => 'taggings',
            'join_class_name' => 'Tagging'
        )
    );

    public $has_many = 'taggings';
}

