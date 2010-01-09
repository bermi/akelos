<?php

require_once(dirname(__FILE__).'/../helpers.php');

class ActiveRecordHelper_TestCase extends HelperUnitTest
{
    public function test_setup() {
        //echo NumberHelper::human_size(memory_get_peak_usage()).' '.__LINE__."\n";
        $Request = new MockAkRequest();
        $Request->setReturnValue('getController','test');
        $Request->setReturnValue('getRelativeUrlRoot','');
        $Request->setReturnValue('getParametersFromRequestedUrl',array('controller'=>'test'));
        $this->controller = new AkActionController();
        $this->controller->Request = $Request;

        $this->active_record_helper = $this->controller->active_record_helper;

        $this->installAndIncludeModels(array('DummyProtectedPerson','DummyProperty'));

        $this->controller->DummyProtectedPerson = new DummyProtectedPerson();

        $this->LuckyLuke = $this->controller->DummyProtectedPerson;
        $this->controller->DummyProtectedPerson->name = "Lucky Luke";
        $this->controller->DummyProtectedPerson->created_by = "1";
        $this->controller->DummyProtectedPerson->birthday = Ak::getDate(mktime(8,42,36,3,27,1982));
        $this->controller->DummyProtectedPerson->save();
        $this->controller->DummyProtectedPerson->created_at = Ak::getDate(mktime(8,42,36,3,27,1982));
        $this->controller->DummyProtectedPerson->updated_at = Ak::getDate(mktime(8,42,36,3,27,1982));

        $this->controller->DummyProperty = new DummyProperty(array('description' =>'阿尔罕布拉宫','details' => '阿尔罕布拉宫 <> & (阿拉伯语: الحمراء‎‎ = Al Ħamrā\'; 即"红色城堡")'));
        $this->alhambra = $this->controller->DummyProperty;
        $this->alhambra->save();
        //echo NumberHelper::human_size(memory_get_peak_usage()).' '.__LINE__."\n";
    }

    public function tests_input() {
        $this->assertEqual(
        $this->active_record_helper->input('DummyProtectedPerson', 'name'),
        '<input id="DummyProtectedPerson_name" name="DummyProtectedPerson[name]" size="30" type="text" value="Lucky Luke" />'
        );
        //echo NumberHelper::human_size(memory_get_peak_usage()).' '.__LINE__."\n";

        $this->assertEqual(
        $this->active_record_helper->input('DummyProtectedPerson', 'id'),
        ''
        );
        //echo NumberHelper::human_size(memory_get_peak_usage()).' '.__LINE__."\n";


        $this->assertEqual(
        $this->active_record_helper->input('DummyProtectedPerson', 'birthday'),
        file_get_contents(HelperUnitTest::getFixturesDir().DS.'active_record_input_date.txt')
        );

        $this->assertEqual(
        $this->active_record_helper->input('DummyProtectedPerson', 'is_active'),
        '<input name="DummyProtectedPerson[is_active]" type="hidden" value="0" /><input checked="checked" id="DummyProtectedPerson_is_active" name="DummyProtectedPerson[is_active]" type="checkbox" value="1" />'
        );

    }
/**/
    public function test_form() {
        $this->assertEqual(
        $this->active_record_helper->form('DummyProtectedPerson'),
        file_get_contents(HelperUnitTest::getFixturesDir().DS.'active_record_form.txt')
        );
    }

    public function test_should_render_limited_form_fields() {
        $this->assertEqual(
        $this->active_record_helper->form('DummyProtectedPerson', array('columns'=>array('id','name'))),
        file_get_contents(HelperUnitTest::getFixturesDir().DS.'active_record_limited_form.txt')
        );
    }

    public function test_error_message_on() {
        $this->LuckyLuke->addError('name');
        $this->assertEqual(
        $this->active_record_helper->error_message_on('DummyProtectedPerson', 'name'),
        '<div class="formError">is invalid</div>'
        );

        $this->assertEqual(
        $this->active_record_helper->error_message_on('DummyProtectedPerson', 'name', 'before ',' after','nameError'),
        '<div class="nameError">before is invalid after</div>'
        );
    }

    public function test_error_messages_for() {
        $this->LuckyLuke->addError('birthday');
        $this->assertEqual(
        $this->active_record_helper->error_messages_for('DummyProtectedPerson'),
        file_get_contents(HelperUnitTest::getFixturesDir().DS.'active_record_errors.txt')
        );

        $this->assertEqual(
        $this->active_record_helper->error_messages_for('DummyProtectedPerson', array('header_tag'=>'h3','id'=>'LuckyLukeErrors','class'=>'errors')),
        file_get_contents(HelperUnitTest::getFixturesDir().DS.'active_record_errors_2.txt')
        );
    }

    public function test_textarea_should_escape_characters_correctly() {
        $this->assertEqual(
        $this->active_record_helper->form('DummyProperty'),
        file_get_contents(HelperUnitTest::getFixturesDir().DS.'active_record_textarea_should_escape_characters_correctly.txt')
        );
    }

    /**/
}

ak_test_case('ActiveRecordHelper_TestCase');

