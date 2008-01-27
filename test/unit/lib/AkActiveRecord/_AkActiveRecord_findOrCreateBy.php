<?php

defined('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION') ? null : define('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION', false);
defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);

require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

class test_AkActiveRecord_findOrCreateBy extends  AkUnitTest
{
    function test_start()
    {
        $this->installAndIncludeModels(array('Account'));
    }

    function test_should_create_new_users()
    {
        $Account = new Account();
        $Bermi =& $Account->findOrCreateBy('username', 'Bermi');

        $this->assertFalse($Bermi->isNewRecord());
        $this->assertEqual($Bermi->get('username'), 'Bermi');

        $Alicia =& $Account->findOrCreateBy('username AND password', 'Alicia', 'pass');

        $this->assertFalse($Alicia->isNewRecord());
        $this->assertEqual($Alicia->get('username'), 'Alicia');
        $this->assertEqual($Alicia->get('password'), 'pass');

        $SavedBermi =& $Account->findFirstBy('username', 'Bermi');
        $this->assertEqual($SavedBermi->getId(), $Bermi->getId());
        $SavedBermi =& $Account->findOrCreateBy('username', 'Bermi');
        $this->assertEqual($SavedBermi->getId(), $Bermi->getId());

        $SavedAlicia =& $Account->findOrCreateBy('username', 'Alicia');
        $this->assertEqual($SavedAlicia->getId(), $Alicia->getId());
    }

    function test_should_return_existing_record()
    {
        $Account = new Account();
        $Alicia =& $Account->findFirstBy('username', 'Alicia');
        $this->assertEqual($Alicia->get('password'), 'pass');
    }
}

ak_test('test_AkActiveRecord_findOrCreateBy',true);

?>
