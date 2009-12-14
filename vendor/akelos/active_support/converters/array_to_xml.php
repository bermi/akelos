<?php

class AkArrayToXml
{
    public function convert($options) {
        static $_tags = array();
        $header = !isset($options['header']) ? "<?xml version=\"1.0\"?>\r\n" : $options['header'];
        $parent = !isset($options['parent']) ? 'EMPTY_TAG' : $options['parent'];
        $xml = $header;
        foreach ($this->source as $key => $value) {
            $key = is_numeric($key) ? $parent : $key;
            $value = is_array($value) ? "\r\n".Ak::convert('array', 'xml', $this->source, array('header'=>'', 'parent' => $parent)) : $value;
            $_tags[$key] = $key;
            $xml .= sprintf("<%s>%s</%s>\r\n", $key, $value, $key);
            $parent = $key;
        }
        foreach ($_tags as $_tag){
            $xml = str_replace(array("<$_tag>\r\n<$_tag>","</$_tag>\r\n</$_tag>"),array("<$_tag>","</$_tag>"),$xml);
        }
        return $xml;
    }
}

