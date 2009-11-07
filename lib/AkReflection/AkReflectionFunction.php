<?php
require_once(AK_LIB_DIR.DS.'AkReflection.php');
require_once(AK_LIB_DIR.DS.'AkReflection'.DS.'AkReflectionDocBlock.php');

class AkReflectionFunction extends AkReflection
{
    var $_definition;
    var $_docBlock;
    var $methods = array();
    var $properties = array();
    
    
    
    function AkReflectionFunction($method_definition)
    {
        if (is_array($method_definition)) {
            if (@$method_definition['type'] == 'function') {
                $this->_definition = $method_definition;
            } else {
                return;
            }
        } else if (is_string($method_definition)) {
            $this->_parse($method_definition);
            foreach ($this->definitions as $def) {
                if ($def['type'] == 'function') {
                    $this->_definition = $def;
                    break;
                }
            }
            $this->definitions = array();
            $this->tokens = array();
        } else {
            return;
        }
        $this->_docBlock = &new AkReflectionDocBlock($this->_definition['docBlock']);
        $this->_parse($this->_definition['code']);
        $this->_parseDefinitions();
        
    }
    
    
    function getDefaultOptions()
    {
        return isset($this->_definition['default_options'])?$this->_definition['default_options']:false;
    }
    
    function getAvailableOptions()
    {
        return isset($this->_definition['available_options'])?$this->_definition['available_options']:false;
    }
    function getName()
    {
        return isset($this->_definition['name'])?$this->_definition['name']:false;
    }
    function setTag($tag,$value)
    {
        if (!is_object($this->_docBlock)) {
            $this->_docBlock = new AkReflectionDocBlock('');
        }
        $this->_docBlock->setTag($tag,$value);
    }
    function getTag($tag)
    {
        return $this->_docBlock->getTag($tag);
    }
    function getParams()
    {
        return isset($this->_definition['params'])?$this->_definition['params']:false;
    }
    function toString($indent=0,$methodName = null)
    {
        $docBlock = &$this->_docBlock;
        if ($docBlock->changed) {
            $string = $this->_definition['toString'];
            $orgDocBlock = trim($docBlock->original);
            if (!empty($orgDocBlock)) {
                $string = str_replace($orgDocBlock,$docBlock->toString(),$string);
            } else {
                $string = $docBlock->toString()."\n".$string;
            }
        } else {
            $string=isset($this->_definition['toString'])?$this->_definition['toString']:null;
        }
        if ($indent>0) {
            $lines = split("\n",$string);
            foreach ($lines as $idx=>$line) {
                $lines[$idx] = str_repeat(' ',$indent).$line;
            }
            $string = implode("\n",$lines);
        } 
        if ($methodName!=null) {
            $string = preg_replace('/function(.*?)('.$this->getName().')(.*?)\(/','function\\1'.$methodName.'\\3(',$string);
        }
        return $string;
        
    }
    function returnByReference()
    {
        return isset($this->_definition['returnByReference'])?$this->_definition['returnByReference']:false;
    }
    
    function &getDocBlock()
    {
        return $this->_docBlock;
    }
    function _parseDefinitions()
    {
        foreach($this->definitions as $definition) {
            switch ($definition['type']) {
                case 'function':
                    $this->methods[] = new AkReflectionMethod($definition);
                    break;
            }
        }
    }
    

}
?>