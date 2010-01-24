<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

class AkSintagsLexer extends AkLexer
{
    public $_SINTAGS_REMOVE_PHP_SILENTLY = AK_SINTAGS_REMOVE_PHP_SILENTLY;
    public $_SINTAGS_HIDDEN_COMMENTS_TAG = AK_SINTAGS_HIDDEN_COMMENTS_TAG;

    public $_SINTAGS_OPEN_HELPER_TAG = AK_SINTAGS_OPEN_HELPER_TAG;
    public $_SINTAGS_CLOSE_HELPER_TAG = AK_SINTAGS_CLOSE_HELPER_TAG;

    public $_modes = array(
    'Xml',
    'Php',
    'Comment',
    'Block',
    'Helper',
    'EscapedText',
    'HelperTranslation',
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

    public function __construct(&$parser) {
        parent::__construct($parser, 'Text');
        $this->mapHandler('Text', 'Text');
        foreach ($this->_modes as $mode){
            $this->{'_add'.$mode.'Tokens'}();
        }
    }

    public function _addXmlTokens() {
        $this->addSpecialPattern('<\?xml','Text','XmlOpening');
    }

    public function _addPhpTokens() {
        if(!$this->_SINTAGS_REMOVE_PHP_SILENTLY){
            $this->addEntryPattern('<\?','Text','PhpCode');
            $this->addExitPattern('\?>','PhpCode');
        }else{
            $this->mapHandler('php', 'ignore');
            $this->addEntryPattern('<\?', 'Text', 'php');
            $this->addExitPattern('\?>', 'php');
        }
    }

    public function _addCommentTokens() {
        if(!empty($this->_SINTAGS_HIDDEN_COMMENTS_TAG)){
            $this->mapHandler('comment', 'ignore');
            $this->addEntryPattern("<$this->_SINTAGS_HIDDEN_COMMENTS_TAG>", 'Text', 'comment');
            $this->addExitPattern("</$this->_SINTAGS_HIDDEN_COMMENTS_TAG>", 'comment');
        }
    }

    public function _addEscapedTextTokens() {
        $this->addSpecialPattern('\x5C_(?={)','Text','EscapedText');
    }

    public function _addTranslationTokens() {
        $this->addEntryPattern('_{','Text','Translation');
        $this->addExitPattern('}','Translation');

        $this->addSpecialPattern('\x5C?\x25\x5C?[A-Za-z][\.A-Za-z0-9_-]*','Translation','TranslationToken');
    }

    public function _addHelperTranslationTokens() {
        $this->addEntryPattern('_\'','Hash','HelperTranslation');
        $this->addEntryPattern('_\'','Helper','HelperTranslation');
        $this->addExitPattern('\'','HelperTranslation');

        $this->addSpecialPattern('\x5C?\x25\x5C?[A-Za-z][\.A-Za-z0-9_-]*','HelperTranslation','TranslationToken');
    }

    public function _addVariableTranslationTokens() {
        $this->addSpecialPattern('{_[A-Za-z][\.A-Za-z0-9_-]*}','Text','VariableTranslation');
    }

    public function _addVariableTokens() {
        $this->addSpecialPattern('{\\\?[A-Za-z][\.A-Za-z0-9_-]*}','Text','Variable');
    }

    public function _addConditionalVariableTokens() {
        $this->addSpecialPattern('{\\\?[A-Za-z][\.A-Za-z0-9_-]*\?}','Text','ConditionalVariable');
    }

    public function _addConditionStartTokens() {
        $this->addSpecialPattern('{[\?!][A-Za-z][\.A-Za-z0-9_-]*}','Text','ConditionStart');
    }

    public function _addEndTagTokens() {
        $this->addSpecialPattern('{end}','Text','EndTag');
    }

    public function _addElseTagTokens() {
        $this->addSpecialPattern('{else}','Text','ElseTag');
    }

    public function _addLoopTokens() {
        $this->addSpecialPattern('{loop[ \n\t]+[A-Za-z][\.A-Za-z0-9_-]*\??}','Text','Loop');
    }

    public function _addLoopAsTokens() {
        $this->addSpecialPattern('{loop[ \n\t]+[A-Za-z][\.A-Za-z0-9_-]+[ \n\t]+as[ \n\t]+[A-Za-z][\.A-Za-z0-9_-]*\??}','Text','Loop');
    }

    public function _addBlockTokens() {
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

    public function _addHelperTokens() {
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


    public function _addInlineHelperTokens() {
        $this->addSpecialPattern('#{[A-Za-z][\.A-Za-z0-9_-]*}','DoubleQuote','InlineVariable');

        $this->addEntryPattern('#{[ \n\t]*[A-Za-z0-9_]+[ \n\t]*\x28?[ \n\t]*(?=.*})','DoubleQuote','InlineHelper');
        $this->addExitPattern('[ \n\t]*}', 'InlineHelper');
        $this->_addSintagsHelperParametersForScope('InlineHelper');
    }

    public function _addSintagsHelperParametersForScope($scope = 'Helper') {
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

