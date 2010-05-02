<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

class SourceLexer extends AkLexer
{
    public function __construct(&$Parser)
    {
        parent::__construct($Parser, 'Text');
        $this->mapHandler('Text', 'Text');

        $this->addPhpTokens();

    }

    public function addPhpTokens()
    {
        $this->addEntryPattern('<\?','Text','PhpCode');

        $this->addClassName();
        $this->addCategories();
        $this->addJavaDocTokens();
        $this->addFunctionDeclaration();
        $this->addFunctionDefaultOptions();
        $this->addExitPattern('\?>','PhpCode');

    }

    public function addCategories()
    {
        $this->addEntryPattern('\x2f\x2a\x2a[ \n\t]*[A-Za-z _0-9]+[ \n\t]*\x3d{60,}','PhpCode','CategoryStart');
        $this->addPattern('(?<=\x3d{60}\n)See also:[ \n\t]*[a-z,A-Z _0-9]+[ \n\t]*\x2e', 'CategoryStart');

        $this->addEntryPattern('(?<=\n)[ \t]*\* ','CategoryStart','CategoryDetails');
        $this->addEntryPattern('\* ','CategoryStart','CategoryDetails');
        $this->addExitPattern('\n','CategoryDetails');

        $this->addExitPattern('\x2a\x2f','CategoryStart');

        $this->addSpecialPattern('\x2f\x2a\x2f[A-Za-z _0-9]+\x2a\x2f','PhpCode','CategoryFinish');

    }

    public function addJavaDocTokens()
    {
        $this->addEntryPattern('\x2f\x2a\x2a','PhpCode','JavaDoc');
        $this->addExitPattern('\x2a\x2f','JavaDoc');

        $this->addJavaDocLines();
    }

    public function addJavaDocLines()
    {
        $this->addEntryPattern('(?<=\n)[ \t]*\*','JavaDoc','JavaDocLine');
        $this->addEntryPattern('\*','JavaDoc','JavaDocLine');
        $this->addSpecialPattern('\x40[A-Za-z]+ ','JavaDocLine','JavaDocAttribute');
        $this->addExitPattern('\n','JavaDocLine');
    }


    public function addClassName()
    {
        $this->addEntryPattern('(?<=\n)[ \t]*class[ \n\t]*', 'PhpCode', 'ClassName');
        $this->addPattern('(?!extends)[ \n\t]*[a-z]+[ \n\t]*', 'ClassName');
        $this->addEntryPattern('[ \n\t]*extends[ \n\t]*', 'ClassName', 'ParentClassName');
        $this->addExitPattern('[ \n\t]*[a-z]+[ \n\t]*', 'ParentClassName');
        $this->addExitPattern('[ \n\t]*{', 'ClassName');
    }

    public function addFunctionDeclaration()
    {
        $this->addEntryPattern('(?<=\n)[ \t]*function[ \n\t][\x26a-z_]+[ \n\t]*\x28', 'PhpCode', 'FunctionName');
        //$this->addPattern('[ \n\t]*[a-z]+[ \n\t]*(?=\x28)', 'FunctionName');
        $this->addEntryPattern('\x26?[ \t]*\x24', 'FunctionName', 'FunctionParameter');
        $this->addExitPattern(',[ \t]*(?=\x26?[ \t]*\x24)', 'FunctionParameter');
        $this->addExitPattern('\x29', 'FunctionParameter');
        $this->addExitPattern('\x29?[ \n\t]*{', 'FunctionName');
    }


    public function addFunctionDefaultOptions()
    {
        $this->addEntryPattern('\x24default_options[ \t]*\x3d[ \t]*array[ \t]*\x28', 'PhpCode', 'DefaultOptions');
        $this->addEntryPattern('\x27', 'DefaultOptions', 'DefaultOption');
        $this->addExitPattern('\x27', 'DefaultOption');
        $this->addExitPattern(';', 'DefaultOptions');
    }

}


class SourceParser
{
    public $output = '';
    public $input = '';
    public $_Lexer;
    public $parsed = array();
    public $_current_category = 'none';
    public $_current_category_details = '';
    public $_current_category_relations = array();
    public $_current_class = null;
    public $_current_class_extends = null;
    public $_current_method = null;
    public $_current_params = array();
    public $_latest_attributes = array();
    public $_current_javadoc_attribute = false;
    public $_latest_docs = null;
    public $_is_reference = null;

    public $package = 'Active Support';
    public $subpackage = 'Utils';

    public function SourceParser($code)
    {
        $this->input = $code;
        $this->_Lexer = new SourceLexer($this);
    }

    public function parse()
    {
        $this->beforeParsing();
        $this->_Lexer->parse($this->input);
        $this->afterParsing();
        return $this->parsed;
    }

    public function afterParsing()
    {
        $this->parsed['details'] = array();
        $this->parsed['details']['package'] = $this->package;
        $this->parsed['details']['subpackage'] = $this->subpackage;
    }

    public function beforeParsing()
    {
        $this->input = preg_replace("/\n[ \t]*/","\n", $this->input);
    }

    public function PhpCode($match, $state)
    {
        //AkDebug::trace(__FUNCTION__.' '.$match.' '.$state);
        if($match == AK_LEXER_UNMATCHED){
        }
        return true;
    }

    public function ClassName($match, $state)
    {
        if(AK_LEXER_MATCHED == $state){
            $this->_current_class = trim($match);
        }elseif(AK_LEXER_EXIT == $state){
            if(!empty($this->_current_class)){
                $this->parsed['classes'][$this->_current_class] = array(
                'doc' => trim($this->_latest_docs, "\n\t "),
                'doc_metadata' => $this->_latest_attributes,
                'class_name' => $this->_current_class,
                'extends' => trim($this->_current_class_extends),
                'methods' => array(),
                //'category' => $this->_current_category,
                );
            }
            $this->_current_class_extends = null;
            $this->_latest_docs = '';
            $this->_is_reference = false;
        }elseif (AK_LEXER_ENTER == $state){
            $this->_is_reference = strstr($match,'&');
        }
        //AkDebug::trace(__FUNCTION__.' '.$match.' '.$state);
        return true;
    }

    public function ParentClassName($match, $state)
    {
        if(AK_LEXER_EXIT == $state){
            $this->_current_class_extends = trim($match);
        }
        //AkDebug::trace(__FUNCTION__.' '.$match.' '.$state);
        return true;
    }

    public function FunctionName($match, $state)
    {
        if(AK_LEXER_ENTER === $state){
            $this->_current_method_returns_reference = strstr($match,'&') != '';
            $this->_current_method = trim(str_replace(array('function ','(', '&'),'',$match));
        }elseif(AK_LEXER_EXIT == $state){
            if(!empty($this->_current_class) && !empty($this->_current_method)){
                $this->parsed['classes'][$this->_current_class]['methods'][$this->_current_method] = array(
                'doc' => trim($this->_latest_docs, "\n\t "),
                'doc_metadata' => $this->_latest_attributes,
                'method_name' => $this->_current_method,
                'is_private' => substr($this->_current_method,0,1) == '_',
                'returns_reference' => $this->_current_method_returns_reference,
                'params' => $this->_current_params,

                'category' => $this->_current_category,
                'category_details' => $this->_current_category_details,
                'category_relations' => $this->_current_category_relations
                );
            }

            //AkDebug::trace($this->_current_category_details);

            $this->_current_method = null;
            $this->_current_params = array();
            $this->_latest_docs = '';

            $this->_current_category = 'none';
            $this->_current_category_details = '';
            $this->_current_category_relations = array();
        }
        //AkDebug::trace(__FUNCTION__.' '.$match.' '.$state);
        return true;
    }
    public function FunctionParameter($match, $state)
    {
        if(AK_LEXER_UNMATCHED == $state){
            list($param,$value) = explode('=',$match.'=');
            $type = null;
            if(strstr($value,'array')){
                $type = 'array';
                $value = '';
            }elseif(strstr($value,'"') || strstr($value,"'")){
                $type = 'string';
                $value = trim($value,'"\' ');
            }elseif(strstr($value,'true') || strstr($value,'false')){
                $type =  'bool';
            }elseif(is_numeric(trim($value))){
                $type =  'int';
            }elseif(strstr($value,'null') || empty($value)){
                $type =  null;
                $value = null;
            }elseif (preg_match('/[A-Z]/', @$value[0])){
                $type = preg_match('/[A-Z]/', @$value[1]) ? 'constant' : 'object';
            }

            array_push($this->_current_params, array(
            'name' => trim($param),
            'type' => $type,
            'value' => trim($value),
            ));
        }
        //AkDebug::trace(__FUNCTION__.' '.$match.' '.$state);
        return true;
    }

    public function CategoryStart($match, $state)
    {
        if(AK_LEXER_ENTER === $state){
            $this->_current_category = trim($match,"\n= /*");
            $this->parsed['categories'][$this->_current_category] = array();
        }elseif (AK_LEXER_UNMATCHED == $state){

            $match = trim(str_replace(array('See also',"*","\n","\t",'.',':'),'',$match));
            if(!empty($match)){
                $this->_current_category_relations = array_map('trim', explode(',',$match));
                $this->parsed['categories'][$this->_current_category]['relations'] = $this->_current_category_relations;
            }
        }
        // AkDebug::trace(__FUNCTION__.' '.$match.' '.$state);
        return true;
    }

    public function CategoryDetails($match, $state)
    {
        if(AK_LEXER_UNMATCHED == $state){
            $this->_current_category = 'none';
            $this->_current_category_relations = array();
        }
        $this->_current_category_details .= trim($match, '/*');

        // AkDebug::trace(__FUNCTION__.' '.$match.' '.$state);
        return true;
    }

    public function CategoryFinish($match, $state)
    {
        if(AK_LEXER_SPECIAL == $state){
            $this->_current_category_details .= trim($match, '/* ');
        }
        //AkDebug::trace($this->_current_category_details);
        return true;
    }

    public function DefaultOptions($match, $state)
    {
        //AkDebug::trace(__FUNCTION__.' '.$match.' '.$state);
        return true;
    }

    public function DefaultOption($match, $state)
    {
        //AkDebug::trace(__FUNCTION__.' '.$match.' '.$state);
        return true;
    }

    public function JavaDoc($match, $state)
    {
        if(AK_LEXER_ENTER === $state){
            $this->_current_javadoc_attribute = false;
            $this->_latest_attributes = array();
        }elseif(AK_LEXER_UNMATCHED == $state){
            $this->_latest_docs .= (trim($match) == '*'?'':$match)."\n";
        }

        //AkDebug::trace(__FUNCTION__.' '.$match.' '.$state);
        return true;
    }

    public function JavaDocLine($match, $state)
    {
        if(AK_LEXER_UNMATCHED == $state){
            $match = (trim($match) == '*'?'':$match)."\n";
            if(!$this->_current_javadoc_attribute){
                $this->_latest_docs .= $match;
            }elseif(trim($match) != ''){
                $handler_name = 'handle'.AkInflector::camelize($this->_current_javadoc_attribute);
                if(method_exists($this,$handler_name)){
                    $this->$handler_name($match);
                }else{
                    $this->_latest_attributes[$this->_current_javadoc_attribute] = $match;
                }
            }
        }
        //AkDebug::trace(__FUNCTION__.$match.$state);
        return true;
    }

    public function JavaDocAttribute($match, $state)
    {
        $match = trim($match,'@ ');
        $this->_current_javadoc_attribute = empty($match) ? false : $match;
        return true;
    }

    public function Text($text)
    {
        $this->output .= $text;
        return true;
    }

    public function handlePackage($package_description)
    {
        $this->package = AkInflector::titleize($package_description);
        //AkDebug::trace($this->package);
    }

    public function handleSubpackage($subpackage_description)
    {
        $this->subpackage = AkInflector::titleize($subpackage_description);
        //AkDebug::trace($this->subpackage);
    }

}

