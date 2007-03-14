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

require_once(AK_LIB_DIR.DS.'Ak.php');
require_once(AK_LIB_DIR.DS.'AkLexer.php');
require_once(AK_LIB_DIR.DS.'AkActionView'.DS.'TemplateEngines'.DS.'AkSintags'.DS.'AkSintagsLexer.php');
require_once(AK_LIB_DIR.DS.'AkActionView'.DS.'TemplateEngines'.DS.'AkSintags'.DS.'AkSintagsParser.php');

ak_define('SINTAGS_REMOVE_PHP_SILENTLY', false);
ak_define('SINTAGS_REPLACE_SHORTHAND_PHP_TAGS', true);
ak_define('SINTAGS_HIDDEN_COMMENTS_TAG', 'hidden');

ak_define('SINTAGS_OPEN_HELPER_TAG', '<%');
ak_define('SINTAGS_CLOSE_HELPER_TAG', '%>');

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
 *       Remember that Sintags is not meant to replace PHP, but to speed up development. It's good to use this syntax because it keeps views more clear. So if you start to seeing to much PHP code on your view, consider moving some view logic into the helpers
 */
class AkSintags
{
    var $_code;

    function init($options = array())
    {
        $this->_code =& $options['code'];
    }

    function toPhp()
    {
        $Parser =& new AkSintagsParser();
        return $Parser->parse($this->_code);
    }
}

?>
