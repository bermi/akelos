<?php

defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);
require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

class test_AkActiveRecord_serialize extends  AkUnitTest
{
    function setUp()
    {
        $this->includeAndInstatiateModels('Bb,Cc');
    }

    function test_first_level_serialization()
    {
        $bb1 = &$this->Bb->create(array('name'=>'first bb','languages'=>array('en','es','de')));
        $cc1 = &$this->Cc->create(array('name'=>'first cc'));
        $cc2 = &$this->Cc->create(array('name'=>'second cc'));

        $first_cc_group = array($cc1,$cc2);
        $bb1->cc->set($first_cc_group);
        $bb1->save();
        $this->assertFalse($bb1->isNewRecord());

        $bb1retrieved=$this->Bb->find($bb1->id);

        $this->assertFalse($bb1retrieved->isNewRecord());
        $this->assertEqual(array('en','es','de'),$bb1retrieved->languages);

    }

    function test_first_level_serialization_with_association_finder()
    {
        $bb1 = &$this->Bb->create(array('name'=>'first bb','languages'=>array('en','es','de')));
        $cc1 = &$this->Cc->create(array('name'=>'first cc'));
        $cc2 = &$this->Cc->create(array('name'=>'second cc'));

        $first_cc_group = array($cc1,$cc2);
        $bb1->cc->set($first_cc_group);
        $bb1->save();
        $this->assertFalse($bb1->isNewRecord());

        $bb1retrieved=$this->Bb->find($bb1->id,array('include'=>'ccs'));

        $this->assertFalse($bb1retrieved->isNewRecord());
        $this->assertTrue(is_array($bb1retrieved->ccs));
        $this->assertEqual(array('en','es','de'),$bb1retrieved->languages);

    }
}