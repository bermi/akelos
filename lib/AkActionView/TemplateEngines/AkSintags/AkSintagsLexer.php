<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2007, Akelos Media, S.L.  & Bermi Ferrer Martinez |
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
 * @package ActionView
 * @subpackage Sintags
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

class AkSintagsLexer extends AkLexer
{
    var $_SINTAGS_REMOVE_PHP_SILENTLY = AK_SINTAGS_REMOVE_PHP_SILENTLY;
    var $_SINTAGS_HIDDEN_COMMENTS_TAG = AK_SINTAGS_HIDDEN_COMMENTS_TAG;

    var $_SINTAGS_OPEN_HELPER_TAG = AK_SINTAGS_OPEN_HELPER_TAG;
    var $_SINTAGS_CLOSE_HELPER_TAG = AK_SINTAGS_CLOSE_HELPER_TAG;

    var $_modes = array(
    'Xml',
    'Php',
    'Comment',
    'Block',
    'Helper',
    'EscapedText',
    'Translation',
    'VariableTranslation',
    'EndTag',
    'ElseTag',
    'ConditionStart',
    'ConditionalVariable',
    'Variable',
    'Loop',
    'LoopAs',
    'Helper',
    'InlineHelper',
    );

    function AkSintagsLexer(&$parser)
    {
        $this->AkLexer($parser, 'Text');
        $this->mapHandler('Text', 'Text');
        foreach ($this->_modes as $mode){
            $this->{'_add'.$mode.'Tokens'}();
        }
    }

    function _addXmlTokens()
    {
        $this->addSpecialPattern('<\?xml','Text','XmlOpening');
    }

    function _addPhpTokens()
    {
        if(!$this->_SINTAGS_REMOVE_PHP_SILENTLY){
            $this->addEntryPattern('<\?','Text','PhpCode');
            $this->addExitPattern('\?>','PhpCode');
        }else{
            $this->mapHandler('php', 'ignore');
            $this->addEntryPattern('<\?', 'Text', 'php');
            $this->addExitPattern('\?>', 'php');
        }
    }

    function _addCommentTokens()
    {
        if(!empty($this->_SINTAGS_HIDDEN_COMMENTS_TAG)){
            $this->mapHandler('comment', 'ignore');
            $this->addEntryPattern("<$this->_SINTAGS_HIDDEN_COMMENTS_TAG>", 'Text', 'comment');
            $this->addExitPattern("</$this->_SINTAGS_HIDDEN_COMMENTS_TAG>", 'comment');
        }
    }

    function _addEscapedTextTokens()
    {
        $this->addSpecialPattern('\x5C_(?={)','Text','EscapedText');
    }

    function _addTranslationTokens()
    {
        $this->addEntryPattern('_{','Text','Translation');
        $this->addExitPattern('}','Translation');

        $this->addSpecialPattern('\x5C?\x25[A-Za-z][\.A-Za-z0-9_-]*','Translation','TranslationToken');
    }

    function _addVariableTranslationTokens()
    {
        $this->addSpecialPattern('{_[A-Za-z][\.A-Za-z0-9_-]*}','Text','VariableTranslation');
    }

    function _addVariableTokens()
    {
        $this->addSpecialPattern('{[A-Za-z][\.A-Za-z0-9_-]*}','Text','Variable');
    }

    function _addConditionalVariableTokens()
    {
        $this->addSpecialPattern('{[A-Za-z][\.A-Za-z0-9_-]*\?}','Text','ConditionalVariable');
    }

    function _addConditionStartTokens()
    {
        $this->addSpecialPattern('{[\?!][A-Za-z][\.A-Za-z0-9_-]*}','Text','ConditionStart');
    }

    function _addEndTagTokens()
    {
        $this->addSpecialPattern('{end}','Text','EndTag');
    }

    function _addElseTagTokens()
    {
        $this->addSpecialPattern('{else}','Text','ElseTag');
    }

    function _addLoopTokens()
    {
        $this->addSpecialPattern('{loop[ \n\t]+[A-Za-z][\.A-Za-z0-9_-]*\??}','Text','Loop');
    }

    function _addLoopAsTokens()
    {
        $this->addSpecialPattern('{loop[ \n\t]+[A-Za-z][\.A-Za-z0-9_-]+[ \n\t]+as[ \n\t]+[A-Za-z][\.A-Za-z0-9_-]*\??}','Text','Loop');
    }

    function _addBlockTokens()
    {
        if(!$this->_SINTAGS_REMOVE_PHP_SILENTLY){
            $this->addEntryPattern($this->_SINTAGS_OPEN_HELPER_TAG.'[ \n\t]*[A-Za-z][\.A-Za-z0-9_ ,=-]*[ \n\t]*\x7B[ \n\t]*\x7c','Text', 'Block');
            $this->addPattern('[A-Za-z0-9_, \n\t\x7c]+[ \n\t]*\x7c','Block');
            $this->addExitPattern('\x7D[ \n\t]*'.$this->_SINTAGS_CLOSE_HELPER_TAG, 'Block');
        }else{
            $this->mapHandler('php', 'ignore');
            $this->addEntryPattern($this->_SINTAGS_OPEN_HELPER_TAG.'[ \n\t]*[A-Za-z][\.A-Za-z0-9_ ,=-]*[ \n\t]*\x7B[ \n\t]*\x7c','Text', 'ignore');
            $this->addPattern('[A-Za-z0-9_, \n\t\x7c]+[ \n\t]*\x7c','ignore');
            $this->addExitPattern('\x7D[ \n\t]*'.$this->_SINTAGS_CLOSE_HELPER_TAG, 'ignore');
        }




    }

    function _addHelperTokens()
    {
        $this->addEntryPattern($this->_SINTAGS_OPEN_HELPER_TAG.'\x3D?[ \n\t]*[A-Za-z0-9_]+[ \n\t\x3D]*\x28?[ \n\t]*'.
        '(?=.*'.$this->_SINTAGS_CLOSE_HELPER_TAG.')','Text','Helper');
        $this->addExitPattern('\x29?[ \n\t]*'.$this->_SINTAGS_CLOSE_HELPER_TAG, 'Helper');

        $this->addSpecialPattern('#{[A-Za-z][\.A-Za-z0-9_-]*}','DoubleQuote','InlineVariable');

        $this->addEntryPattern('#{[ \n\t]*[A-Za-z0-9_]+[ \n\t]*\x28?[ \n\t]*(?=.*})','DoubleQuote','InlineHelper');
        $this->addExitPattern('[ \n\t]*}', 'InlineHelper');
        $this->_addSintagsHelperParametersForScope('InlineHelper');

        $this->_addSintagsHelperParametersForScope('Helper');
        $this->_addSintagsHelperParametersForScope('Hash');
        $this->_addSintagsHelperParametersForScope('HelperFunction');
        $this->_addSintagsHelperParametersForScope('Struct');
    }


    function _addInlineHelperTokens()
    {
        $this->addSpecialPattern('#{[A-Za-z][\.A-Za-z0-9_-]*}','DoubleQuote','InlineVariable');

        $this->addEntryPattern('#{[ \n\t]*[A-Za-z0-9_]+[ \n\t]*\x28?[ \n\t]*(?=.*})','DoubleQuote','InlineHelper');
        $this->addExitPattern('[ \n\t]*}', 'InlineHelper');
        $this->_addSintagsHelperParametersForScope('InlineHelper');
    }

    function _addSintagsHelperParametersForScope($scope = 'Helper')
    {
        $this->addEntryPattern('[A-Za-z][A-Za-z0-9_]*[ \n\t]*\x28(?=.*\x29)',$scope,'HelperFunction');
        $this->addExitPattern('\x29', 'HelperFunction');

        $this->addEntryPattern('_[ \n\t]*\x28(?=.*\x29)',$scope,'HelperFunction');
        $this->addExitPattern('\x29', 'HelperFunction');

        $this->addEntryPattern("\x7B", $scope, 'Hash');
        $this->addExitPattern("\x7D", 'Hash');

        $this->addEntryPattern('"', $scope, 'DoubleQuote');
        $this->addPattern("\\\\\"", 'DoubleQuote');
        $this->addExitPattern('"', 'DoubleQuote');


        $this->addEntryPattern("'", $scope, 'SingleQuote');
        $this->addPattern("\\\\'", 'SingleQuote');
        $this->addExitPattern("'", 'SingleQuote');

        $this->addSpecialPattern('[0-9]+[\.0-9]*', $scope, 'Numbers');

        $this->addSpecialPattern('true|false|null', $scope, 'Text');

        $this->addSpecialPattern('\x3A[A-Za-z0-9_]+',$scope,'Symbol');

        $this->addSpecialPattern('@?[A-Za-z][\.A-Za-z0-9_-]*',$scope,'HelperVariable');


        $this->addSpecialPattern('\x5B',$scope,'Struct');
        $this->addSpecialPattern('\x5D',$scope,'Struct');
    }
}

?>