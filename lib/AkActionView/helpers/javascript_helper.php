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
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */


require_once(AK_LIB_DIR.DS.'AkActionView'.DS.'AkActionViewHelper.php');
require_once(AK_LIB_DIR.DS.'AkActionView'.DS.'helpers'.DS.'tag_helper.php');

/**
* Provides functionality for working with JavaScript in your views.
* 
* == Ajax, controls and visual effects
* 
* * For information on using Ajax, see 
*   AkActionView/helpers/prototype_helper.php
* * For information on using controls and visual effects, see
*   AkActionView/helpers/scriptaculous_helper.php
*
* == Including the JavaScript libraries into your pages
*
* Akelos Framework includes the Prototype JavaScript framework and the Scriptaculous
* JavaScript controls and visual effects library.  If you wish to use
* these libraries and their helpers (ActionView::Helpers::PrototypeHelper
* and ActionView::Helpers::ScriptaculousHelper), you must do one of the
* following:
*
* * Use <tt><?= $asset->javascript_include_tag('defaults') ?></tt> in the HEAD 
*   section of your page (recommended): This function will return 
*   references to the JavaScript files in your <tt>public/javascripts</tt> directory. 
*   Using it is recommended as the browser can then cache the libraries instead of 
*   fetching all the functions anew on every request.
* * Use <tt><?= $asset->javascript_include_tag('prototype') ?></tt>: As above, but 
*   will only include the Prototype core library, which means you are able
*   to use all basic AJAX functionality. For the Scriptaculous-based 
*   JavaScript helpers, like visual effects, autocompletion, drag and drop 
*   and so on, you should use the method described above.
*
* For documentation on +javascript_include_tag+ see 
* AkActionView/helpers/asset_tag_helpers.php
*/

defined('AK_JAVASCRIPT_PATH') ? null : define('AK_JAVASCRIPT_PATH', AK_PUBLIC_DIR.DS.'javascripts');

class JavascriptHelper extends AkActionViewHelper 
{

    /**
    * Returns a link that'll trigger a JavaScript +function+ using the 
    * onclick handler and return false after the fact.
    *
    * Examples:
    *   $javascript->link_to_function("Greeting", "alert('Hello world!')");
    *   $javascript->link_to_function($tag->image_tag("delete"), "if confirm('Really?'){ do_delete(); }");
    */
    function link_to_function($name, $function, $html_options = array())
    {
        $default_html_options = array(
        'href'    => '#',
        'onclick' => (!empty($html_options['onclick']) ? "{$html_options['onclick']}; " : ""). "{$function}; return false;"
        );

        $html_options = array_merge($default_html_options, $html_options);
        
        return TagHelper::content_tag('a',$name, $html_options);
    }

    /**
    * Returns a link that'll trigger a JavaScript +function+ using the 
    * onclick handler.
    *
    * Examples:
    *   $javascript->button_to_function("Greeting", "alert('Hello world!')");
    *   $javascript->button_to_function("Delete", "if confirm('Really?'){ do_delete(); }"));
    */
    function button_to_function($name, $function, $html_options = array())
    {
        $default_html_options = array(
        'type'  => 'button',
        'value' => $name,
        'onclick' => (!empty($html_options['onclick']) ?  "{$html_options['onclick']}; " : ""). "{$function};"
        );

        $html_options = array_merge($default_html_options, $html_options);

        return TagHelper::tag('input', $html_options);
    }

    /**
    * Escape carrier returns and single and double quotes for JavaScript segments.
    */
    function escape_javascript($javascript)
    {
        return preg_replace(array('/\r\n|\n|\r/',"/[\"']/"), array('\\n','\\\${0}'), $javascript);
    }

    /**
    * Returns a JavaScript tag with the +content+ inside. Example:
    *   javascript_tag("alert('All is good')") => <script type="text/javascript">alert('All is good')</script>
    */
    function javascript_tag($content)
    {
        return TagHelper::content_tag("script", $this->javascript_cdata_section($content), array('type' => 'text/javascript'));
    }

    function javascript_cdata_section($content)
    {
        return TagHelper::cdata_section("\n$content\n");
    }

    function _options_for_javascript($options)
    {
        $_js_options = array();
        foreach ($options as $k=>$v){
            $_js_options[] = "$k:$v";
        }
        sort($_js_options);
        return '{'.join(', ',$_js_options).'}';

    }

    function _array_or_string_for_javascript($option)
    {
        return is_array($option) ? "['".join("', '",$option)."']" : "'".$option."'";
    }


    /**
    * Includes the Action Pack JavaScript libraries inside a single <script> 
    * tag. The function first includes prototype.js and then its core extensions,
    * (determined by filenames starting with "prototype").
    * Afterwards, any additional scripts will be included in undefined order.
    *
    * Note: The recommended approach is to copy the contents of
    * lib/action_view/helpers/javascripts/ into your application's
    * public/javascripts/ directory, and use +javascript_include_tag+ to 
    * create 
    * remote <script> links.
    */
    function define_javascript_functions()
    {
        die('This function is not recomended. Please use $asset->javascript_include_tag() instead');
    }



}
?>