<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

define('SINTAGS_VERSION', '1.0.3');
defined('AK_SINTAGS_REMOVE_PHP_SILENTLY')           || define('AK_SINTAGS_REMOVE_PHP_SILENTLY', false);
defined('AK_SINTAGS_REPLACE_SHORTHAND_PHP_TAGS')    || define('AK_SINTAGS_REPLACE_SHORTHAND_PHP_TAGS', true);
defined('AK_SINTAGS_HIDDEN_COMMENTS_TAG')           || define('AK_SINTAGS_HIDDEN_COMMENTS_TAG', 'hidden');
defined('AK_SINTAGS_OPEN_HELPER_TAG')               || define('AK_SINTAGS_OPEN_HELPER_TAG', '<%');
defined('AK_SINTAGS_CLOSE_HELPER_TAG')              || define('AK_SINTAGS_CLOSE_HELPER_TAG', '%>');
defined('AK_SINTAGS_HASH_KEY_VALUE_DELIMITER')      || define('AK_SINTAGS_HASH_KEY_VALUE_DELIMITER', '=>');


/**
 * Sintags is Akelos default template language. The initial goal when
 * designing the Sintags was to allow WYSIWYG HTML editor compatibility using
 * a simplistic approach to looping collections and printing variables.
 *
 * Sintags is not building another full-fledged language. It’s to make coding
 * views less frustrating and verbose. In fact Sintags code will be compiled
 * into PHP for performance reasons.
 *
 * The name Sintags comes uses the Spanish word "Sin" which means without.
 * Ironically, the only tag allowed in Sintags is the <hidden></hidden> tag,
 * which will skip the content within the tags.
 *
 *  h2. *Basic Sintags syntax*
 * 
 * Basic Sintags is what most designers will need to learn to work with the views. It is composed of the following elements:
 * 
 * | { | Starts a Sintag block |
 * | } | Ends a Sintag block |
 * | {var_name?} | Asserts if given element has been set by the controller and prints the value of "*$var_name*" |
 * | {?var_name} | Asserts if element is not empty and starts a php condition block like if(!empty($var_name)) { You need to close this blocks using {end}" or <?php } ?> |
 * | {end} | Closes a block generating *<?php } ?>* |
 * | {object.attribute} | "*.*" is used for accessing object attributes. This is the same as *<?php echo $object->attribute; ?>* |
 * | {array-key} | "-" is used for accessing array on a specific key. This is the same as *<?php echo $array['key']; ?>* |
 * | _{Multilingual text} | *"_{ }"* will enclose a string for internationalization. |
 * | {_multilingual_var} | "*{_ }*" will enclose a variable for internationalization. This variable must be an array with the current locale as the key. |
 * | {\var} | "*{\ }*" will escape malicious html entities to avoid [[http://en.wikipedia.org/wiki/Cross-site_scripting|XSS attacks]]. |
 * | {loop people} {end} | Iterates over a collection. In this case the variable $person will be available inside the loop.. |
 * 
 * The following examples will show you how Sintags is converted into PHP. These have been taken from the test suite.
 * 
 * h2. Printing variables, object attributes and array members
 * 
 * Printing variables.
 * 
 * Sintags
 * 
 *     {comment}
 * 
 * PHP
 * 
 *     <?php echo $comment; ?>
 * 
 * Printing object attributes.
 * 
 * Sintags
 * 
 *     {post.Comments}
 * 
 * PHP
 * 
 *     <?php echo $post->Comments; ?>
 * 
 * Printing nested object attributes.
 * 
 * Sintags
 * 
 *     {post.Comments.latest}
 * 
 * PHP
 * 
 *     <?php echo $post->Comments->latest; ?>
 * 
 * Printing array members.
 * 
 * Sintags
 * 
 *     {people-members}
 * 
 * PHP
 * 
 *     <?php echo $people['members']; ?>
 * 
 * Printing array members with numeric indexes.
 * 
 * Sintags
 * 
 *     {people-0-member}
 * 
 * PHP
 * 
 *     <?php echo $people[0]['member']; ?>
 * 
 * Mixing object attributes and array members.
 * 
 * Sintags
 * 
 *     {people.members-0.name}
 * 
 * PHP
 * 
 *     <?php echo $people->members[0]->name; ?>
 * 
 * Mixing array members and object attributes.
 * 
 * Sintags
 * 
 *     {posts-latest.created_at}
 * 
 * PHP
 * 
 *     <?php echo $posts['latest']->created_at; ?>
 * 
 * 
 * h3. Hiding stuff
 * 
 * You can remove blocks using the *hidden* tag. The content inside <hidden></hidden> will not be included in the compiled template.
 * 
 * Sintags
 * 
 * <hidden>\\ <?php This will not be executed ?>\\ </hidden>
 * 
 * PHP
 * 
 * h3. Private variables
 * 
 * By convention, object attributes and array keys that start with an underscore are considered private. Therefore they will not compile to PHP.
 * 
 * Sintags
 * 
 *     {posts-cc._number}
 * 
 * PHP
 * 
 * {posts-cc._number}
 * 
 * h3. Filtering malicious html entities from variables
 * 
 * You should avid [[http://en.wikipedia.org/wiki/Cross-site_scripting|XSS attacks]] by escaping variables using a backslash after the opening brace.
 * 
 * Sintags
 * 
 *     {\comment}
 * 
 * PHP
 * 
 * <?php echo $controller->text_helper->h($comment); ?>
 * 
 * Also on object attributes and array elements.
 * 
 * Sintags
 * 
 *     {\comment.one}
 * 
 * PHP
 * 
 *     <?php echo $controller->text_helper->h($comment->one); ?>
 * 
 * 
 * h3. Escaping from Sintags
 * 
 * If you want the Sintags parser to ignore content within braces, you can escape it by using a backslash like:
 * 
 * Sintags
 * 
 *     This \{should} \{be?} \{ignored\}. _\{Multilingual block} _\{escaped using\} \{?backslashes} \{end}
 * 
 * PHP
 * 
 *     This {should} {be?} {ignored}. _{Multilingual block} _{escaped using} {?backslashes} {end}
 * 
 * Sometimes you’ll have to print two sintags string separated by an underscore – *_{}* is for multilingual block as we will see later. Then you’ll have to escape the multilingual block start *_* with a backslash.
 * 
 * Sintags
 * 
 *     {key}\_{loop_key}
 * 
 * PHP
 * 
 *     <?php echo $key; ?>_<?php echo $loop_key; ?>
 * 
 * h3. Multilingual text
 * 
 * By prefixing with an underscore *_* a sintags block, you’ll start a multilingual block.
 * 
 * Sintags
 * 
 *     <h1>_{Ain't easy to translate text?}</h1>
 * 
 * PHP
 * 
 *     <h1><?php echo $controller->text_helper->translate('Ain\'t easy to translate text?', array()); ?></h1>
 * 
 * You can even include HTML.
 * 
 * Sintags
 * 
 *     _{<p>It's really simple even to add\\  <a href='http://google.co.uk'>Localized links</a>\\  </p>}
 * 
 * PHP
 * 
 *     <?php echo $controller->text_helper->translate('<p>It\'s really simple even to add\\  <a href=\'http://google.co.uk\'>Localized links</a>\\  </p>', array()); ?>
 * 
 * If you need to nest brackets on multilingual blocks, you can escape them using a backslash.
 * 
 * Sintags
 * 
 *     _{I need to print \{something_inside_curly_brackets\}. _\{Maybe a multilingual text example\} }
 * 
 * PHP
 * 
 *     <?php echo $controller->text_helper->translate('I need to print {something_inside_curly_brackets}. _{Maybe a multilingual text example} ', array()); ?>
 * 
 * If you need to include variables in multilingual Sintags blocks, you can do so by prefixing the variable with a *%* symbol. This will bind the variables in the second parameter of the translation method.
 * 
 * Sintags
 * 
 *     <h1>_{You can use %variables using the %sintags.variable-naming-way}</h1>
 * 
 * PHP
 * 
 *     <h1><?php echo $controller->text_helper->translate('You can use %variables using the %sintags.variable-naming-way', array('%variables' => @$variables, '%sintags.variable-naming-way' => @$sintags->variable['naming']['way'])); ?></h1>
 * 
 * You should also escape variables binded to translations when using users input.
 * 
 * Sintags
 * 
 *     _{Signed up using %\email address}
 * 
 * PHP
 * 
 *     <?php echo $controller->text_helper->translate('Signed up using %\email address', array('%\email' => $controller->text_helper->h(@$email))); ?>
 * 
 * Escaping variables is also done by prefixing with a backslash.
 * 
 * Sintags
 * 
 *     <h1>_{Mixing %variables and \%escaped_variables}</h1>
 * 
 * PHP
 * 
 *     <h1><?php echo $controller->text_helper->translate('Mixing %variables and %escaped_variables', array('%variables' => @$variables)); ?></h1>
 * 
 * h3. Multilingual variables
 * 
 * When a variable is underscored at the beginning of a Sintags block, it’s not considered private. Underscoring a variable will tell Sintags that the variable needs to be translated.
 * 
 * Sintags
 * 
 *     {_post.comment}
 * 
 * PHP
 * 
 *     <?php echo empty($post->comment) || is_object($post->comment) ? '' : $controller->text_helper->translate($post->comment); ?>
 * 
 * h3. Conditional printing
 * 
 * If your PHP error reporting settings are set to complain when using undeclared variables, you can prevent the notices by adding a question mark after the sintags variable.
 * 
 * The following example will print the variable $comment only if not empty.
 * 
 * Sintags
 * 
 *     {comment?}
 * 
 * PHP
 * 
 *     <?php echo empty($comment) ? '' : $comment; ?>
 * 
 * h3. Conditional Statements
 * 
 * You can use *{? }* statement to execute some code only if a specified variable is *true*.
 * 
 * Sintags
 * 
 *     {?comment}Hello world{end}
 * 
 * PHP
 * 
 *     <?php if(!empty($comment)) { ?>Hello world<?php } ?>
 * 
 * Or the *{! }* statement to execute some code only if a specified variable is *false*.
 * 
 * Sintags
 * 
 *     {!Page.id}style="display:none;"{end}
 * 
 * PHP
 * 
 *     <?php if(empty($Page->id)) { ?>style="display:none;"<?php } ?>
 * 
 * Conditional statements can also be used on object attributes
 * 
 * Sintags
 * 
 *     {?comment.author}\\      {comment.author}\\  {end}
 * 
 * PHP
 * 
 *     <?php if(!empty($comment->author)) { ?>\\      <?php echo $comment->author; ?>\\ <?php } ?>
 * 
 * and on any object attribute array combination
 * 
 * Sintags
 * 
 *     {?comment.author}\\      {comment.author}\\      {?comment.author-name}\\          {comment.author-name}\\      {end}\\  {end}
 * 
 * PHP
 * 
 *     <?php if(!empty($comment->author)) { ?>\\      <?php echo $comment->author; ?>\\      <?php if(!empty($comment->author['name'])) { ?>\\          <?php echo $comment->author['name']; ?>\\      <?php } ?>\\ <?php } ?>
 * 
 * You can use *{else}* syntax to execute a block with the opposite assertion.
 * 
 * Sintags
 * 
 *     {?comment.author}\\      {comment.author}\\  {else}\\      Anonymous coward\\  {end}
 * 
 * PHP
 * 
 *     <?php if(!empty($comment->author)) { ?>\\      <?php echo $comment->author; ?>\\ <?php } else { ?>\\      Anonymous coward\\ <?php } ?>
 * 
 * h3. Iterating a collection of elements. Sintags *loops*
 * 
 * Perhaps one of the most common and parts of sintags iterating over an array of items. In order to do so, Sintags uses a *{loop* items*} {end}*
 * 
 * Sintags loops *expects a plural noun* for iterating and provides the singular form for each item.
 * 
 * Sintags
 * 
 *     {loop posts}\\ <q> {post.comment} {post.author} </q>\\  {end}
 * 
 * as you can see, the generated PHP contains some useful variables wich are helpful for formatting iterated collections
 * 
 * PHP
 * 
 *     <?php\\   empty($posts) ? null : $post_loop_counter = 0;\\   empty($posts) ? null : $posts_available = count($posts);\\   if(!empty($posts))\\       foreach ($posts as $post_loop_key=>$post){\\           $post_loop_counter++;\\           $post_is_first = $post_loop_counter h3. 1;\\           $post_is_last = $post_loop_counter h3. $posts_available;\\           $post_odd_position = $post_loop_counter%2;\\ ?>\\  <q> <?php echo $post->comment; ?> <?php $post->author; ?> </q>\\ <?php } ?>
 * 
 * Looping array elements will take the last item of the array as the variable name to singularize in the loop body.
 * 
 * Sintags
 * 
 *     {loop items-directories}\\  {end}
 * 
 * PHP
 * 
 *     <?php\\   empty($items['directories']) ? null : $directory_loop_counter = 0;\\   empty($items['directories']) ? null : $directories_available = count($items['directories']);\\   if(!empty($items['directories']))\\       foreach ($items['directories'] as $directory_loop_key=>$directory){\\           $directory_loop_counter++;\\           $directory_is_first = $directory_loop_counter h3. 1;\\           $directory_is_last = $directory_loop_counter h3. $directories_available;\\           $directory_odd_position = $directory_loop_counter%2;\\ ?>\\ <?php } ?>
 * 
 * The same works for object attributes. In this case given Post.author.friends it will set individual items as $friend.
 * 
 * Sintags
 * 
 *     {loop Post.author.friends}\\  {end}
 * 
 * PHP
 * 
 *     <?php\\   empty($Post->author->friends) ? null : $friend_loop_counter = 0;\\   empty($Post->author->friends) ? null : $friends_available = count($Post->author->friends);\\   if(!empty($Post->author->friends))\\       foreach ($Post->author->friends as $friend_loop_key=>$friend){\\           $friend_loop_counter++;\\           $friend_is_first = $friend_loop_counter h3. 1;\\           $friend_is_last = $friend_loop_counter h3. $friends_available;\\           $friend_odd_position = $friend_loop_counter%2;\\ ?>\\ <?php } ?>
 * 
 * If you can’t match the convention giving a plural word, or you just want a different name in the loop, you can specify it using the the *as* key.
 * 
 * Sintags
 * 
 *     {loop Post.versions as Post}\\  {end}
 * 
 * PHP
 * 
 *     <?php\\   empty($Post->versions) ? null : $Post_loop_counter = 0;\\   empty($Post->versions) ? null : $Posts_available = count($Post->versions);\\   if(!empty($Post->versions))\\       foreach ($Post->versions as $Post_loop_key=>$Post){\\           $Post_loop_counter++;\\           $Post_is_first = $Post_loop_counter h3. 1;\\           $Post_is_last = $Post_loop_counter h3. $Posts_available;\\           $Post_odd_position = $Post_loop_counter%2;\\ ?>\\ <?php } ?>
 * 
 * h3. Cleaning up lazy PHP
 * 
 * Sintags will expand your old-school shorthand php tags to prevent PHP from barking on runtime.
 * 
 * Sintags
 * 
 *     <?=PHP_CONSTANTS?>
 * 
 * PHP
 * 
 *     <?php echo PHP_CONSTANTS?>
 * 
 * It will also take care of preventing the famous xml declaration bug for you.
 * 
 * Sintags
 * 
 *     <?xml version="1.0" encoding="UTF-8" ?>
 * 
 * PHP
 * 
 *     <?php echo '<?xml'; ?> version="1.0" encoding="UTF-8" ?>
 * 
 * 
 * ----
 * 
 * 
 * h2. *Ruby-esque Sintags syntax*
 * 
 * Sintags provides a shorthand method for calling helper functions in your views. This syntax is modeled after Ruby’s erb template language and makes it simpler for designers to jump from Rails to PHP views.
 * 
 * If you are used to Rails this might sound familiar. If you prefer writing helpers in php, go ahead, Sintags does not force you to use the Ruby-esque way.
 * 
 * h3. Examples using the helpers registered by the [[http://www.akelos.org/|Akelos Framework]]
 * 
 * Calling the render helper.
 * 
 * Sintags
 * 
 *     <%= render :partial => "account", :locals => { :account => @buyer } %>
 * 
 * PHP
 * 
 *     <?php echo $controller->render( array('partial' => "account", 'locals' => array('account' => $buyer))); ?>
 * 
 * Link to using object attributes.
 * 
 * Sintags
 * 
 *     <%= link_to(document.title, :controller => "document", :action => "show", :id => document.id) %>
 * 
 * PHP
 * 
 *     <?php echo $controller->url_helper->link_to($document->title, array('controller' => "document", 'action' => "show", 'id' => $document->id)); ?>
 * 
 * Linking and translating.
 * 
 * Sintags
 * 
 *     <%= link_to (translate("Visit Other Site"), "http://www.akelos.org/", :confirm => "Are you sure?" ) %>
 * 
 * PHP
 * 
 *     <?php echo $controller->url_helper->link_to($controller->text_helper->translate("Visit Other Site"), "http://www.akelos.org/", array('confirm' => "Are you sure?")); ?>
 * 
 * More translations.
 * 
 * Sintags
 * 
 *     <%= translate 'Write me home' %>
 * 
 * PHP
 * 
 *     <?php echo $controller->text_helper->translate('Write me home'); ?>
 * 
 * Escaping double quotes.
 * 
 * Sintags
 * 
 *     <%= translate "Let's \"Jump\"", "D'Akelos Way" %>
 * 
 * PHP
 * 
 *     <?php echo $controller->text_helper->translate("Let's \"Jump\"", "D'Akelos Way"); ?>
 * 
 * Symbols are treated as strings.
 * 
 * Sintags
 * 
 *     <%= translate :page_title %>
 * 
 * PHP
 * 
 *     <?php echo $controller->text_helper->translate('page_title'); ?>
 * 
 * Translating variables.
 * 
 * Sintags
 * 
 *     <%= translate @weekday %>
 * 
 * PHP
 * 
 *     <?php echo $controller->text_helper->translate($weekday); ?>
 * 
 * Translating arrays.
 * 
 * Sintags
 * 
 *     <%= translate @date-weekday %>
 * 
 * PHP
 * 
 *     <?php echo $controller->text_helper->translate($date['weekday']); ?>
 * 
 * Using the optional parenthesis.
 * 
 * Sintags
 * 
 *     <%= link_to ("Visit Other Site", "http://www.akelos.org/", {:confirm => "Are you sure?"} ) %>
 * 
 * PHP
 * 
 *     <?php echo $controller->url_helper->link_to("Visit Other Site", "http://www.akelos.org/", array('confirm' => "Are you sure?")); ?>
 * 
 * The last set of options does not require to be enclosed in braces.
 * 
 * Sintags
 * 
 *     <%=link_to "Delete Image", { :action => "delete", :id => @image.id }, :confirm => "Are you sure?", :method => :delete %>
 * 
 * PHP
 * 
 *     <?php echo $controller->url_helper->link_to("Delete Image", array('action' => "delete", 'id' => $image->id), array('confirm' => "Are you sure?", 'method' => 'delete')); ?>
 * 
 * Arrays without implicit indexes.
 * 
 * Sintags
 * 
 *     <%= link_to "Visit Other Site", "http://www.akelos.org/", :confirm => "Are you sure?", :options => ["Yes", 'No'] %>
 * 
 * PHP
 * 
 *     <?php echo $controller->url_helper->link_to("Visit Other Site", "http://www.akelos.org/", array('confirm' => "Are you sure?", 'options' => array("Yes", 'No'))); ?>
 * 
 * Nesting function calls.
 * 
 * Sintags
 * 
 *     <%= link_to ( translate("Visit Other Site"), "http://www.akelos.org/", {:confirm => "Are you sure?"} ) %>
 * 
 * PHP
 * 
 *     <?php echo $controller->url_helper->link_to($controller->text_helper->translate("Visit Other Site"), "http://www.akelos.org/", array('confirm' => "Are you sure?")); ?>
 * 
 * Setting null’s.
 * 
 * Sintags
 * 
 *     <%= url_for true, false, null %>
 * 
 * PHP
 * 
 *     <?php echo $controller->url_helper->url_for(true, false, null); ?>
 * 
 * Shorthand for translate.
 * 
 * Sintags
 * 
 *     <%= _('Write me home') %>
 * 
 * PHP
 * 
 *     <?php echo $controller->text_helper->translate('Write me home'); ?>
 * 
 * Even shorter translate alias.
 * 
 * Sintags
 * 
 *     <%= _'Write me home' %>
 * 
 * PHP
 * 
 *     <?php echo $controller->text_helper->translate('Write me home'); ?>
 * 
 * Binding basic Sintags into method parameters.
 * 
 * Sintags
 * 
 *     <%= link_to "Posted by #{Post.author}" %>
 * 
 * PHP
 * 
 *     <?php echo $controller->url_helper->link_to("Posted by ".$Post->author.""); ?>
 * 
 * Nesting Sintags calls.
 * 
 * Sintags
 * 
 *     <%= link_to "Status: #{translate 'active',{'key'=> Value.text}, :foo => _('Bar')}" %>
 * 
 * PHP
 * 
 *     <?php echo $controller->url_helper->link_to("Status: ".$controller->text_helper->translate('active', array('key' => $Value->text), array('foo' => $controller->text_helper->translate('Bar'))).""); ?>
 * 
 * Simpler nested call.
 * 
 * Sintags
 * 
 *     <%= link_to "Status: #{translate 'active'}" %>
 * 
 * PHP
 * 
 * <?php echo $controller->url_helper->link_to("Status: ".$controller->text_helper->translate('active').""); ?>
 * 
 * Using url functions in Akelos.
 * 
 * Sintags
 * 
 *     <%= named_route_path :action => 'select_database' %>
 * 
 * PHP
 * 
 *     <?php echo named_route_path( array('action' => 'select_database')); ?>
 * 
 * Emulating simple blocks.
 * 
 * Sintags
 * 
 *     <% keys {|key| echo $key} %>
 * 
 * PHP
 * 
 *     <?php\\ if(!empty($keys)){\\      foreach (array_keys((array)$keys) as $ak_sintags_key){\\          $key = $keys[$ak_sintags_key];\\          echo $key;\\      }\\ }?>
 * 
 * Assigning the result of a block.
 * 
 * Sintags
 * 
 *     <% incremented = keys {|key| ++$key} %>
 * 
 * PHP
 * 
 *     <?php\\ if(!empty($keys)){\\      $incremented = array();\\      foreach (array_keys((array)$keys) as $ak_sintags_key){\\          $key = $keys[$ak_sintags_key];\\          ++$key;\\          $incremented[$ak_sintags_key] = $keys[$ak_sintags_key];\\      }\\ }?>
 * 
 * Simple assignments.
 * 
 * Sintags
 * 
 *     <% simple_var = 'value' %>
 * 
 * PHP
 * 
 *     <?php $simple_var = ('value'); ?>
 * 
 * Assigning array members.
 * 
 * Sintags
 * 
 *     <% simple_var = var-foo %>
 * 
 * PHP
 * 
 *     <?php $simple_var = ($var['foo']); ?>
 * 
 * Assigning object attributes.
 * 
 * Sintags
 * 
 *     <%= var = var.foo %>
 * 
 * PHP
 * 
 *     <?php $var = ($var->foo); ?>
 * 
 * Assigning the result of a helper method.
 * 
 * Sintags
 * 
 *     <% url = url_for(:controller => 'page') %>
 * 
 * PHP
 * 
 *     <?php $url = ($controller->url_helper->url_for( array('controller' => 'page'))); ?>
 * 
 * Assigning an array.
 * 
 * Sintags
 * 
 *     <% url = {:controller => 'page'} %>
 * 
 * PHP
 * 
 *     <?php $url = (array('controller' => 'page')); ?>
 * 
 * Assigning an array without braces.
 * 
 * Sintags
 * 
 *     <% url = :controller => 'page' %>
 * 
 * PHP
 * 
 *     <?php $url = ( array('controller' => 'page')); ?>
 * 
 *
 */
class AkSintags
{
    protected
    $_helper_loader,
    $_code;

    public function init($options = array()) {
        $this->_code = $options['code'];
        if(isset($options['helper_loader'])){
            $this->_helper_loader = $options['helper_loader'];
        }
    }

    public function toPhp() {
        $this->Parser = new AkSintagsParser();
        if(!empty($this->_helper_loader)){
            $this->Parser->setHelperLoader($this->_helper_loader);
        }
        return $this->Parser->parse($this->_code);
    }

    public function getErrors() {
        return $this->Parser->getErrors();
    }

    public function getParsedCode() {
        return $this->Parser->parsed_code;
    }
}

