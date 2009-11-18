<?php

class DependentCategory extends ActiveRecord
{
    public $act_as = array("tree" => array("dependent" => true));
    public $table_name = "categories";
}

