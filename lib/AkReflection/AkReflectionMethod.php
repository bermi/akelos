<?php
require_once(AK_LIB_DIR.DS.'AkReflection.php');
require_once(AK_LIB_DIR.DS.'AkReflection'.DS.'AkReflectionDocBlock.php');
require_once(AK_LIB_DIR.DS.'AkReflection'.DS.'AkReflectionFunction.php');

class AkReflectionMethod extends AkReflectionFunction
{
    var $_definition;
    var $_docBlock;
    var $properties = array();
    
    
    
    function AkReflectionMethod($method_definition)
    {
        parent::AkReflectionFunction($method_definition);
        
    }
    

    function getVisibility()
    {
        return isset($this->_definition['visibility'])?$this->_definition['visibility']:false;
    }
    
    function isStatic()
    {
        return isset($this->_definition['static'])?$this->_definition['static']:false;
    }
    
   
}
?>