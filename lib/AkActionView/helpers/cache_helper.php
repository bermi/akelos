<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2006, Akelos Media, S.L.  & Bermi Ferrer Martinez |
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
 * @package ActionView
 * @subpackage Helpers
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */


/**
 * Cache Helpers lets you cache fragments of templates
*
* == Caching a block into a fragment
*
*   <b>Hello {name}</b>
*   <?php if (!$cache_helper->begin()) { ?>
*     All the topics in the system:
*     <?= $controller->renderPartial("topic", $Topic->findAll()); ?>
*   <?= $cache_helper->end();} ?>
*  
*
*
*   Normal view text
*/

require_once(AK_LIB_DIR.DS.'AkActionView'.DS.'AkActionViewHelper.php');

class CacheHelper extends AkActionViewHelper 
{
    
    function begin($key = array(), $options = array())
    {
        return $this->_controller->cacheTplFragmentStart($key, $options);
    }

    function end($key = array(), $options = array())
    {
        return $this->_controller->cacheTplFragmentEnd($key, $options);
    }
}

?>
