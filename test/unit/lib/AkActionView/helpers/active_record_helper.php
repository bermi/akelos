<?php

defined('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION') ? null : define('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION', false);
defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);

require_once(dirname(__FILE__).'/../../../../fixtures/config/config.php');

require_once('_HelpersUnitTester.php');
require_once(AK_LIB_DIR.DS.'AkActionView'.DS.'helpers'.DS.'active_record_helper.php');
require_once(AK_LIB_DIR.DS.'AkActionController.php');
require_once(AK_LIB_DIR.DS.'AkRequest.php');

Mock::generate('AkRequest');


class ActiveRecordHelperTests extends HelpersUnitTester
{
    function test_setup()
    {
        $this->controller = &new AkActionController();
        $this->controller->Request =& new MockAkRequest($this);
        $this->controller->controller_name = 'test';
        $this->controller->instantiateHelpers();

        $this->active_record_helper =& $this->controller->active_record_helper;
        $this->installAndIncludeModels(array('ProtectedPerson'));

        $this->controller->ProtectedPerson =& new ProtectedPerson();
        $this->LuckyLuke =& $this->controller->ProtectedPerson;
        $this->controller->ProtectedPerson->name = "Lucky Luke";
        $this->controller->ProtectedPerson->created_by = "1";
        $this->controller->ProtectedPerson->birthday = Ak::getDate(mktime(8,42,36,3,27,1982));
        $this->controller->ProtectedPerson->save();
        $this->controller->ProtectedPerson->created_at = Ak::getDate(mktime(8,42,36,3,27,1982));
        $this->controller->ProtectedPerson->updated_at = Ak::getDate(mktime(8,42,36,3,27,1982));
    }

    function tests_input()
    {
        $this->assertEqual(
            $this->active_record_helper->input('ProtectedPerson', 'name'),
            '<input id="ProtectedPerson_name" name="ProtectedPerson[name]" size="30" type="text" value="Lucky Luke" />'
        );
        $this->assertEqual(
            $this->active_record_helper->input('ProtectedPerson', 'id'),
            ''
        );

        $this->assertEqual(
            $this->active_record_helper->input('ProtectedPerson', 'birthday'),
            file_get_contents(AK_TEST_HELPERS_DIR.DS.'active_record_input_date.txt')
        );


        $this->assertEqual(
            $this->active_record_helper->input('ProtectedPerson', 'is_active'),
            '<input name="ProtectedPerson[is_active]" type="hidden" value="0" /><input checked="checked" id="ProtectedPerson_is_active" name="ProtectedPerson[is_active]" type="checkbox" value="1" />'
        );
    }

    function test_form()
    {
        $this->assertEqual(
            $this->active_record_helper->form('ProtectedPerson'),
            file_get_contents(AK_TEST_HELPERS_DIR.DS.'active_record_form.txt')
        );
    }

    function test_error_message_on()
    {
        $this->LuckyLuke->addError('name');
        $this->assertEqual(
            $this->active_record_helper->error_message_on('ProtectedPerson', 'name'),
            '<div class="formError">is invalid</div>'
        );
        
        $this->assertEqual(
            $this->active_record_helper->error_message_on('ProtectedPerson', 'name', 'before ',' after','nameError'),
            '<div class="nameError">before is invalid after</div>'
        );
    }

    function test_error_messages_for()
    {
        $this->LuckyLuke->addError('birthday');
        $this->assertEqual(
            $this->active_record_helper->error_messages_for('ProtectedPerson'),
            file_get_contents(AK_TEST_HELPERS_DIR.DS.'active_record_errors.txt')
        );

        $this->assertEqual(
            $this->active_record_helper->error_messages_for('ProtectedPerson', array('header_tag'=>'h3','id'=>'LuckyLukeErrors','class'=>'errors')),
            file_get_contents(AK_TEST_HELPERS_DIR.DS.'active_record_errors_2.txt')
        );
    }
}


Ak::test('ActiveRecordHelperTests');

?>