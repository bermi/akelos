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
 * CenteredCrop filter
 *
 * Options are:
 * 'width'
 * 'height'
 *
 * Example:
 *
 *     $Image = new AkImage('/photo.jpg'); *
 *     $Image->transform('centered_crop',array('size'=>'105x105'));
 *     $Image->save('/cropped.jpg');
 */
class AkImageCenteredCropFilter extends AkImageFilter
{
    function setOptions($options = array())
    {
        $default_options = array(
        'width'=> $this->Image->getWidth(),
        'height'=> $this->Image->getHeight()
        );

        $this->options = array_merge($default_options, $options);
        $this->_setWidthAndHeight_($this->options);
        $this->_variablizeOptions_($this->options);
    }

    function apply()
    {
        //width bigger
        if($this->Image->getWidth() > $this->Image->getHeight()){
            $ratio  = $this->Image->getHeight()/$this->options['height'];
            $width  = $this->Image->getWidth()/$ratio;
            $height = $this->options['height'];
            $x      = ($width-$this->options['width'])/2;
            $y      = 0;
        // height bigger
        }else{
            $ratio  = $this->Image->getWidth()/$this->options['width'];
            $height = $this->Image->getHeight()/$ratio;
            $width  = $this->options['width'];
            $x      = 0;
            $y      = ($height-$this->options['height'])/2;
        }
        $this->Image->Transform->resize($width, $height);
        $this->Image->Transform->crop($this->options['width'], $this->options['height'], $x, $y);
    }

    function getName()
    {
        return 'centered_crop';
    }

}

?>