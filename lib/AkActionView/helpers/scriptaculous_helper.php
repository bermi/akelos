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


require_once(AK_LIB_DIR.DS.'AkActionView'.DS.'helpers'.DS.'javascript_helper.php');

/**
* Provides a set of helpers for calling Scriptaculous JavaScript 
* functions, including those which create Ajax controls and visual effects.
*
* To be able to use these helpers, you must include the Prototype 
* JavaScript framework and the Scriptaculous JavaScript library in your 
* pages. See the documentation for ActionView::Helpers::JavaScriptHelper
* for more information on including the necessary JavaScript.
*
* The Scriptaculous helpers' behavior can be tweaked with various options.
* See the documentation at http://script.aculo.us for more information on
* using these helpers in your application.
*/

class ScriptaculousHelper extends AkActionViewHelper
{
    var $_toggle_effects = array('toggle_appear','toggle_slide','toggle_blind');

    /**
    * Returns a JavaScript snippet to be used on the Ajax callbacks for
    * starting visual effects.
    *
    * Example:
    *   <?= $prototype_helper->link_to_remote(
    *         'Reload', 
    *         'update' => 'posts', 
    *         'url' => array('action' => 'reload'), 
    *         'complete' => $scriptaculous_helper->visual_effect('highlight', 'posts', array('duration' => 0.5))) ?>
    *
    * If no element_id is given, it assumes "element" which should be a local
    * variable in the generated JavaScript execution context. This can be 
    * used for example with drop_receiving_element:
    *
    *   <?= $scriptaculous_helper->drop_receiving_element (..., array('loading' => $scriptaculous_helper->visual_effect('fade'))) ?>
    *
    * This would fade the element that was dropped on the drop receiving 
    * element.
    *
    * For toggling visual effects, you can use 'toggle_appear', 'toggle_slide', and
    * 'toggle_blind' which will alternate between appear/fade, slidedown/slideup, and
    * blinddown/blindup respectively.
    *
    * You can change the behaviour with various options, see
    * http://script.aculo.us for more documentation.
    */
    function visual_effect($name, $element_id = false, $js_options = array())
    {
        $element = $element_id ? Ak::toJson($element_id) : "element";

        if (!empty($js_options['queue']) && is_array($js_options['queue'])) {
            $js_queue = array();
            foreach ($js_options['queue'] as $k=>$v){
                $js_queue[] = ($k == 'limit' ? "$k:$v" : "$k:'$v'");
            }
            if(!empty($js_options['queue'])){
                $js_options['queue'] = '{'.join(',',$js_queue).'}';
            }
        }elseif (!empty($js_options['queue'])){
            $js_options['queue'] = "'{$js_options['queue']}'";
        }

        if(in_array('toggle_'.$name, $this->_toggle_effects)){
            return "Effect.toggle({$element},'".str_replace('toggle_','',$name)."',".JavascriptHelper::_options_for_javascript($js_options).");";
        }else{
            return "new Effect.".AkInflector::camelize($name)."({$element},".JavascriptHelper::_options_for_javascript($js_options).");";
        }
    }


    /**
    * Makes the element with the DOM ID specified by +element_id+ sortable
    * by drag-and-drop and make an Ajax call whenever the sort order has
    * changed. By default, the action called gets the serialized sortable
    * element as parameters.
    *
    * Example:
    *   <?= $scriptaculous_helper->sortable_element('my_list', 'url' => array('action' => 'order')) ?>
    *
    * In the example, the action gets a "my_list" array parameter 
    * containing the values of the ids of elements the sortable consists 
    * of, in the current order.
    *
    * You can change the behaviour with various options, see
    * http://script.aculo.us for more documentation.
    */
    function sortable_element($element_id, $options = array())
    {
        $options['with'] = !empty($options['with']) ? $options['with'] : "Sortable.serialize('{$element_id}')";
        $options['onUpdate'] = !empty($options['onUpdate']) ? $options['onUpdate'] : "function(){".PrototypeHelper::remote_function($options)."}";

        foreach ($options as $key=>$option) {

            /**
             * @todo: fix this code when implemented PrototypeHelper
             * 
             * if (in_array(PrototypeHelper::AJAX_OPTIONS[$option])) {
             *      unset($options[$option]);
             * }
             */


        }

        $more_ajax_options = array('tag', 'overlap', 'constraint', 'handle');

        foreach ($more_ajax_options as $key=>$option) {
            if (in_array($options[$option])) {
                $options[$option] = "'{$options[$option]}'";
            }
        }

        if (in_array($options['containment'])){
            $options['containment'] = JavascriptHelper::_array_or_string_for_javascript($options['containment']);
        }

        if (in_array($options['only'])){
            $options['only'] = JavascriptHelper::_array_or_string_for_javascript($options['only']);
        }

        return JavascriptHelper::javascript_tag("Sortable.create('{$element_id}', ".JavascriptHelper::_options_for_javascript($options).")");
    }


    /**
    * Makes the element with the DOM ID specified by +element_id+ draggable.
    *
    * Example:
    *   <?= $scriptaculous_helper->draggable_element("my_image", 'revert' => true)
    * 
    * You can change the behaviour with various options, see
    * http://script.aculo.us for more documentation. 
    */
    function draggable_element($element_id, $options = array())
    {
        return JavascriptHelper::javascript_tag(ScriptaculousHelper::draggable_element_js($element_id, $options));
    }

    function draggable_element_js($element_id, $options = array())
    {
        return "new Draggable('{$element_id}', ".JavascriptHelper::_options_for_javascript($options).")";
    }


    /**
    * Makes the element with the DOM ID specified by +element_id+ receive
    * dropped draggable elements (created by draggable_element).
    * and make an AJAX call  By default, the action called gets the DOM ID 
    * of the element as parameter.
    *
    * Example:
    *   <?= $scriptaculous_helper->drop_receiving_element("my_cart", array('url' => 
    *     array('controller' => "cart", 'action' => "add" ))) ?>
    *
    * You can change the behaviour with various options, see
    * http://script.aculo.us for more documentation.
    */
    function drop_receiving_element($element_id, $options = array())
    {
        $options['with'] = !empty($options['with']) ? $options['with'] : "'id='".urlencode(element.id);
        $options['onDrop'] = !empty($options['onDrop']) ? $options['onDrop'] : "function(element){". PrototypeHelper::remote_function($options) . "}";

        foreach ($options as $key=>$option) {
            /**
             * @todo: fix this code when implemented PrototypeHelper
             * 
             * if (in_array(PrototypeHelper::AJAX_OPTIONS[$option])) {
             *      unset($options[$option]);
             * }
             */
        }

        if (in_array($options['accept'])){
            $options['accept'] = JavascriptHelper::_array_or_string_for_javascript($options['accept']);
        }

        if (in_array($options['hoverclass'])){
            $options['hoverclass'] = "'{$options['hoverclass']}'";
        }

        return JavascriptHelper::javascript_tag("Droppables.add('{$element_id}', ".JavascriptHelper::_options_for_javascript($options).")");
    }

}

?>
