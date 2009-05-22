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
        $height_ratio  = $this->Image->getHeight()/$this->options['height'];
        $width_ratio  = $this->Image->getWidth()/$this->options['width'];
        
        //width bigger
        if($this->Image->getWidth() > $this->Image->getHeight()){
            if($width_ratio>1){
                $width  = $this->Image->getWidth()/$height_ratio;
                $height = $this->options['height'];
                $x      = ($width-$this->options['width'])/2;
                $y      = 0;
                
                $this->_resizeAndCrop($width, $height, $x, $y);
            }
        // height bigger
        }else{
            if($height_ratio>1){
                $height = $this->Image->getHeight()/$width_ratio;
                $width  = $this->options['width'];
                $x      = 0;
                $y      = ($height-$this->options['height'])/2;
                
                $this->_resizeAndCrop($width, $height, $x, $y);
            }
        }
    }

    function getName()
    {
        return 'centered_crop';
    }
    
    private function _resizeAndCrop($width, $height, $x, $y)
    {
        $this->Image->Transform->resize($width, $height);
        $this->Image->Transform->crop($this->options['width'], $this->options['height'], $x, $y);
    }
}

?>
