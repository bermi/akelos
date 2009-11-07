<?php
class Kid extends ActiveRecord
{
    var $hasMany = 'Activities';
    var $belongsTo = 'Father';
}
?>