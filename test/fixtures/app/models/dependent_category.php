<?php
    class DependentCategory extends ActiveRecord 
    {
        var $act_as = array("tree" => array("dependent" => true));
        var $table_name = "categories";
    } 
?>