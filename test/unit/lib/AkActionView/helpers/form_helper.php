<?php

require_once('_HelpersUnitTester.php');
require_once(AK_LIB_DIR.DS.'AkActionView'.DS.'helpers'.DS.'form_helper.php');


class FormHelperTests extends HelpersUnitTester 
{    
    function test_for_form_helpers()
    {
        $Controller = &new MockAkActionController($this);
        $Controller->setReturnValue('urlFor','/url/for/test');
        $ActiveRecord = &new MockAkActiveRecord($this);
        $ActiveRecord->setReturnValue('get', 'Bermi');
        
        $Mock = new stdClass();
        $Mock->_controller->person =& $ActiveRecord;
        $AkFormHelperInstanceTag =& new AkFormHelperInstanceTag('person','name',$Mock);

        $options = array();
        $AkFormHelperInstanceTag->add_default_name_and_id($options);
        $this->assertEqual($options,array('name'=>'person[name]','id'=>'person_name'));

        $options = array('index'=>3);
        $AkFormHelperInstanceTag->add_default_name_and_id($options);
        $this->assertEqual($options,array('name'=>'person[3][name]','id'=>'person_3_name'));

        $this->assertReference($AkFormHelperInstanceTag->getObject(),$ActiveRecord);
        $this->assertEqual($AkFormHelperInstanceTag->getValue(),'Bermi');

        $this->assertEqual($AkFormHelperInstanceTag->value_before_type_cast(),'Bermi');

        $ActiveRecord->name_before_type_cast = 'bermi';
        $this->assertEqual($AkFormHelperInstanceTag->value_before_type_cast(),'bermi');

        $this->assertEqual($AkFormHelperInstanceTag->to_input_field_tag('text'),'<input id="person_name" name="person[name]" size="30" type="text" value="bermi" />');
        $this->assertEqual($AkFormHelperInstanceTag->to_input_field_tag('hidden'),'<input id="person_name" name="person[name]" type="hidden" value="bermi" />');
        $this->assertEqual($AkFormHelperInstanceTag->to_input_field_tag('file'),'<input id="person_name" name="person[name]" size="30" type="file" />');

        $this->assertEqual($AkFormHelperInstanceTag->to_radio_button_tag('Bermi'),'<input checked="checked" id="person_name_bermi" name="person[name]" type="radio" value="Bermi" />');
        $this->assertEqual($AkFormHelperInstanceTag->to_radio_button_tag('Hilario'),'<input id="person_name_hilario" name="person[name]" type="radio" value="Hilario" />');

        $ActiveRecord->name_before_type_cast = 'Something "NEW"';

        $this->assertEqual($AkFormHelperInstanceTag->to_text_area_tag(array('class'=>'wysiwyg')),
        '<textarea class="wysiwyg" cols="40" id="person_name" name="person[name]" rows="20">Something &quot;NEW&quot;</textarea>'
        );

        $this->assertEqual($AkFormHelperInstanceTag->to_check_box_tag(array(),'Bermi'),'<input name="person[name]" type="hidden" value="0" /><input checked="checked" id="person_name" name="person[name]" type="checkbox" value="Bermi" />');
        $this->assertEqual($AkFormHelperInstanceTag->to_check_box_tag(array(),'si','no'),'<input name="person[name]" type="hidden" value="no" /><input id="person_name" name="person[name]" type="checkbox" value="si" />');

        $this->assertEqual($AkFormHelperInstanceTag->to_boolean_select_tag(),'<select id="person_name" name="person[name]"><option value="false">False</option><option value="true" selected>True</option></select>');
        $this->assertEqual($AkFormHelperInstanceTag->to_boolean_select_tag(),'<select id="person_name" name="person[name]"><option value="false">False</option><option value="true" selected>True</option></select>');

        $this->assertEqual($AkFormHelperInstanceTag->to_content_tag('h1'),'<h1>Bermi</h1>');

        $ActiveRecord = &new MockAkActiveRecord($this);
        $ActiveRecord->setReturnValue('get', '1978-06-16');
        $AkFormHelperInstanceTag =& new AkFormHelperInstanceTag('person','join_date',$ActiveRecord,null,$ActiveRecord);

        $this->assertEqual(trim(str_replace("\n",'',$AkFormHelperInstanceTag->to_date_tag())),trim(str_replace("\n",'','
<select name="person[join_date(3)]">
<option value="1">1</option>
<option value="2">2</option>
<option value="3">3</option>

<option value="4">4</option>
<option value="5">5</option>
<option value="6">6</option>
<option value="7">7</option>
<option value="8">8</option>
<option value="9">9</option>
<option value="10">10</option>
<option value="11">11</option>
<option value="12">12</option>

<option value="13">13</option>
<option value="14">14</option>
<option value="15">15</option>
<option value="16" selected="selected">16</option>
<option value="17">17</option>
<option value="18">18</option>
<option value="19">19</option>
<option value="20">20</option>
<option value="21">21</option>

<option value="22">22</option>
<option value="23">23</option>
<option value="24">24</option>
<option value="25">25</option>
<option value="26">26</option>
<option value="27">27</option>
<option value="28">28</option>
<option value="29">29</option>
<option value="30">30</option>

<option value="31">31</option>
</select>
<select name="person[join_date(2)]">
<option value="January">January</option>
<option value="February">February</option>
<option value="March">March</option>
<option value="April">April</option>
<option value="May">May</option>
<option value="June" selected="selected">June</option>
<option value="July">July</option>

<option value="August">August</option>
<option value="September">September</option>
<option value="October">October</option>
<option value="November">November</option>
<option value="December">December</option>
</select>
<select name="person[join_date(1)]">
<option value="1973">1973</option>
<option value="1974">1974</option>
<option value="1975">1975</option>

<option value="1976">1976</option>
<option value="1977">1977</option>
<option value="1978" selected="selected">1978</option>
<option value="1979">1979</option>
<option value="1980">1980</option>
<option value="1981">1981</option>
<option value="1982">1982</option>
<option value="1983">1983</option>
</select>
')));

        $Controller = &new MockAkActionController($this);
        $Controller->setReturnValue('urlFor','/url/for/test');
        $Controller->form_tag_helper = new FormTagHelper();
        $Controller->form_tag_helper->setController($Controller);

        $Person = &new MockAkActiveRecord($this);
        $Person->setReturnValue('get', 'Bermi', array('name'));

        $Task = &new MockAkActiveRecord($this);
        $Task->setReturnValue('get', 'Do the testing');
        
        $FormHelper = new FormHelper(array('person'=>&$Person));
        $FormHelper->setController($Controller);

        $this->assertReference($FormHelper->getObject('person'),$Person);

        ob_start();
        $f = $FormHelper->form_for('person',$Person,array('url' => array('action' => 'update')));
        $this->assertEqual(ob_get_clean(),'<form action="/url/for/test" method="post">');

        $this->assertEqual($FormHelper->text_field('task','description'),
        '<input id="task_description" name="task[description]" size="30" type="text" />');

        $this->assertEqual($FormHelper->text_field('task','description',array('object'=>&$Task)),
        '<input id="task_description" name="task[description]" size="30" type="text" value="Do the testing" />');

        $this->assertEqual($f->text_field('person','name'),
        '<input id="person_name" name="person[name]" size="30" type="text" value="Bermi" />');

        $Person->setReturnValue('get', 'Alicia', array('first_name'));
        $this->assertEqual($f->text_field('person','first_name',array('size'=>80)),
        '<input id="person_first_name" name="person[first_name]" size="80" type="text" value="Alicia" />');

        $this->assertEqual(
        $FormHelper->password_field('person','password').
        $FormHelper->file_field('person','photo').
        $FormHelper->hidden_field('person','referer').
        $FormHelper->text_area('person','notes').
        $FormHelper->text_field('person','name'),
        '<input id="person_password" name="person[password]" size="30" type="password" />'.
        '<input id="person_photo" name="person[photo]" size="30" type="file" />'.
        '<input id="person_referer" name="person[referer]" type="hidden" />'.
        '<textarea cols="40" id="person_notes" name="person[notes]" rows="20"></textarea>'.
        '<input id="person_name" name="person[name]" size="30" type="text" />');


        $Person->setReturnValue('get', '1234', array('password'));
        $Person->setReturnValue('get', 'no_value_on_file_types', array('photo'));
        $Person->setReturnValue('get', 'http://www.example.com', array('referer'));
        $Person->setReturnValue('get', 'Check this "NOTES"', array('notes'));

        $this->assertEqual(
        $f->password_field('person','password').
        $f->file_field('person','photo').
        $f->hidden_field('person','referer').
        $f->text_area('person','notes').
        $f->text_field('person','name'),
        '<input id="person_password" name="person[password]" size="30" type="password" value="1234" />'.
        '<input id="person_photo" name="person[photo]" size="30" type="file" />'.
        '<input id="person_referer" name="person[referer]" type="hidden" value="http://www.example.com" />'.
        '<textarea cols="40" id="person_notes" name="person[notes]" rows="20">Check this &quot;NOTES&quot;</textarea>'.
        '<input id="person_name" name="person[name]" size="30" type="text" value="Bermi" />');

        $Person->setReturnValue('get', 1, array('validate'));
        $this->assertEqual($f->check_box("post", "validate"),
        '<input name="post[validate]" type="hidden" value="0" />'.
        '<input checked="checked" id="post_validate" name="post[validate]" type="checkbox" value="1" />');

        $this->assertEqual($f->radio_button('post', 'validate','si'),
        '<input id="post_validate_si" name="post[validate]" type="radio" value="si" />');

        $this->assertEqual($f->radio_button('post', 'validate','1'),
        '<input checked="checked" id="post_validate_1" name="post[validate]" type="radio" value="1" />');
    }
}

Ak::test('FormHelperTests', true);

?>