<?php

// +----------------------------------------------------------------------+
// | Akelos PHP Framework - http://www.akelos.org                         |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2007, Akelos Media, S.L.  & Bermi Ferrer Martinez |
// |               2008-2009, Bermi Ferrer Martinez                       |
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
 * @package ActiveSupport
 * @subpackage ImageManipulation
 * @author Bermi Ferrer
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

/**
 * Crop filter
 * 
 * Options are:
 * 'width'
 * 'height'
 * 'x'
 * 'y'
 * 
 * Example:
 * 
 *     $Image = new AkImage('/photo.jpg'); *     
 *     $Image->transform('crop',array('x'=>20, 'y'=>0, 'size'=>'30x30'));
 *     $Image->save('/cropped.jpg');
 */
class AkImageCropFilter extends AkImageFilter
{
    function setOptions($options = array())
    {
        $default_options = array(
        'width'=> $this->Image->getWidth(),
        'height'=> $this->Image->getHeight(),
        'x' => 0,
        'y' => 0
        );
        
        $this->options = array_merge($default_options, $options);
        $this->_setWidthAndHeight_($this->options);
        $this->_variablizeOptions_($this->options);
    }

    function apply()
    {
        $this->Image->Transform->crop($this->options['width'], $this->options['height'], $this->options['x'], $this->options['y']);
    }

    function getName()
    {
        return 'crop';
    }

}

?>