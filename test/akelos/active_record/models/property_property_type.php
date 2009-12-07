<?php 

//This code was generated automatically by the active record hasAndBelongsToMany Method

class PropertyPropertyType extends ActiveRecord {
    public $_avoidTableNameValidation = true;
    public function PropertyPropertyType() {
        $this->setModelName("PropertyPropertyType");
        $attributes = (array)func_get_args();
        $this->setTableName('properties_property_types', true, true);
        $this->init($attributes);
    }
}

?>