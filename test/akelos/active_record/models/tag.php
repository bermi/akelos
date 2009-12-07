<?php

class Tag extends AkActiveRecord
{
    public $habtm = array(
        'files' => array(
            'join_table' => 'taggings',
            'join_class_name' => 'Tagging'
        ),
        'posts'
    );

    public $has_many = 'taggings';
}

