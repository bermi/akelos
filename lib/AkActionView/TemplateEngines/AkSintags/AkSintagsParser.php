<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2007, Akelos Media, S.L.  & Bermi Ferrer Martinez |
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
 * @package AkelosFramework
 * @subpackage AkActionView
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

class AkSintagsParser
{

    var $_SINTAGS_OPEN_HELPER_TAG = AK_SINTAGS_OPEN_HELPER_TAG;
    var $_SINTAGS_CLOSE_HELPER_TAG = AK_SINTAGS_CLOSE_HELPER_TAG;
    var $_SINTAGS_HASH_KEY_VALUE_DELIMITER = AK_SINTAGS_HASH_KEY_VALUE_DELIMITER;
    
    var $_Lexer;
    var $_lexer_name = 'AkSintagsLexer';
    var $_mode;
    var $_last_match;
    var $_matches;
    var $_current_match;
    var $output;
    var $escape_chars = array(
    '\{' => '____AKST_OT____',
    '\}' => '____AKST_CT____',
    '\"' => '____AKST_DQ____',
    "\'" => '____AKST_SQ____'
    );

    function AkSintagsParser($mode = 'Text')
    {
        $this->_Lexer =& new $this->_lexer_name($this);
        $this->_mode = $mode;
        $this->_matches = array();
        $this->_last_match = '';
        $this->_current_match = '';
    }

    function parse($raw)
    {
        $this->_Lexer->parse($this->beforeParsing($this->_escapeChars($raw)));
        return $this->afterParsing($this->getResults());
    }

    function beforeParsing($raw)
    {
        return $raw;
    }

    function afterParsing($parsed)
    {
        return $parsed;
    }

    function ignore($match, $state)
    {
        return true;
    }

    //------------------------------------
    //  PHP CODE
    //------------------------------------
    function PhpCode($match, $state)
    {
        if(!AK_SINTAGS_REPLACE_SHORTHAND_PHP_TAGS){
            $this->output .= $match;
            return true;
        }
        switch ($state){
            case AK_LEXER_ENTER:
            $this->output .= '<?php ';
            break;
            case AK_LEXER_UNMATCHED:
            $match = ltrim($match);
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
    function XmlOpening($match, $state)
    {
        if(AK_LEXER_SPECIAL === $state){
            $this->output .= '<?php echo \'<?xml\'; ?>';
        }
        return true;
    }

    //------------------------------------
    //  PLAIN TEXT
    //------------------------------------

    function Text($text)
    {
        $this->output .= $text;
        return true;
    }

    // UTILS
    function getResults()
    {
        return $this->_unescapeChars($this->output);
    }
    function _escapeChars($string)
    {
        return str_replace(array_keys($this->escape_chars),array_values($this->escape_chars),$string);
    }
    function _unescapeChars($string, $strip_slashes_from_tokens = false)
    {
        $replacements = $strip_slashes_from_tokens ? array_map('stripcslashes',array_keys($this->escape_chars)) : array_keys($this->escape_chars);
        return str_replace(array_values($this->escape_chars),$replacements,$string);
    }

    //------------------------------------
    //  TRANSLATIONS
    //------------------------------------

    function Translation($match, $state)
    {
        switch ($state){
            case AK_LEXER_ENTER:
            $this->_translation_tokens = array();
            $this->output .= '<?php echo $text_helper->translate(\'';
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
    //  TRANSLATIONS TOKEN
    //------------------------------------

    function TranslationToken($match)
    {
        $this->output.= ltrim($match,'\\');
        $php_variable = $this->_convertSintagsVarToPhp(trim($match,'%'));
        if($match[0] != '\\' && $php_variable){
            $this->_translation_tokens[] = '\''.$match.'\' => @'.$php_variable;
        }
        return true;
    }



    //------------------------------------
    //  VARIABLE TRANSLATIONS
    //------------------------------------

    function VariableTranslation($match, $state)
    {
        $php_variable = $this->_convertSintagsVarToPhp(trim($match,'{_}?'));
        if($php_variable){
            $this->output .= '<?php echo empty('.$php_variable.') || !is_array('.$php_variable.') ? \'\' : $text_helper->translate('.$php_variable.'); ?>';
        }else{
            $this->output .= $match;
        }
        return true;
    }


    //------------------------------------
    //  SINTAGS CONDITIONAL VARIABLES
    //------------------------------------

    function ConditionalVariable($match, $state)
    {
        $php_variable = $this->_convertSintagsVarToPhp(trim($match,'{}?'));
        if($php_variable){
            $this->output .= '<?php echo empty('.$php_variable.') ? \'\' : '.$php_variable.'; ?>';
        }else{
            $this->output .= $match;
        }
        return true;
    }



    //------------------------------------
    //  SINTAGS VARIABLES
    //------------------------------------

    function Variable($match, $state)
    {
        $php_variable = $this->_convertSintagsVarToPhp($match);
        if($php_variable){
            $this->output .= '<?php echo '.$php_variable.'; ?>';
        }else{
            $this->output .= $match;
        }
        return true;
    }


    function _convertSintagsVarToPhp($var)
    {
        if(preg_match('/[\.-]_/',$var)){
            return false;
        }
        $var = str_replace(array('-','.'),array('~','->'),trim($var,'-_.{}@'));
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

    function ConditionStart($match, $state)
    {
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

    function EndTag($match, $state)
    {
        if(AK_LEXER_SPECIAL === $state){
            $this->output .= '<?php } ?>';
        }
        return true;
    }

    //------------------------------------
    //  SINTAGS ELSE TAG
    //------------------------------------

    function ElseTag($match, $state)
    {
        if(AK_LEXER_SPECIAL === $state){
            $this->output .= '<?php } else { ?>';
        }
        return true;
    }


    //------------------------------------
    //  SINTAGS LOOP
    //------------------------------------

    function Loop($match, $state)
    {
        if(AK_LEXER_SPECIAL === $state){
            $sintags_var = rtrim(substr($match, 6,-1),'?');
            $php_variable = $this->_convertSintagsVarToPhp($sintags_var);
            if($php_variable){
                $php_variable = $php_variable;
                $termination = $this->_getTerminationName($sintags_var);
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

    function _getTerminationName($plural)
    {
        return substr($plural,max(strpos($plural,'.'),strpos($plural,'-'),-1)+1);
    }


    //------------------------------------
    //  SINTAGS HELPER MODE
    //------------------------------------

    function Helper($match, $state, $position = null, $is_inline_function = false)
    {
        switch ($state){
            case AK_LEXER_ENTER:
            $method_name = trim($match,' =('.$this->_SINTAGS_OPEN_HELPER_TAG);
            if($helper = $this->_getHelperNameForMethod($method_name)){
                $this->avoid_php_tags = !$is_inline_function && !strstr($match,'=');
                $this->_current_function_opening = strlen($this->output);
                if(!$this->avoid_php_tags){
                    $this->output .= $is_inline_function ? '' : '<?php echo ';
                }
                $this->output .= "\${$helper}->$method_name(";
                return true;
            }else{
                trigger_error(Ak::t('Could not find a helper to handle the method "%method" you called using Sintags in your view', array('%method'=>$method_name)), E_USER_NOTICE);
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

    function HelperFunction($match, $state, $position = null)
    {
        return $this->Helper($match, $state, $position, true);
    }

    //------------------------------------
    //  SINTAGS INLINE HELPER MODE
    //------------------------------------

    function InlineHelper($match, $state, $position = null)
    {
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

    function InlineVariable($match, $state, $position = null)
    {
        $php_variable = $this->_convertSintagsVarToPhp(trim($match,'#{}'));
        if($php_variable){
            $this->output .= '".'.$php_variable.'."';
        }
        return true;
    }

    //------------------------------------
    //  SINTAGS VARIABLES
    //------------------------------------

    function HelperVariable($match, $state, $position = null, $inline = false)
    {
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
    function SingleQuote($match, $state)
    {
        return $this->_handleQuotedParam($match, $state, "'");
    }

    //-----------------------------------------
    //  SINTAGS HELPER DOUBLE QUOTES PARAMETER
    //-----------------------------------------
    function DoubleQuote($match, $state)
    {
        return $this->_handleQuotedParam($match, $state, '"');
    }

    function _handleQuotedParam($match, $state, $quote_using)
    {
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
    function Numbers($match, $state)
    {
        if(AK_LEXER_SPECIAL === $state){
            $this->output .= $match;
        }
        return true;
    }

    //-----------------------------------------
    //  SINTAGS HELPER RUBY STYLE SYMBOLS
    //-----------------------------------------
    function Symbol($match, $state)
    {
        if(AK_LEXER_SPECIAL === $state){
            $this->output .= "'".ltrim($match,': ')."'";
        }
        return true;
    }

    //-----------------------------------------
    //  SINTAGS HELPER RUBY STYLE STRUCTS
    //-----------------------------------------
    function Struct($match, $state)
    {
        if(AK_LEXER_SPECIAL === $state){
            $this->output .= $match == '[' ? 'array(' : ')';
        }
        return true;
    }


    //-----------------------------------------
    //  SINTAGS HELPER RUBY HASHES
    //-----------------------------------------
    function Hash($match, $state)
    {
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

    function _tokenizeHelperStructures($raw_structures)
    {
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


    function _getAvailableHelpers()
    {
        $helpers = array();
        if(empty($this->available_helpers)){
            if(defined('AK_SINTAGS_AVALABLE_HELPERS')){
                $helpers = unserialize(AK_SINTAGS_AVALABLE_HELPERS);
            }elseif (defined('AK_ACTION_CONTROLLER_AVAILABLE_HELPERS')){
                $underscored_helper_names = Ak::toArray(AK_ACTION_CONTROLLER_AVAILABLE_HELPERS);
                foreach ($underscored_helper_names as $underscored_helper_name){
                    $helper_class_name = AkInflector::camelize($underscored_helper_name);
                    if(class_exists($helper_class_name)){
                        foreach (get_class_methods($helper_class_name) as $method_name){
                            if($method_name[0] != '_'){
                                $helpers[$method_name] = $underscored_helper_name;
                            }
                        }
                    }
                }
                $helpers['render'] = 'controller';
            }
            $this->available_helpers = $helpers;
        }
        return $this->available_helpers;

    }

    function _getHelperNameForMethod(&$method_name)
    {
        if($method_name == '_'){
            $method_name = 'translate';
        }
        $this->_getAvailableHelpers();
        return empty($this->available_helpers[$method_name]) ? false : $this->available_helpers[$method_name];
    }

}

?>