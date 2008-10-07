<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2006, Akelos Media, S.L.  & Bermi Ferrer Martinez |
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
 * @package ActiveSupport
 * @subpackage Compatibility
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

if(!class_exists('AkObject')){ 

/**
* Allows for __construct and __destruct to be used in PHP4.
*
* A hack to support __construct() on PHP 4
* Hint: descendant classes have no PHP4 class_name()
* constructors, so this one gets called first and calls the
* top-layer __construct() which (if present) should call
* parent::__construct()
*
* @author Bermi Ferrer <bermi a.t akelos c.om>
* @copyright Copyright (c) 2002-2005, Akelos Media, S.L. http://www.akelos.org
* @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
*/
class AkObject
{


    // ------ CLASS METHODS ------ //




    // ---- Public methods ---- //


    // {{{ AkObject()

    /**
    * A hack to support __construct() on PHP 4
    *
    * Hint: descendant classes have no PHP4 class_name()
    * constructors, so this one gets called first and calls the
    * top-layer __construct() which (if present) should call
    * parent::__construct()
    *
    * @access public
    * @return void
    */
    function AkObject()
    {
        Ak::profile('Instantiating '.get_class($this));
        $args = func_get_args();
        ____ak_shutdown_function(&$this);
        call_user_func_array(array(&$this, '__construct'), $args);
        ____ak_shutdown_function(true);
        
    }
    
    // }}}
    
    
    // {{{ toString()

    /**
    * Object-to-string conversion
    *
    * Each class can override it as necessary
    *
    * @access public
    * @return string in this case returns this class name
    */
    function toString()
    {
        return get_class($this);
    }
    
    function __toString()
    {
        return $this->toString();
    }

    // }}}


    // ---- Protected methods ---- //


    // {{{ __construct()

    /**
    * Class constructor, overriden in descendant classes
    *
    * @access protected
    * @return void
    */
    function __construct()
    {

    }

    // }}}
    // {{{ __destruct()

    /**
    * Class destructor, overriden in descendant classes
    *
    * @access protected
    * @return void
    */
    function __destruct()
    {
        unset($this);
    }


    // }}}

    // {{{ __clone()

    /**
    * Clone class (Zend Engine 2 compatibility trick)
    */
    function __clone()
    {
        return $this;
    }

    // }}}

    function log($message, $type = '', $identifyer = '')
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
    function freeMemory()
    {
        // We can't use get_class_vars as it does not include runtime assigned attributes
        foreach (array_keys((array)$this) as $attribute){
            if(isset($this->$attribute)){
                unset($this->$attribute);
            }
        }
    }

}

function ____ak_shutdown_function($details = false)
{
    static $_registered = false;
    static $___registered_objects = array();
    if($details === false){
        Ak::profile('Calling shutdown destructors');
        foreach (array_keys($___registered_objects) as $k){
            if(!empty($___registered_objects[$k]) && is_object($___registered_objects[$k]) && method_exists($___registered_objects[$k],'__destruct')){
                Ak::profile('Calling destructor for '.get_class($___registered_objects[$k]));
                $___registered_objects[$k]->__destruct();
            }
        }
    } else if ($details === true && $_registered === false) {
        register_shutdown_function('____ak_shutdown_function');
        $_registered = true;
    } else {
        $___registered_objects[] =& $details;
    }
}

}

?>
