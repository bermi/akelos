<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

class AkImageFilter
{
    public
    $Image,
    $options = array();

    public function setImage(&$Image) {
        $this->Image = $Image;
    }

    public function &getImage() {
        return $this->Image;
    }

    public function getOptions() {
        return $this->options;
    }

    /**
     * Options for pear ImageTransform are normally in lower camelCase so we need to remap the option keys
     * to adhere to the framework convention of underscored options
     */
    protected function _variablizeOptions_(&$options) {
        foreach ($options as $k=>$v){
            $options[AkInflector::variablize($k)] = $v;
        }
    }

    protected function _setWidthAndHeight_(&$options) {
        if(!empty($options['size'])){
            list($options['width'], $options['height']) = preg_split('/x|X| /',trim(str_replace(' ','',$options['size'])).'x');
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

