<?php

class AkLazyObject
{
    private
    $__extenssionPoints = array(),
    $__extenssionPointOptions = array(),
    $__extendedPoints   = array();

    static function extenssionRegistry($ExtendedClass = null, $ProxyClass = null, $register = null)
    {
        static $_extenssion_registry = array();

        if(is_null($ExtendedClass) && is_null($ProxyClass)){
            return $_extenssion_registry;
        }
        $extended_name = is_object($ExtendedClass) ? get_class($ExtendedClass) : $ExtendedClass;
        $proxied_name = is_object($ProxyClass) ? get_class($ProxyClass) : $ProxyClass;

        if($register === true){
            $_extenssion_registry[$extended_name][] = $proxied_name;
            return ;
        }
        if($register === false){
            $_extenssion_registry[$extended_name] = array_diff($_extenssion_registry[$extended_name], array($proxied_name));
            return ;
        }
        if($proxied_name === false){ // unregisterExtenssion
            unset($_extenssion_registry[$extended_name]);
        }elseif(empty($proxied_name)){
            return isset($_extenssion_registry[$extended_name]) ? $_extenssion_registry[$extended_name] : array();
        }elseif (empty($extended_name)){
            foreach ($_extenssion_registry as $extended_name => $proxied_names){
                $result = array();
                if(in_array($proxied_name, $proxied_names)){
                    $result[] = $extended_name;
                }
            }
            return $result;
        }
    }

    public function registerExtenssion($extenssion_name)
    {
        return self::extenssionRegistry($this, $extenssion_name, true);
    }

    public function unregisterExtenssion($extenssion_name)
    {
        return self::extenssionRegistry($this, $extenssion_name, false);
    }

    public function getExtenssionClasses()
    {
        return self::extenssionRegistry($this);
    }

    public function getExtendedClasses()
    {
        return self::extenssionRegistry(null, $this);
    }

    public function isExtending($LazyObject)
    {
        return in_array(is_object($LazyObject) ? get_class($LazyObject) : $LazyObject, $this->getExtendedClasses());
    }

    public function isExtendedBy($Extenssion)
    {
        return in_array(is_object($Extenssion) ? get_class($Extenssion) : $Extenssion, $this->getExtenssionClasses());
    }

    public function extendClass(&$ClassToExtend, $options = array())
    {
        $class_name = get_class($ClassToExtend);
        $this->__extenssionPoints[$class_name] = $ClassToExtend;
        $this->setExtenssionPointOptions($class_name, $options);
        AkLazyObject::registerExtenssion($class_name);
    }

    public function extendClassByName($extended_class_name, $options = array())
    {
        $this->__extenssionPoints[$extended_class_name] = null;
        $this->setExtenssionPointOptions($extended_class_name, $options);
        AkLazyObject::registerExtenssion($extended_class_name);
    }

    public function setExtenssionPointOptions($extenssion_point, $options = array())
    {
        $this->__extenssionPointOptions[$extenssion_point] = $options;
    }

    public function setExtendedBy(&$ExtendedClass)
    {
        $class_name = get_class($ExtendedClass);
        $this->__extendedPoints[$class_name] = $ExtendedClass;
        AkLazyObject::extenssionRegistry($ExtendedClass, $class_name, true);
    }

    public function &getExtendedClassInstance($extended_class_name)
    {
        $class_name = !is_string($extended_class_name) ? get_class($extended_class_name) : $extended_class_name;
        if(array_key_exists($class_name, $this->__extenssionPoints)){
            if(is_null($this->__extenssionPoints[$class_name])){
                $this->instantiateExtendedClass($class_name);
            }
            if(isset($this->__extenssionPoints[$class_name])){
                return $this->__extenssionPoints[$class_name];
            }
        }
        trigger_error('Class '.get_class($this).' has not been extended with '.$extended_class_name, E_USER_ERROR);
    }

    public function &instantiateExtendedClass($class_name)
    {
        $this->__extenssionPoints[$class_name] = new $class_name();

        if(isset($this->__extenssionPointOptions[$class_name]['init_method'])){
            $init_method = $this->__extenssionPointOptions[$class_name]['init_method'];
            if(method_exists($this->__extenssionPoints[$class_name], $init_method)){
                $init_options = isset($this->__extenssionPointOptions[$class_name]['init_options']) ? $this->__extenssionPointOptions[$class_name]['init_options'] : $this;
                $this->__extenssionPoints[$class_name]->$init_method($init_options);
            }else{
                trigger_error('Could not find init method '.$init_method.' for Lazy class '.$class_name, E_USER_ERROR);
            }
        }

        if(method_exists($this->__extenssionPoints[$class_name], 'extendsClass')){
            $this->__extenssionPoints[$class_name]->setExtendedBy($this);
        }
        return $this->__extenssionPoints[$class_name];
    }

    public function extenssionImplements($class_name, $method)
    {
        return
        (!empty($this->__extenssionPointOptions[$class_name]['methods'])) &&
        in_array($method, $this->__extenssionPointOptions[$class_name]['methods']);
    }

    public function __get($name)
    {
        foreach ($this->__extenssionPoints as $ExtenssionPoint){
            if(isset($ExtenssionPoint->$name)){
                $this->$name =& $ExtenssionPoint->$name;
            }
        }
        if(isset($this->$name)){
            return $this->$name;
        }
        trigger_error("Call to undefined attribute ".get_class($this)."::".$name, E_USER_NOTICE);
    }

    public function __call($name, $attributes = array())
    {
        static $handlers = array();

        if(isset($handlers[$name])){

            if(!$this->isExtendedBy($handlers[$name])){
                unset($handlers[$name]);
            }else{
                $extenssion_name = $handlers[$name];
                if(array_key_exists($extenssion_name, $this->__extenssionPoints)){
                    if(is_null($this->__extenssionPoints[$extenssion_name]) && $this->extenssionImplements($extenssion_name, $name)){
                        $this->instantiateExtendedClass($extenssion_name);
                    }
                    if(method_exists($this->__extenssionPoints[$extenssion_name], $name)){
                        return call_user_func_array(array($this->__extenssionPoints[$extenssion_name], $name), $attributes);
                    }
                }
            }
        }
        foreach ($this->__extenssionPoints as $extenssion_name => $ExtenssionPoint){
            if(is_null($ExtenssionPoint) && $this->extenssionImplements($extenssion_name, $name)){
                $ExtenssionPoint = $this->instantiateExtendedClass($extenssion_name);
            }
            if(method_exists($ExtenssionPoint, $name)){
                $handlers[$name] = $extenssion_name;
                return call_user_func_array(array($ExtenssionPoint, $name), $attributes);
            }
        }
        $backtrace = debug_backtrace();
        trigger_error("Fatal error: Call to undefined method ".get_class($this)."::".$name.'() in '.$backtrace[1]['file'].' on line '.$backtrace[1]['line'], E_USER_ERROR);
    }
}