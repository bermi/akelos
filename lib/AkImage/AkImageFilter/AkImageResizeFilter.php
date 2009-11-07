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

/**
 * Resizing filter
 *
 * Options are:
 * 'width
 * 'height'
 * 'mode' This setting will define how the image will be resized. Options are:
 *  - "normal" (default) will shrink to the largest side but will not grow the image if it is smaller
 *  - "expand" grows the image to the largest side
 *  - "force" forces the image to an specific size without maintaining the aspect ratio
 */
class AkImageResizeFilter extends AkImageFilter
{
    // Image->path
    // Image->filter_backup->path
    function setOptions($options = array())
    {
        $default_options = array(
        'width'=> $this->Image->getWidth(),
        'height'=> $this->Image->getHeight(),
        'scale_method' => 'smooth',
        'mode' => 'normal'
        );

        $this->_setWidthAndHeight_($options);
        $this->options = array_merge($default_options, $options);
        $this->_recalculateTargetDimenssions();
        $this->_variablizeOptions_($this->options);
    }

    function apply()
    {
        $this->Image->Transform->resize($this->options['width'], $this->options['height'], $this->options);
    }

    function getName()
    {
        return 'resize';
    }

    function _recalculateTargetDimenssions()
    {
        $original_width = $this->Image->getWidth();
        $original_height = $this->Image->getHeight();

        $target_width = empty($this->options['width']) ? $original_width : $this->options['width'];
        $target_height = empty($this->options['height']) ? $original_height : $this->options['height'];

        if($this->options['mode'] == 'normal' && $original_width < $target_width && $original_height < $target_height) {

            $this->options['width'] = $original_width;
            $this->options['height'] = $original_height;
            return true;
        }

        if ($this->options['mode'] != 'force') {

            $original_aspect_ratio = $original_height / $original_width;
            $target_aspect_ratio = $target_height / $target_width;

            if ($this->options['mode'] != 'expand') {
                if ($original_aspect_ratio > $target_aspect_ratio) {
                    $target_width = $original_width / $original_height * $target_height;
                } else {
                    $target_height = $original_height / $original_width * $target_width;
                }
            } else {
                if ($original_aspect_ratio > $target_aspect_ratio) {
                    $target_height = $original_height / $original_width * $target_width;
                } else {
                    $target_width = $original_width / $original_height * $target_height;
                }
            }
        }

        $this->options['width'] = $target_width;
        $this->options['height'] = $target_height;
    }

    function _getProportionalWidth($proportion = '100%')
    {
        return intval($proportion)/100*$this->Image->getWidth();
    }

    function _getProportionalHeight($proportion = '100%')
    {
        return intval($proportion)/100*$this->Image->getHeight();
    }
}

?>