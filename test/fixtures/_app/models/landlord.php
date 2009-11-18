<?php

class Landlord extends ActiveRecord
{
    // var $hasMany = 'properties';
    public $hasOne = 'picture';

    public function validateOnCreate()
    {
        if(!empty($this->test_validators)){
            foreach ($this->test_validators as $validator=>$args) {
                call_user_func_array(array(&$this,$validator),$args);
            }
        }
    }
}

?>