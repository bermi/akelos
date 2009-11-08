<?php

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
 * @package ActionView
 * @subpackage Helpers
 * @author Bermi Ferrer <bermi a.t bermilabs c.om>
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
