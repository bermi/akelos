<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

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
class AkJavascriptHelper extends AkBaseHelper 
{

    /**
    * Returns a link that'll trigger a JavaScript +function+ using the 
    * onclick handler and return false after the fact.
    *
    * Examples:
    *   $javascript_helper->link_to_function("Greeting", "alert('Hello world!')");
    *   $javascript_helper->link_to_function($tag->image_tag("delete"), "if confirm('Really?'){ do_delete(); }");
    */
    static function link_to_function($name, $function, $html_options = array()) {
        $default_html_options = array(
        'href'    => '#',
        'onclick' => (!empty($html_options['onclick']) ? "{$html_options['onclick']}; " : ""). "{$function}; return false;"
        );

        $html_options = array_merge($default_html_options, $html_options);
        
        return AkTagHelper::content_tag('a',$name, $html_options);
    }

    /**
    * Returns a link that'll trigger a JavaScript +function+ using the 
    * onclick handler.
    *
    * Examples:
    *   $javascript_helper->button_to_function("Greeting", "alert('Hello world!')");
    *   $javascript_helper->button_to_function("Delete", "if confirm('Really?'){ do_delete(); }"));
    */
    static function button_to_function($name, $function, $html_options = array()) {
        $default_html_options = array(
        'type'  => 'button',
        'value' => $name,
        'onclick' => (!empty($html_options['onclick']) ?  "{$html_options['onclick']}; " : ""). "{$function};"
        );

        $html_options = array_merge($default_html_options, $html_options);

        return AkTagHelper::tag('input', $html_options);
    }

    /**
    * Escape carrier returns and single and double quotes for JavaScript segments.
    */
    static function escape_javascript($javascript) {
        return preg_replace(array('/\r\n|\n|\r/',"/[\"']/"), array('\\n','\\\${0}'), $javascript);
    }

    /**
    * Returns a JavaScript tag with the +content+ inside. Example:
    *   javascript_tag("alert('All is good')") => <script type="text/javascript">alert('All is good')</script>
    */
    static function javascript_tag($content) {
        return AkTagHelper::content_tag("script", AkJavascriptHelper::javascript_cdata_section($content), array('type' => 'text/javascript'));
    }

    static function javascript_cdata_section($content) {
        return "\n//<![CDATA[\n".$content."\n//]]>\n";
    }

    static function _options_for_javascript($options) {
        $_js_options = array();
        foreach ($options as $k=>$v){
            $_js_options[] = "$k:$v";
        }
        sort($_js_options);
        return '{'.join(', ',$_js_options).'}';

    }

    static function array_or_string_for_javascript($option) {
        return is_array($option) ? "['".join("', '",$option)."']" : "'".$option."'";
    }
    
    static function _array_or_string_for_javascript($option) {
        Ak::deprecateMethod(__CLASS__.'::'.__METHOD__, __CLASS__.'::'.'array_or_string_for_javascript');
        return AkJavascriptHelper::array_or_string_for_javascript($option);
    }
}
