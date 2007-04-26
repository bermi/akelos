<?php

require_once('_HelpersUnitTester.php');
require_once(AK_LIB_DIR.DS.'AkActionView'.DS.'helpers'.DS.'form_helper.php');


class FormHelperTests extends HelpersUnitTester 
{    
    function setUp()
    {
        $this->test_value = "Akelos";
        $this->controller = &new MockAkActionController($this);
        $this->controller->setReturnValue('urlFor', '/url/for/test');
        $this->active_record = &new MockAkActiveRecord($this);
        $this->active_record->setReturnValue('get', $this->test_value);
        
        $this->mock = new stdClass();
        $this->mock->_controller->person =& $this->active_record;
        $this->ak_form_helper_instance_tag =& new AkFormHelperInstanceTag('person', 'name', $this->mock);
    }

    function test_add_default_name_and_id()
    {
        $options = array();
        $this->ak_form_helper_instance_tag->add_default_name_and_id($options);
        $this->assertEqual($options,array('name'=>'person[name]','id'=>'person_name'));

        $options = array('index'=>3);
        $this->ak_form_helper_instance_tag->add_default_name_and_id($options);
        $this->assertEqual($options,array('name'=>'person[3][name]','id'=>'person_3_name'));
    }

    function test_get_object()
    {
        $this->assertReference($this->ak_form_helper_instance_tag->getObject(), $this->active_record);
    }

    function test_get_value()
    {
        $this->assertEqual($this->ak_form_helper_instance_tag->getValue(), $this->test_value);
    }

    function test_value_before_type_cast()
    {
        $this->assertEqual($this->ak_form_helper_instance_tag->value_before_type_cast(), $this->test_value);
        $this->active_record->name_before_type_cast = 'test_akelos';
        $this->assertEqual($this->ak_form_helper_instance_tag->value_before_type_cast(), 'test_akelos');
    }

    function test_to_input_field_tag()
    {
        $this->assertEqual($this->ak_form_helper_instance_tag->to_input_field_tag('text'), '<input id="person_name" name="person[name]" size="30" type="text" value="'.$this->test_value.'" />');
        $this->assertEqual($this->ak_form_helper_instance_tag->to_input_field_tag('hidden'), '<input id="person_name" name="person[name]" type="hidden" value="'.$this->test_value.'" />');
        $this->assertEqual($this->ak_form_helper_instance_tag->to_input_field_tag('file'), '<input id="person_name" name="person[name]" size="30" type="file" />');
    }

    function test_to_radio_button_tag()
    {
        $this->assertEqual($this->ak_form_helper_instance_tag->to_radio_button_tag('Bermi'), '<input id="person_name_bermi" name="person[name]" type="radio" value="Bermi" />');
        $this->assertEqual($this->ak_form_helper_instance_tag->to_radio_button_tag('Hilario'), '<input id="person_name_hilario" name="person[name]" type="radio" value="Hilario" />');
    }

    function test_to_text_area_tag()
    {
        $this->active_record->name_before_type_cast = 'Something "NEW"';

        $this->assertEqual(
            $this->ak_form_helper_instance_tag->to_text_area_tag(array('class'=>'wysiwyg')),
            '<textarea class="wysiwyg" cols="40" id="person_name" name="person[name]" rows="20">Something &quot;NEW&quot;</textarea>'
        );
    }

    function test_to_check_box_tag()
    {
        $this->assertEqual($this->ak_form_helper_instance_tag->to_check_box_tag(array(),'Bermi'),'<input name="person[name]" type="hidden" value="0" /><input id="person_name" name="person[name]" type="checkbox" value="Bermi" />');
        $this->assertEqual($this->ak_form_helper_instance_tag->to_check_box_tag(array(),'si','no'),'<input name="person[name]" type="hidden" value="no" /><input id="person_name" name="person[name]" type="checkbox" value="si" />');
    }

    function test_to_boolean_select_tag()
    {
        $this->assertEqual($this->ak_form_helper_instance_tag->to_boolean_select_tag(),'<select id="person_name" name="person[name]"><option value="false">False</option><option value="true" selected>True</option></select>');
        $this->assertEqual($this->ak_form_helper_instance_tag->to_boolean_select_tag(),'<select id="person_name" name="person[name]"><option value="false">False</option><option value="true" selected>True</option></select>');
    }

    function test_to_content_tag()
    {
        $this->assertEqual($this->ak_form_helper_instance_tag->to_content_tag('h1'),'<h1>'.$this->test_value.'</h1>');
    }

    function test_to_date_tag()
    {
        $active_record = &new MockAkActiveRecord($this);
        $active_record->setReturnValue('get', '1978-06-16');
        $ak_form_helper_instance_tag =& new AkFormHelperInstanceTag('person', 'join_date', $active_record, null, $active_record);
        $this->assertEqual($ak_form_helper_instance_tag->to_date_tag(), file_get_contents(AK_TEST_HELPERS_DIR.DS.'form_helper_to_date_tag.txt'));
    }

    function test_to_date_select_tag()
    {
        $active_record = &new MockAkActiveRecord($this);
        $active_record->setReturnValue('get', '1978-06-16');
        $ak_form_helper_instance_tag =& new AkFormHelperInstanceTag('person', 'join_date', $active_record, null, $active_record);
        $this->assertEqual($ak_form_helper_instance_tag->to_date_select_tag(), file_get_contents(AK_TEST_HELPERS_DIR.DS.'form_helper_to_date_select_tag.txt'));
    }

    function test_to_datetime_select_tag()
    {
        $active_record = &new MockAkActiveRecord($this);
        $active_record->setReturnValue('get', '1978-06-16');
        $ak_form_helper_instance_tag =& new AkFormHelperInstanceTag('person', 'join_date', $active_record, null, $active_record);
        $this->assertEqual($ak_form_helper_instance_tag->to_datetime_select_tag(), file_get_contents(AK_TEST_HELPERS_DIR.DS.'form_helper_to_datetime_select_tag.txt'));
    }

    function test_tag_name()
    {
        $this->assertEqual($this->ak_form_helper_instance_tag->tag_name(),'person[name]');
    }

    function test_tag_name_with_index()
    {
        $this->assertEqual($this->ak_form_helper_instance_tag->tag_name_with_index(42),'person[42][name]');
    }

    function test_tag_id()
    {
        $this->assertEqual($this->ak_form_helper_instance_tag->tag_id(),'person_name');
    }

    function test_tag_id_with_index()
    {
        $this->assertEqual($this->ak_form_helper_instance_tag->tag_id_with_index(42),'person_42_name');
    }

    function test_for_form_helpers()
    {

        $controller = &new MockAkActionController($this);
        $controller->setReturnValue('urlFor', '/url/for/test');
        $controller->form_tag_helper = new FormTagHelper();
        $controller->form_tag_helper->setController($controller);

        $person = &new MockAkActiveRecord($this);
        $person->setReturnValue('get', 'Bermi', array('name'));

        $task = &new MockAkActiveRecord($this);
        $task->setReturnValue('get', 'Do the testing');
        
        $form_helper = new FormHelper(array('person' => &$person));
        $form_helper->setController($controller);

        $this->assertReference($form_helper->getObject('person'), $person);

        ob_start();
        $f = $form_helper->form_for('person', $person, array('url' => array('action' => 'update')));
        $this->assertEqual(ob_get_clean(),'<form action="/url/for/test" method="post">');

        $this->assertEqual(
            $form_helper->text_field('task', 'description'),
            '<input id="task_description" name="task[description]" size="30" type="text" />'
        );

        $this->assertEqual(
            $form_helper->text_field('task', 'description', array('object' => &$task)),
            '<input id="task_description" name="task[description]" size="30" type="text" value="Do the testing" />'
        );

        $this->assertEqual(
            $f->text_field('person', 'name'),
            '<input id="person_name" name="person[name]" size="30" type="text" value="Bermi" />'
        );

        $person->setReturnValue('get', 'Alicia', array('first_name'));

        $this->assertEqual(
            $f->text_field('person', 'first_name', array('size'=>80)),
            '<input id="person_first_name" name="person[first_name]" size="80" type="text" value="Alicia" />'
        );

        $this->assertEqual(
            $form_helper->password_field('person','password'),
            '<input id="person_password" name="person[password]" size="30" type="password" />'
        );

        $this->assertEqual(
            $form_helper->file_field('person','photo'),
            '<input id="person_photo" name="person[photo]" size="30" type="file" />'
        );

        $this->assertEqual(
            $form_helper->hidden_field('person','referer'),
            '<input id="person_referer" name="person[referer]" type="hidden" />'
        );

        $this->assertEqual(
            $form_helper->text_area('person','notes'),
            '<textarea cols="40" id="person_notes" name="person[notes]" rows="20"></textarea>'
        );

        $this->assertEqual(
            $form_helper->text_field('person','name'),
            '<input id="person_name" name="person[name]" size="30" type="text" />'
        );


        $person->setReturnValue('get', '1234', array('password'));
        $person->setReturnValue('get', 'no_value_on_file_types', array('photo'));
        $person->setReturnValue('get', 'http://www.example.com', array('referer'));
        $person->setReturnValue('get', 'Check this "NOTES"', array('notes'));

        $this->assertEqual(
            $f->password_field('person','password'),
            '<input id="person_password" name="person[password]" size="30" type="password" value="1234" />'
        );

        $this->assertEqual(
            $f->file_field('person','photo'),
            '<input id="person_photo" name="person[photo]" size="30" type="file" />'
        );

        $this->assertEqual(
            $f->hidden_field('person','referer'),
            '<input id="person_referer" name="person[referer]" type="hidden" value="http://www.example.com" />'
        );

        $this->assertEqual(
            $f->text_area('person','notes'),
            '<textarea cols="40" id="person_notes" name="person[notes]" rows="20">Check this &quot;NOTES&quot;</textarea>'
        );

        $this->assertEqual(
            $f->text_field('person','name'),
            '<input id="person_name" name="person[name]" size="30" type="text" value="Bermi" />'
        );

        $person->setReturnValue('get', 1, array('validate'));

        $this->assertEqual(
            $f->check_box("post", "validate"),
            '<input name="post[validate]" type="hidden" value="0" />'.
            '<input checked="checked" id="post_validate" name="post[validate]" type="checkbox" value="1" />'
        );

        $this->assertEqual(
            $f->radio_button('post', 'validate','si'),
            '<input id="post_validate_si" name="post[validate]" type="radio" value="si" />'
        );

        $this->assertEqual(
            $f->radio_button('post', 'validate','1'),
            '<input checked="checked" id="post_validate_1" name="post[validate]" type="radio" value="1" />'
        );
    }
}

ak_test('FormHelperTests', true);

?>