<?php

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
 * @package ActiveSupport
 * @subpackage ImageManipulation
 * @author Bermi Ferrer <bermi a.t bermilabs c.om>
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


    function _setWidthAndHeight_(&$options)
    {
        if(!empty($options['size'])){
            list($options['width'], $options['height']) = split('x|X| ',trim(str_replace(' ','',$options['size'])).'x');
            unset($options['size']);
        }

        if(isset($options['width']) && strstr($options['width'],'%')){
            $options['width'] = $this->_getProportionalWidth($options['width']);
        }
        if(isset($options['height']) && strstr($options['height'],'%')){
            $options['height'] = $this->_getProportionalHeight($options['height']);
        }
    }

}



?>