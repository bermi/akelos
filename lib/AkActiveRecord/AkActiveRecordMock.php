<?php
class AkActiveRecordMock
{
    var $_parent,$_class,$_pkValue,$_handler;
    
    function __construct($pk,$class, $handler, &$parent)
    {
        $this->_class = $class;
        $this->_pkValue = $pk;
        $this->_handler = $handler;
        $this->_parent = &$parent;
    }
    
    function getId()
    {
        return $this->_pkValue;
    }
    
    function get($name)
    {
        return $this->$name;
    }
    
    function getAttribute($name)
    {
        return $this->$name;
    }
    function &_getObject($handler = false)
    {   
        
        if (!empty($handler) && !empty($this->_parent)) {
            $object = $this->_parent->_getObject($this->_handler);
            $object = $object->$handler;
            if ($object && in_array($object->getType(),array('hasOne','belongsTo'))) {
                $oclass = $object = &$object->getAssociationOption('class_name');
                $object = new $oclass();
            } else if ($object && in_array($object->getType(),array('hasMany','hasAndBelongsToMany',))) {
                $object =$object->getAssociatedModelInstance();
            }
            
        } else if (!empty($handler) && empty($this->_parent)) {
            $class_name = $this->_class;
            $obj = new $class_name();
            $object = $obj->$handler;
            if (in_array($object->getType(),array('hasOne','belongsTo'))) {
                $oclass = $object = &$object->getAssociationOption('class_name');
                $object = new $oclass();
            } else if ($object && in_array($object->getType(),array('hasMany','hasAndBelongsToMany',))) {
                $object =$object->getAssociatedModelInstance();
            }
        } else {
           
            if (!empty($this->_parent)) {
                $object=&$this->_parent->_getObject($this->_handler);
            }
           
        }
        return $object;
    }
    function _getClass()
    {
        return $this->_class;
    }
    function __call($name, $args = array())
    {
        $obj = &$this->_getObject();
        
        if($obj) {
            
            if (method_exists(&$obj,$name)) {
                $obj->setAttributes(get_object_vars($this));
                return call_user_func_array(array(&$obj,$name),$args);
            }
        }
        
        
    }
}
?>