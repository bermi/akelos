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
 * @subpackage Helpers
 * @author Jose Salavert <salavert a.t akelos c.om>
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

require_once(AK_VENDOR_DIR.DS.'phputf8'.DS.'utf8.php');

defined('AK_VALID_URL_CHARS_REGEX') ? null : define('AK_VALID_URL_CHARS_REGEX','A-Z-a-z0-9:=?&\/\.\-\\%~#_;,+');
define('AK_AUTO_LINK_REGEX','/
        (                          # leading text
          <\w+.*?>|                # leading HTML tag, or
          [^=!:\'"\/]|               # leading punctuation, or 
          ^                        # beginning of line
        )
        (
          (?:https?:\/\/)|           # protocol spec, or
          (?:www\.)                # www.*
        )
        (
          [-\w]+                   # subdomain or domain
          (?:\.[-\w]+)*            # remaining subdomains or domain
          (?::\d+)?                # port
          (?:\/(?:(?:[~\w\+%-]|(?:[,.;:][^\s$]))+)?)* # path
          (?:\?[\w\+%&=.;-]+)?     # query string
          (?:\#[\w\-]*)?           # trailing anchor
        )
        ([[:punct:]]|\s|<|$)       # trailing text
        /x');

/**
* Provides a set of methods for working with text strings that can help unburden 
* the level of inline AkelosFramework code in the templates. In the example 
* below we iterate over a collection of posts provided to the template and print 
* each title after making sure it doesn't run longer than 20 characters:
*   {loop posts}
*     Title: <?= $text_helper->truncate($post->title, 20) ?>
*   {end}
*/

class  TextHelper
{

    function setController(&$controller)
    {
        $this->_controller =& $controller;
    }

    function TextHelper()
    {
        TextHelper::cycle(array('reset'=>'all'));
    }


    /**
    * Truncates "$text" to the length of "length" and replaces the last three 
    * characters with the "$truncate_string" if the "$text" is longer than 
    * "$length" and the last characters will be replaced with the +truncate_string+.
    * If +break+ is specified and if it's present in +text+ and if its position is 
    * lesser than +length+, then the truncated +text+ will be limited to +break+.
    * 
    */
    function truncate($text, $length = 30, $truncate_string = '...', $break = false)
    {
        if(utf8_strlen($text) <= $length){
            return $text;
        }

        if (false !== ($breakpoint = (empty($break) ? $length : utf8_strpos($text, $break))) && ($breakpoint >= utf8_strlen($truncate_string)))
        {
            if ($breakpoint > $length)
            {
                $breakpoint = $length;
            }
            return utf8_substr($text, 0, $breakpoint - utf8_strlen($truncate_string)) . $truncate_string;
        }
        return $text;
    }

    /**
	* Highlights the string or array of strings "$phrase" where it is found in 
	* the "$text" by surrounding it  like 
	* <strong class="highlight">I'm a highlight phrase</strong>. 
	* 
	* The highlighter can be specialized by passing "$highlighter" as string 
	* with \1 where the phrase is supposed to be inserted.
	* 
	* Note: The "$phrase" is sanitized with preg_quote before use.
	* 
	* Examples:
	*  
	*  <?=$text_helper->highlight('I am highlighting the phrase','highlighting');?> 			
	*  //outputs: I am <strong class="highlight">highlighting</strong> the phrase
	* 
	*  <?=$text_helper->highlight('I am highlighting the phrase', 
	*      array('highlighting','the')?>
	*  //outputs: I am <strong class="highlight">highlighting</strong> <strong class="highlight">the</strong> phrase
	* 
	*/
    function highlight($text, $phrase, $highlighter = '<strong class="highlight">\1</strong>')
    {
        $phrase = is_array($phrase) ? join('|',array_map('preg_quote',$phrase)) : preg_quote($phrase);
        return !empty($phrase) ? preg_replace('/('.$phrase.')/iu', $highlighter, $text) : $text;
    }

    /**
 	 * Extracts an excerpt from the "$text" surrounding the "$phrase" with a 
 	 * number of characters on each side determined by "$radius". If the phrase 
	 * isn't found, '' is returned. 
	 * 
	 * Example:
	 * 
	 *  <?=$text_helper->excerpt("hello my world", "my", 3);?>
	 *  //outputs:  ...lo my wo...
	 * 
	 */
    function excerpt($text, $phrase, $radius = 100, $excerpt_string = '...')
    {
        if(empty($text)){
            return $text;
        }
        $phrase = preg_quote($phrase);

        if(preg_match('/('.$phrase.')/iu',$text, $found)){
            $found_pos = utf8_strpos($text, $found[0]);
            $start_pos = max($found_pos - $radius, 0);
            $end_pos = min($found_pos + utf8_strlen($phrase) + $radius, utf8_strlen($text));

            $prefix  = $start_pos > 0 ? $excerpt_string : '';
            $postfix = $end_pos < utf8_strlen($text) ? $excerpt_string : '';

            return $prefix.trim(utf8_substr($text,$start_pos,$end_pos-$start_pos)).$postfix;
        }
        return '';
    }

    /**
	 * Attempts to pluralize the "$singular" word unless "$count" is 1.
	 */
    function pluralize($count, $singular, $plural = null)
    {
        if ($count==1) {
            return $singular;
        }elseif (!empty($plural)){
            return $plural;
        }else{
            return AkInflector::conditionalPlural($count, $singular);
        }
    }

    /**
     * Word wrap long lines to line_width.
     */
    function word_wrap($text, $line_width = 80, $break = "\n")
    {
        // No need to use an UTF-8 wordwrap function as we are using the default cut character.
        return trim(wordwrap($text, $line_width, $break));
    }

    /**
     * Like word wrap but allows defining text indenting  and boby indenting
     */
    function format($text, $options = array())
    {
        $default_options = array(
        'columns' => 72,
        'first_indent' => 4,
        'body_indent' => 0,
        'cut_words' => false
        );

        $options = array_merge($default_options, $options);

        $text = empty($text) && !empty($options['text']) ? $options['text'] : $text;
        if(empty($text)) {
            return '';
        }
        $text = str_replace(array("\r","\t",'  ','  '),array("\n",' ',' ',' '),$text);
        $formated_text = '';
        foreach(explode("\n",$text."\n") as $paragraph) {
            if(empty($paragraph)) {
                continue;
            }
            $paragraph = ($options['first_indent'] > 0 ? str_repeat(' ',$options['first_indent']) : '' ).$paragraph;
            $paragraph = wordwrap($paragraph, $options['columns']-$options['body_indent'], "\n", $options['cut_words']);

            if($options['body_indent'] > 0) {
                $paragraph = preg_replace('!^!m',str_repeat(' ',$options['body_indent']),$paragraph);
            }
            $formated_text .= $paragraph . "\n\n";
        }
        return $formated_text;
    }


    /**
     * Returns the "$text" with all the Textile codes turned into HTML-tags.
     */
    function textilize($text)
    {
        require_once(AK_VENDOR_DIR.DS.'TextParsers'.DS.'Textile.php');
        if (!empty($text)) {
            $Textile = new Textile();
            $text = trim($Textile->TextileThis($text));
        }
        return $text;
    }

    /**
     * Returns the "$text" with all the Textile codes turned into HTML-tags, but 
     * without the regular bounding <p> tag.
     */
    function textilize_without_paragraph($text)
    {
        return preg_replace('/^<p([A-Za-z0-9& ;\-=",\/:\.\']+)?>(.+)<\/p>$/u','\2', TextHelper::textilize($text));
    }

    /**
	 * Returns "$text" transformed into HTML using very simple formatting rules
	* Surrounds paragraphs with <tt>&lt;p&gt;</tt> tags, and converts line 
	* breaks into <tt>&lt;br /&gt;</tt> Two consecutive newlines(<tt>\n\n</tt>) 
	* are considered as a paragraph, one newline (<tt>\n</tt>) is considered a 
	* linebreak, three or more consecutive newlines are turned into two newlines 
	*/
    function simple_format($text)
    {
        $rules = array(
        '/(\\r\\n|\\r)/'=> "\n",
        '/(\\n\\n+)/' => "\n\n",
        '/(\\n\\n)/' => "</p><p>",
        '/([^\\n])(\\n)([^\\n])/' => "\1\2<br />\3"
        );
        $text = preg_replace(array_keys($rules), array_values($rules), $text);
        $text = TagHelper::content_tag('p',$text);
        return str_replace(array('<p></p>','</p><p>'),array('<br /><br />',"</p>\n<p>"),$text);
    }

    /**
    * Turns all urls and email addresses into clickable links. The "$link" 
    * parameter can limit what should be linked.
    * 
    * Options are "all" (default), "email_addresses", and "urls".
    *
    * Example:
    * 
    *   <?=$text_helper->auto_link("Go to http://www.akelos.org and say hello to bermi@example.com");?>
    *   //outputs: Go to <a href="http://www.akelos.org">http://www.akelos.org</a> and
    *     say hello to <a href="mailto:example.com">bermi@example.com</a>
    *
    */
    function auto_link($text, $link = 'all', $href_options = array(), $email_link_options = array())
    {
        if (empty($text)){
            return '';
        }

        switch ($link) {
            case 'all':
            return TextHelper::auto_link_urls(
            TextHelper::auto_link_email_addresses($text, $email_link_options),
            $href_options);
            break;

            case 'email_addresses':
            return TextHelper::auto_link_email_addresses($text, $email_link_options);
            break;

            case 'urls':
            return TextHelper::auto_link_urls($text, $href_options);
            break;

            default:
            return TextHelper::auto_link($text, 'all', $href_options);
            break;
        }
    }


    /**
    * Strips all HTML tags from the input, including comments. 
    * 
    * Returns the tag free text.
    */
    function strip_tags($html)
    {
        return strip_tags($html);
    }

    /**
	* Turns all links into words, like "<a href="something">else</a>" to "else".
	*/
    function strip_links($text)
    {
        return TextHelper::strip_selected_tags($text, 'a');
    }

    /**
	 * Turns all email addresses into clickable links.  You can provide an options
	 * array in order to generate links using UrlHelper::mail_to()
	 * 
	 * Example:
	 *   $text_helper->auto_link_email_addresses($post->body);
	 */
    function auto_link_email_addresses($text, $email_options = array())
    {
        if(empty($email_options)){
            return preg_replace('/([\w\.!#\$%\-+.]+@[A-Za-z0-9\-]+(\.[A-Za-z0-9\-]+)+)/',
            "<a href='mailto:$1'>$1</a>",$text);
        }elseif(preg_match_all('/([\w\.!#\$%\-+.]+@[A-Za-z0-9\-]+(\.[A-Za-z0-9\-]+)+)/',$text, $match)){
            $emails = $match[0];
            foreach ($emails as $email){
                $encoded_emails[] = UrlHelper::mail_to($email, null, $email_options);
            }
            $text = str_replace($emails,$encoded_emails,$text);
        }
        return $text;
    }

    /**
	 * Works like PHP function strip_tags, but it only removes selected tags.
	 * Example:
	 * 	<?=$text_helper->strip_selected_tags(
	 *      '<b>Person:</b> <strong>Salavert</strong>', 'strong');?>
	 *  //outputs: <b>Person:</b> Salavert
	 */
    function strip_selected_tags($text, $tags = array())
    {
        $args = func_get_args();
        $text = array_shift($args);
        $tags = func_num_args() > 2 ? array_diff($args,array($text))  : (array)$tags;
        foreach ($tags as $tag){
            if(preg_match_all('/<'.$tag.'[^>]*>([^<]*)<\/'.$tag.'>/iU', $text, $found)){
                $text = str_replace($found[0],$found[1],$text);
            }
        }

        return preg_replace('/(<('.join('|',$tags).')(\\n|\\r|.)*\/>)/iU', '', $text);
    }


    /**
     * Turns all urls into clickable links.  
     * 
     * Example:
     *  <?=$text_helper->auto_link_urls($post->body, array('all', 'target' => '_blank'));?>
     */
    function auto_link_urls($text, $href_options = array())
    {
        $extra_options = TagHelper::_tag_options($href_options);
        $extra_options_array = var_export($extra_options,true);
        return preg_replace_callback(AK_AUTO_LINK_REGEX, create_function(
        '$matched',
        'return TextHelper::_replace_url_with_link_callback($matched,'.$extra_options_array.');'
        ), $text);
    }

    function _replace_url_with_link_callback($matched, $extra_options)
    {
        list($all, $a, $b, $c, $d) = $matched;
        if (preg_match('/<a\s/i',$a)){ // don't replace URL's that are already linked
            return $all;
        }else{
            $text = $b.$c;
            return $a.'<a href="'.($b=="www."?"http://www.":$b).$c.'"'.$extra_options.'>'.$text.'</a>'.$d;
        }
    }

    /**
     * Returns an array with all the urls found as key and their valid link url as value
     *  
     * Example: 
     *  $text_helper->get_urls_from_text('www.akelos.com');
     *  //returns: array('www.akelos.com'=>'http://www.akelos.com');
     */
    function get_urls_from_text($text)
    {
        $urls = array();
        if(preg_match_all(AK_AUTO_LINK_REGEX, $text, $found_urls)){
            foreach ($found_urls[0] as $url){
                $urls[$url] = (strtolower(substr($url,0,4)) == 'http' ? $url : 'http://'.$url);
            }
        }
        return $urls;
    }

    /**
     * Returns an array with the linked urls found on a text 
     *  
     *  Example: 
     * $text_helper->get_linked_urls_from_text('<a href="http://akelos.com">Akelos.com</a>');
     * //returns: array('http://akelos.com');
     */
    function get_linked_urls_from_text($text)
    {
        $linked_urls = array();
        if(preg_match_all('/<a [^>]*href[ ]?=[ \'"]?(['.AK_VALID_URL_CHARS_REGEX.']+)[ \'"]?[^>]*>/i', $text, $linked_urls_pieces)){
            $linked_urls = array_unique($linked_urls_pieces[1]);
        }
        return $linked_urls;
    }


    /**
     * Returns an array with the image urls found on a text 
     *  
     *  Example: 
     * $text_helper->get_linked_urls_from_text('<a href="http://akelos.com">Akelos.com</a>');
     * //returns: array('http://akelos.com/images/logo.gif');
     */
    function get_image_urls_from_html($text)
    {
        $linked_urls = array();
        if(preg_match_all('/<img [^>]*src[ ]?=[ \'"]?(['.AK_VALID_URL_CHARS_REGEX.']+)[ \'"]?[^>]*>/i', $text, $linked_urls_pieces)){
            $linked_urls = array_unique($linked_urls_pieces[1]);
        }
        return $linked_urls;
    }


    /**
     * Cycles through items of an array every time it is called. 
     * This can be used to alternate classes for table rows:
     *
     * {loop items}
     *   <tr class="<?=$text_helper->cycle("even", "odd")?>">
     *     ... use $item ...
     *   </tr>
     * {end}
     *
     * You can use named cycles to prevent clashes in nested loops.  You'll
     * have to reset the inner cycle, manually:
     *
     * {loop items}
     *   <tr class="<?=$text_helper->cycle("even", "odd", array('name' => "row_class"))?>
     *     <td>
     *       {loop values}
     *         <span style="color:'<?=$text_helper->cycle("red", "green", "blue",array('name' => "colors"))?>'">
     *           ... use $item ...
     *         </span>
     *       {end}
     *       <php $text_helper->reset_cycle("colors"); ?>
     *     </td>
     *   </tr>
     * {end}
     */
    function cycle($first_value, $values = null)
    {
        static $cycle_position;
        $params = func_get_args();
        if(is_array($params[func_num_args()-1])){
            $options = array_pop($params);
            $name = isset($options['name']) ? $options['name'] : 'default';
        }else{
            $name = 'default';
        }

        if(isset($options['reset'])){
            $cycle_position[$options['reset']] = 0;
            if($options['reset'] == 'all'){
                $cycle_position = array();
            }
            return;
        }
        $cycle_position[$name] = !isset($cycle_position[$name]) ? 0 : $cycle_position[$name];
        $number_params = count($params);
        $current_param = $cycle_position[$name] > $number_params-1 ? 0 : $cycle_position[$name];
        $cycle_position[$name] = $current_param+1;
        return $params[$current_param];
    }

    function reset_cycle($name)
    {
        TextHelper::cycle(array('reset'=>$name));
    }


    /**
    * Returns the text with all the Markdown codes turned into HTML-tags.
    */
    function markdown($text)
    {
        if (!empty($text)) {
            require_once(AK_VENDOR_DIR.DS.'TextParsers'.DS.'markdown.php');
            $text = trim(Markdown($text));
        }
        return $text;
    }


    /**
    * Translate strings to the current locale.
    */
    function translate($string, $args = null, $locale_namespace = null)
    {
        return Ak::t($string, $args, empty($locale_namespace) ?
        AkInflector::underscore($this->_controller->getControllerName()) : $locale_namespace);
    }

    /**
    * Alias for translate
    */
    function t($string, $args = null, $locale_namespace = null)
    {
        return TextHelper::translate($string, $args, $locale_namespace);
    }


    function humanize($text)
    {
        return AkInflector::humanize($text);
    }

    /**
    * Converts an underscored or CamelCase word into a English
    * sentence.
    * 
    * The titleize function converts text like "WelcomePage",
    * "welcome_page" or  "welcome page" to this "Welcome
    * Page".
    * If second parameter is set to 'first' it will only
    * capitalize the first character of the title.
    * 
    * @access public
    * @static
    * @param    string    $word    Word to format as tile
    * @param    string    $uppercase    If set to 'first' it will only uppercase the
    * first character. Otherwise it will uppercase all
    * the words in the title.
    * @return string Text formatted as title
    */
    function titleize($text, $uppercase = '')
    {
        return AkInflector::titleize($text, $uppercase);
    }

    /**
     * Use this function to automatically handle flash messages.
     *
     * Examples:
     * 
     *    <?=$text_helper->flash();?>
     *    //will handle all flash messages automatically
     * 
     *    <?=$text_helper->flash(null,array('secconds_to_close'=>5));?>
     *   //will handle all flash messages automatically and will close in 5 secconds. NOTE. you need to include javascript dependencies for using interactive options
     *          
     */
    function flash($message = null, $options = array(), $html_options = array())
    {
        if(empty($message) && empty($this->_controller->flash)){
            return '';
        }

        $options = empty($options) ? (empty($this->_controller->flash_options) ? array() : $this->_controller->flash_options) : $options;

        $default_options = array(
        'close_button' => false,
        'seconds_to_close' => false,
        'animate'=> true,
        'effects'=> array(
        )
        );

        $options = array_merge($default_options, $options);
        if(empty($options['seconds_to_close']) && isset($options['close_in'])){
            $options['seconds_to_close'] = strtotime($options['close_in'])-time();
        }

        $options['effects'] = empty($options['effects']) ? array() : $options['effects'];
        $effects = !empty($options['effect']) && is_string($options['effect']) ? array_merge(array($options['effect']), $options['effects']) : $options['effects'];

        $options['seconds_to_close'] = empty($options['seconds_to_close']) && !empty($options['seconds']) ? $options['seconds'] : $options['seconds_to_close'];

        $html_options = array_merge(array('id'=>'flash','class'=>'flash'), $html_options);

        $close_button = !empty($options['close_button']) ? $this->_controller->asset_tag_helper->image_tag($options['close_button']).' ' : '';

        if(empty($message)){
            $message = '';
            foreach ($this->_controller->flash as $k=>$v){
                if(is_string($v)){
                    $message .= TagHelper::content_tag('div', $v, array('id'=>'flash_'.$k));
                }
            }
        }elseif (is_array($message)){
            $message = '';
            foreach ($this->_controller->flash as $k=>$v){
                if(is_string($v)){
                    $message .= TagHelper::content_tag('div', $v, array('id'=>'flash_'.$k));
                }
            }
        }
        if(empty($message)){
            return '';
        }

        $flash_message = TagHelper::content_tag('div', $close_button.$message,$html_options);

        if ($options['animate']) {
            $animation_effects = '';
            if(!empty($effects)){
                foreach ($effects as $name=>$effect_options){
                    if(is_numeric($name)){
                        $animation_effects .= $this->_controller->scriptaculous_helper->visual_effect($effect_options, $html_options['id']);
                    }else{
                        $animation_effects .= $this->_controller->scriptaculous_helper->visual_effect($name, $html_options['id'], $effect_options);
                    }
                }
            }
            if (!empty($options['seconds_to_close'])) {
                $animation_effects .= 'setTimeout(\'new Effect.Fade($("'.$html_options['id'].'"));\', '.($options['seconds_to_close']*1000).');';
            }
            if(!empty($animation_effects)){
                $flash_message .= $this->_controller->javascript_helper->javascript_tag($animation_effects);
            }
        }elseif (!empty($options['seconds_to_close'])) {
            $flash_message .= $this->_controller->javascript_helper->javascript_tag('setTimeout(\'$("'.$html_options['id'].'").hide();\', '.($options['seconds_to_close']*1000).');');
        }

        return $flash_message;
    }

    /**
     * Recodes "$text" into utf-8 from "$input_string_encoding"
     */
    function utf8($text, $input_string_encoding = null)
    {
        return Ak::utf8($text, $input_string_encoding);
    }


    /**
     * Will atempt to close unmatched tags. This is useful for truncating messages 
     * and not breaking the layout.
     */
    function close_unmatched_html($html)
    {
        preg_match_all('/<(\w+)[^>\/]*?(?!\/)>/', $html, $start_tags);
        preg_match_all('/<\/(\w+)[^>\/]*?>/', $html, $end_tags);

        $start_tags = (array)@$start_tags[1];
        $end_tags = (array)@$end_tags[1];

        if(count($start_tags) == count($end_tags)){
            return $html;
        }
        $missing_tags = array_reverse(array_diff($start_tags, $end_tags));

        foreach ($missing_tags as $missing_tag){
            if(!in_array($missing_tag,array('hr','br','img','input',''))){
                $html .= "</{$missing_tag}>";
            }
        }
        return $html;
    }

}

?>
