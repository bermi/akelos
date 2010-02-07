<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

class AkSintagsParser
{
    public $_SINTAGS_OPEN_HELPER_TAG = AK_SINTAGS_OPEN_HELPER_TAG;
    public $_SINTAGS_CLOSE_HELPER_TAG = AK_SINTAGS_CLOSE_HELPER_TAG;
    public $_SINTAGS_HASH_KEY_VALUE_DELIMITER = AK_SINTAGS_HASH_KEY_VALUE_DELIMITER;

    public $_Lexer;
    public $_lexer_name = 'AkSintagsLexer';
    public $_mode;
    public $_last_match;
    public $_matches;
    public $_current_match;
    public $_block_vars = array();
    public $_errors = array();
    public $_current_function_opening = null;
    public $avoid_php_tags = null;
    public $parsed_code = null;
    public $output;
    public $escape_chars = array(
    '\{' => '____AKST_OT____',
    '\}' => '____AKST_CT____',
    '\"' => '____AKST_DQ____',
    "\'" => '____AKST_SQ____'
    );

    private $_HelperLoader;

    public function __construct($mode = 'Text') {
        $this->_loadLexer();
        $this->_mode = $mode;
        $this->_matches = array();
        $this->_last_match = '';
        $this->_current_match = '';
    }

    public function parse($raw) {
        if(empty($this->parsed_code)){
            $this->_Lexer->parse($this->beforeParsing($this->_escapeChars($raw)));
            $this->parsed_code = $this->afterParsing($this->getResults());
            return $this->hasErrors() ? false : $this->parsed_code;
        }
        return $this->parsed_code;
    }

    public function beforeParsing($raw) {
        return $raw;
    }

    public function afterParsing($parsed) {
        return $parsed;
    }

    public function ignore($match, $state) {
        return true;
    }



    public function setHelperLoader(&$HelperLoader){
        $this->_HelperLoader = $HelperLoader;
    }

    public function hasErrors() {
        return !empty($this->_errors);
    }

    public function getErrors() {
        return $this->_errors;
    }

    //------------------------------------
    //  PHP CODE
    //------------------------------------
    public function PhpCode($match, $state) {
        if(!AK_SINTAGS_REPLACE_SHORTHAND_PHP_TAGS){
            $this->output .= $match;
            return true;
        }
        switch ($state){
            case AK_LEXER_ENTER:
                $this->output .= '<?php ';
                break;
            case AK_LEXER_UNMATCHED:
                $match = preg_replace('/\$([\w_]+_helper)->/i', '$controller->$1->', ltrim($match));
                if(!empty($match)){
                    if(substr($match,0,3) == 'php'){
                        $match = substr($match,3);
                    }elseif($match[0] == '='){
                        $match = 'echo '.substr($match,1);
                    }
                    $this->output.=  $match;
                }
                break;
            case AK_LEXER_EXIT:
                $this->output .= '?>';
        }
        return true;
    }

    //----------------------------------------------------
    //  XML OPENING COMPATIBILITY WHITH SHORTAGS SETTINGS
    //----------------------------------------------------
    public function XmlOpening($match, $state) {
        if(AK_LEXER_SPECIAL === $state){
            $this->output .= '<?php echo \'<?xml\'; ?>';
        }
        return true;
    }

    //------------------------------------
    //  PLAIN TEXT
    //------------------------------------

    public function Text($text) {
        $this->output .= $text;
        return true;
    }

    // UTILS
    public function getResults() {
        return $this->_unescapeChars($this->output);
    }
    public function _escapeChars($string) {
        return str_replace(array_keys($this->escape_chars),array_values($this->escape_chars),$string);
    }
    public function _unescapeChars($string, $strip_slashes_from_tokens = false) {
        $escape_chars = array_merge(array('{' => '____AKST_OT____','}' => '____AKST_CT____'), $this->escape_chars);
        $replacements = $strip_slashes_from_tokens ? array_map('stripcslashes',array_keys($escape_chars)) : array_keys($escape_chars);
        return str_replace(array_values($escape_chars),$replacements,$string);
    }

    //------------------------------------
    //  ESCAPED TEXT
    //------------------------------------

    public function EscapedText($match, $state) {
        $this->output .= ltrim($match,'\\');
        return true;
    }

    //------------------------------------
    //  TRANSLATIONS
    //------------------------------------

    public function Translation($match, $state) {
        switch ($state){
            case AK_LEXER_ENTER:
                $this->_translation_tokens = array();
                $this->output .= '<?php echo $controller->text_helper->translate(\'';
                break;
            case AK_LEXER_UNMATCHED:
                $this->output.= $this->_unescapeChars(str_replace("'","\'",$match), true);
                break;
            case AK_LEXER_EXIT:
                $this->output .= '\', array('.(empty($this->_translation_tokens)?'':join(', ',$this->_translation_tokens)).')); ?>';
        }
        return true;
    }


    //------------------------------------
    //  HELPER TRANSLATION TOKEN
    //------------------------------------

    public function HelperTranslation($match, $state) {
        switch ($state){
            case AK_LEXER_ENTER:
                $this->_translation_tokens = array();
                $this->output .= '$controller->text_helper->translate(\'';
                break;
            case AK_LEXER_UNMATCHED:
                $this->output.= $this->_unescapeChars($match, false);
                break;
            case AK_LEXER_EXIT:
                if (!empty($this->_translation_tokens)) {
                    $this->output .= '\', array('.join(', ',$this->_translation_tokens).'))';
                } else {
                    $this->output .= '\')';
                }
        }
        return true;
    }

    //------------------------------------
    //  TRANSLATIONS TOKEN
    //------------------------------------

    public function TranslationToken($match) {
        $this->output.= ltrim($match,'\\');
        $php_variable = $this->_convertSintagsVarToPhp(ltrim($match,'\%'));
        if($match[0] != '\\' && $php_variable){
            if($match[1] != '\\' && !strstr($php_variable, '$params[')){
                $this->_translation_tokens[] = '\''.$match.'\' => @'.$php_variable;
            }else{
                $this->_translation_tokens[] = '\''.$match.'\' => $controller->text_helper->h(@'.$php_variable.')';
            }
        }
        return true;
    }

    //------------------------------------
    //  VARIABLE TRANSLATIONS
    //------------------------------------

    public function VariableTranslation($match, $state) {
        $php_variable = $this->_convertSintagsVarToPhp(trim($match,'{_}?'));
        if($php_variable){
            $this->output .= '<?php echo empty('.$php_variable.') || is_object('.$php_variable.') ? \'\' : $controller->text_helper->translate('.$php_variable.'); ?>';
        }else{
            $this->output .= $match;
        }
        return true;
    }


    //------------------------------------
    //  SINTAGS CONDITIONAL VARIABLES
    //------------------------------------

    public function ConditionalVariable($match, $state) {
        $_skip_sanitizing = ($match[1] != '\\');
        $php_variable = $this->_convertSintagsVarToPhp(trim($match,'{}?'));
        if($php_variable){
            $this->output .= '<?php echo empty('.$php_variable.') ? \'\' : '.
            ($_skip_sanitizing ? $php_variable : '$controller->text_helper->h('.$php_variable.')').
            '; ?>';
        }else{
            $this->output .= $match;
        }
        return true;
    }



    //------------------------------------
    //  SINTAGS VARIABLES
    //------------------------------------

    public function Variable($match, $state) {
        $_skip_sanitizing = ($match[1] != '\\');
        $php_variable = $this->_convertSintagsVarToPhp($match);
        if($php_variable){
            $this->output .= $_skip_sanitizing ? '<?php echo '.$php_variable.'; ?>' :  '<?php echo $controller->text_helper->h('.$php_variable.'); ?>';
        }else{
            $this->output .= $match;
        }
        return true;
    }


    public function _convertSintagsVarToPhp($var) {
        if(preg_match('/[\.-]_/',$var)){
            return false;
        }
        $var = str_replace(array('-','.'),array('~','->'),trim($var,'-_.{}@\\'));
        if(strstr($var,'~')){
            $pieces = explode('~',$var);
            $var = array_shift($pieces);
            if(!empty($pieces)){
                foreach ($pieces as $piece){
                    $array_start = strpos($piece,'-');
                    $array_key = $array_start ? substr($piece,0,$array_start) : substr($piece,0);
                    $var .= str_replace($array_key, (is_numeric($array_key) ? '['.$array_key.']' : '[\''.$array_key.'\']'),$piece);
                }
            }
        }
        return '$'.$var;
    }

    //------------------------------------
    //  SINTAGS CONDITIONS
    //------------------------------------

    public function ConditionStart($match, $state) {
        if(AK_LEXER_SPECIAL === $state){
            $match = trim($match,'{}');
            $assert_simbol = substr($match,0,1) == '?' ? '!' : '';
            $php_variable = $this->_convertSintagsVarToPhp(trim($match,'?!'));
            if($php_variable){
                $this->output .= '<?php if('.$assert_simbol.'empty('.$php_variable.')) { ?>';
            }else{
                $this->output .= $match;
            }
        }
        return true;
    }

    //------------------------------------
    //  SINTAGS END TAG
    //------------------------------------

    public function EndTag($match, $state) {
        if(AK_LEXER_SPECIAL === $state){
            $this->output .= '<?php } ?>';
        }
        return true;
    }

    //------------------------------------
    //  SINTAGS ELSE TAG
    //------------------------------------

    public function ElseTag($match, $state) {
        if(AK_LEXER_SPECIAL === $state){
            $this->output .= '<?php } else { ?>';
        }
        return true;
    }


    //------------------------------------
    //  SINTAGS LOOP
    //------------------------------------

    public function Loop($match, $state) {
        if(AK_LEXER_SPECIAL === $state){
            $sintags_var = trim(preg_replace('/[\s|?]+/',' ', substr($match, 6,-1)));
            if(strstr($sintags_var,' as ')){
                $new_sintags_var = substr($sintags_var,0, strpos($sintags_var,' '));
                $termination = $this->_getTerminationName(AkInflector::pluralize(str_replace($new_sintags_var.' as ','', $sintags_var)));
                $sintags_var = $new_sintags_var;
            }
            $php_variable = $this->_convertSintagsVarToPhp($sintags_var);
            if($php_variable){
                $php_variable = $php_variable;
                $termination = empty($termination) ? $this->_getTerminationName($sintags_var) : $termination;
                $singular_variable = '$'.AkInflector::singularize($termination);
                $plural_variable = '$'.$termination;

                $this->output .=
                "<?php ".
                "\n empty({$php_variable}) ? null : {$singular_variable}_loop_counter = 0;".
                "\n empty({$php_variable}) ? null : {$plural_variable}_available = count({$php_variable});".
                "\n if(!empty({$php_variable}))".
                "\n     foreach ({$php_variable} as {$singular_variable}_loop_key=>{$singular_variable}){".
                "\n         {$singular_variable}_loop_counter++;".
                "\n         {$singular_variable}_is_first = {$singular_variable}_loop_counter === 1;".
                "\n         {$singular_variable}_is_last = {$singular_variable}_loop_counter === {$plural_variable}_available;".
                "\n         {$singular_variable}_odd_position = {$singular_variable}_loop_counter%2;".
                "\n?>";
            }else{
                $this->output .= $match;
            }
        }
        return true;
    }

    public function _getTerminationName($plural) {
        $plural = str_replace('-','.', $plural);
        $pos = strrpos($plural, '.');
        return substr($plural, $pos > 0 ? $pos+1 : 0);
    }


    //------------------------------------
    //  SINTAGS HELPER MODE
    //------------------------------------

    public function Helper($match, $state, $position = null, $is_inline_function = false) {
        switch ($state){
            case AK_LEXER_ENTER:
                if(preg_match('/=+$/', trim($match))){
                    $this->avoid_php_tags = $this->_current_function_opening = false;
                    $this->output .= '<?php '.$this->_convertSintagsVarToPhp(trim($match," =(\n\t".$this->_SINTAGS_OPEN_HELPER_TAG)).' = (';
                    return true;
                }
                $method_name = trim($match," =(\n\t".$this->_SINTAGS_OPEN_HELPER_TAG);
                $helper = $this->_getHelperNameForMethod($method_name);
                $named_route = in_array($method_name, AkRouterHelper::getDefinedFunctions());
                if($helper || $named_route){
                    $this->avoid_php_tags = !$is_inline_function && !strstr($match,'=');
                    $this->_current_function_opening = strlen($this->output);
                    if(!$this->avoid_php_tags){
                        $this->output .= $is_inline_function ? '' : '<?php echo ';
                    }
                    if($named_route){
                        $this->output .= "$method_name(";
                    }else{
                        if(!strpos($helper, 'helper')){
                            $method_name = AkInflector::variablize($method_name);
                        }
                        if($helper == 'controller'){
                            $this->output .= "\$controller->$method_name(";
                        }else{
                            $this->output .= "\$controller->{$helper}->$method_name(";
                        }
                    }
                    return true;
                }else{
                    $this->addError(Ak::t('Could not find a helper to handle the method "%method" you called in your view', array('%method'=>$method_name)));
                }
                return false;
                break;

            case AK_LEXER_UNMATCHED:
                $match = trim($match);
                if($match == ','){
                    $this->output .= $match.' ';
                }elseif ($match == $this->_SINTAGS_HASH_KEY_VALUE_DELIMITER){
                    if(empty($this->_inside_array) && empty($this->_has_last_argument_params)){
                        $current_function = substr($this->output,$this->_current_function_opening);

                        $function_opening = strrpos($current_function,'(')+1;
                        $last_comma = strrpos($current_function,',')+1;
                        $insert_point = $function_opening > $last_comma && $last_comma === 1 ? $function_opening : $last_comma;

                        $this->output = substr($this->output,0,$this->_current_function_opening+$insert_point).' array('.ltrim(substr($this->output,$this->_current_function_opening+$insert_point));
                        $this->_has_last_argument_params = true;
                    }

                    $this->output .= ' => ';
                }
                break;

            case AK_LEXER_EXIT:
                $this->output .= (!empty($this->_has_last_argument_params) ? ')':'').')'.
                ($this->avoid_php_tags ? '' : ($is_inline_function?'':'; ?>'));
                $this->_has_last_argument_params = false;
                break;
        }

        return true;
    }


    //------------------------------------
    //  SINTAGS HELPER FUNCTION MODE
    //------------------------------------

    public function HelperFunction($match, $state, $position = null) {
        return $this->Helper($match, $state, $position, true);
    }

    //------------------------------------
    //  SINTAGS INLINE HELPER MODE
    //------------------------------------

    public function InlineHelper($match, $state, $position = null) {
        $success = true;
        if(AK_LEXER_ENTER === $state){
            $this->output .= '".';
            $success = $this->Helper(ltrim($match,'{#'), $state, $position, true);
        }elseif(AK_LEXER_EXIT === $state){
            $success = $this->Helper($match, $state, $position, true);
            $this->output .= '."';
        }else{
            $success = $this->Helper($match, $state, $position, true);
        }
        return $success;
    }

    //------------------------------------
    //  SINTAGS INLINE VARIABLE MODE
    //------------------------------------

    public function InlineVariable($match, $state, $position = null) {
        $php_variable = $this->_convertSintagsVarToPhp(trim($match,'#{}'));
        if($php_variable){
            $this->output .= '".'.$php_variable.'."';
        }
        return true;
    }

    //------------------------------------
    //  SINTAGS VARIABLES
    //------------------------------------

    public function HelperVariable($match, $state, $position = null, $inline = false) {
        $php_variable = $this->_convertSintagsVarToPhp(trim($match));
        if($php_variable){
            $this->output .= $inline ? '".'.$php_variable.'."' : $php_variable;
            return true;
        }else{
            return false;
        }
    }

    //-----------------------------------------
    //  SINTAGS HELPER SINGLE QUOTES PARAMETER
    //-----------------------------------------
    public function SingleQuote($match, $state) {
        return $this->_handleQuotedParam($match, $state, "'");
    }

    //-----------------------------------------
    //  SINTAGS HELPER DOUBLE QUOTES PARAMETER
    //-----------------------------------------
    public function DoubleQuote($match, $state) {
        return $this->_handleQuotedParam($match, $state, '"');
    }

    public function _handleQuotedParam($match, $state, $quote_using) {
        if(AK_LEXER_ENTER === $state){
            $this->output .= $quote_using;
        }
        if(AK_LEXER_UNMATCHED === $state){
            $this->output .= $match;
        }
        if(AK_LEXER_EXIT === $state){
            $this->output .= $quote_using;
        }
        return true;
    }

    //-----------------------------------------
    //  SINTAGS HELPER NUMBER PARAMETER
    //-----------------------------------------
    public function Numbers($match, $state) {
        if(AK_LEXER_SPECIAL === $state){
            $this->output .= $match;
        }
        return true;
    }

    //-----------------------------------------
    //  SINTAGS HELPER RUBY STYLE SYMBOLS
    //-----------------------------------------
    public function Symbol($match, $state) {
        if(AK_LEXER_SPECIAL === $state){
            $this->output .= "'".ltrim($match,': ')."'";
        }
        return true;
    }

    //-----------------------------------------
    //  SINTAGS HELPER RUBY STYLE STRUCTS
    //-----------------------------------------
    public function Struct($match, $state) {
        if(AK_LEXER_SPECIAL === $state){
            $this->output .= $match == '[' ? 'array(' : ')';
        }
        return true;
    }


    //-----------------------------------------
    //  SINTAGS HELPER RUBY HASHES
    //-----------------------------------------
    public function Hash($match, $state) {
        switch ($state){
            case AK_LEXER_ENTER:
                $this->_inside_array = true;
                $this->output .= 'array(';
                break;
            case AK_LEXER_UNMATCHED:
                $match = trim($match);
                if($match == $this->_SINTAGS_HASH_KEY_VALUE_DELIMITER){
                    $this->output .= ' => ';
                }elseif($match == ','){
                    $this->output .= ', ';
                }
                break;
            case AK_LEXER_EXIT:
                $this->_inside_array = false;
                $this->output .= ')';
                break;
        }
        return true;
    }


    //-----------------------------------------
    //  SINTAGS BLOCKS
    //-----------------------------------------
    public function Block($match, $state) {
        switch ($state){
            case AK_LEXER_ENTER:
                $this->_block = '';
                $this->_block_params = array();
                $this->_block_data = array();
                if(strstr($match, '=')){
                    list($parameters, $match) = explode('=', $match);
                    $parameters = array_diff(array_map('trim', Ak::toArray(trim($parameters,' (){|'.$this->_SINTAGS_OPEN_HELPER_TAG))), array(''));
                    foreach ($parameters as $parameter){
                        if($parameter = $this->_convertSintagsVarToPhp($parameter)){
                            $this->_block_params[] = $parameter;
                        }else{
                            return false;
                        }
                    }
                }
                $method_or_var_names = array_diff(array_map('trim', Ak::toArray(trim($match,' (){|'.$this->_SINTAGS_OPEN_HELPER_TAG))), array(''));
                foreach ($method_or_var_names as $method_or_var_name){
                    if($helper = $this->_getHelperNameForMethod($method_or_var_name)){
                        if(!strpos($helper, 'helper')){
                            $method_or_var_name = AkInflector::variablize($method_or_var_name);
                        }
                        $this->_block_data[] = "\${$helper}->$method_or_var_name()";
                        return true;
                    }elseif(!strstr($match,'(') && $php_variable = $this->_convertSintagsVarToPhp($method_or_var_name)){
                        $this->_block_data[] = $php_variable;
                    }else{
                        $this->addError(Ak::t('Could not find a helper to handle the method "%method" you called in your view', array('%method'=>$method_or_var_name)));
                    }
                }

                break;
            case AK_LEXER_MATCHED:
                $this->_block_keys = array();
                $parameters = Ak::toArray($match);
                foreach ($parameters as $parameter){
                    if($parameter = $this->_convertSintagsVarToPhp($parameter)){
                        $this->_block_keys[] = $parameter;
                    }else{
                        return false;
                    }
                }
                break;
            case AK_LEXER_UNMATCHED:
                $this->_block .= $match;
                break;
            case AK_LEXER_EXIT:

                $this->output .= "<?php \n";
                foreach ($this->_block_data as $k=>$block_data){
                    if(strstr($block_data,'->')){
                        /**
                         * @todo Implement helper calls on blocks
                         */
                    }else{
                        $this->output .= "if(!empty($block_data)){\n";
                        if(!empty($this->_block_params[$k])){
                            $this->output .= "    {$this->_block_params[$k]} = array();\n";
                        }
                        $this->output .= "    foreach (array_keys((array)$block_data) as \$ak_sintags_key){\n";
                        if(count($this->_block_keys) == 1){
                            $this->output .= "        {$this->_block_keys[0]} =& {$block_data}[\$ak_sintags_key];\n";
                        }
                        $this->output .= "       $this->_block;\n";
                        if(!empty($this->_block_params[$k])){
                            $this->output .= "        {$this->_block_params[$k]}[\$ak_sintags_key] = {$block_data}[\$ak_sintags_key];\n";
                        }
                        $this->output .= "    }\n";
                        $this->output .= "}";
                    }
                }
                $this->output .= "?>";

                return true;
        }

        return true;
    }

    public function addError($error) {
        $this->_errors[] = $error;
    }

    public function _tokenizeHelperStructures($raw_structures) {
        $i = 1;
        $arrays = array();
        while(preg_match('/\x5B(?!.*\x5B+.*)[^\x5D]+\x5D/',$raw_structures,$match)){
            $token = '___SINTAGS_TOKEN_POS___'.$i;
            $raw_structures = str_replace($match[0],$token,$raw_structures);
            $arrays[$token] = 'array('.trim($match[0],'[]').')';
            $i++;
        }
        if(!empty($arrays)){
            krsort($arrays);
            return str_replace(array_keys($arrays), array_values($arrays), $raw_structures);
        }else{
            return $raw_structures;
        }
    }


    private function _getAvailableHelpers() {
        $helpers = array();
        if(empty($this->available_helpers)){
            if(defined('AK_SINTAGS_AVAILABLE_HELPERS')){
                $helpers = unserialize(AK_SINTAGS_AVAILABLE_HELPERS);
            }elseif($this->_HelperLoader){
                $this->_HelperLoader->instantiateHelpers();
                if($underscored_helper_names = AkHelperLoader::getInstantiatedHelperNames()){
                    foreach ($underscored_helper_names as $underscored_helper_name){
                        $helper_class_name = AkInflector::camelize($underscored_helper_name);
                        if(class_exists($helper_class_name)){
                            $methods = get_class_methods($helper_class_name);
                            $vars = get_class_vars($helper_class_name);
                            if (isset($vars['dynamic_helpers'])) {
                                $dynamic_helpers = Ak::toArray($vars['dynamic_helpers']);
                                foreach ($dynamic_helpers as $method_name){
                                    $this->dynamic_helpers[$method_name] = $underscored_helper_name;
                                }
                            }
                            foreach (get_class_methods($helper_class_name) as $method_name){
                                if($method_name[0] != '_'){
                                    $helpers[$method_name] = $underscored_helper_name;
                                }
                            }
                        }
                    }
                    $helpers['render'] = 'controller';
                    $helpers['render_partial'] = 'controller';
                }
            }
            $this->available_helpers = $helpers;
        }
        return $this->available_helpers;

    }

    private function _getHelperNameForMethod(&$method_name) {
        if($method_name == '_'){
            $method_name = 'translate';
        }
        $this->_getAvailableHelpers();
        //return empty($this->available_helpers[$method_name]) ? false : $this->available_helpers[$method_name];
        if(empty($this->available_helpers[$method_name])) {
            if (!empty($this->dynamic_helpers)) {
                foreach($this->dynamic_helpers as $regex => $helper) {
                    $regex = trim($regex,'/');
                    if (@preg_match('/'.$regex.'/', $method_name)) {
                        return $helper;
                    }
                }
            }
            return false;
        } else {
            return $this->available_helpers[$method_name];
        }
    }

    private function _loadLexer(){
        static $Lexer;
        if(empty($Lexer)){
            $this->_Lexer = new $this->_lexer_name($this);
            $Lexer = $this->_Lexer;
        }else{
            $this->_Lexer = $Lexer;
            $this->_Lexer->init($this);
        }
    }

}

