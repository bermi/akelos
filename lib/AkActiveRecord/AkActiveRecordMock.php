<?php
class AkActiveRecordMockHandler
{
    public $_parent, $_association_id;
    public function __construct(&$parent, $association_id)
    {
        $this->_parent = $parent;
        $this->_association_id = $association_id;
    }
    public function load()
    {
        if (!empty($this->_parent)) {
            $assoc = $this->_association_id;
            return $this->_parent->$assoc;
        }
        return false;
    }
    
    public function __call($name, $args)
    {
        $handler = &$this->_parent->_getHandlerForAssociation($this->_association_id);
        return call_user_func_array(array($handler, $name),$args);
    }
}
class AkActiveRecordMock
{
    public $_parent,$_class,$_pkValue,$_handler;
    public $load_acts = false;
    public $load_associations = true;
    public $__associations=array();
    public $__handlers = array();
    public $_dummy_instance;
    public function __construct($pk,$class, $handler, &$parent)
    {
        $this->_class = $class;
        $this->_pkValue = $pk;
        $this->_handler = $handler;
        $this->_parent = &$parent;
    }
    
    public function getId()
    {
        return $this->_pkValue;
    }
    public function isCallable($method)
    {
        return is_callable(array($this->_class,$method));
    }
    public function get($name)
    {
        return isset($this->$name)?$this->$name:null;
    }
    
    public function getAttribute($name)
    {
        return $this->get($name);
    }
    public function &_getHandlerForAssociation($association_id)
    {
        $false = false;
        if (isset($this->__handlers[$association_id])) {
            $class = $this->_class;
            $obj=&$this->_getObject();
            $handler_name = $this->__handlers[$association_id];
            $myobj  = new $class();
            if (isset($myobj->$handler_name)) {
                $handler = $myobj->$handler_name;
                $handler->Owner = &$obj;
                $obj->$handler_name = &$handler;
                $obj->$handler_name->_loaded=true;
                return $obj->$handler_name;
            }
        } else {
            $class = $this->_class;
            $obj=&$this->_getObject();
            $handler_name = $obj->getCollectionHandlerName($association_id);
            if(!$handler_name) {
                $handler_name = $association_id;
            }
            $myobj  = new $class();
            if (isset($myobj->$handler_name)) {
                $handler = &$myobj->$handler_name;
                $handler->Owner = &$obj;
                $obj->$handler_name = &$handler;
                return $obj->$handler_name;
            }
        }
        return $false;
    }
    public function _getAssociationId($handler_name)
    {
        return isset($this->__associations[$handler_name])?$this->__associations[$handler_name]:false;
    }
    public function load()
    {
        if (!empty($this->_parent)) {
            $assoc = $this->_parent->_getAssociationId($this->_handler);
            return $this->_parent->$assoc;
        }
        return false;
    }
    public function _addAssociation($association_id, $handler_name)
    {
        //Ak::getLogger()->message('addAssociation on '.$this->_getClass().' with association_id:'.$association_id.' handler_name:'.$handler_name);
        if ($association_id != $handler_name) {
            $this->$handler_name = &new AkActiveRecordMockHandler($this,$association_id);
        }
        if(is_object($this->$handler_name)) {
            $this->$handler_name->_loaded=true;
            $this->__associations[$handler_name] = $association_id;
            $this->__handlers[$association_id] = $handler_name;
        }
    }
    public function &_getObject()
    {
        static $obj;
        
        if (!empty($obj)) return $obj;
        $class = $this->_class;
        $object_vars = get_object_vars($this);
        $attributes = array();
        $associations = array();
        foreach($object_vars as $key => $value) {
            if (!($is_association=in_array($key, $this->__associations)) && is_scalar($value)) {
                $attributes[$key]=$value;
            } else if ($is_association) {
                $associations[]=$key;
            }
        }
   
        $obj =& new $class('attributes', $attributes);
        
        $obj->_newRecord = false;
        foreach($associations as $assoc) {
            
            $handler_name = $this->__handlers[$assoc];
            $obj->$handler_name = new AkActiveRecordMockHandler($this, $assoc);
            $obj->$assoc = &$this->$assoc;
        }
        return $obj;
     }

    
    public function _getClass()
    {
        return $this->_class;
    }
    
    public function __call($name, $args = array())
    {
        $obj = &$this->_getObject();
        if($obj) {
            //Ak::getLogger()->message('calling '.$name.' on '.$this->_getClass());
            if (method_exists(&$obj,$name)) {
                return call_user_func_array(array(&$obj,$name),$args);
            }
        }
        
        
    }
}
?>