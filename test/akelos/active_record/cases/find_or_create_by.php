<?php

require_once(dirname(__FILE__).'/../config.php');

class FindOrCreateBy_TestCase extends ActiveRecordUnitTest
{
    public function test_start() {
        $this->installAndIncludeModels(array('Account'));
    }

    public function test_should_create_new_users() {
        $Account = new Account();
        $Bermi = $Account->findOrCreateBy('username', 'Bermi');

        $this->assertFalse($Bermi->isNewRecord());
        $this->assertEqual($Bermi->get('username'), 'Bermi');

        $Alicia = $Account->findOrCreateBy('username AND password', 'Alicia', 'pass');

        $this->assertFalse($Alicia->isNewRecord());
        $this->assertEqual($Alicia->get('username'), 'Alicia');
        $this->assertEqual($Alicia->get('password'), 'pass');

        $SavedBermi = $Account->findFirstBy('username', 'Bermi');
        $this->assertEqual($SavedBermi->getId(), $Bermi->getId());
        $SavedBermi = $Account->findOrCreateBy('username', 'Bermi');
        $this->assertEqual($SavedBermi->getId(), $Bermi->getId());

        $SavedAlicia = $Account->findOrCreateBy('username', 'Alicia');
        $this->assertEqual($SavedAlicia->getId(), $Alicia->getId());
    }

    public function test_should_return_existing_record() {
        $Account = new Account();
        $Alicia = $Account->findFirstBy('username', 'Alicia');
        $this->assertEqual($Alicia->get('password'), 'pass');
    }
}

ak_test_case('FindOrCreateBy_TestCase');

