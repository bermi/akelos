<?php

class AkReflectionDocBlock
{
    
    var $_structure = array();
    var $changed = false;
    var $original='';
    function AkReflectionDocBlock($string)
    {
        $this->original = $string;
        $this->_structure = $this->_parseDocBlock($string);
    }
    
    function getComment()
    {
        return isset($this->_structure['comment'])?$this->_structure['comment']:false;
    }
    function getParams()
    {
        return isset($this->_structure['tags']['params'])?$this->_structure['tags']['params']:false;
    }
    function toString()
    {
        $string = '/**';
        isset($this->_structure['comment'])?$string.="\n * ".$this->_structure['comment']."\n *":null;
        $tags = $this->_structure['tags'];
        $params = isset($tags['params'])?$tags['params']:array();
        unset($tags['_unmatched_']);
        unset($tags['params']);
        if (count($tags)>0) {
            foreach ($tags as $key=>$value) {
                $string .= "\n * @$key $value";
            }
        }
        if (count($params)>0) {
            foreach ($params as $key=>$value) {
                $string .= "\n * @param \$$key $value";
            }
        }
        $string.="\n */";
        return $string;
    }
    
    function setTag($tag, $value) {
        $this->_structure['tags'][$tag] = $value;
        $this->changed = true;
    }
    
    function getTag($tag)
    {
        return isset($this->_structure['tags'][$tag])?$this->_structure['tags'][$tag]:false;
    }
    function _parseDocBlock($string)
    {
        preg_match_all('/\/\*\*\n(\s*\*([^\n]+?\n)+)+.*?\*\//',$string,$matches);
        $docBlockStructure = array('comment'=>null);
        if (isset($matches[1][0])) {
            $docPart = $matches[1][0];
            $docPart = preg_replace('/\s*\*\s*/',"\n",$docPart);
            $docPart = trim($docPart);
            $commentLines = array();
            $tags = array('_unmatched_'=>array());
            $docLines = split("\n",$docPart);
            $inComment = true;
            $tempTag=array();
            foreach ($docLines as $line) {
                 if (preg_match('/^@([a-zA-Z0-9_]+)\s+(.+)$/',$line, $matches)) {
                    if (!empty($tempTag)) {
                        $this->_parseTag(&$tags, $tempTag);
                    }
                    $inComment = false;
                    $tempTag = array($matches[1],$matches[2]);
                } else if ($inComment) {
                    $commentLines[] = $line;
                } else {
                    $tempTag[1].="\n".$line;
                }
            }
            if (!empty($tempTag)) {
                $this->_parseTag(&$tags, $tempTag);
            }
            $docBlockStructure['comment'] = trim(implode("\n",$commentLines));
            $docBlockStructure['tags'] = $tags;
        }
        return $docBlockStructure;
    }
    
function _parseTag(&$tags, $tempTag)
    {
        switch($tempTag[0]) {
            case 'param':
                if (preg_match('/\$([a-zA-Z0-9_]+)\s*(.*?)/s',$tempTag[1],$pmatches)) {

                    if (!isset($tags['params'])) {
                        $tags['params'] = array(); 
                    } else if (!is_array($tags['params'])) {
                        $currentValue = $tags['params'];
                        $tags['params'] = array($currentValue); 
                    }
                    $tags['params'][$pmatches[1]] = trim($pmatches[2]);
                } else {
                    
                    $tags['_unmatched_'][] = array($tempTag[0],$tempTag[1]);
                }
                break;
            default:
                if(!empty($tags[$tempTag[0]])) {
                    if(!is_array($tags[$tempTag[0]])) {
                        
                        $currentValue = $tags[$tempTag[0]];
                        $tags[$tempTag[0]] = array($currentValue);
                    }
                    $tags[$tempTag[0]][]=trim($tempTag[1]);
                } else {
                    $tags[$tempTag[0]]=trim($tempTag[1]);
                }
                
        }
    }
}
?>