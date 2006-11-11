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
require_once(AK_LIB_DIR.DS.'AkActionView'.DS.'helpers'.DS.'asset_tag_helper.php');

/**
* Provides a number of methods for creating form tags that doesn't rely on conventions with an object assigned to the template like
* FormHelper does. With the FormTagHelper, you provide the names and values yourself.
*
* NOTE: The html options disabled, readonly, and multiple can all be treated as booleans. So specifying <tt>'disabled' => true</tt>
* will give <tt>disabled="disabled"</tt>.
*/

class FormTagHelper  extends AkActionViewHelper
{
    
    /**
      * Starts a form tag that points the action to an url configured with <tt>url_for_options</tt> just like
      * $controller->urlFor. The method for the form defaults to POST.
      *
      * Options:
      * * <tt>'multipart'</tt> - If set to true, the enctype is set to "multipart/form-data".
      * * <tt>'method'</tt> - The method to use when submitting the form, usually either "get" or "post".
      */
    function form_tag($url_for_options = array(), $options = array())
    {
        $html_options = array_merge(array('method'=>'post'), $options);
        if(!empty($html_options['multipart'])){
            $html_options['enctype'] = 'multipart/form-data';
            unset($html_options['multipart']);
        }
        
        // we need to avoid double ampersand scaping when calling TagHelper::tag method
        $html_options['action'] = str_replace('&amp;', '&', $this->_controller->urlFor($url_for_options)); 
        

        return TagHelper::tag('form', $html_options, true);
    }

    function start_form_tag($url_for_options = array(), $options = array())
    {
        return $this->form_tag($url_for_options, $options);
    }


    /**
      * Outputs '</form>'
      */
    function end_form_tag()
    {
        return '</form>';
    }

    /**
      * Creates a dropdown selection box, or if the <tt>'multiple'</tt> option is set to true, a multiple
      * choice selection box.
      *
      * Helpers::FormOptions can be used to create common select boxes such as countries, time zones, or
      * associated records.
      *
      * <tt>option_tags</tt> is a string containing the option tags for the select box:
      *   # Outputs <select id="people" name="people"><option>David</option></select>
      *  $form_tag->select_tag('people', '<option>David</option>');
      *
      * Options:
      * * <tt>'multiple'</tt> - If set to true the selection will allow multiple choices.
      */
    function select_tag($name, $option_tags = null, $options = array())
    {
        return TagHelper::content_tag('select', $option_tags, array_merge(array('name'=> $name, 'id' => $name), $options));
    }

    /**
      * Creates a standard text field.
      *
      * Options:
      * * <tt>'disabled'</tt> - If set to true, the user will not be able to use this input.
      * * <tt>'size'</tt> - The number of visible characters that will fit in the input.
      * * <tt>'maxlength'</tt> - The maximum number of characters that the browser will allow the user to enter.
      * 
      * An array of standard HTML options for the tag.
      */
    function text_field_tag($name, $value = null, $options = array())
    {
        return TagHelper::tag('input', array_merge(array('type'=>'text','name'=>$name,'id'=>$name,'value'=>$value), $options));
    }

    /**
      * Creates a hidden field.
      *
      * Takes the same options as text_field_tag
      */
    function hidden_field_tag($name, $value = null, $options = array())
    {
        return $this->text_field_tag($name, $value, array_merge($options,array('type'=>'hidden')));
    }

    /**
      * Creates a file upload field.
      *
      * If you are using file uploads then you will also need to set the multipart option for the form:
      *   <?= $form->form_tag(array('action'=>'post'),array('multipart'=>true)); ?>
      *     <label for="file">File to Upload</label> <?= $form->file_field_tag('file'); ?>
      *     <?= $form->submit_tag(); ?>
      *   <?= $form->end_form_tag(); ?>
      */
    function file_field_tag($name, $options = array())
    {
        return $this->text_field_tag($name, null, array_merge($options,array('type'=>'file')));
    }

    /**
      * Creates a password field.
      *
      * Takes the same options as text_field_tag
      */
    function password_field_tag($name = 'password', $value = null, $options = array())
    {
        return $this->text_field_tag($name, $value, array_merge($options,array('type'=>'password')));
    }

    /**
      * Creates a text input area.
      *
      * Options:
      * * <tt>'size'</tt> - A string specifying the dimensions of the textarea.
      *     # Outputs <textarea name="body" id="body" cols="25" rows="10"></textarea>
      *     <?= $form->text_area_tag('body', null, array('size'=>'25x10')); ?>
      */
    function text_area_tag($name, $content = null, $options = array())
    {
        if(!empty($options['size'])){
            list($options['cols'], $options['rows']) = split('x|X| ',trim(str_replace(' ','',$options['size'])));
            unset($options['size']);
        }
        return TagHelper::content_tag('textarea', $content, array_merge(array('name'=>$name,'id'=>$name),$options));
    }

    /**
      * Creates a check box.
      */
    function check_box_tag($name, $value = '1', $checked = false, $options = array())
    {
        $html_options = array_merge(array('type'=>'checkbox','name'=>$name,'id'=>$name,'value'=>$value),$options);
        if(!empty($html_options['checked']) || !empty($checked)){
            $html_options['checked'] = 'checked';
        }
        return TagHelper::tag('input', $html_options);
    }

    /**
      * Creates a radio button.
      */
    function radio_button_tag($name, $value, $checked = false, $options = array())
    {
        $html_options = array_merge(array('type'=>'radio','name'=>$name,'id'=>$name,'value'=>$value),$options);
        if(!empty($html_options['checked']) || !empty($checked)){
            $html_options['checked'] = 'checked';
        }
        return TagHelper::tag('input', $html_options);
    }

    /**
      * Creates a submit button with the text <tt>value</tt> as the caption. If options contains a pair with the key of "disable_with",
      * then the value will be used to rename a disabled version of the submit button.
      */
    function submit_tag($value = null, $options = array())
    {
        $value = empty($value) ? Ak::t('Save changes',array(),'helpers/form') : $value;
        if(!empty($options['disable_with'])){
            $disable_with = $options['disable_with'];
            unset($options['disable_with']);
            $options['onclick'] = "this.disabled=true;this.value='".addslashes($disable_with)."';this.form.submit();".@$options["onclick"];
        }
        return TagHelper::tag('input', array_merge(array('type'=>'submit','name'=>'commit','value'=>$value),$options));
    }

    /**
      * Displays an image which when clicked will submit the form.
      *
      * <tt>source</tt> is passed to AssetTagHelper#image_path
      */
    function image_submit_tag($source, $options = array())
    {
        return TagHelper::tag('input',array_merge(array('type'=>'image','src'=>$this->_controller->asset_tag_helper->image_path($source)),$options));
    }
}

?>
