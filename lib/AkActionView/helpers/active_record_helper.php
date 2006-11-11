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


require_once(AK_LIB_DIR.DS.'AkActionView'.DS.'helpers'.DS.'form_helper.php');

/**
* The Active Record Helper makes it easier to create forms for records kept in instance variables. The most far-reaching is the form
* method that creates a complete form for all the basic content types of the record (not associations or aggregations, though). This
* is a great of making the record quickly available for editing, but likely to prove lackluster for a complicated real-world form.
* In that case, it's better to use the input method and the specialized form methods from the FormHelper
*/
class ActiveRecordHelper extends AkActionViewHelper 
{

    /**
    * Returns a default input tag for the type of object returned by the method. Example
    * (title is a VARCHAR column and holds "Hello World"):
    *   $active_record->input('post', 'title'); =>
    *     <input id="post_title" name="post[title]" size="30" type="text" value="Hello World" />
    */
    function input($record_name, $method, $options = array())
    {
        $InstanceTag = new ActiveRecordInstanceTag($record_name, $method, $this);
        return $InstanceTag->to_tag($options);
    }

    /**
    * Returns an entire form with input tags and everything for a specified Active Record object. Example
    * (post is a new record that has a title using VARCHAR and a body using TEXT):
    *   $active_record->form('post'); =>
    *     <form action='/post/create' method='post'>
    *       <p>
    *         <label for="post_title">Title</label><br />
    *         <input id="post_title" name="post[title]" size="30" type="text" value="Hello World" />
    *       </p>
    *       <p>
    *         <label for="post_body">Body</label><br />
    *         <textarea cols="40" id="post_body" name="post[body]" rows="20">
    *           Back to the hill and over it again!
    *         </textarea>
    *       </p>
    *       <input type='submit' value='Create' />
    *     </form>
    * 
    * It's possible to specialize the form builder by using a different action name and by supplying another
    * block renderer that will be evaled by PHP. 
    * Example (entry is a new record that has a message attribute using VARCHAR):
    *
    *   $active_record->form('entry', array('action'=>'sign','input_block' => 
    *  '<p><?=AkInflector::humanize($column)?>: <?=$this->input($record_name, $column)?></p><br />'
    *   );
    *
    *     <form action='/post/sign' method='post'>
    *       Message:
    *       <input id="post_title" name="post[title]" size="30" type="text" value="Hello World" /><br />
    *       <input type='submit' value='Sign' />
    *     </form>
    */
    function form($record_name, $options = array())
    {
        $record =& $this->_controller->$record_name;

        $options['action'] = !empty($options['action']) ? $options['action'] : ($record->isNewRecord() ? 'create' : 'update');

        $action = $this->_controller->urlFor(array('action'=>$options['action'], 'id' => $record->getId()));

        $submit_value = !empty($options['submit_value']) ? $options['submit_value'] : strtoupper(preg_replace('/[^\w]/','',$options['action']));

        $contents = '';
        $contents .= $record->isNewRecord() ? '' : $this->_controller->form_helper->hidden_field($record_name, 'id');
        $contents .= $this->all_input_tags($record, $record_name, $options);
        $contents .= FormTagHelper::submit_tag(Ak::t($submit_value,array(),'helpers/active_record'));
        return TagHelper::content_tag('form', $contents, array('action'=>$action, 'method'=>'post',
        'enctype'=> !empty($options['multipart']) ? 'multipart/form-data': null ));
    }

    /**
    * Returns a string containing the error message attached to the +method+ on the +object+, if one exists.
    * This error message is wrapped in a DIV tag, which can be specialized to include both a +prepend_text+ and +append_text+
    * to properly introduce the error and a +css_class+ to style it accordingly. Examples (post has an error message
    * "can't be empty" on the title attribute):
    *
    *   <?= $active_record->error_message_on('post', 'title'); ?>
    *     <div class="formError">can't be empty</div>
    *
    *   <?=$active_record->error_message_on('post','title','Title simply ', " (or it won't work)", 'inputError') ?> =>
    *     <div class="inputError">Title simply can't be empty (or it won't work)</div>
    */
    function error_message_on($object_name, $method, $prepend_text = '', $append_text = '', $css_class = 'formError')
    {
        if($errors = $this->_controller->$object_name->getErrorsOn($method)){
            return TagHelper::content_tag('div', $prepend_text.(is_array($errors) ? array_shift($errors) : $errors).$append_text, array('class'=>$css_class));
        }
        return '';
    }

    /**
    * Returns a string with a div containing all the error messages for the object located as an instance variable by the name
    * of <tt>object_name</tt>. This div can be tailored by the following options:
    *
    * * <tt>header_tag</tt> - Used for the header of the error div (default: h2)
    * * <tt>id</tt> - The id of the error div (default: errorExplanation)
    * * <tt>class</tt> - The class of the error div (default: errorExplanation)
    *
    * NOTE: This is a pre-packaged presentation of the errors with embedded strings and a certain HTML structure. If what
    * you need is significantly different from the default presentation, it makes plenty of sense to access the $object->getErrors()
    * instance yourself and set it up. View the source of this method to see how easy it is.
    */
    function error_messages_for($object_name, $options = array())
    {
        $object =& $this->_controller->$object_name;
        if($object->hasErrors()){
            $error_list = '<ul>';
            foreach ($object->getFullErrorMessages() as $field=>$errors){
                foreach ($errors as $error){
                    $error_list .= TagHelper::content_tag('li',$error);
                }
            }
            $error_list .= '</ul>';            
            return 
            TagHelper::content_tag('div',
            TagHelper::content_tag(
            (!empty($options['header_tag']) ? $options['header_tag'] :'h2'),
            Ak::t('%number_of_errors %errors prohibited this %object_name from being saved' ,
            array('%number_of_errors'=>$object->countErrors(),'%errors'=>Ak::t(AkInflector::conditionalPlural($object->countErrors(),'error'),array(),'helpers/active_record'),
            '%object_name'=>Ak::t(AkInflector::humanize($object->getModelName()),array(),'helpers/active_record'))
            ,'helpers/active_record')).
            TagHelper::content_tag('p', Ak::t('There were problems with the following fields:',array(),'helpers/active_record')).
            $error_list,
            array('id'=> !empty($options['id']) ? $options['id'] : 'errorExplanation', 'class' => !empty($options['class']) ? $options['class'] : 'errorExplanation')
            );
        }
    }


    function all_input_tags(&$record, $record_name, $options = array())
    {
        $input_block = !empty($options['input_block']) ? $options['input_block'] : $this->default_input_block();

        $result = '';
        foreach (array_keys($record->getContentColumns()) as $column){
            ob_start();
            eval("?>$input_block<?");
            $result .= ob_get_clean()."\n";
        }
        return $result;
    }

    function default_input_block()
    {
        return '<p><label for="<?=$record_name?>_<?=$column?>"><?=AkInflector::humanize($column)?></label><br /><?=$this->input($record_name, $column)?></p>';
    }
}

class ActiveRecordInstanceTag extends AkFormHelperInstanceTag
{
    var $method_name;
    
    function ActiveRecordInstanceTag($object_name, $column_name, &$template_object)
    {        
        $this->method_name = $column_name;
        $this->AkFormHelperInstanceTag($object_name, $column_name, $template_object);
    }
    function to_tag($options = array())
    {
        switch ($this->get_column_type()) {

            case 'string':
            $field_type = strstr($this->method_name,'password') ? 'password' : 'text';
            return $this->to_input_field_tag($field_type, $options);
            break;

            case 'text':
            return $this->to_text_area_tag($options);
            break;

            case 'integer':
            case 'float':
            return $this->to_input_field_tag('text', $options);
            break;

            case 'date':
            return $this->to_date_select_tag($options);
            break;

            case 'datetime':
            case 'timestamp':
            return $this->to_datetime_select_tag($options);
            break;

            default:
            return '';
            break;
        }
    }

    function tag($name, $options)
    {
        if($this->object->hasErrors()){
            return $this->error_wrapping($this->tag_without_error_wrapping($name, $options), $this->object->getErrorsOn($this->method_name));
        }else{
            return $this->tag_without_error_wrapping($name, $options);
        }
    }

    function tag_without_error_wrapping($name, $options)
    {
        return $this->tag($name, $options);
    }


    function content_tag($name, $value, $options)
    {
        if($this->object->hasErrors()){
            return $this->error_wrapping($this->content_tag_without_error_wrapping($name, $value, $options), $this->object->getErrorsOn($this->method_name));
        }else{
            return $this->content_tag_without_error_wrapping($name, $value, $options);
        }
    }

    function content_tag_without_error_wrapping($name, $value, $options)
    {
        return $this->content_tag($name, $value, $options);
    }

    function to_date_select_tag($options = array())
    {
        if($this->object->hasErrors()){
            return $this->error_wrapping($this->to_date_select_tag_without_error_wrapping($options), $this->object->getErrorsOn($this->method_name));
        }else{
            return $this->to_date_select_tag_without_error_wrapping($options);
        }
    }

    function to_date_select_tag_without_error_wrapping($options = array())
    {
        return $this->to_date_select_tag($options);
    }

    function to_datetime_select_tag($options = array())
    {
        if($this->object->hasErrors()){
            return $this->error_wrapping($this->to_datetime_select_tag_without_error_wrapping($options), $this->object->getErrorsOn($this->method_name));
        }else{
            return $this->to_datetime_select_tag_without_error_wrapping($options);
        }
    }

    function to_datetime_select_tag_without_error_wrapping($options = array())
    {
        return $this->to_datetime_select_tag($options);
    }

    function error_wrapping($html_tag, $has_error)
    {
        return $has_error ? "<div class=\"fieldWithErrors\">$html_tag</div>" : $html_tag;
    }

    function error_message()
    {
        return $this->object->getErrorsOn($this->method_name);
    }

    function get_column_type()
    {        
        return $this->object->getColumnType($this->method_name);
    }
}

?>
