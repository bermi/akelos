<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2006, Akelos Media, S.L.  & Bermi Ferrer Martinez |
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
 * @package AkelosFramework
 * @subpackage AkActionView
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

require_once(AK_LIB_DIR.DS.'Ak.php');

/**
 * Sintags, The Akelos Framework special syntax for view Templates
 * 
 * The Akelos Framework uses PHP as its language for templates, but some times looping and printing PHP var becomes a repetitive task that makes your view templates look not beauty.
 * 
 * The Akelos Framework doesn't want you force you to learn another useless template language. You have the power of PHP on your templates. But in some cases there's need for a graphic designer to create templates for views, so we have added a very limited syntax that allows you to create simple but powerful templates on WYSIWYG HTML editors.
 * 
 * This special syntax is known as Sintags (where "Sin" comes from the Spanish word for without) wich is a set of rules on your view using only "?", "_", "{", "}", "end", "." and "-" characters. Parsed Sintag is converted to PHP code and cached as a .php file for better performance.
 * 
 * Sintags elements
 * 
 *     * { Starts a Sintag block
 *     * } Ends a Sintag block
 *     * {var_name?} Asserts if given element has been set by the controller and prints the value of "var_name"
 *     * {?var_name} Asserts if element is not empty and starts a php condition block like "if(!empty($var_name)) {". IMPORTANT NOTE, You need to close this blocks using "{end}" or <?php } ?>
 *     * {end} Closes a block generating <?php } ?>
 *     * {object.attribute} "." is used for accessing object attributes. This is the same as <?php echo $object->attribute; ?>
 *     * {array-key} "-" is used for accessing array on a specific key. This is the same as <?php echo $array['key']; ?>
 *     * _{Multilingual text} _{ } will enclose a text for internationalization
 *     * {_multilingual_var} {_ } will enclose a variable for internationalization. This variable must actually be an array with the locale code as key
 * 
 *       Most of this template syntax has been added to make easy display of Active Record on the views, and handling internationalization with easy, therefore it stands on the following basic rules.
 *       Printing variables
 * 
 *       Sintags:
 *       {comment}
 * 
 *       PHP CODE:
 *       <?php 
 *        echo $comment;
 *       ?>
 * 
 *       Printing object attributes
 * 
 *       Sintags:
 *       {post.Comments}
 *       {post.Comments.latest}
 * 
 *       PHP CODE:
 *       <?php 
 *        echo $post->Comments;
 *       ?>
 *       <?php 
 *        echo $post->Comments->latest;
 *       ?>
 * 
 *       Printing array elements
 * 
 *       Sintags:
 *       {people-members}
 *       {people-0-member}
 * 
 *       PHP CODE:
 *       <?php 
 *        echo $people['members'];
 *       ?>
 *       <?php 
 *        echo $people[0]['member'];
 *       ?>
 * 
 *       Printing a mix of arrays and objects
 * 
 *       Sintags:
 *       {people.members-0.name}
 *       {posts-latest.created_at}
 * 
 *       PHP CODE:
 *       <?php 
 *        echo $people->members[0]->name;
 *       ?>
 *       <?php 
 *        echo $posts['latest']->created_at;
 *       ?>
 * 
 *       Create a condition block if a element is has a value on it (using PHPs empty call)
 * 
 *       Sintags:
 *       {?post-comments}
 *           {post.comment.details}
 *       {end}
 * 
 *       PHP CODE:
 *       <?php 
 *        if(!empty($post['comments'])) { 
 *       ?>
 *           <?php 
 *        echo $post->comment->details;
 *       ?>
 *       <?php } ?>
 * 
 *       Attempt to print a variable only if has been set by the controller
 * 
 *       Sintags:
 *       {Post.comment.details?}
 * 
 *       PHP CODE:
 *       <?php 
 *        echo isset($Post->comment->details) ? $Post->comment->details : '';
 *       ?>
 * 
 *       Looping over arrays and Active Record collections
 * 
 *       Sintags:
 *       {loop posts}
 *            {post.comment?} {post.author?} 
 *       {end}
 * 
 *       PHP CODE:
 *       <?php 
 *        empty($posts) ? null : $post_loop_counter = 0;
 *        if(!empty($posts))
 *            foreach ($posts as $post_loop_key=>$post){
 *                $post_loop_counter++;
 *                $post_odd_position = $post_loop_counter%2;
 *       ?>
 *            <?php 
 *        echo isset($post->comment) ? $post->comment : '';
 *       ?> <?php 
 *        echo isset($post->author) ? $post->author : '';
 *       ?> 
 *       <?php } ?>
 * 
 *       We need to assign a plural "posts" on the {loop *} tag and fetch a singular "post" for retrieving single element details
 * 
 *       As you have seen, generated PHP code comes with some helpful vars automatically added that are useful when iterating collections:
 * 
 *       Replace * with the singular form of the array name we are iterating
 *           o *_loop_counter Holds current iteration pass stating on 1 (something like the tedious ++; on every iteration we usually code
 *           o *_loop_key Holds current array key
 *           o *_odd_position Holds true or false boolean to help us on making nice row color changes
 *       Getting a database stored multilingual value for an active record field
 * 
 *       Sintags:
 *       {_post.comment}
 * 
 *       PHP CODE:
 *       <?php 
 *        echo empty($post->comment) || !is_array($post->comment)? '' : $text_helper->translate($post->comment);
 *       ?>
 * 
 *       Just by adding "_" after "{" you will get the translated version of "comment". In case you have English and Spanish comments you must have the following columns in your database "en_comment" and "es_comment".
 *       
 *       Note that $post->comment actually holds an array with the locale code as key, so you can set your own localized arrays that will not be added to the locale/ files
 * 
 * 
 *       Getting a multilingual value for a string
 * 
 *       Sintags:
 *       <h1>_{Ain't easy to translate text?}</h1>
 *       _{<p<It's really simple even to add 
 *       <a href='http://google.co.uk'>Localized links</a>
 *       </p>}
 * 
 *       PHP CODE:
 *       <h1><?php echo $text_helper->translate('Ain\'t easy to translate text?'); ?></h1>
 *       <?php echo $text_helper->translate('<p>It\'s really simple even to add 
 *       <a href=\'http://google.co.uk\'>Localized links</a>
 *       </p>'); ?>
 * 
 *       Using this function, locales are added automatically to your app/locales/CONTROLLER_NAME/ folder. Note that the multilingual text must written in the framework default language (English by default)
 *       Escaping {} from your translated text
 * 
 *       Sintags:
 *       _{I need to print \{something_inside_curly_brackets\}. _\{Maybe a multilingual text example\} }
 * 
 *       PHP CODE:
 *       <?php echo $text_helper->translate('I need to print {something_inside_curly_brackets}. _{Maybe a multilingual text example} ', array(), $controller_name); ?>
 * 
 *       You just need to add a backslash before open and close curly brackets in order to escape it
 *       Colophon
 * 
 *       Remember that Sintags is not meant to replace PHP, but to speed up development. It's good to use this syntax because it keeps views more clear. So if you start to seeing to much PHP code on your view, consider moving some view logic
 */
class AkSintags
{
    var $_code;
    var $_tokens = array();
    var $_token_options = array();
    var $_Inflector;

    function init($options = array())
    {
        $default_options = array(
        'inflector' => 'AkInflector',
        'file_path' => false
        );

        $options = array_merge($default_options, $options);

        $this->_code = $options['code'];
        require_once(AK_LIB_DIR.DS.$options['inflector'].'.php');
        $this->_Inflector = Ak::singleton('AkInflector', $options['inflector']);

        $this->options = $options;
    }

    function toPhp()
    {
        $this->_code = str_replace(array('\{','\}'),array(':~AKBO~:',':~AKBC~:'), $this->_code);
        $this->_renderMultilingualText();
        $this->_tokenizeTemplate();
        $this->_loadTokenReplacementOptions();
        $this->_LoadReplacementCode();
        $this->_replaceTokensWithPhpCode();
        $this->_code = str_replace(array(':~AKBO~:',':~AKBC~:'),array('{','}'),$this->_code);
        $this->_untokenizeTemplate();

        return $this->hasErrors() ? false : $this->_code;
    }

    function hasErrors()
    {
        return false;
    }

    function getErrors()
    {
        return array();
    }



    function _tokenizeTemplate()
    {
        $this->_code = str_replace(
        array('{_','{?','?}','{loop ','{end}','<?xml','<?=','<? ',"<?\n","<?\t"),
        array('{AKTRANSVAR__','{AKNOTEMPTY__','__AKPRINTIFISSET}','{AKPERFLOOP__','<?php } ?>','AKXMLOPENTAG','AKPHPOPENSHORTTAGECHO','AKPHPOPENSHORTTAG','AKPHPOPENSHORTTAG','AKPHPOPENSHORTTAG'), 
        $this->_code);
        if(preg_match_all('/{[A-Za-z0-9_]+((\.|-){1}[A-Za-z0-9_]+){0,}}/i',$this->_code,$match)){
            $this->_tokens = $match[0];
        }
        $this->_tokens = array_unique($this->_tokens);


    }

    function _untokenizeTemplate()
    {
        $this->_code = str_replace(
        array('{AKTRANSVAR__','{AKNOTEMPTY__','__AKPRINTIFISSET}','{AKPERFLOOP__',
        'AKXMLOPENTAG','AKPHPOPENSHORTTAGECHO','AKPHPOPENSHORTTAG'),
        array('{_','{?','?}','{loop ',
        '<?php echo \'<?xml\'; ?>','<?php echo ','<?php '), $this->_code);
    }

    function _loadTokenReplacementOptions()
    {
        $_open_tags = array('AKTRANSVAR','AKNOTEMPTY','AKPERFLOOP');
        $_close_tag = 'AKPRINTIFISSET';

        foreach ($this->_tokens as $token){
            $_open = substr($token,1,10);
            $_close = substr($token,-15,14);

            $_token_options = array();
            $_token_options['loop'] = $_open == 'AKPERFLOOP';
            $_token_options['not_empty'] = $_open == 'AKNOTEMPTY' | $_token_options['loop'];
            $_token_options['translate'] = $_open == 'AKTRANSVAR';
            $_token_options['print_if_set'] = $_close == 'AKPRINTIFISSET' && !$_token_options['loop'];

            $var = trim($token,'{}');
            $var = in_array($_open,$_open_tags) ? str_replace($_open.'__','',$var) : $var;
            $var = $_close == 'AKPRINTIFISSET' ? str_replace('__'.$_close,'',$var) : $var;

            if($_token_options['loop']){
                $_token_options['loop_singular'] = $this->_getSingularVariableNameForLoop($var);
            }

            $_token_options['php_var'] = $this->_convertSintagsVarToPhp($var);

            $this->_token_options[$token] = $_token_options;
        }
    }

    function _getSingularVariableNameForLoop($plural)
    {
        return '$'.AkInflector::singularize(substr($plural,max(strpos($plural,'.'),strpos($plural,'-'),-1)+1));
    }

    function _convertSintagsVarToPhp($var)
    {
        $var = str_replace(array('-','.'),array('~','->'),$var);
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

    function _LoadReplacementCode()
    {
        foreach ($this->_token_options as $token=>$options){
            $options['Sintags'] = str_replace(array('{AKTRANSVAR__','{AKNOTEMPTY__','__AKPRINTIFISSET}','{AKPERFLOOP__','<?php } ?>'),array('{_','{?','?}','{loop ','{end}'), $token);
            if(!empty($options['loop'])){
                $this->_token_options[$token]['code'] = $this->_renderLoop($options);
            }elseif(!empty($options['translate'])){
                $this->_token_options[$token]['code'] = $this->_renderTranslateVar($options);
            }elseif(!empty($options['print_if_set'])){
                $this->_token_options[$token]['code'] = $this->_renderPrintIfSet($options);
            }elseif(!empty($options['not_empty'])){
                $this->_token_options[$token]['code'] = $this->_renderIsNotEmpty($options);
            }else{
                $this->_token_options[$token]['code'] = $this->_renderPrint($options);
            }
        }
    }

    function _renderLoop($options = array())
    {
        return
        "<?php ".
        "\n empty({$options['php_var']}) ? null : {$options['loop_singular']}_loop_counter = 0;".
        "\n empty({$options['php_var']}) ? null : {$options['php_var']}_available = count({$options['php_var']});".
        "\n if(!empty({$options['php_var']}))".
        "\n     foreach ({$options['php_var']} as {$options['loop_singular']}_loop_key=>{$options['loop_singular']}){".
        "\n         {$options['loop_singular']}_loop_counter++;".
        "\n         {$options['loop_singular']}_is_first = {$options['loop_singular']}_loop_counter === 1;".
        "\n         {$options['loop_singular']}_is_last = {$options['loop_singular']}_loop_counter === {$options['php_var']}_available;".
        "\n         {$options['loop_singular']}_odd_position = {$options['loop_singular']}_loop_counter%2;".
        "\n?>";
    }

    function _renderIsNotEmpty($options = array())
    {
        return
        "<?php ".
        "\n if(!empty({$options['php_var']})) { ".
        "\n?>";

    }

    function _renderPrint($options = array())
    {
        return
        "<?php ".
        "\n echo {$options['php_var']};".
        "\n?>";
    }

    function _renderPrintIfSet($options = array())
    {
        return
        "<?php ".
        "\n echo isset({$options['php_var']}) ? {$options['php_var']} : '';".
        "\n?>";
    }

    function _renderTranslateVar($options = array())
    {
        $namespace = $this->_getTemplateNamespace();

        return
        "<?php ".
        "\n echo empty({$options['php_var']}) || !is_array({$options['php_var']}) ? '' : \$text_helper->translate({$options['php_var']}". (empty($namespace) ? '' : ', null, \''.$namespace.'\'').");".
        "\n?>";
    }

    function _getTemplateNamespace()
    {
        if(!empty($this->options['template_namespace'])){
            return $this->options['template_namespace'];
        }
        if(strstr($this->options['file_path'], AK_VIEWS_DIR.DS.'layouts')){
            return 'layouts';
        }
        return '';
    }

    function _renderMultilingualText()
    {
        $namespace = $this->_getTemplateNamespace();
        
        if(preg_match_all('/(\_{[^\}]+\})+/', $this->_code, $match)){
            foreach ($match[0] as $matched_text){
                $tmp_variables = array();
                $new_text = str_replace('\%','AKBINDINGESCAPE',$matched_text);

                if(preg_match_all('/(%([A-Za-z0-9_])+){1}/',$new_text,$bindings)){
                    foreach ($bindings[0] as $binding){
                        $tmp_variables[] = "'$binding'=>".str_replace('%','$',$binding);
                    }
                }
                $new_text = str_replace('AKBINDINGESCAPE','%',$new_text);
                $this->_code = str_replace($matched_text,
                '<?php echo $text_helper->translate(\''.str_replace("'","\'",trim($new_text,'_{}')).'\''.
                (!empty($tmp_variables) ? ', array('.join(',',$tmp_variables).')' : ', array()').
                (empty($namespace) ? '' : ', \''.$namespace.'\'')
                .'); ?>'
                , $this->_code);
            }
        }

    }

    function _replaceTokensWithPhpCode()
    {
        $code_replacements = array();
        foreach ($this->_token_options as $token=>$options){
            $code_replacements[] = $options['code'];
        }
        $this->_code = str_replace(array_keys($this->_token_options), $code_replacements, $this->_code);
    }
}


?>
