<?php

if(!class_exists('AkObject')){

    class AkObject
    {
        /**
        * Object-to-string conversion
        *
        * Each class can override it as necessary
        *
        * @access public
        * @return string in this case returns this class name
        */
        public function toString()
        {
            return get_class($this);
        }

        public function __construct()
        {
        }

        public function __destruct()
        {
            unset($this);
        }

        /**
        * Clone class (Zend Engine 2 compatibility trick)
        */
        public function __clone()
        {
            return $this;
        }

        public function __toString()
        {
            return $this->toString();
        }

        public function __call($name, $args)
        {
            $backtrace = debug_backtrace();
            $file = $backtrace[1]['file'];
            $line = $backtrace[1]['line'];
            trigger_error(Ak::t('Call to undefined method %class::%method()', array('%method' => $name, '%class'=>get_class($this))).' '.Ak::t('Called in %file line %line', array('%file' => $file, '%line' => $line)), E_USER_ERROR);
        }

        public function log($message, $type = '', $identifyer = '')
        {
            if (AK_LOG_EVENTS){
                $Logger =& Ak::getLogger();
                $Logger->log($message, $type);
            }
        }

        /**
        * Unsets circular reference children that are not freed from memory
        * when calling unset() or when the parent object is garbage collected.
        *
        * @see http://paul-m-jones.com/?p=262
        * @see http://bugs.php.net/bug.php?id=33595
        */
        public function freeMemory()
        {
            // We can't use get_class_vars as it does not include runtime assigned attributes
            foreach (array_keys((array)$this) as $attribute){
                if(isset($this->$attribute)){
                    unset($this->$attribute);
                }
            }
        }

    }
}

