<?php

class AkXmlToArray
{
    public function convert()
    {
        $xml_parser = xml_parser_create ();
        xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($xml_parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parse_into_struct ($xml_parser, $this->source, $vals, $index);
        xml_parser_free ($xml_parser);
        $params = array();
        $ptrs[0] = & $params;
        foreach ($vals as $xml_elem) {
            $level = $xml_elem['level'] - 1;
            switch ($xml_elem['type']) {
                case 'open':
                    $tag_or_id = (array_key_exists ('attributes', $xml_elem)) ? @$xml_elem['attributes']['ID'] : $xml_elem['tag'];
                    $ptrs[$level][$tag_or_id][] = array ();
                    $ptrs[$level+1] = & $ptrs[$level][$tag_or_id][count($ptrs[$level][$tag_or_id])-1];
                    break;
                case 'complete':
                    $ptrs[$level][$xml_elem['tag']] = (isset ($xml_elem['value'])) ? $xml_elem['value'] : '';
                    break;
            }
        }
        return ($params);
    }
}

