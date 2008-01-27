<?php

defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);
require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

require_once(AK_LIB_DIR.DS.'AkActiveRecord.php');
require_once(AK_LIB_DIR.DS.'AkActiveRecord'.DS.'AkObserver.php');


class test_AkActiveRecord_observer extends  AkUnitTest
{

    function test_start()
    {
        $this->installAndIncludeModels(array(
            'ObservedAccount'=>'id, balance, created_at, updated_at',
            'ObservedPerson'=>'id, user_name, first_name, last_name, city, state'));
        Ak::import('TestAuditor','ObservedPersonObserver');
    }

    function Test_of__instantiateDefaultObserver()
    {
        $Observed = new ObservedPerson();
        $ObeserversReference =& $Observed->getObservers();
        $this->assertEqual(strtolower(get_class($ObeserversReference[0])), 'observedpersonobserver');
    }

    function Test_of_addObserver()
    {
        $Observed = new ObservedPerson();

        $null = null;
        $Observer =& Ak::singleton('ObservedPersonObserver', $null);
        
        $params = 'ObservedAccount';
        $Auditor =& Ak::singleton('TestAuditor',$params);
        $Auditor->observe(&$Observed);

        $ObeserversReference =& $Observed->getObservers();

        $ObeserversReference[0]->message = 'Hello. I come from the past';

        $this->assertEqual($ObeserversReference[0]->__singleton_id, $Observer->__singleton_id);
        $this->assertReference($ObeserversReference[1], $Auditor);
    }


    function Test_of_addObserver2()
    {
        $ObservedPerson =& new ObservedPerson();

        $ObeserversReference =& $ObservedPerson->getObservers();
        $this->assertEqual(strtolower(get_class($ObeserversReference[0])), 'observedpersonobserver');
        $this->assertEqual($ObeserversReference[0]->message, 'Hello. I come from the past');
        $this->assertEqual(strtolower(get_class($ObeserversReference[1])), 'testauditor');

        $ObservedAccount =& new ObservedAccount();
        $ObeserversReference =& $ObservedAccount->getObservers();
        $this->assertEqual(strtolower(get_class($ObeserversReference[0])), 'testauditor');
    }

    function __Test_of_setObservableState_and_getObservableState()
    {
        $ObservedAccount1 =& new ObservedAccount();
        $ObservedAccount1->setObservableState('creating account 1');

        $ObservedAccount2 =& new ObservedAccount();
        $ObservedAccount2->setObservableState('creating account 2');

        $this->assertEqual($ObservedAccount2->getObservableState(), 'creating account 2');
        $this->assertEqual($ObservedAccount1->getObservableState(), 'creating account 1');
    }

    function Test_of_notifyObservers()
    {
        $ObservedPerson =& new ObservedPerson();
        $ObservedPerson->setObservableState('new person created');

        ob_start();
        $ObservedPerson->notifyObservers();
        $content = ob_get_contents();
        ob_end_clean();

        $this->assertEqual($content,'new person creatednew person created');

        $this->assertEqual($ObservedPerson->getObservableState(), '');
    }


    function Test_of_default_Active_record_observer_triggers()
    {
        $ObservedPerson =& new ObservedPerson('first_name->','Bermi');
        
        $this->assertTrue(empty($ObservedPerson->audited));
        ob_start();
        $ObservedPerson->save();
        
        $this->assertTrue($ObservedPerson->audited);
        
        $content = ob_get_contents();
        ob_end_clean();
        $this->assertEqual($content, "Bermi has been email with account details");

        $notified = array();
        foreach ($ObservedPerson->notified_observers as $k=>$v){
            $notified[strtolower($k)] = $v;
        }
        $this->assertEqual($notified, array ( 'beforevalidation' => 1, 'beforevalidationoncreate' => 1, 'aftervalidationoncreate' => 1, 'aftervalidation' => 1, 'beforecreate' => 1, 'beforesave' => 1, 'aftersave' => 1, 'aftercreate' => 1, ));

        $ObservedPerson->set('last_name','Ferrer');
        $ObservedPerson->save();
        
        $notified = array();
        foreach ($ObservedPerson->notified_observers as $k=>$v){
            $notified[strtolower($k)] = $v;
        }
        $this->assertEqual($notified, array ( 'beforevalidation' => 2, 'beforevalidationoncreate' => 1, 'aftervalidationoncreate' => 1, 'aftervalidation' => 2, 'beforecreate' => 1, 'beforesave' => 2, 'aftersave' => 2, 'aftercreate' => 1, 'aftervalidationonupdate' => 1, ));

    }
    

    function Test_of_beforeSave_trigger()
    {
        $ObservedPerson =& new ObservedPerson();
        
        $ObservedPerson->city = "Carlet";
        $ObservedPerson->state = "Madrid";
        
        ob_start();
        $this->assertTrue($ObservedPerson->save());
        ob_end_clean();
        $this->assertTrue($ObservedPerson->reload());
        $this->assertEqual($ObservedPerson->get('state'), "Valencia");
    }
    
}

ak_test('test_AkActiveRecord_observer', true);

?>
