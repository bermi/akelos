<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2006, Akelos Media, S.L.  & Bermi Ferrer Martinez |
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
 * @package ActionController
 * @subpackage Dispatcher
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2007, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 * @deprecated Please use AkDispatcher on your public/index.php instead
 */


require_once(AK_LIB_DIR.DS.'Ak.php');
require_once(AK_LIB_DIR.DS.'AkObject.php');
require_once(AK_LIB_DIR.DS.'AkActionController.php');
require_once(AK_LIB_DIR.DS.'AkInflector.php');
require_once(AK_LIB_DIR.DS.'AkRequest.php');
require_once(AK_LIB_DIR.DS.'AkResponse.php');
require_once(AK_LIB_DIR.DS.'AkRouter.php');


/**
 * This class provides an interface for dispatching a request
 * to the appropriate controller and action.
 */
class AkDispatcher
{
    var $Request;
    var $Response;
    var $Controller;

    function dispatch()
    {
        $this->Request =& AkRequest();
        $this->Response =& AkResponse();
        $this->Controller =& $this->Request->recognize();
        $this->Controller->process($this->Request, $this->Response);
    }


    /**
     * @todo Implement a mechanism for enabling multiple requests on the same dispatcher
     * this will allow using Akelos as an Application Server using the
     * approach described at http://blog.milkfarmsoft.com/?p=51
     *
     */
    function restoreRequest()
    {
    }
}

?>