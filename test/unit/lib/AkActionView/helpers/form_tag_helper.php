<?php

require_once('_HelpersUnitTester.php');
require_once(AK_LIB_DIR.DS.'AkActionView'.DS.'helpers'.DS.'form_tag_helper.php');


class FormTagHelperTests extends HelpersUnitTester 
{    
    function test_for_form_tag_helpers()
    {
        //$ActiveRecord = &new MockAkActiveRecord($this);
        //$ActiveRecord->setReturnValue('get', '1978-06-16 04:37:00');

        $Controller = &new MockAkActionController($this);
        $Controller->setReturnValue('urlFor','/url/for/test');
        $form_tag = new FormTagHelper();
        $form_tag->setController($Controller);

        $this->assertEqual($form_tag->form_tag(),'<form action="/url/for/test" method="post">');
        $this->assertEqual($form_tag->form_tag(array(),array('method'=>'get')),'<form action="/url/for/test" method="get">');
        $this->assertEqual($form_tag->form_tag(array(),array('multipart'=>true)),'<form action="/url/for/test" enctype="multipart/form-data" method="post">');
        $this->assertEqual($form_tag->end_form_tag(),'</form>');
        $this->assertEqual($form_tag->start_form_tag(),'<form action="/url/for/test" method="post">');


        $this->assertEqual($form_tag->select_tag('person','<option>Bermi</option>',array('id'=>'bermi')),'<select id="bermi" name="person"><option>Bermi</option></select>');
        $this->assertEqual($form_tag->text_field_tag('person', 'Bermi',array('id'=>'bermi')),'<input id="bermi" name="person" type="text" value="Bermi" />');
        $this->assertEqual($form_tag->hidden_field_tag('person', 'Bermi',array('id'=>'bermi')),'<input id="bermi" name="person" type="hidden" value="Bermi" />');
        $this->assertEqual($form_tag->file_field_tag('photo', array('id'=>'pick_photo')),'<input id="pick_photo" name="photo" type="file" />');
        $this->assertEqual($form_tag->password_field_tag('password', '',array('id'=>'pass')),'<input id="pass" name="password" type="password" />');
        $this->assertEqual($form_tag->text_area_tag('address', 'My address',array('id'=>'address_box')),'<textarea id="address_box" name="address">My address</textarea>');
        $this->assertEqual($form_tag->check_box_tag('subscribe', 'subscribed',true),'<input checked="checked" id="subscribe" name="subscribe" type="checkbox" value="subscribed" />');

        $this->assertEqual($form_tag->radio_button_tag('subscribe', 'subscribed',true),'<input checked="checked" id="subscribe" name="subscribe" type="radio" value="subscribed" />');

        $this->assertEqual($form_tag->submit_tag(),'<input name="commit" type="submit" value="Save changes" />');
        $this->assertEqual($form_tag->submit_tag('Commit changes',array('disable_with'=>"Wait'Please")),'<input name="commit" onclick="this.disabled=true;this.value=\'Wait\\\'Please\';this.form.submit();" type="submit" value="Commit changes" />');

        /**
         * @todo TEST FOR image_submit_tag($source, $options = array())
         */  
    }
}

Ak::test('FormTagHelperTests');

?>