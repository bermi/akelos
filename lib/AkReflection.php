<?php


class AkReflection
{
     
    var $definitions = array();
    var $requires = array();
    var $tokens;
     
    var $symbols;
    
    

    function _parse($source)
    {
        if (!function_exists('token_get_all')) {
            trigger_error('Function "token_get_all" is not defined');
            return false;
        }
        $source = @preg_match('/<\?php.*'.$source.'.*\?>/', $source)?$source:"<?php ".$source." ?>";
        $this->tokens = token_get_all($source);
        $this->definitions = array();
        reset($this->tokens);
        $previous = array();
        $visibility = false;
        $static = false;
        $byReference = false;
        $functionIndent = '';
        $docBlock='';
        while ($t = current($this->tokens)) {
            
            if (is_array($t)) {
                if ($t[0] == T_CLASS || (defined('T_INTERFACE')? $t[0] == T_INTERFACE:false) || $t[0] == T_FUNCTION) {
                    $previous = array_reverse($previous);
                    foreach($previous as $prev) {
                        if ($prev[0] == T_STATIC) {
                            $static = true;
                        } else if ($prev[0] == T_STRING && in_array($prev[1],array('private','public','protected'))) {
                            $visibility = $prev[1];
                        } else if ($prev[0] == T_PAAMAYIM_NEKUDOTAYIM) {
                            $byReference = true;
                        } else if (((defined('T_DOC_COMMENT')?$prev[0] == T_DOC_COMMENT:false) || T_COMMENT) && !@preg_match('/<\?php.*/',$prev[1]) && @preg_match('/\/\*/',$prev[1])) {
                            $docBlock = isset($prev[1])?$prev[1]:null;
                            break;
                        } else if (isset($prev[1]) && in_array($prev[1],array('private','public','protected'))){
                            $visibility = $prev[1];
                        }
                    }
                    $indent='';
                    if(!empty($docBlock)) {
                        $doclines = split("\n",$docBlock);
                        $lastLine = $doclines[count($doclines)-1];
                        if (preg_match('/(\s*)?\*/',$lastLine,$matches)) {
                            
                            $indent = substr($matches[1],0,strlen($matches[1])-1);

                            $doclines[0]=$indent.$doclines[0];
                            foreach($doclines as $idx=>$line) {
                                $pre = '';
                                if ($idx>0) {
                                    $pre = ' ';
                                }
                                $doclines[$idx] = $pre.trim($line);
                            }
                            $docBlock=implode("\n",$doclines);
                        }
                    } else {
                        $indent = $functionIndent;
                    }
                    $docBlock = str_replace('<?php','',$docBlock);
                    $string = (!empty($docBlock)?$docBlock."\n":'').($visibility?$visibility.' ':'').($static?' static ':'');
                    $this->readDefinition($static, $visibility, $byReference, $docBlock,$string, $indent);
                    $previous = array();
                    $docBlock = '';
                    $static = false;
                    $visibility = false;
                    $byReference = false;
                    $functionIndent = '';
                    $indent = '';
                    continue;
                } else if ($t[0] == T_REQUIRE || $t[0] == T_REQUIRE_ONCE || $t[0] == T_INCLUDE || $t[0] == T_INCLUDE_ONCE) {
                    if (!isset($this->requires[$t[1]])) {
                        $this->requires[$t[1]] = array();
                    }
                    $org = $t;
                    $type= $t[1];
                    $val='';
                    next($this->tokens);
                    $t = current($this->tokens);
                    while ($t != '(') {
                        next($this->tokens);
                        $t = current($this->tokens);
                    }
                    next($this->tokens);
                    $t = current($this->tokens);
                    while ($t != ')') {
                        next($this->tokens);
                        if (is_array($t)) {
                            $val.=$t[1];
                        } else {
                            $val.=$t;
                        }
                        $t = current($this->tokens);
                    }
                    $this->requires[$type][]=$val;
                    $t = $org;
                }
            }
            if ($t[0] != T_WHITESPACE) {
                $previous[] = $t;
            } else if ($t[0] == T_WHITESPACE){
                $functionIndent.=$t[1];
            }
             
            next($this->tokens);
        }
        $this->definitions = array_merge($this->definitions,$this->requires);
    }
    function _parseTag(&$tags, $tempTag)
    {
        switch($tempTag[0]) {
            case 'param':
                if (preg_match('/\$([a-zA-Z0-9_]+)\s+(.*)/s',$tempTag[1],$pmatches)) {
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
    
    function readDefinition($static = false, $visibility = 'public', $byReference = false, $docBlock = '', $string = '', $indent)
    {
        $t = current($this->tokens);
        $definitionType = $t[1];

        // move past the class/interface/function token
         
        next($this->tokens);
        $string.=$definitionType;
        $string.=$this->skipWhiteAndComments();
         
        $t = current($this->tokens);
        if (!isset($t[1])) {
            while(!isset($t[1])) {
                if ($t=='&') {
                    
                    $string.=$t;
                    $byReference = true;
                    next($this->tokens);
                    $t = current($this->tokens);
                    
                }
            }
            //$definitionType = $t[1];
            $string.=$t[1];
            
        } else {
            $string.=$t[1];
        }

        $definitionName = $t[1];
         
        $this->definitions[] = array(
          'type' => $definitionType,
          'name' => $definitionName,
          'visibility'=>$visibility==false?(substr($definitionName,0,2)=='__')?'private':(substr($definitionName,0,1)=='_'?'protected':false):$visibility,
          'static'=>$static,
          'returnByReference'=>$byReference,
          'docBlock' => $docBlock,
          'toString' => $string
        );
        
        // move past the name identifier
        next($this->tokens);
        
        list($params,$block,$pre,$post) = $this->getCodeBlock();
        $default_options = false;
        $available_options = false;
        if (preg_match('/\$default_options.*?=.*?(array\(.*?\)).*?;/s',$block,$default_option_matches)) {
            $default_options_string=$default_option_matches[1];
            $default_options_string = preg_replace_callback('/\$([A-Za-z0-9_\->])+/',array(&$this,'_replaceVariablesInsideOptions'),$default_options_string);
           @eval('$default_options = '.$default_options_string.';');
        }
        if (preg_match('/\$available_options.*?=.*?(array\(.*?\)).*?;/s',$block,$available_option_matches)) {
            $available_options_string=$available_option_matches[1];
            $available_options_string = preg_replace_callback('/\$([A-Za-z0-9_\->])+/',array(&$this,'_replaceVariablesInsideOptions'),$available_options_string);
           @eval('$available_options = '.$available_options_string.';');
        }
        $string.=$pre.$block.$post;
        $this->definitions[count($this->definitions)-1]['code'] = $block;
        $this->definitions[count($this->definitions)-1]['params'] = $params;
        $this->definitions[count($this->definitions)-1]['toString'] = $string;
        $this->definitions[count($this->definitions)-1]['default_options'] = $default_options;
        $this->definitions[count($this->definitions)-1]['available_options'] = $available_options;
        $strlines = split("\n",$string);
        foreach ($strlines as $idx=>$line) {
            $first = substr($line,0,strlen($indent));
            if ($first == $indent) {
                $line = substr($line,strlen($first));
                $strlines[$idx] = $line;
            }
        }
        $doclines = split("\n",$docBlock);
        foreach ($doclines as $idx=>$line) {
            $first = substr($line,0,strlen($indent));
            if ($first == $indent) {
                $line = substr($line,strlen($first));
                $doclines[$idx] = $line;
            }
        }
        $this->definitions[count($this->definitions)-1]['toString'] = implode("\n",$strlines);
        $this->definitions[count($this->definitions)-1]['docBlock'] = implode("\n",$doclines);
    }
    function _replaceVariablesInsideOptions($matches)
    {
        $name = $matches[0];
        return '"'.str_replace('$','\$',$name).'"';
    }
    function skipWhiteAndComments()
    {
        $string = '';
        while ($t = current($this->tokens)) {
            if (is_array($t) && ($t[0] == T_WHITESPACE || (defined('T_DOC_COMMENT')?$t[0] == T_DOC_COMMENT:false) || $t[0] == T_COMMENT)) {
                next($this->tokens);
                $string.=$t[1];
            } else {
                return $string;
            }
        }
    }

    function skipCodeBlock()
    {
         
        // we go forward until we find the first "{" token
         
        while(($t = current($this->tokens)) && $t != '{') {
            next($this->tokens);
        }
        // we're about to enter the top level block
        // which is our class/interface/function definition body
        $nestingLevel = 0;

        // we go forward keeping the $nestingLevel up-to-date
        // until we get out of the definition body block
        while($t = current($this->tokens)) {
            if ($t == '{') {
                $nestingLevel++;
            }
             
            if ($t == '}') {
                $nestingLevel--;
            }
             
            next($this->tokens);
             
            if ($nestingLevel == 0) return;
        }
    }
    function getCodeBlock()
    {
        $prestring = '';
        $poststring = '';
        $codeblock = '';
        // we go forward until we find the first "{" token
        $params = array();
        $preParam = '';
        while(($t = current($this->tokens)) && $t != '{') {
            if (is_array($t)) {
                switch ($t[0]) {
                    case T_VARIABLE:
                        $params[]=$preParam.$t[1];
                        $preParam = '';
                        break;
                }
                $prestring.=$t[1];
            } else if (!in_array($t,array(',','(',')'))) {
                $preParam.=$t;
                $prestring.=$t;
            } else {
                $prestring.=$t;
            }
            next($this->tokens);
             
        }
        
        // we're about to enter the top level block
         
        // which is our class/interface/function definition body
        $nestingLevel = 0;
         
        // we go forward keeping the $nestingLevel up-to-date
        // until we get out of the definition body block
        while($t = current($this->tokens)) {
            if ($t == '{') {
                $nestingLevel++;
            }
            
            if ($t == '}') {
                $nestingLevel--;
            }
            
            next($this->tokens);
            
            
            if ($nestingLevel == 0) {
                $poststring.=$t;
                return array($params,$codeblock,$prestring,$poststring);
            } else {
                if ($t == '{' && $nestingLevel==1) {
                    $prestring.=$t;
                    continue;
                }
                $codeblock.=is_array($t)?$t[1]:$t;
            }
        }
    }
    
    function getDefinitions()
    {
        return $this->definitions;
    }
}
?>