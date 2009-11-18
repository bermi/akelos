<?php 

//This code was generated automatically by the active record hasAndBelongsToMany Method

class PostUser extends ActiveRecord {
    public $_avoidTableNameValidation = true;
    public function PostUser()
    {
        $this->setModelName("PostUser");
        $attributes = (array)func_get_args();
        $this->setTableName('posts_users', true, true);
        $this->init($attributes);
    }
}

?>