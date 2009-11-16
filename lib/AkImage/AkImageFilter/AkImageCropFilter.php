<?php

// +----------------------------------------------------------------------+
// | Akelos PHP Framework - http://www.akelos.org                         |
// +----------------------------------------------------------------------+

/**
 * @package ActiveSupport
 * @subpackage ImageManipulation
 * @author Bermi Ferrer
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
    public function setOptions($options = array())
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

    public function apply()
    {
        $this->Image->Transform->crop($this->options['width'], $this->options['height'], $this->options['x'], $this->options['y']);
    }

    public function getName()
    {
        return 'crop';
    }
}

