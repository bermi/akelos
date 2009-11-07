<?php

defined('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION') ? null : define('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION', false);
defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);

require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

class AkActiveRecord_association_finders_TestCase extends  AkUnitTest
{

    public function setup()
    {
        $this->installAndIncludeModels(array('Aa', 'Bb', 'Cc','Dd', 'Ee'));

    }

    public function test_find_on_first_level_has_many_finder_with_conditions()
    {
        $aa = $this->Aa->create(array('name'=>'first aa'));
        $this->assertTrue($aa);
        $bb1 = $this->Bb->create(array('name'=>'first bb'));
        $bb2 = $this->Bb->create(array('name'=>'second bb'));
        $babies = array($bb1,$bb2);
        $aa->babies->set($babies);

        $aa = $this->Aa->findFirstBy('name','first aa');
        $this->assertTrue($aa);
        $firstbb = $aa->babies->find('first',array('conditions'=>"name LIKE '%first%'",'order'=>'id ASC'));
        $this->assertTrue($firstbb);
        $this->assertEqual('first bb',$firstbb->name);
    }
    public function test_find_on_first_level_has_many_finder_with_conditions_as_array()
    {
        $aa = $this->Aa->create(array('name'=>'first aa'));
        $this->assertTrue($aa);
        $bb1 = $this->Bb->create(array('name'=>'first bb'));
        $bb2 = $this->Bb->create(array('name'=>'second bb'));
        $babies = array($bb1,$bb2);
        $aa->babies->set($babies);

        $aa = $this->Aa->findFirstBy('name','first aa');
        $this->assertTrue($aa);
        $firstbb = $aa->babies->find('first',array('conditions'=>array('name LIKE ?','%first%'),'order'=>'id ASC'));
        $this->assertTrue($firstbb);
        $this->assertEqual('first bb',$firstbb->name);
    }
    public function test_find_on_first_level_has_many_finder_with_conditions_and_bind()
    {
        $aa = $this->Aa->create(array('name'=>'first aa'));
        $this->assertTrue($aa);
        $bb1 = $this->Bb->create(array('name'=>'first bb'));
        $bb2 = $this->Bb->create(array('name'=>'second bb'));
        $babies = array($bb1,$bb2);
        $aa->babies->set($babies);

        $aa = $this->Aa->findFirstBy('name','first aa');
        $this->assertTrue($aa);
        $firstbb = $aa->babies->find('first',array('conditions'=>'name LIKE ?','bind'=>'%first%','order'=>'id ASC'));
        $this->assertTrue($firstbb);
        $this->assertEqual('first bb',$firstbb->name);
    }
    public function test_find_on_first_level_has_many_finder_with_order()
    {
        $aa = $this->Aa->create(array('name'=>'first aa'));
        $this->assertTrue($aa);
        $bb1 = $this->Bb->create(array('name'=>'first bb'));
        $bb2 = $this->Bb->create(array('name'=>'second bb'));
        $babies = array($bb1,$bb2);
        $aa->babies->set($babies);

        $aa = $this->Aa->findFirstBy('name','first aa');
        $this->assertTrue($aa);
        $babies = $aa->babies->find('all',array('order'=>'name DESC'));
        $this->assertTrue($babies);
        $this->assertEqual(2,count($babies));
        $this->assertEqual('second bb',$babies[0]->name);
        $this->assertEqual('first bb',$babies[1]->name);
    }

    public function test_find_on_first_level_has_many_finder_with_include()
    {
        $aa = $this->Aa->create(array('name'=>'first aa'));
        $this->assertTrue($aa);
        $bb1 = $this->Bb->create(array('name'=>'first bb'));
        $bb2 = $this->Bb->create(array('name'=>'second bb'));

        $cc1 = $this->Cc->create(array('name'=>'first cc'));
        $cc2 = $this->Cc->create(array('name'=>'second cc'));
        $cc3 = $this->Cc->create(array('name'=>'third cc'));
        $first_cc_group = array($cc1,$cc2);
        $bb1->cc->set($first_cc_group);
        $bb2->cc->set($cc3);

        $babies = array($bb1,$bb2);
        $aa->babies->set($babies);

        $aa = $this->Aa->findFirstBy('name','first aa');
        $this->assertTrue($aa);
        $babies = $aa->babies->find('all',array('order'=>'id ASC','include'=>'ccs'));
        $this->assertTrue($babies);
        $this->assertEqual(2,count($babies));
        $this->assertEqual('first bb',$babies[0]->name);
        $this->assertEqual('second bb',$babies[1]->name);
        $this->assertEqual(2,count($babies[0]->ccs));
        $this->assertEqual(2,$babies[0]->cc->count());
        $this->assertEqual(1,count($babies[1]->ccs));
        $this->assertEqual(1,$babies[1]->cc->count());

    }
    /**
     * fixing #219
     *
     */
    public function test_find_on_first_level_has_many_finder_with_id_and_include()
    {
        $aa = $this->Aa->create(array('name'=>'first aa'));
        $this->assertTrue($aa);
        $bb1 = $this->Bb->create(array('name'=>'first bb'));
        $bb2 = $this->Bb->create(array('name'=>'second bb'));

        $cc1 = $this->Cc->create(array('name'=>'first cc'));
        $cc2 = $this->Cc->create(array('name'=>'second cc'));
        $cc3 = $this->Cc->create(array('name'=>'third cc'));
        $first_cc_group = array($cc1,$cc2);
        $bb1->cc->set($first_cc_group);
        $bb2->cc->set($cc3);

        $babies = array($bb1,$bb2);
        $aa->babies->set($babies);

        $aa = $this->Aa->findFirstBy('name','first aa');
        $this->assertTrue($aa);
        $firstbb = $aa->babies->find($bb1->getId(),array('order'=>'id ASC','include'=>'ccs'));
        $this->assertTrue($firstbb);
        $this->assertEqual($bb1->name,$firstbb->name);
        $this->assertEqual(2,count($firstbb->ccs));

        $babies = $aa->babies->find(array($bb1->getId(),$bb2->getId()),array('order'=>'id ASC','include'=>'ccs'));
        $this->assertTrue($babies);
        $this->assertEqual($bb1->name,$babies[0]->name);
        $this->assertEqual(2,count($babies[0]->ccs));
        $this->assertEqual($bb2->name,$babies[1]->name);
        $this->assertEqual(1,count($babies[1]->ccs));
    }
    public function test_find_on_second_level_habtm_finder_with_conditions_and_bind()
    {
        $aa = $this->Aa->create(array('name'=>'first aa'));
        $this->assertTrue($aa);
        $bb1 = $this->Bb->create(array('name'=>'first bb'));
        $bb2 = $this->Bb->create(array('name'=>'second bb'));
        $cc1 = $this->Cc->create(array('name'=>'first cc'));
        $cc2 = $this->Cc->create(array('name'=>'second cc'));
        $cc3 = $this->Cc->create(array('name'=>'third cc'));
        $first_cc_group = array($cc1,$cc2);
        $bb1->cc->set($first_cc_group);
        $bb2->cc->set($cc3);


        $babies = array($bb1,$bb2);
        $aa->babies->set($babies);

        $aa = $this->Aa->findFirstBy('name','first aa',array('include'=>array('bbs'=>array('order'=>'id ASC')),'order'=>'id ASC'));
        $this->assertTrue($aa);
        $this->assertTrue($aa->babies);
        $firstcc = $aa->bbs[0]->cc->find('first',array('conditions'=>'name LIKE ?','bind'=>'%first%','order'=>'id ASC'));

        $this->assertTrue($firstcc);
        $this->assertEqual('first cc',$firstcc->name);

        $nonexistingCc = $aa->bbs[1]->cc->find('first',array('conditions'=>'name LIKE ?','bind'=>'%first%','order'=>'id ASC'));
        $this->assertFalse($nonexistingCc);
        //die;
        $thirdcc = $aa->bbs[1]->cc->find('first',array('conditions'=>'name LIKE ?','bind'=>'third%','order'=>'id ASC'));
        $this->assertEqual('third cc',$thirdcc->name);
    }
    /**
     * fixing #219
     *
     */
    public function test_find_on_second_level_habtm_finder_with_id()
    {
        $aa = $this->Aa->create(array('name'=>'first aa'));
        $this->assertTrue($aa);
        $bb1 = $this->Bb->create(array('name'=>'first bb'));
        $bb2 = $this->Bb->create(array('name'=>'second bb'));
        $cc1 = $this->Cc->create(array('name'=>'first cc'));
        $cc2 = $this->Cc->create(array('name'=>'second cc'));
        $cc3 = $this->Cc->create(array('name'=>'third cc'));
        $first_cc_group = array($cc1,$cc2);
        $bb1->cc->set($first_cc_group);
        $bb2->cc->set($cc3);


        $babies = array($bb1,$bb2);
        $aa->babies->set($babies);

        $aa = $this->Aa->findFirstBy('name','first aa',array('include'=>array('bbs'=>array('order'=>'id ASC')),'order'=>'id ASC'));
        $this->assertTrue($aa);
        $this->assertTrue($aa->babies);
        $firstcc = $aa->bbs[0]->cc->find($cc1->getId());

        $this->assertTrue($firstcc);
        $this->assertEqual('first cc',$firstcc->name);


        $thirdcc = $aa->bbs[1]->cc->find($cc3->getId());
        $this->assertEqual('third cc',$thirdcc->name);
    }

    public function test_find_on_second_level_habtm_finder_with_conditions_as_array()
    {
        $aa = $this->Aa->create(array('name'=>'first aa'));
        $this->assertTrue($aa);
        $bb1 = $this->Bb->create(array('name'=>'first bb'));
        $bb2 = $this->Bb->create(array('name'=>'second bb'));
        $cc1 = $this->Cc->create(array('name'=>'first cc'));
        $cc2 = $this->Cc->create(array('name'=>'second cc'));
        $cc3 = $this->Cc->create(array('name'=>'third cc'));
        $first_cc_group = array($cc1,$cc2);
        $bb1->cc->set($first_cc_group);
        $bb2->cc->set($cc3);


        $babies = array($bb1,$bb2);
        $aa->babies->set($babies);

        $aa = $this->Aa->findFirstBy('name','first aa',array('include'=>array('bbs'=>array('order'=>'id ASC'))));
        $this->assertTrue($aa);
        $this->assertTrue($aa->babies);
        $firstcc = $aa->bbs[0]->cc->find('first',array('conditions'=>array('name LIKE ?','%first%'),'order'=>'id ASC'));
        $this->assertTrue($firstcc);
        $this->assertEqual('first cc',$firstcc->name);

        $nonexistingCc = $aa->bbs[1]->cc->find('first',array('conditions'=>array('name LIKE ?','%first%'),'order'=>'id ASC'));
        $this->assertFalse($nonexistingCc);

        $thirdcc = $aa->bbs[1]->cc->find('first',array('conditions'=>'name LIKE ?','bind'=>'third%','order'=>'id ASC'));
        $this->assertEqual('third cc',$thirdcc->name);
    }
    /**
     * not working yet!!
     * habtm finder and includes is a bit difficult
     *
     */
    public function xtest_find_on_second_level_habtm_finder_with_conditions_as_array_and_include()
    {
        $aa = $this->Aa->create(array('name'=>'first aa'));
        $this->assertTrue($aa);
        $bb1 = $this->Bb->create(array('name'=>'first bb'));
        $bb2 = $this->Bb->create(array('name'=>'second bb'));

        $cc1 = $this->Cc->create(array('name'=>'first cc'));
        $cc2 = $this->Cc->create(array('name'=>'second cc'));
        $cc3 = $this->Cc->create(array('name'=>'third cc'));

        $dd1 = $this->Dd->create(array('name'=>'first dd'));
        $dd2 = $this->Dd->create(array('name'=>'second dd'));
        $dd3 = $this->Dd->create(array('name'=>'third dd'));

        $cc1->dd->assign($dd1);
        $cc2->dd->assign($dd2);
        $cc3->dd->assign($dd3);

        $first_cc_group = array($cc1,$cc2);
        $bb1->cc->set($first_cc_group);
        $bb2->cc->set($cc3);


        $babies = array($bb1,$bb2);
        $aa->babies->set($babies);

        $aa = $this->Aa->findFirstBy('name','first aa',array('include'=>array('bbs'=>array('order'=>'id ASC')),'order'=>'id ASC'));
        $this->assertTrue($aa);
        $this->assertTrue($aa->babies);
        $firstcc = $aa->bbs[0]->cc->find('first',array('conditions'=>array('name LIKE ?','%first%'),'include'=>'dd','order'=>'id ASC'));
        $this->assertTrue($firstcc);
        $this->assertTrue($firstcc->dd);
        $this->assertEqual('first dd',$firstcc->dd->name);
        $this->assertEqual('first cc',$firstcc->name);

        $nonexistingCc = $aa->bbs[1]->cc->find('first',array('conditions'=>array('name LIKE ?','%first%'),'include'=>'dd','order'=>'id ASC'));
        $this->assertFalse($nonexistingCc);

        $thirdcc = $aa->bbs[1]->cc->find('first',array('conditions'=>'name LIKE ?','bind'=>'third%','include'=>'dd','order'=>'id ASC'));
        $this->assertTrue($thirdcc->dd);
        $this->assertEqual('third dd',$thirdcc->dd->name);
        $this->assertEqual('third cc',$thirdcc->name);
    }

}

ak_test('AkActiveRecord_association_finders_TestCase',true);

?>
