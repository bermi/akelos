<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2006, Akelos Media, S.L.  & Bermi Ferrer Martinez |
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
 * @package ActionView
 * @subpackage Helpers
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

require_once(AK_LIB_DIR.DS.'AkActionView'.DS.'helpers'.DS.'javascript_helper.php');

class UrlHelper extends AkObject
{
    function setController(&$controller)
    {
        $this->_controller =& $controller;
    }

    /**
    * Returns the URL for the set of +$options+ provided. This takes the same options 
    * as url_for. For a list, see the documentation for AKActionController::urlFor.
    * Note that it'll set ('only_path' => true) so you'll get /controller/action instead of the 
    * http://example.com/controller/action part (makes it harder to parse httpd log files)
    */
    function url_for($options = array(), $parameters_for_method_reference = null)
    {
        $default_options = array(
        'only_path' => true
        );
        $options = array_merge($default_options, $options);
        return $this->_controller->urlFor($options, $parameters_for_method_reference);
    }


    function modify_current_url($options_to_add = array(), $options_to_exclude = array(), $remove_unnecesary_options = true)
    {
        $options_to_exclude = $remove_unnecesary_options ? array_merge(array('ak','lang',AK_SESSION_NAME,'AK_SESSID','PHPSESSID'), $options_to_exclude) : $options_to_exclude;
        $options_to_add = array_merge($this->_controller->Request->getRouteParams(),$options_to_add);
        foreach ($options_to_exclude as $option_to_exclude){
            unset($options_to_add[$option_to_exclude]);
        }
        return $this->url_for($options_to_add);
    }

    /**
    * Creates a link tag of the given +name+ using an URL created by the set of +options+. See the valid options in
    * the documentation for ActionController::urlFor. It's also possible to pass a string instead of an array of options
    * to get a link tag that just points without consideration. If null is passed as a name, the link itself will become 
    * the name.
    *
    * The html_options has three special features. One for creating javascript confirm alerts where if you pass
    * 'confirm' => 'Are you sure?', the link will be guarded with a JS popup asking that question. 
    * If the user accepts, the link is processed, otherwise not.
    *
    * Another for creating a popup window, which is done by either passing 'popup' with true or the options of the window in 
    * Javascript form.
    *
    * And a third for making the link do a POST request (instead of the regular GET) through a dynamically added form 
    * element that is instantly submitted. Note that if the user has turned off Javascript, the request will fall back on 
    * the GET. So its your responsibility to determine what the action should be once it arrives at the controller. 
    * The POST form is turned on by passing 'post' as true. Note, it's not possible to use POST requests and popup targets 
    * at the same time (an exception will be thrown).
    *
    * Examples:
    *   $url_helper->link_to('Delete this page', array('action' => 'destroy', 'id' => $page->id ), array('confirm' => 'Are you sure?'));
    *   $url_helper->link_to('Help', array('action' => 'help'), array('popup' => true));
    *   $url_helper->link_to('Busy loop', array('action' => 'busy'), array('popup' => array('new_window', 'height=300,width=600')));
    *   $url_helper->link_to('Destroy account', array('action' => 'destroy'), array('confirm' => 'Are you sure?'), array('post' => true));
    */
    function link_to($name = null, $options = array(), $html_options = array(), $parameters_for_method_reference = null)
    {
        if (!empty($html_options)) {
            $this->convert_options_to_javascript($html_options);
            $tag_options = TagHelper::_tag_options($html_options);            
        }
        else{
            $tag_options = null;
        }
        $url = is_string($options) ? $options : $this->url_for($options, $parameters_for_method_reference);
        $name = empty($name) ? $url : $name;
        return  "<a href=\"{$url}\"{$tag_options}>{$name}</a>";
    }

    /**
    * Generates a form containing a sole button that submits to the
    * URL given by _$options_.  Use this method instead of +link_to+
    * for actions that do not have the safe HTTP GET semantics
    * implied by using a hypertext link.
    *
    * The parameters are the same as for +link_to+.  Any _html_options_
    * that you pass will be applied to the inner +input+ element.
    * In particular, pass
    * 
    *   'disabled' => true/false
    *
    * as part of _html_options_ to control whether the button is
    * disabled.  The generated form element is given the class
    * 'button-to', to which you can attach CSS styles for display
    * purposes.
    *
    * Example 1:
    *
    *   // inside of controller for "feeds"
    *   $url_helper->button_to('Edit', array('action' => 'edit', 'id' => 3));
    *
    * Generates the following HTML (sans formatting):
    *
    *   <form method="post" action="/feeds/edit/3" class="button-to">
    *     <div><input type="submit" value="Edit"  /></div>
    *   </form>
    *
    * Example 2:
    *
    *   $url_helper->button_to('Destroy', array('action' => 'destroy', 'id' => 3 , 'confirm' => 'Are you sure?'));
    *
    * Generates the following HTML (sans formatting):
    *
    *   <form method="post" action="/feeds/destroy/3" class="button-to">
    *     <div><input onclick="return confirm('Are you sure?');" value="Destroy" type="submit" /></div>
    *   </form>
    *
    * Note: This method generates HTML code that represents a form.
    * Forms are "block" content, which means that you should not try to
    * insert them into your HTML where only inline content is expected.
    * For example, you can legally insert a form inside of a <div> or
    * <td> element or in between <p> elements, but not in the middle of
    * a run of text, nor can you place a form within another form.
    * (Bottom line: Always validate your HTML before going public.)
    */
    function button_to($name, $options = array(), $html_options = array())
    {
        $html_options = $this->_convert_boolean_attributes($html_options, 'disabled');

        if(!empty($html_options['confirm'])){
            $html_options['onclick'] = 'return '.$this->_confirm_javascript_function($html_options['confirm']).';';
            unset($html_options['confirm']);
        }

        $url = is_string($options) ? $options : $this->url_for($options);

        $name = !empty($name) ? $name : (is_string($options) ?  $options : TagHelper::escape_once($this->url_for($options)));

        $html_options = array_merge($html_options,array('type'=>'submit','value'=>$name));
        return '<form method="post" action="'.$url.'" class="button-to"><div>'.
        TagHelper::tag('input', $html_options) . '</div></form>';
    }


    /**
    * Creates a link tag of the given +$name+ using an URL created by the set of +$options+, unless the current
    * request uri is the same as the link's, in which case only the name is returned. 
    * This is useful for creating link bars where you don't want to link
    * to the page currently being viewed.
    */
    function link_to_unless_current($name, $options = array(), $html_options = array(), $parameters_for_method_reference = null)
    {
        return !$this->current_page($options) ? $this->link_to_unless($options, $name, $options, $html_options, $parameters_for_method_reference) : $name;
    }

    /**
    * Create a link tag of the given +$name+ using an URL created by the set of +options+, unless +condition+
    * is true, in which case only the name is returned. 
    */
    function link_to_unless($condition, $name, $options = array(), $html_options = array(), $parameters_for_method_reference = null)
    {
        if ($condition !== true) {
            return $this->link_to($name, $options, $html_options, $parameters_for_method_reference);
        }
        return $name;
    }

    /**
    * Create a link tag of the given +name+ using an URL created by the set of +$options+, if +$condition+
    * is true, in which case only the name is returned. 
    */      
    function link_to_if($condition, $name, $options = array(), $html_options = array(), $parameters_for_method_reference = null)
    {
        return $this->link_to_unless(!$condition, $name, $options, $html_options, $parameters_for_method_reference);
    }

    /**
    * Returns true if the current page uri is generated by the options passed (in url_for format).
    */
    function current_page($options)
    {
        return ($this->url_for($options) == $this->_controller->Request->getPath());
    }

    /**
    * Creates a link tag for starting an email to the specified <tt>email_address</tt>, which is also used as the name of the
    * link unless +$name+ is specified. Additional HTML options, such as class or id, can be passed in the 
    * <tt>$html_options</tt> array.
    *
    * You can also make it difficult for spiders to harvest email address by obfuscating them.
    * Examples:
    *   $url_helper->mail_to('me@domain.com', 'My email', array('encode' => 'javascript')) =>
    *     <script type="text/javascript" language="javascript">eval(unescape('%64%6f%63%75%6d%65%6e%74%2e%77%72%69%74%65%28%27%3c%61%20%68%72%65%66%3d%22%6d%61%69%6c%74%6f%3a%6d%65%40%64%6f%6d%61%69%6e%2e%63%6f%6d%22%3e%4d%79%20%65%6d%61%69%6c%3c%2f%61%3e%27%29%3b'))</script>
    *
    *   $url_helper->mail_to('me@domain.com', 'My email', array('encode' => 'hex')) =>
    *     <a href="mailto:%6d%65@%64%6f%6d%61%69%6e.%63%6f%6d">My email</a>
    *
    * You can also specify the cc address, bcc address, subject, and body parts of the message header to create a complex e-mail 
    * using the corresponding +cc+, +bcc+, +subject+, and +body+ <tt>html_options</tt> keys. Each of these options are URI escaped 
    * and then appended to the <tt>email_address</tt> before being output. <b>Be aware that javascript keywords will not be
    * escaped and may break this feature when encoding with javascript.</b>
    * 
    * Examples:
    *   $url_helper->mail_to("me@domain.com", "My email", array('cc' => "ccaddress@domain.com", 'bcc' => "bccaddress@domain.com", 'subject' => "This is an example email", 'body' => "This is the body of the message."))   # =>
    *     <a href="mailto:me@domain.com?cc="ccaddress@domain.com"&bcc="bccaddress@domain.com"&body="This%20is%20the%20body%20of%20the%20message."&subject="This%20is%20an%20example%20email">My email</a>
    */
    function mail_to($email_address, $name = null, $html_options = array())
    {
        $name = empty($name) ? $email_address : $name;

        $default_options = array(
        'cc' => null,
        'bcc' => null,
        'subject' => null,
        'body' => null,
        'encode' => ''
        );

        $options = array_merge($default_options, $html_options);
        $encode = $options['encode'];

        $string = '';
        $extras = '';
        $extras .= !empty($options['cc']) ? "cc=".urlencode(trim($options['cc'])).'&' : '';
        $extras .= !empty($options['bcc']) ? "bcc=".urlencode(trim($options['bcc'])).'&' : '';
        $extras .= !empty($options['body']) ? "body=".urlencode(trim($options['body'])).'&' : '';
        $extras .= !empty($options['subject']) ? "subject=".urlencode(trim($options['subject'])).'&' : '';

        $extras = empty($extras) ? '' : '?'.str_replace('+','%20',rtrim($extras,'&'));

        $html_options = Ak::delete($html_options, 'cc','bcc','subject','body','encode');

        if ($encode == 'javascript'){
            $tmp  = "document.write('".TagHelper::content_tag('a', htmlentities($name, null, Ak::locale('charset')), array_merge($html_options,array('href' => 'mailto:'.$email_address.$extras )))."');";
            for ($i=0; $i < strlen($tmp); $i++){
                $string.='%'.dechex(ord($tmp[$i]));
            }
            return "<script type=\"text/javascript\">eval(unescape('$string'))</script>";

        }elseif ($encode == 'hex'){
            $encoded_email_address = '';
            $encoded_email_for_name = '';
            for ($i=0;$i<strlen($email_address);$i++){
                if(preg_match('/\w/',$email_address[$i])){
                    $encoded_email_address .= sprintf('%%%x',ord($email_address[$i]));
                }else{                    
                    if ($email_address[$i] == '@') {
                    	$encoded_email_address .= '%40';
                    }
                    elseif ($email_address[$i] == '.'){
                        $encoded_email_address .= '%2e';
                    }
                    else{
                        $encoded_email_address .= $email_address[$i];
                    }
                }
                $encoded_email_for_name .= (rand(1,2)%2 ? '&#'.ord($email_address[$i]).';' : '&#x'.dechex(ord($email_address[$i])).';');
            }

            $name = str_replace($email_address,$encoded_email_for_name,$name);

            return TagHelper::content_tag('a', $name, array_merge($html_options,array('href' => 'mailto:'.$encoded_email_address.$extras)));

        }else{
            return TagHelper::content_tag('a', $name, array_merge($html_options,array('href' => 'mailto:'.$email_address.$extras)));
        }
    }



    function convert_options_to_javascript(&$html_options)
    {
        foreach (array('confirm', 'popup', 'post') as $option){
            $$option = isset($html_options[$option]) ? $html_options[$option] : false;
            unset($html_options[$option]);
        }

        $onclick = '';
        if ($popup && $post){
            trigger_error(Ak::t("You can't use popup and post in the same link"), E_USER_ERROR);
            
        }elseif($confirm && $popup){
            $onclick = 'if ('.$this->_confirm_javascript_function($confirm).') { '.$this->_popup_javascript_function($popup).' };return false;';
            
        }elseif ($confirm && $post) {
            $onclick = 'if ('.$this->_confirm_javascript_function($confirm).') { '.$this->_post_javascript_function().' };return false;';
            
        }elseif ($confirm) {
            $onclick = 'return '.$this->_confirm_javascript_function($confirm).';';
            
        }elseif ($post) {
            $onclick = $this->_post_javascript_function().'return false;';
            
        }elseif ($popup) {
            $onclick = $this->_popup_javascript_function($popup).'return false;';
            
        }
        $html_options['onclick'] = empty($html_options['onclick']) ? $onclick : $html_options['onclick'].$onclick;
    }

    function _confirm_javascript_function($confirm)
    {
        return "confirm('".JavaScriptHelper::escape_javascript($confirm)."')";
    }



    function _popup_javascript_function($popup)
    {
        return is_array($popup) ? "window.open(this.href,'".array_shift($popup)."','".array_pop($popup)."');" : "window.open(this.href);";
    }

    function _post_javascript_function()
    {
        return "var f = document.createElement('form'); document.body.appendChild(f); f.method = 'POST'; f.action = this.href; f.submit();";
    }

    /**
    * processes the _html_options_ array, converting the boolean
    * attributes from true/false form into the form required by
    * html/xhtml.  (an attribute is considered to be boolean if
    * its name is listed in the given _$boolean_attributes_ array.)
    *
    * more specifically, for each boolean attribute in _$html_option_
    * given as:
    *
    *     "attr" => bool_value
    *
    * if the associated _bool_value_ evaluates to true, it is
    * replaced with the attribute's name; otherwise the attribute is
    * removed from the _html_options_ array.  (see the xhtml 1.0 spec,
    * section 4.5 "attribute minimization" for more:
    * http://www.w3.org/tr/xhtml1/    *h-4.5)
    *
    * returns the updated _$html_options_ array, which is also modified
    * in place.
    *
    * example:
    *
    *   $url_helper->convert_boolean_attributes( $html_options,
    *                                array('checked','disabled','readonly' ) );
    */
    function _convert_boolean_attributes(&$html_options, $boolean_attributes)
    {
        $boolean_attributes = (array)$boolean_attributes;
        foreach ($boolean_attributes as $boolean_attribute){
            if(empty($html_options[$boolean_attribute])){
                unset($html_options[$boolean_attribute]);
            }else {
                $html_options[$boolean_attribute] = $boolean_attribute;
            }
        }
        return $html_options;
    }
}


?>