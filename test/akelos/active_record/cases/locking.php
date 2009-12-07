<?php

require_once(dirname(__FILE__).'/../config.php');

class Locking_TestCase extends ActiveRecordUnitTest
{

    public function test_should_give_a_deprecated_warning() {
        $this->assertUpcomingError("DEPRECATED WARNING: Column lock_version should have a default setting");
        $this->installAndIncludeModels(array('BankAccount'=>'id,balance,lock_version int'));
        $Account = new BankAccount(array('balance'=>2000));
        $Account->save();
    }

    public function test_start() {
        $this->installAndIncludeModels(array('BankAccount'=>'id,balance,lock_version,created_at,updated_at'));
    }

    public function test_should_have_locking_enabled() {
        $Account = new BankAccount();
        $this->assertTrue($Account->isLockingEnabled(),'Optimistic locking is enabled by default.');

        $Account->lock_optimistically = false;
        $this->assertFalse($Account->isLockingEnabled(),'Optimistic locking can be turned off.');
    }

    public function test_should_lock_optimistically() {
        $Account1 = new BankAccount(array('balance'=>2000));
        $this->assertEqual($Account1->lock_version,1,'Version attribute initially starts at 1.');
        $Account1->save();
        $this->assertEqual($Account1->lock_version,1,'We are now on Version 1.');

        $Account2 = new BankAccount($Account1->getId());
        $this->assertEqual($Account2->lock_version,1,'We reloaded Version 1.');

        $Account1->balance = 5;
        $Account1->save();
        $this->assertEqual($Account1->lock_version,2,'We are now on Version 2.');

        $Account2->balance = 3000000;
        $this->assertUpcomingError('Attempted to update a stale object');
        $this->assertFalse($Account2->save(),'We cant save because version number is wrong.');

        $Account1->balance = 1000;
        $this->assertTrue($Account1->save());

        $Account3 = new BankAccount($Account1->getId());
        $this->assertEqual($Account3->balance, 1000);
        $this->assertEqual($Account3->lock_version,3,'We are now on Version 3.');
    }

}

ak_test_case('Locking_TestCase');

