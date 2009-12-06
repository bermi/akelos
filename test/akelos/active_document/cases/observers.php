<?php

require_once(dirname(__FILE__).'/../config.php');

class DocumentObservers_TestCase extends ActiveDocumentUnitTest
{
    public function setup()
    {
        $this->db = new AkOdbAdapter();
        $this->db->connect(array('type' => 'mongo_db', 'database' => 'akelos_testing'));
        $this->WebPage = new WebPage();
        $this->WebPage->setAdapter($this->db);
        $this->WebPage->setAdapter($this->db);

        $Auditor = new WebPageAuditor();
        $Auditor->observe($this->WebPage);
    }

    public function tearDown()
    {
        $this->db->dropDatabase();
        $this->db->disconnect();
    }

    public function test_should_issue_observer_callbacks_in_the_right_order()
    {
        // Creation callbacks
        $Akelos = $this->WebPage->create(array('title' => 'Akelos PHP framework'));

        $this->assertEqual($Akelos->callbacks, array (
        'beforeValidation',
        'WebPageAuditor::beforeValidation',
        'beforeValidationOnCreate',
        'WebPageAuditor::beforeValidationOnCreate',
        'afterValidation',
        'WebPageAuditor::afterValidation',
        'afterValidationOnCreate',
        'WebPageAuditor::afterValidationOnCreate',
        'beforeSave',
        'WebPageAuditor::beforeSave',
        'beforeCreate',
        'WebPageAuditor::beforeCreate',
        'afterCreate',
        'WebPageAuditor::afterCreate',
        'afterSave',
        'WebPageAuditor::afterSave',
        ));

        $this->assertFalse($Akelos->isNewRecord());

        // Instantiating callbacks
        $Akelos->callbacks = array();
        $Akelos->reload();

        $this->assertEqual($Akelos->callbacks, array('afterInstantiate', 'WebPageAuditor::afterInstantiate'));

        // Update callbacks
        $Akelos->callbacks = array();
        $Akelos->save();


        $this->assertEqual($Akelos->callbacks, array (
        'beforeValidation',
        'WebPageAuditor::beforeValidation',
        'beforeValidationOnUpdate',
        'WebPageAuditor::beforeValidationOnUpdate',
        'afterValidation',
        'WebPageAuditor::afterValidation',
        'afterValidationOnUpdate',
        'WebPageAuditor::afterValidationOnUpdate',
        'beforeSave',
        'WebPageAuditor::beforeSave',
        'beforeUpdate',
        'WebPageAuditor::beforeUpdate',
        'afterUpdate',
        'WebPageAuditor::afterUpdate',
        'afterSave',
        'WebPageAuditor::afterSave',
        ));

        // Destroy callbacks
        $Akelos->callbacks = array();
        $Akelos->destroy();

        $this->assertEqual($Akelos->callbacks, array (
        'beforeDestroy',
        'WebPageAuditor::beforeDestroy',
        'afterDestroy',
        'WebPageAuditor::afterDestroy'
        ));
    }

    public function test_should_halt_operation_if_observer_callback_returns_false()
    {
        $Akelos = $this->WebPage->create(array('title' => 'Akelos PHP framework'));
        $Akelos->callbacks = array();
        $Akelos->halt_on_callback = 'WebPageAuditor::afterValidation';
        $this->assertFalse($Akelos->save());
        $this->assertEqual($Akelos->callbacks, array (
        'beforeValidation',
        'WebPageAuditor::beforeValidation',
        'beforeValidationOnUpdate',
        'WebPageAuditor::beforeValidationOnUpdate',
        'afterValidation',
        'WebPageAuditor::afterValidation',
        ));

        // Destroy callbacks
        $Akelos->callbacks = array();
        $Akelos->halt_on_callback = 'WebPageAuditor::beforeDestroy';
        $this->assertFalse($Akelos->destroy());
        $this->assertEqual($Akelos->callbacks, array (
        'beforeDestroy',
        'WebPageAuditor::beforeDestroy',
        ));

        $this->assertTrue($Akelos->reload());
        $this->assertFalse($Akelos->isNewRecord());

    }
}

ak_test_case('DocumentObservers_TestCase');
