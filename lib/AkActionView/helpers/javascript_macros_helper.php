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
 * @author Jerome Loyet
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */


require_once(AK_LIB_DIR.DS.'AkActionView'.DS.'helpers'.DS.'tag_helper.php');
require_once(AK_LIB_DIR.DS.'AkActionView'.DS.'helpers'.DS.'form_helper.php');


/**
* Provides a set of helpers for creating JavaScript macros that rely on and often bundle methods from JavaScriptHelper into
* larger units. These macros are deprecated and will be removed on Akelos 0.9
* 
* @deprecated 
*/
class JavascriptMacrosHelper extends AkActionViewHelper
{

    /**
    * Makes an HTML element specified by the DOM ID +field_id+ become an in-place
    * editor of a property.
    *
    * A form is automatically created and displayed when the user clicks the element,
    * something like this:
    * <form id="myElement-in-place-edit-form" target="specified url">
    *   <input name="value" text="The content of myElement"/>
    *   <input type="submit" value="ok"/>
    *   <a onclick="javascript to cancel the editing">cancel</a>
    * </form>
    * 
    * The form is serialized and sent to the server using an AJAX call, the action on
    * the server should process the value and return the updated value in the body of
    * the reponse. The element will automatically be updated with the changed value
    * (as returned from the server).
    * 
    * Required +options+ are:
    * <tt>url</tt>::       Specifies the url where the updated value should
    *                       be sent after the user presses "ok".
    * 
    * Addtional +options+ are:
    * <tt>rows</tt>::              Number of rows (more than 1 will use a TEXTAREA)
    * <tt>cancel_text</tt>::       The text on the cancel link. (default: "cancel")
    * <tt>save_text</tt>::         The text on the save link. (default: "ok")
    * <tt>external_control</tt>::  The id of an external control used to enter edit mode.
    * <tt>options</tt>::           Pass through options to the AJAX call (see prototype's Ajax.Updater)
    * <tt>with</tt>::              JavaScript snippet that should return what is to be sent
    *                               in the AJAX call, +form+ is an implicit parameter
    * @deprecated 
    */
    function in_place_editor($field_id, $options = array())
    {
        $function =  "new Ajax.InPlaceEditor(";
        $function .= "'{$field_id}', ";
        $function .= "'".UrlHelper::url_for($options['url'])."'";

        $js_options = array();
        if (!empty($options['cancel_text'])){
            $js_options['cancelText'] = AK::t("{$options['cancel_text']}");
        }
        if (!empty($options['save_text'])){
            $js_options['okText'] = AK::t("{$options['save_text']}");
        }
        if (!empty($options['rows'])){
            $js_options['rows'] = $options['rows'];
        }
        if (!empty($options['external_control'])){
            $js_options['externalControl'] = $options['external_control'] ;
        }
        if (!empty($options['options'])){
            $js_options['ajaxOptions'] = $options['options'];
        }
        if (!empty($options['with'])){
            $js_options['callback'] = "function(form) { return {$options['with']} }" ;
        }
        if (!empty($js_options)) {
            $function .= (', ' . JavaScriptHelper::_options_for_javascript($js_options));
        }
        $function .= ')';

        return JavaScriptHelper::javascript_tag($function);
    }


    /**
      * Adds AJAX autocomplete functionality to the text input field with the 
      * DOM ID specified by +field_id+.
      *
      * This function expects that the called action returns a HTML <ul> list,
      * or nothing if no entries should be displayed for autocompletion.
      *
      * You'll probably want to turn the browser's built-in autocompletion off,
      * so be sure to include a autocomplete="off" attribute with your text
      * input field.
      *
      * The autocompleter object is assigned to a Javascript variable named <tt>field_id</tt>_auto_completer.
      * This object is useful if you for example want to trigger the auto-complete suggestions through
      * other means than user input (for that specific case, call the <tt>activate</tt> method on that object). 
      * 
      * Required +options+ are:
      * <tt>url</tt>::       URL to call for autocompletion results
      *                       in url_for format.
      * 
      * Addtional +options+ are:
      * <tt>update</tt>::    Specifies the DOM ID of the element whose 
      *                       innerHTML should be updated with the autocomplete
      *                       entries returned by the AJAX request. 
      *                       Defaults to field_id + '_auto_complete'
      * <tt>with</tt>::      A JavaScript expression specifying the
      *                       parameters for the XMLHttpRequest. This defaults
      *                       to 'fieldname=value'.
      * <tt>indicator</tt>:: Specifies the DOM ID of an element which will be
      *                       displayed while autocomplete is running.
      * <tt>tokens</tt>::    A string or an array of strings containing
      *                       separator tokens for tokenized incremental 
      *                       autocompletion. Example: <tt>tokens => ','</tt> would
      *                       allow multiple autocompletion entries, separated
      *                       by commas.
      * <tt>min_chars</tt>:: The minimum number of characters that should be
      *                       in the input field before an Ajax call is made
      *                       to the server.
      * <tt>on_hide</tt>::   A Javascript expression that is called when the
      *                       autocompletion div is hidden. The expression
      *                       should take two variables: element and update.
      *                       Element is a DOM element for the field, update
      *                       is a DOM element for the div from which the
      *                       innerHTML is replaced.
      * <tt>on_show</tt>::   Like on_hide, only now the expression is called
      *                       then the div is shown.
      * <tt>select</tt>::    Pick the class of the element from which the value for 
      *                       insertion should be extracted. If this is not specified,
      *                       the entire element is used.
      * @deprecated 
      */
    function auto_complete_field($field_id, $options = array())
    {
        $function =  "var {$field_id}_auto_completer = new Ajax.Autocompleter(";
        $function .= "'{$field_id}', ";
        $function .= !empty($options['update']) ? "'{$options['update']}', " : "'{$field_id}_auto_complete', ";
        $function .= "'".UrlHelper::url_for($options['url'])."'";

        $js_options = array();
        if (!empty($options['tokens'])){
            $js_options['tokens'] = JavaScriptHelper::_array_or_string_for_javascript($options['tokens']) ;
        }
        if (!empty($options['with'])) {
            $js_options['callback'] = "function(element, value) { return {$options['with']} }";
        }
        if (!empty($options['indicator'])) {
            $js_options['indicator'] = "'{$options['indicator']}'";
        }
        if (!empty($options['select'])) {
            $js_options['select'] = "'{$options['select']}'";
        }

        $default_options = array(
        'on_show' => 'onShow',
        'on_hide' => 'onHide',
        'min_chars' => 'min_chars'
        );

        foreach ($default_options as $key=>$default_option) {
            if (!empty($options[$key])) {
                $js_options[$default_option] = $options[$key];
            }
        }
        $function .= ', '.JavaScriptHelper::_options_for_javascript($js_options).')';
        return JavaScriptHelper::javascript_tag($function);
    }

    /**
      * Use this method in your view to generate a return for the AJAX autocomplete requests.
      *
      * Example action:
      *
      *   function auto_complete_for_item_title()
      *   {
      *     $this->items = $Item->find('all', array('conditions' => array('strtolower($description).' LIKE ?', '%' . strtolower($this->_controller->Request->getRawPostData(). '%' ))))
      *     return $this->_controller->render(array('inline'=> '<?= $javascript_macros->auto_complete_result(@$items, 'description') ?>'));
      *   }
      *
      * The auto_complete_result can of course also be called from a view belonging to the 
      * auto_complete action if you need to decorate it further.
      *
      * @deprecated 
      */
    function auto_complete_result($entries, $field, $phrase = null)
    {
        if (empty($entries)) {
            return '';
        }
        foreach ($entries as $entry) {
            $items[] = TagHelper::content_tag('li',!empty($phrase) ? TextHelper::highlight(TextHelper::h($entry[$field]), $phrase) : TextHelper::h(@$entry[$field]));
        }
        return TagHelper::content_tag('ul', join('', array_unique($items)));
    }


    /**
      * Wrapper for text_field with added AJAX autocompletion functionality.
      *
      * In your controller, you'll need to define an action called
      * auto_complete_for_object_method to respond the AJAX calls,
      * 
      * @deprecated 
      */
    function text_field_with_auto_complete($object, $method, $tag_options = array(), $completion_options = array())
    {
        if (!isset($tag_options['autocomplete'])) $tag_options['autocomplete'] = "off";

        return (
        !empty($completion_options['skip_style']) ? "" : $this->_auto_complete_stylesheet()) .
        $this->_controller->form_helper->text_field($object, $method, $tag_options) .
        TagHelper::content_tag('div', '', array('id' => "{$object}_{$method}_auto_complete", 'class' => 'auto_complete')) .
        $this->auto_complete_field("{$object}_{$method}", array_merge(array('url' => array('action' => "auto_complete_for_{$object}_{$method}" )), $completion_options)
        );
    }


    /**
       * @deprecated 
       */
    function _auto_complete_stylesheet()
    {
        return TagHelper::content_tag('style',
        <<<EOT
          div.auto_complete {
              width: 350px;
              background: #fff;
            }
            div.auto_complete ul {
              border:1px solid #888;
              margin:0;
              padding:0;
              width:100%;
              list-style-type:none;
            }
            div.auto_complete ul li {
              margin:0;
              padding:3px;
            }
            div.auto_complete ul li.selected { 
              background-color: #ffb; 
            }
            div.auto_complete ul strong.highlight { 
              color: #800; 
              margin:0;
              padding:0;
            }
EOT
);
    }

}
?>
