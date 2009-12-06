<?php

require_once(dirname(__FILE__).'/../config.php');

class DocumentCallbacks_TestCase extends ActiveDocumentUnitTest
{
    public function setup()
    {
        $this->db = new AkOdbAdapter();
        $this->db->connect(array('type' => 'mongo_db', 'database' => 'akelos_testing'));
        $this->WebPage = new WebPage();
        $this->WebPage->setAdapter($this->db);
    }

    public function tearDown()
    {
        $this->db->dropDatabase();
        $this->db->disconnect();
    }

    public function test_should_issue_callbacks_in_the_right_order()
    {
        // Creation callbacks
        $Akelos = $this->WebPage->create(array('title' => 'Akelos PHP framework'));
        $this->assertEqual($Akelos->callbacks, array (
        'beforeValidation',
        'beforeValidationOnCreate',
        'afterValidation',
        'afterValidationOnCreate',
        'beforeSave',
        'beforeCreate',
        'afterCreate',
        'afterSave'
        ));

        $this->assertFalse($Akelos->isNewRecord());

        // Instantiating callbacks
        $Akelos->callbacks = array();
        $Akelos->reload();
        $this->assertEqual($Akelos->callbacks, array('afterInstantiate'));

        // Update callbacks
        $Akelos->callbacks = array();
        $Akelos->save();
        $this->assertEqual($Akelos->callbacks, array (
        'beforeValidation',
        'beforeValidationOnUpdate',
        'afterValidation',
        'afterValidationOnUpdate',
        'beforeSave',
        'beforeUpdate',
        'afterUpdate',
        'afterSave'
        ));

        // Destroy callbacks
        $Akelos->callbacks = array();
        $Akelos->destroy();
        $this->assertEqual($Akelos->callbacks, array (
        'beforeDestroy',
        'afterDestroy'
        ));
    }

    public function test_should_halt_operation_if_callback_returns_false()
    {
        $Akelos = $this->WebPage->create(array('title' => 'Akelos PHP framework'));
        $Akelos->callbacks = array();
        $Akelos->halt_on_callback = 'afterValidation';
        $this->assertFalse($Akelos->save());
        $this->assertEqual($Akelos->callbacks, array (
        'beforeValidation',
        'beforeValidationOnUpdate',
        'afterValidation',
        ));

        // Destroy callbacks
        $Akelos->callbacks = array();
        $Akelos->halt_on_callback = 'beforeDestroy';
        $this->assertFalse($Akelos->destroy());
        $this->assertEqual($Akelos->callbacks, array (
        'beforeDestroy',
        ));

        $this->assertTrue($Akelos->reload());
        $this->assertFalse($Akelos->isNewRecord());

    }
}

ak_test_case('DocumentCallbacks_TestCase');
