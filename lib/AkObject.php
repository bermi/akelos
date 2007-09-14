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
        static $_callback_called;
        Ak::profile('Instantiating '.get_class($this));
        $args = func_get_args();
        // register_shutdown_function(array(&$this, '__destruct'));
        ____ak_shutdown_function(&$this);
        call_user_func_array(array(&$this, '__construct'), $args);

        if(empty($_callback_called)){
            $_callback_called = true;
            register_shutdown_function('____ak_shutdown_function');
        }
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
        require_once 'Log.php';
        $ident = empty($ident) ? 'main' : $ident;

        $log = Log::singleton('file', AK_LOGS_DIR.DS.$ident.'.log',$ident);
        $log->log($type, $message);
    }

}


function ____ak_shutdown_function($details = false)
{
    static $___registered_objects;
    if(!$details){
        Ak::profile('Calling shutdown destructors');
        foreach (array_keys($___registered_objects) as $k){
            if(!empty($___registered_objects[$k]) && is_object($___registered_objects[$k]) && method_exists($___registered_objects[$k],'__destruct')){
                Ak::profile('Calling destructor for '.get_class($___registered_objects[$k]));
                $___registered_objects[$k]->__destruct();
            }
        }
    }else{
        $___registered_objects[] =& $details;
    }
}

}

?>
