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


require_once(AK_LIB_DIR.DS.'AkActionView'.DS.'helpers'.DS.'javascript_helper.php');

/**
* Provides a set of helpers for calling Prototype JavaScript functions, 
* including functionality to call remote methods using 
* Ajax[http://www.adaptivepath.com/publications/essays/archives/000385.php]. 
* This means that you can call actions in your controllers without 
* reloading the page, but still update certain parts of it using 
* injections into the DOM. The common use case is having a form that adds
* a new element to a list without reloading the page.
*
* To be able to use these helpers, you must include the Prototype 
* JavaScript framework in your pages. See the documentation for 
* ActionView/helpers/javascript_helper.php for more information on including 
* the necessary JavaScript.
*
* See link_to_remote for documentation of options common to all Ajax
* helpers.
*
* See also ActionView/helpers/scriptaculous_helper.php for helpers which work
* with the Scriptaculous controls and visual effects library.
*
* See JavaScriptGenerator for information on updating multiple elements
* on the page in an Ajax response. 
*/
class PrototypeHelper extends AkActionViewHelper
{
    function getCallbacks()
    {
        if(empty($this->callbacks)){
            $callbacks = array_merge(array('uninitialized', 'loading', 'loaded',
            'interactive', 'complete', 'failure', 'success'),
            range(100,599));
            if(empty($this)) {
                return $callbacks;
            }
            $this->callbacks = $callbacks;
        }
        return $this->callbacks;
    }

    function getAjaxOptions()
    {
        if(empty($this->ajax_options)){
            $ajax_options = array_merge(array('before', 'after', 'condition', 'url',
            'asynchronous', 'method', 'insertion', 'position',
            'form', 'with', 'update', 'script'),
            $this->getCallbacks());
            if(empty($this)) {
                return $ajax_options;
            }
            $this->ajax_options = $ajax_options;
        }
        return $this->ajax_options;
    }


    /**
    * Returns a link to a remote action defined by <tt>options['url']</tt> 
    * (using the url_for format) that's called in the background using 
    * XMLHttpRequest. The result of that request can then be inserted into a
    * DOM object whose id can be specified with <tt>options['update']</tt>. 
    * Usually, the result would be a partial prepared by the controller with
    * either render_partial or render_partial_collection. 
    *
    * Examples:
    *   $prototype_helper->link_to_remote('Delete this post', array('url' => array('action' => 'destroy', 'id' => $_POST['id']), array('update' => 'posts'));
    *   $prototype_helper->link_to_remote(Asset$this->_controller->tag_helper->image_tag('refresh'), array('url' => array('action' => 'list_emails'), array('update => 'emails'));
    *
    * You can also specify a hash for <tt>options['update']</tt> to allow for
    * easy redirection of output to an other DOM element if a server-side 
    * error occurs:
    *
    * Example:
    *   $prototype_helper->link_to_remote('Delete this post', array('url' => array('action' => 'destroy', 'id' => $_POST['id']), array('update' => array('success' => 'posts', 'failure' => 'error');
    *
    * Optionally, you can use the <tt>options['position']</tt> parameter to 
    * influence how the target DOM element is updated. It must be one of 
    * <tt>'before'</tt>, <tt>'top'</tt>, <tt>'bottom'</tt>, or <tt>'after'</tt>.
    *
    * By default, these remote requests are processed asynchronous during 
    * which various JavaScript callbacks can be triggered (for progress 
    * indicators and the likes). All callbacks get access to the 
    * <tt>request</tt> object, which holds the underlying XMLHttpRequest. 
    *
    * To access the server response, use <tt>request.responseText</tt>, to
    * find out the HTTP status, use <tt>request.status</tt>.
    *
    * Example:
    * 
    *   $prototype_helper->link_to_remote($word, array('url' => array('action' => 'undo', 'n' => $word_counter) , 'complete' => 'undoRequestCompleted(request)');
    *
    * The callbacks that may be specified are (in order):
    *
    * <tt>'loading'</tt>::       Called when the remote document is being 
    *                           loaded with data by the browser.
    * <tt>'loaded'</tt>::        Called when the browser has finished loading
    *                           the remote document.
    * <tt>'interactive'</tt>::   Called when the user can interact with the 
    *                           remote document, even though it has not 
    *                           finished loading.
    * <tt>'success'</tt>::       Called when the XMLHttpRequest is completed,
    *                           and the HTTP status code is in the 2XX range.
    * <tt>'failure'</tt>::       Called when the XMLHttpRequest is completed,
    *                           and the HTTP status code is not in the 2XX
    *                           range.
    * <tt>'complete'</tt>::      Called when the XMLHttpRequest is complete 
    *                           (fires after success/failure if they are 
    *                           present).
    *                     
    * You can further refine <tt>'success'</tt> and <tt>'failure'</tt> by 
    * adding additional callbacks for specific status codes.
    *
    * Example:
    *   $this->link_to_remote($word, array('url' => array('action' => 'action')), array('404' => "alert('Not found...? Wrong URL...?')"), array('failure' => "alert('HTTP Error ' + request.status + '!')"));
    *
    * A status code callback overrides the success/failure handlers if present.
    *
    * If you for some reason or another need synchronous processing (that'll
    * block the browser while the request is happening), you can specify 
    * <tt>options['type'] = 'synchronous'</tt>.
    *
    * You can customize further browser side call logic by passing in
    * JavaScript code snippets via some optional parameters. In their order 
    * of use these are:
    *
    * <tt>'confirm'</tt>::      Adds confirmation dialog.
    * <tt>'condition'</tt>::    Perform remote request conditionally
    *                          by this expression. Use this to
    *                          describe browser-side conditions when
    *                          request should not be initiated.
    * <tt>'before'</tt>::       Called before request is initiated.
    * <tt>'after'</tt>::        Called immediately after request was
    *                          initiated and before <tt>'loading'</tt>.
    * <tt>'submit'</tt>::       Specifies the DOM element ID that's used
    *                          as the parent of the form elements. By 
    *                          default this is the current form, but
    *                          it could just as well be the ID of a
    *                          table row or any other DOM element.
    */
    function link_to_remote($name, $options = array(), $html_options = array())
    {
        return $this->_controller->javascript_helper->link_to_function($name, $this->remote_function($options), $html_options);

    }


    /**
    * Periodically calls the specified url (<tt>options[:url]</tt>) every 
    * <tt>options['frequency']</tt> seconds (default is 10). Usually used to
    * update a specified div (<tt>options['update']</tt>) with the results 
    * of the remote call. The options for specifying the target with 'url' 
    * and defining callbacks is the same as link_to_remote.
    */
    function periodically_call_remote($options = array())
    {
        $frequency = !empty($options['frequency']) ? $options['frequency'] : 10; // every ten seconds by default
        $code = "new PeriodicalExecuter(function() {".$this->remote_function($options)."}, {$frequency})";
        return $this->_controller->javascript_helper->javascript_tag($code);
    }

    /**
    * Returns a form tag that will submit using XMLHttpRequest in the 
    * background instead of the regular reloading POST arrangement. Even 
    * though it's using JavaScript to serialize the form elements, the form
    * submission will work just like a regular submission as viewed by the
    * receiving side (all elements available in @params). The options for 
    * specifying the target with 'url' and defining callbacks is the same as
    * link_to_remote.
    *
    * A 'fall-through' target for browsers that doesn't do JavaScript can be
    * specified with the 'action'/'method' options on 'html'.
    *
    * Example:
    *   $prototype_helper->form_remote_tag('html' => array('action' => $this->_controller->url_helper->url_for(array('controller' => 'some', 'action' => 'place')));
    *   $prototype_helper->form_remote_tag('url' => array('controller' => 'foo', 'action' => 'bar'), 'update' => 'div_to_update', html => array('id' => 'form_id'));
    *
    * The Hash passed to the 'html' key is equivalent to the options (2nd) 
    * argument in the FormTagHelper.form_tag method.
    *
    * By default the fall-through action is the same as the one specified in 
    * the 'url' (and the default method is 'post').
    */
    function form_remote_tag($options = array())
    {
        
        $options['url'] = empty($options['url']) ? array() : $options['url'];
        
        $options['form'] = true;
        $options['html'] = empty($options['html']) ? array() : $options['html'];
        $options['html']['onsubmit'] = $this->remote_function($options).'; return false;';
        $options['html']['action'] = !empty($options['html']['action']) ? $options['html']['action'] : (is_array($options['url']) ? $this->_controller->url_helper->url_for($options['url']) : $options['url']);
        $options['html']['method'] = !empty($options['html']['method']) ? $options['html']['method'] : 'post';

        return $this->_controller->tag_helper->tag('form', $options['html'], true);

    }

    /**
    * Works like form_remote_tag, but uses form_for semantics.
    */
    function remote_form_for($object_name, $object, $options = array(), $proc)
    {
        //$this->_controller->text_helper->concat($this->_controller->form_remote_tag($options),proc.binding);
        return $this->_controller->form_helper->fields_for($object_name,$object,$proc);
        //return $this->_controller->text_helper->concat('</form>', proc.binding);
    }

    /* Alias: remote_form_for */
    function form_remote_for($object_name, $object, $options = array(), $proc)
    {
        return $this->remote_form_for($object_name, $object, $options, $proc);
    }

    /**
    * Returns a button input tag that will submit form using XMLHttpRequest 
    * in the background instead of regular reloading POST arrangement. 
    * <tt>options</tt> argument is the same as in <tt>form_remote_tag</tt>.
    */
    function submit_to_remote($name, $value, $options = array())
    {
        $options['with'] = !empty($options['with']) ? $options['with'] : 'Form.serialize(this.form)';

        $options['html'] = empty($options['html']) ? array() : $options['html'];
        $options['html']['type'] = 'button';
        $options['html']['onclick'] = $this->remote_function($options).'; return false;';
        $options['html']['name'] = $name;
        $options['html']['value'] = $value;

        return $this->_controller->tag_helper->tag('input', $options['html'], false);
    }

    /**
    * Returns a JavaScript function (or expression) that'll update a DOM 
    * element according to the options passed.
    *
    * * <tt>'content'</tt>: The content to use for updating. Can be left out if using block, see example.
    * * <tt>'action'</tt>: Valid options are 'update' (assumed by default), 'empty', 'remove'
    * * <tt>'position'</tt> If the 'action' is 'update', you can optionally 
    *   specify one of the following positions: 'before', 'top', 'bottom', 'after'.
    *
    * Examples:
    *   <?= $javascript_helper->javascript_tag($prototype_helper->update_element_function('products', array('position' => 'bottom'), array('content' => '<p>New product!</p>')) ?>
    *
    *   <% replacement_function = update_element_function("products") do %>
    *     <p>Product 1</p>
    *     <p>Product 2</p>
    *   <% end %>
    *   <%= javascript_tag(replacement_function) %>
    *
    * This method can also be used in combination with remote method call 
    * where the result is evaluated afterwards to cause multiple updates on
    * a page. Example:
    *
    *   * Calling view
    *   <?= $this->_controller->form_helper->form_remote_tag(array('url' => array('action' => 'buy')), array('complete' => evaluate_remote_response)) ?>
    *   all the inputs here...
    *
    *   * Controller action
    *   function buy(){
    *     $this->_controller->product = $this->_controller->Product->find(1);
    *   }
    *
    *   * Returning view
    *   <?= $prototype_helper->update_element_function('cart', array('action' => 'update', 'position' => 'bottom', 'content' => "<p>New Product: {$product.name}</p>")) ?>
    * 
    *   <% update_element_function("status", :binding => binding) do %>
    *     You've bought a new product!
    *   <% end %>
    *
    * Notice how the second call doesn't need to be in an ERb output block
    * since it uses a block and passes in the binding to render directly. 
    * This trick will however only work in ERb (not Builder or other 
    * template forms).
    *
    * See also JavaScriptGenerator and update_page.
    */
    function update_element_function($element_id, $options = array())
    {
        $content = !empty($options['content']) ? $this->_controller->javascript_helper->escape_javascript($options['content']) : '';
        $content = empty($content) && func_num_args() == 3 ? func_get_arg(2) : (is_string($options) ? $options : $content);
        $action = !empty($options['action']) ? $options['action'] : 'update';

        switch ($action) {

            case 'update':
            if (!empty($options['position'])){
                $javascript_function = "new Insertion.".AkInflector::camelize($options['position'])."('{$element_id}','{$content}')";
            }else{
                $javascript_function = "$('{$element_id}').innerHTML = '{$content}'";
            }
            break;

            case 'empty':
            $javascript_function =  "$('{$element_id}').innerHTML = ''";
            break;

            case 'remove':
            $javascript_function = "Element.remove('{$element_id}')";
            break;

            default:
            trigger_error(Ak::t('Invalid action, choose one of update, remove, empty'), E_USER_WARNING);
            break;
        }

        $javascript_function .= ";\n";
        return !empty($options['binding']) ? $this->_controller->text_helper->concat($javascript_function, $options['binding']) : $javascript_function;
    }

    /**
    * Returns 'eval(request.responseText)' which is the JavaScript function
    * that form_remote_tag can call in 'complete' to evaluate a multiple
    * update return document using update_element_function calls.
    */
    function evaluate_remote_response()
    {
        return "eval(request.responseText)";
    }

    /**
    * Returns the JavaScript needed for a remote function.
    * Takes the same arguments as link_to_remote.
    * 
    * Example:
    *   <select id="options" onchange="<?= $prototype_helper->remote_function(array('update' => 'options',  'url' => array('action' => 'update_options' )) ?>">
    *     <option value="0">Hello</option>
    *     <option value="1">World</option>
    *   </select>
    */
    function remote_function($options = array())
    {

        $javascript_options = $this->_optionsForAjax($options);

        if (!empty($options['update'])) {

            if (is_array($options['update'])) {
                $update = array();
                if (!empty($options['update']['success'])) {
                    $update[] = "success:'{$options['update']['success']}'";
                }
                if (!empty($options['update']['failure'])) {
                    $update[] = "failure:'{$options['update']['failure']}'";
                }
                $update  = '{'. join(',',$update). '}';
            }else{
                $update = '';
                $update .= "'{$options['update']}'";
            }
        }
        $function = empty($update) ? "new Ajax.Request(" : "new Ajax.Updater({$update}, ";
        $function .= "'".(is_array($options['url'])?$this->_controller->url_helper->url_for($options['url']):$options['url'])."'";
        $function .= ", {$javascript_options})";

        if (!empty($options['before'])) {
            $function = "{$options['before']}; {$function}";
        }
        if (!empty($options['after'])) {
            $function = "{$function}; {$options['after']}";
        }
        if (!empty($options['condition'])) {
            $function = "if ({$options['condition']}) { {$function}; }";
        }
        if (!empty($options['confirm'])) {
            $function = "if (confirm('".$this->_controller->javascript_helper->escape_javascript($options['confirm'])."')) { {$function}; }";
        }

        return $function;
    }

    /**
    * Observes the field with the DOM ID specified by +field_id+ and makes
    * an Ajax call when its contents have changed.
    * 
    * Required +options+ are:
    * <tt>'url</tt>::       +url_for+-style options for the action to call
    *                       when the field has changed.
    * 
    * Additional options are:
    * <tt>frequency</tt>:: The frequency (in seconds) at which changes to
    *                       this field will be detected. Not setting this
    *                       option at all or to a value equal to or less than
    *                       zero will use event based observation instead of
    *                       time based observation.
    * <tt>update</tt>::    Specifies the DOM ID of the element whose 
    *                       innerHTML should be updated with the
    *                       XMLHttpRequest response text.
    * <tt>with</tt>::      A JavaScript expression specifying the
    *                       parameters for the XMLHttpRequest. This defaults
    *                       to 'value', which in the evaluated context 
    *                       refers to the new field value.
    * <tt>on</tt>::        Specifies which event handler to observe. By default,
    *                       it's set to "changed" for text fields and areas and
    *                       "click" for radio buttons and checkboxes. With this,
    *                       you can specify it instead to be "blur" or "focus" or
    *                       any other event.
    *
    * Additionally, you may specify any of the options documented in
    * link_to_remote.
    */
    function observe_field($field_id, $options = array())
    {
        if (!empty($options['frequency']) && $options['frequency']>0) {
            return $this->_buildObserver('Form.Element.Observer', $field_id, $options);
        }else{
            return $this->_buildObserver('Form.Element.EventObserver', $field_id, $options);
        }
    }

    /**
    * Like +observe_field+, but operates on an entire form identified by the
    * DOM ID +form_id+. +options+ are the same as +observe_field+, except 
    * the default value of the <tt>with</tt> option evaluates to the
    * serialized (request string) value of the form.
    */
    function observe_form($form_id, $options = array())
    {
        if (!empty($options['frequency']) && $options['frequency']>0) {
            return $this->_buildObserver('Form.Observer', $form_id, $options);
        }else{
            return $this->_buildObserver('Form.EventObserver', $form_id, $options);
        }
    }


    function _buildObserver($class, $name, $options = array())
    {
        if(!empty($options['with']) && !strstr($options['with'],'=')){
            $options['with'] = "'{$options['with']}=' + value";
        }elseif(!empty($options['update'])){
            $options['with'] = empty($options['with']) ? 'value' : $options['with'];
        }
        $callback = empty($options['function']) ? $this->remote_function($options) : $options['function'];
        $javascript  = "new {$class}('{$name}', ";
        $javascript .= empty($options['frequency']) ? '' : "{$options['frequency']}, ";
        $javascript .= "function(element, value) {";
        $javascript .= "{$callback}}";
        $javascript .= empty($options['on']) ? '' : ", '{$options['on']}'";
        $javascript .= ")";

        return $this->_controller->javascript_helper->javascript_tag($javascript);
    }

    function _buildCallbacks($options)
    {
        $callbacks = array();
        $this->callbacks = $this->getCallbacks();
        foreach ($options as $callback=>$code){
            if(in_array($callback, $this->callbacks)){
                $name = 'on' . ucfirst($callback);
                $callbacks[$name] = "function(request){{$code};}";
            }
        }
        return $callbacks;
    }

    function _optionsForAjax($options)
    {
        $js_options = $this->_buildCallbacks($options);

        empty($options['type']) ? null : ($js_options['asynchronous'] = $options['type'] != 'synchronous' ? 'asynchronous' : 'synchronous');
        empty($options['method']) ? null : $js_options['method'] = $this->_methodOptionToString($options['method']);
        empty($options['position']) ? null : $js_options['insertion'] = "Insertion.".AkInflector::camelize($options['position']);
        isset($options['script']) ? $js_options['evalScripts'] = 'true' : null;

        if(!empty($options['form'])){
            $js_options['parameters'] = 'Form.serialize(this)';
        }elseif(!empty($options['submit'])){
            $js_options['parameters'] = "Form.serialize('{$options['submit']}')";
        }elseif(!empty($options['with'])){
            $js_options['parameters'] = $options['with'];
        }

        return $this->_controller->javascript_helper->_options_for_javascript($js_options);
    }

    function _methodOptionToString($method)
    {
        return is_string($method) && substr($method,0,1) == "'" ? $method : "'$method'";
    }
}

?>