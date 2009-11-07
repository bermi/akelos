<?php

defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);
require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

require_once(AK_LIB_DIR.DS.'AkActiveRecord.php');

class test_AkActiveRecord_return_types extends  AkUnitTest
{
    public function setup()
    {
        $this->installAndIncludeModels(array('Aa', 'Bb', 'Cc','Dd', 'Ee'));
    }

    public function test_normal_find_without_association_return_array()
    {
        $aa1 = $this->Aa->create(array('name'=>'first aa'));
        $aa2 = $this->Aa->create(array('name'=>'second aa'));

        $returned=$this->Aa->findAll(array('returns'=>'array','select_prefix'=>'SELECT name FROM aas'));
        $this->assertTrue($returned);
        $this->assertEqual(array(array('name'=>'first aa'),array('name'=>'second aa')), $returned);

        $returned=$this->Aa->findAll(array('returns'=>'array','select_prefix'=>'SELECT name FROM aas', 'order'=>'name DESC'));
        $this->assertTrue($returned);
        $this->assertEqual(array(array('name'=>'second aa'),array('name'=>'first aa')), $returned);

        $returned=$this->Aa->findAll(array('returns'=>'array','select_prefix'=>'SELECT name FROM aas', 'order'=>'name DESC','limit'=>1));
        $this->assertTrue($returned);
        $this->assertEqual(array(array('name'=>'second aa')), $returned);

        $returned=$this->Aa->findAll(array('returns'=>'array','select_prefix'=>'SELECT name FROM aas', 'order'=>'name DESC','limit'=>1,'offset'=>1));
        $this->assertTrue($returned);
        $this->assertEqual(array(array('name'=>'first aa')), $returned);

        $returned=$this->Aa->findFirst(array('returns'=>'array','select_prefix'=>'SELECT name FROM aas', 'order'=>'name DESC'));
        $this->assertTrue($returned);
        $this->assertEqual(array('name'=>'second aa'), $returned);
    }

    public function test_normal_find_without_association_return_simulated()
    {
        $aa1 = $this->Aa->create(array('name'=>'first aa'));
        $aa2 = $this->Aa->create(array('name'=>'second aa'));

        $returned=$this->Aa->findAll(array('returns'=>'simulated'));
        $this->assertTrue($returned);
        $this->assertEqual(2, count($returned));
        $this->assertIsA($returned[0],'AkActiveRecordMock');
        $this->assertEqual($aa1->getId(), $returned[0]->getId());
        $this->assertEqual($aa2->getId(), $returned[1]->getId());
        $this->assertEqual($aa2->getPrimaryKey(), $returned[1]->getPrimaryKey());

        $returned=$this->Aa->findAll(array('returns'=>'simulated', 'order'=>'name DESC'));
        $this->assertTrue($returned);
        $this->assertEqual(2, count($returned));
        $this->assertIsA($returned[0],'AkActiveRecordMock');
        $this->assertEqual($aa2->getId(), $returned[0]->getId());
        $this->assertEqual($aa1->getId(), $returned[1]->getId());
        $this->assertEqual($aa1->getPrimaryKey(), $returned[1]->getPrimaryKey());

        $returned=$this->Aa->findAll(array('returns'=>'simulated', 'order'=>'name DESC','limit'=>1));
        $this->assertTrue($returned);
        $this->assertEqual(1, count($returned));
        $this->assertIsA($returned[0],'AkActiveRecordMock');
        $this->assertEqual($aa2->getId(), $returned[0]->getId());
        $this->assertEqual($aa2->getPrimaryKey(), $returned[0]->getPrimaryKey());

        $returned=$this->Aa->findAll(array('returns'=>'simulated', 'order'=>'name DESC','limit'=>1,'offset'=>1));
        $this->assertTrue($returned);
        $this->assertEqual(1, count($returned));
        $this->assertIsA($returned[0],'AkActiveRecordMock');
        $this->assertEqual($aa1->getId(), $returned[0]->getId());
        $this->assertEqual($aa1->getPrimaryKey(), $returned[0]->getPrimaryKey());

        $returned=$this->Aa->findFirst(array('returns'=>'simulated','order'=>'name ASC'));
        $this->assertTrue($returned);
        $this->assertEqual(1, count($returned));
        $this->assertIsA($returned,'AkActiveRecordMock');
        $this->assertEqual($aa1->getId(), $returned->getId());
        $this->assertEqual($aa1->getPrimaryKey(), $returned->getPrimaryKey());
    }

    public function test_find_on_first_level_has_many_finder_with_conditions_return_as_array()
    {
        $aa = &$this->Aa->create(array('name'=>'first aa'));
        $this->assertTrue($aa);
        $bb1 = &$this->Bb->create(array('name'=>'first bb'));
        $bb2 = &$this->Bb->create(array('name'=>'second bb'));
        $babies = array($bb1,$bb2);
        $aa->babies->set($babies);

        $aa = &$this->Aa->findFirstBy('name','first aa');
        $this->assertTrue($aa);
        $firstbb = $aa->babies->find('first',array('conditions'=>"name LIKE '%first%'",'order'=>'id ASC','returns'=>'array'));
        $this->assertTrue($firstbb);
        $this->assertIsA($firstbb,'array');
        $this->assertEqual('first bb',$firstbb['name']);
    }


    public function test_find_aa_include_bbs_with_custom_handler_name_return_simulated()
    {
        $aa = &$this->Aa->create(array('name'=>'first aa'));
        $this->assertTrue($aa);
        $bb1 = &$this->Bb->create(array('name'=>'first bb'));
        $bb2 = &$this->Bb->create(array('name'=>'second bb'));
        $babies = array($bb1,$bb2);

        $aa->babies->set($babies);

        $this->assertEqual(2,count($aa->bbs));

        /**
         * now find them back in order
         */

        $found_first_aa = $this->Aa->findFirstBy('name','first aa',array('returns'=>'simulated','include'=>array('bbs'=>array('order' => 'id ASC'))));
        $this->assertTrue($found_first_aa);
        $this->assertTrue($found_first_aa->bbs);
        $this->assertEqual(2,$found_first_aa->babies->count());
        $this->assertEqual('first bb',$found_first_aa->bbs[0]->name);
        $this->assertEqual('second bb',$found_first_aa->bbs[1]->name);

        /**
         * now find them back in order and add a condition for the bbs
         */

        $found_first_aa = $this->Aa->findFirstBy('name','first aa',array('returns'=>'simulated','include'=>array('bbs'=>array('order' => 'id ASC','conditions'=>'name LIKE ?','bind'=>'%second%'))));
        $this->assertTrue($found_first_aa);
        $this->assertTrue($found_first_aa->bbs);
        $this->assertEqual(1,$found_first_aa->babies->count());
        $this->assertEqual('second bb',$found_first_aa->bbs[0]->name);

    }

    public function test_find_aa_include_bbs_with_custom_handler_name_return_array()
    {
        $aa = &$this->Aa->create(array('name'=>'first aa'));
        $this->assertTrue($aa);
        $bb1 = &$this->Bb->create(array('name'=>'first bb'));
        $bb2 = &$this->Bb->create(array('name'=>'second bb'));
        $babies = array($bb1,$bb2);

        $aa->babies->set($babies);

        $this->assertEqual(2,count($aa->bbs));

        /**
         * now find them back in order
         */

        $found_first_aa = $this->Aa->findFirstBy('name','first aa',array('returns'=>'array','include'=>array('bbs'=>array('order' => 'id ASC'))));

        $this->assertTrue($found_first_aa);
        $this->assertTrue($found_first_aa['bbs']);
        $this->assertEqual(2,count($found_first_aa['bbs']));
        $this->assertEqual('first bb',$found_first_aa['bbs'][0]['name']);
        $this->assertEqual('second bb',$found_first_aa['bbs'][1]['name']);

        /**
         * now find them back in order and add a condition for the bbs
         */

        $found_first_aa = $this->Aa->findFirstBy('name','first aa',array('returns'=>'array','include'=>array('bbs'=>array('order' => 'id ASC','conditions'=>'name LIKE ?','bind'=>'%second%'))));
        $this->assertTrue($found_first_aa);
        $this->assertTrue($found_first_aa['bbs']);
        $this->assertEqual(1,count($found_first_aa['bbs']));
        $this->assertEqual('second bb',$found_first_aa['bbs'][0]['name']);

    }
}

ak_test('test_AkActiveRecord_return_types', true);

?>