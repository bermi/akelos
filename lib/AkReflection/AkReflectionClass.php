<?php
require_once(AK_LIB_DIR.DS.'AkReflection.php');
require_once(AK_LIB_DIR.DS.'AkReflection'.DS.'AkReflectionMethod.php');
require_once(AK_LIB_DIR.DS.'AkReflection'.DS.'AkReflectionDocBlock.php');
class AkReflectionClass extends AkReflection
{
    var $_definition;
    var $_docBlock;
    var $methods = array();
    var $properties = array();
    
    
    
    function AkReflectionClass($class_definition)
    {
        if (is_array($class_definition)) {
            if (@$class_definition['type'] == 'class') {
                $this->_definition = $class_definition;
            } else {
                return;
            }
        } else if (is_string($class_definition)) {
            $this->_parse($class_definition);
            foreach ($this->definitions as $def) {
                if ($def['type'] == 'class') {
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
    function toString()
    {
        $docBlock = $this->_docBlock;
        if ($docBlock->changed) {
            $string = $this->_definition['toString'];
            $orgDocBlock = $docBlock->original;
            $string = str_replace($orgDocBlock,$docBlock->toString(),$string);
            return $string;
        } else {
            return isset($this->_definition['toString'])?$this->_definition['toString']:null;
        }
    }
    function setTag($tag,$value)
    {
        $this->_docBlock->setTag($tag,$value);
    }
    function getTag($tag)
    {
        return $this->_docBlock->getTag($tag);
    }
    function getName()
    {
        return isset($this->_definition['name'])?$this->_definition['name']:false;
    }
    function getVisibility()
    {
        return isset($this->_definition['visibility'])?$this->_definition['visibility']:false;
    }
    
    function isStatic()
    {
        return isset($this->_definition['static'])?$this->_definition['static']:false;
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
    function &getMethod($name)
    {
        $false = false;
        foreach($this->methods as $method) {
            if ($method->getName()==$name) return $method;
        }
        return $false;
    }
    function getMethods($options = null)
    {
        if ($options == null) {
            return $this->methods;
        } else if (is_array($options)) {
            $default_options = array();
            $available_options = array('visibility','static','tags','returnByReference');
            $parameters = array('available_options'=>$available_options);
            Ak::parseOptions(&$options,$default_options,$parameters);
            $returnMethods = array();
            foreach ($this->methods as $method) {
                if (isset($options['visibility']) && $method->getVisibility()!=$options['visibility']) {
                    continue;
                }
                if (isset($options['returnByReference']) && $method->returnByReference()!=$options['returnByReference']) {
                    continue;
                }
                if (isset($options['static']) && $method->isStatic()!=$options['static']) {
                    continue;
                }
                if (isset($options['tags'])) {
                    $options['tags']=!is_array($options['tags'])?array($options['tags']):$options['tags'];
                    $docBlock = $method->getDocBlock();
                    $broke = false;
                    foreach($options['tags'] as $tag=>$value) {
                        $res = $docBlock->getTag($tag);

                        if (!@preg_match('/'.$value.'/',$res) || ($value!==false && $res===false)) {
                            $broke = true;
                            break;
                        }
                    }
                    if ($broke) {
                        continue;
                    }
                }
                $returnMethods[] = $method;
                
            }
            //echo "Return methods:\n";
            //var_dump($returnMethods);
            return $returnMethods;
        }
        
    }
}
?>