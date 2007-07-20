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
 * @subpackage ImageManipulation
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

class AkImageFilter
{
    var $Image;
    var $options = array();

    
    function setImage(&$Image)
    {
        $this->Image =& $Image;
    }
    
    function &getImage()
    {
        return $this->Image;
    }
    
    function getOptions()
    {
        return $this->options;
    }
    
    /**
     * Options for pear ImageTransform are normally in lower camelCase so we need to remap the option keys
     * to adhere to the framework convention of underscored options
     */
    function _variablizeOptions_(&$options)
    {
        foreach ($options as $k=>$v){
            $options[AkInflector::variablize($k)] = $v;
        }
    }
}



?>