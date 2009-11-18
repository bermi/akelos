<?php 

//This code was generated automatically by the active record hasAndBelongsToMany Method

class PostTag extends ActiveRecord {
    public $_avoidTableNameValidation = true;
    public function PostTag()
    {
        $this->setModelName("PostTag");
        $attributes = (array)func_get_args();
        $this->setTableName('posts_tags', true, true);
        $this->init($attributes);
    }
}

?>