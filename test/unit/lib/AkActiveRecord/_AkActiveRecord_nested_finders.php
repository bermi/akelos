<?php

defined('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION') ? null : define('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION', false);
defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);

require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

class AkActiveRecord_nested_finders_TestCase extends  AkUnitTest
{

    public function setup()
    {
        $this->installAndIncludeModels(array('Aa', 'Bb', 'Cc','Dd', 'Ee'));

    }

    public function test_find_aa()
    {
        $aa = &$this->Aa->create(array('name'=>'first aa'));
        $this->assertTrue($aa);
        /**
         * assert that it has the custom handler name for bb's
         */
        $this->assertTrue($aa->babies);
    }

    public function test_find_aa_include_bbs()
    {
        $aa = &$this->Aa->create(array('name'=>'first aa'));
        $this->assertTrue($aa);
        $bb1 = &$this->Bb->create(array('name'=>'first bb','languages'=>array('es','de'),'other'=>array(1,2,3)));
        $bb2 = &$this->Bb->create(array('name'=>'second bb','languages'=>array('en','fr'),'other'=>array(4,5,6)));
        $babies = array($bb1,$bb2);
        $aa->babies->set($babies);
        $this->assertEqual(2,count($aa->bbs));

        /**
         * now find them back in order
         */

        $found_first_aa = $this->Aa->findFirstBy('name','first aa',array('include'=>array('bbs'=>array('order' => 'id ASC'))));
        $this->assertTrue($found_first_aa);
        $this->assertTrue($found_first_aa->bbs);
        $this->assertEqual(2,$found_first_aa->babies->count());
        $this->assertEqual('first bb',$found_first_aa->bbs[0]->name);
        $this->assertEqual('second bb',$found_first_aa->bbs[1]->name);

        /**
         * now find them back in order and add a condition for the bbs
         */

        $found_first_aa = $this->Aa->findFirstBy('name','first aa',array('include'=>array('bbs'=>array('order' => 'id ASC','conditions'=>'name LIKE ?','bind'=>'%second%'))));
        $this->assertTrue($found_first_aa);
        $this->assertTrue($found_first_aa->bbs);
        $this->assertEqual(1,$found_first_aa->babies->count());
        $this->assertEqual('second bb',$found_first_aa->bbs[0]->name);

        /**
         * now find them back and test the serialized bb values
         */

        $found_first_aa = $this->Aa->findFirstBy('name','first aa',array('include'=>array('bbs'=>array('order' => 'id ASC'))));
        $this->assertTrue($found_first_aa);
        $this->assertTrue($found_first_aa->bbs);
        $this->assertEqual(2,$found_first_aa->babies->count());
        $this->assertEqual(array('en','fr'),$found_first_aa->bbs[1]->languages);
        $this->assertEqual(array(4,5,6),$found_first_aa->bbs[1]->other);
        $this->assertEqual(array('es','de'),$found_first_aa->bbs[0]->languages);
        $this->assertEqual(array(1,2,3),$found_first_aa->bbs[0]->other);

        /**
         * now find them back and test the serialized bb values as array
         */

        $found_first_aa = $this->Aa->findFirstBy('name','first aa',array('returns'=>'array','include'=>array('bbs'=>array('order' => 'id ASC'))));
        $this->assertTrue($found_first_aa);
        $this->assertTrue($found_first_aa['bbs']);
        $this->assertEqual(2,count($found_first_aa['bbs']));
        $this->assertEqual(array('en','fr'),$found_first_aa['bbs'][1]['languages']);
        $this->assertEqual(array(4,5,6),$found_first_aa['bbs'][1]['other']);
        $this->assertEqual(array('es','de'),$found_first_aa['bbs'][0]['languages']);
        $this->assertEqual(array(1,2,3),$found_first_aa['bbs'][0]['other']);
        
        /**
         * and now as simulated activerecords, this serialization is not working properly yet, expecting failures here
         */
        /**$found_first_aa = $this->Aa->findFirstBy('name','first aa',array('returns'=>'simulated','include'=>array('bbs'=>array('order' => 'id ASC'))));
        $this->assertTrue($found_first_aa);
        $this->assertTrue($found_first_aa->bbs);
        $this->assertEqual(2,$found_first_aa->babies->count());
        $this->assertEqual(array('en','fr'),$found_first_aa->bbs[1]->languages);
        $this->assertEqual(array(4,5,6),$found_first_aa->bbs[1]->other);
        $this->assertEqual(array('es','de'),$found_first_aa->bbs[0]->languages);
        $this->assertEqual(array(1,2,3),$found_first_aa->bbs[0]->other);*/
    }

    public function test_find_aa_include_bbs_and_ccs()
    {
        $aa = &$this->Aa->create(array('name'=>'first aa'));
        $this->assertTrue($aa);
        $bb1 = &$this->Bb->create(array('name'=>'first bb'));
        $bb2 = &$this->Bb->create(array('name'=>'second bb'));
        $cc1 = &$this->Cc->create(array('name'=>'first cc'));
        $cc2 = &$this->Cc->create(array('name'=>'second cc'));
        $cc3 = &$this->Cc->create(array('name'=>'third cc'));
        $first_cc_group = array($cc1,$cc2);
        $bb1->cc->set($first_cc_group);
        $bb2->cc->set($cc3);

        $babies = array($bb1,$bb2);
        $aa->babies->set($babies);

        //Ak::debug($aa);

        $this->assertEqual(2,count($aa->bbs));

        /**
         * now find them back in order including bb and cc
         */

        $found_first_aa = $this->Aa->findFirstBy('name','first aa',array('include'=>array('bbs'=>array('order' => 'id ASC','include'=>'ccs'))));
        $this->assertTrue($found_first_aa);
        $this->assertTrue($found_first_aa->bbs);
        $this->assertEqual(2,$found_first_aa->babies->count());
        $this->assertEqual('first bb',$found_first_aa->bbs[0]->name);
        $this->assertTrue($found_first_aa->bbs[0]->ccs);
        $this->assertEqual(2,$found_first_aa->bbs[0]->cc->count());
        $this->assertEqual('second bb',$found_first_aa->bbs[1]->name);
        $this->assertTrue($found_first_aa->bbs[1]->ccs);
        $this->assertEqual(1,$found_first_aa->bbs[1]->cc->count());

        /**
         * now find them back in order and add a condition for the bbs
         */

        $found_first_aa = $this->Aa->findFirstBy('name','first aa',array('include'=>array('bbs'=>array('include'=>'ccs','order' => 'id ASC','conditions'=>'name LIKE ?','bind'=>'%second%'))));
        $this->assertTrue($found_first_aa);
        $this->assertTrue($found_first_aa->bbs);
        $this->assertEqual(1,$found_first_aa->babies->count());
        $this->assertEqual('second bb',$found_first_aa->bbs[0]->name);
        $this->assertTrue($found_first_aa->bbs[0]->ccs);
        $this->assertEqual(1,$found_first_aa->bbs[0]->cc->count());

        /**
         * adding conditions on cc
         */

        $found_first_aa = $this->Aa->findFirstBy('name','first aa',array('include'=>array('bbs'=>array('include'=>array('ccs'=>array('conditions'=>'name LIKE ?','bind'=>'%second%')),'order' => 'id ASC','conditions'=>'name LIKE ?','bind'=>'%first%'))));
        $this->assertTrue($found_first_aa);
        $this->assertTrue($found_first_aa->bbs);
        $this->assertEqual(1,$found_first_aa->babies->count());
        $this->assertEqual('first bb',$found_first_aa->bbs[0]->name);
        $this->assertTrue($found_first_aa->bbs[0]->ccs);
        $this->assertEqual(1,$found_first_aa->bbs[0]->cc->count());
        $this->assertEqual('second cc',$found_first_aa->bbs[0]->ccs[0]->name);

    }

    public function test_find_aa_include_bbs_and_ccs_and_dds()
    {
        $aa = &$this->Aa->create(array('name'=>'first aa'));
        $this->assertTrue($aa);
        $bb1 = &$this->Bb->create(array('name'=>'first bb'));
        $bb2 = &$this->Bb->create(array('name'=>'second bb'));
        $cc1 = &$this->Cc->create(array('name'=>'first cc'));
        $cc2 = &$this->Cc->create(array('name'=>'second cc'));
        $cc3 = &$this->Cc->create(array('name'=>'third cc'));

        $dd1 = &$this->Dd->create(array('name'=>'first dd'));
        $dd2 = &$this->Dd->create(array('name'=>'second dd'));
        $dd3 = &$this->Dd->create(array('name'=>'third dd'));

        $cc1->dd->assign($dd1);
        $cc2->dd->assign($dd2);
        $cc3->dd->assign($dd3);

        $first_cc_group = array($cc1,$cc2);
        $bb1->cc->set($first_cc_group);
        $bb2->cc->set($cc3);

        $babies = array($bb1,$bb2);
        $aa->babies->set($babies);

        //Ak::debug($aa);

        $this->assertEqual(2,count($aa->bbs));

        /**
         * now find them back in order including bb and cc and dd
         */

        $found_first_aa = $this->Aa->findFirstBy('name','first aa',array('include'=>array('bbs'=>array('order' => 'id ASC','include'=>array('ccs'=>array('include'=>'dd','order'=>'id ASC'))))));
        $this->assertTrue($found_first_aa);
        $this->assertTrue($found_first_aa->bbs);
        $this->assertEqual(2,$found_first_aa->babies->count());
        $this->assertEqual('first bb',$found_first_aa->bbs[0]->name);
        $this->assertTrue($found_first_aa->bbs[0]->ccs);
        $this->assertEqual(2,$found_first_aa->bbs[0]->cc->count());
        $this->assertTrue($found_first_aa->bbs[0]->ccs[0]->dd);
        $this->assertEqual('first dd',$found_first_aa->bbs[0]->ccs[0]->dd->name);
        $this->assertTrue($found_first_aa->bbs[0]->ccs[1]->dd);
        $this->assertEqual('second dd',$found_first_aa->bbs[0]->ccs[1]->dd->name);

        $this->assertEqual('second bb',$found_first_aa->bbs[1]->name);
        $this->assertTrue($found_first_aa->bbs[1]->ccs);
        $this->assertEqual(1,$found_first_aa->bbs[1]->cc->count());
        $this->assertTrue($found_first_aa->bbs[1]->ccs[0]->dd);
        $this->assertEqual('third dd',$found_first_aa->bbs[1]->ccs[0]->dd->name);
        /**
         * now find them back in order and add a condition for the bbs
         */

        $found_first_aa = $this->Aa->findFirstBy('name','first aa',array('include'=>array('bbs'=>array('include'=>array('ccs'=>array('include'=>'dd')),'order' => 'id ASC','conditions'=>'name LIKE ?','bind'=>'%second%'))));
        $this->assertTrue($found_first_aa);
        $this->assertTrue($found_first_aa->bbs);
        $this->assertEqual(1,$found_first_aa->babies->count());
        $this->assertEqual('second bb',$found_first_aa->bbs[0]->name);
        $this->assertTrue($found_first_aa->bbs[0]->ccs);
        $this->assertEqual(1,$found_first_aa->bbs[0]->cc->count());
        $this->assertTrue($found_first_aa->bbs[0]->ccs[0]->dd);
        $this->assertEqual('third dd',$found_first_aa->bbs[0]->ccs[0]->dd->name);

        /**
         * adding conditions on cc
         */

        $found_first_aa = $this->Aa->findFirstBy('name','first aa',array('include'=>array('bbs'=>array('include'=>array('ccs'=>array('include'=>'dd','conditions'=>'name LIKE ?','bind'=>'%second%')),'order' => 'id ASC','conditions'=>'name LIKE ?','bind'=>'%first%'))));
        $this->assertTrue($found_first_aa);
        $this->assertTrue($found_first_aa->bbs);
        $this->assertEqual(1,$found_first_aa->babies->count());
        $this->assertEqual('first bb',$found_first_aa->bbs[0]->name);
        $this->assertTrue($found_first_aa->bbs[0]->ccs);
        $this->assertEqual(1,$found_first_aa->bbs[0]->cc->count());
        $this->assertEqual('second cc',$found_first_aa->bbs[0]->ccs[0]->name);
        $this->assertTrue($found_first_aa->bbs[0]->ccs[0]->dd);
        $this->assertEqual('second dd',$found_first_aa->bbs[0]->ccs[0]->dd->name);

    }

    public function test_find_aa_include_bbs_and_ccs_and_dds_and_ees()
    {
        $aa = &$this->Aa->create(array('name'=>'first aa'));
        $this->assertTrue($aa);
        $bb1 = &$this->Bb->create(array('name'=>'first bb'));
        $bb2 = &$this->Bb->create(array('name'=>'second bb'));
        $cc1 = &$this->Cc->create(array('name'=>'first cc'));
        $cc2 = &$this->Cc->create(array('name'=>'second cc'));
        $cc3 = &$this->Cc->create(array('name'=>'third cc'));

        $dd1 = &$this->Dd->create(array('name'=>'first dd'));
        $dd2 = &$this->Dd->create(array('name'=>'second dd'));
        $dd3 = &$this->Dd->create(array('name'=>'third dd'));
        //Ak::debug($dd1);
        //Ak::debug($cc1->dd);
        $cc1->dd->assign($dd1);
        $cc2->dd->assign($dd2);
        $cc3->dd->assign($dd3);

        //Ak::debug($dd1->easy);
        //die;

        $ee1 = &$this->Ee->create(array('name'=>'first ee'));
        $ee2 = &$this->Ee->create(array('name'=>'second ee'));
        $ee3 = &$this->Ee->create(array('name'=>'third ee'));
        $ee4 = &$this->Ee->create(array('name'=>'fourth ee'));
        $ee5 = &$this->Ee->create(array('name'=>'fifth ee'));
        $ee6 = &$this->Ee->create(array('name'=>'sixth ee'));

        $first_ee_group = array($ee1,$ee2);
        $second_ee_group = array($ee3,$ee4,$ee5);
        $third_ee_group = array($ee6);


        $cc1->dd->easy->set($first_ee_group);
        $cc2->dd->easy->set($second_ee_group);
        $cc3->dd->easy->set($third_ee_group);

        $first_cc_group = array($cc1,$cc2);
        $bb1->cc->set($first_cc_group);
        $bb2->cc->set($cc3);

        $babies = array($bb1,$bb2);
        $aa->babies->set($babies);


        $this->assertEqual(2,count($aa->bbs));

        /**
         * now find them back in order including bb and cc and dd and ee
         */

        $found_first_aa = $this->Aa->findFirstBy('name','first aa',array('include'=>array('bbs'=>array('order' => 'id ASC','include'=>array('ccs'=>array('include'=>array('dd'=>array('include'=>array('ees'=>array('order'=>'id ASC'))))))))));
        $this->assertTrue($found_first_aa);
        $this->assertTrue($found_first_aa->bbs);
        $this->assertEqual(2,$found_first_aa->babies->count());
        $this->assertEqual('first bb',$found_first_aa->bbs[0]->name);
        $this->assertTrue($found_first_aa->bbs[0]->ccs);
        $this->assertEqual(2,$found_first_aa->bbs[0]->cc->count());
        $this->assertTrue($found_first_aa->bbs[0]->ccs[0]->dd);
        $this->assertEqual('first dd',$found_first_aa->bbs[0]->ccs[0]->dd->name);

        $this->assertTrue($found_first_aa->bbs[0]->ccs[0]->dd->easy);
        $this->assertEqual(2,$found_first_aa->bbs[0]->ccs[0]->dd->easy->count());
        $this->assertEqual($ee1->name,$found_first_aa->bbs[0]->ccs[0]->dd->ees[0]->name);
        $this->assertEqual($ee2->name,$found_first_aa->bbs[0]->ccs[0]->dd->ees[1]->name);

        $this->assertTrue($found_first_aa->bbs[0]->ccs[1]->dd);
        $this->assertEqual('second dd',$found_first_aa->bbs[0]->ccs[1]->dd->name);

        $this->assertTrue($found_first_aa->bbs[0]->ccs[1]->dd->easy);
        $this->assertEqual(3,$found_first_aa->bbs[0]->ccs[1]->dd->easy->count());
        $this->assertEqual($ee3->name,$found_first_aa->bbs[0]->ccs[1]->dd->ees[0]->name);
        $this->assertEqual($ee4->name,$found_first_aa->bbs[0]->ccs[1]->dd->ees[1]->name);
        $this->assertEqual($ee5->name,$found_first_aa->bbs[0]->ccs[1]->dd->ees[2]->name);

        $this->assertEqual('second bb',$found_first_aa->bbs[1]->name);
        $this->assertTrue($found_first_aa->bbs[1]->ccs);
        $this->assertEqual(1,$found_first_aa->bbs[1]->cc->count());
        $this->assertTrue($found_first_aa->bbs[1]->ccs[0]->dd);
        $this->assertEqual('third dd',$found_first_aa->bbs[1]->ccs[0]->dd->name);

        $this->assertTrue($found_first_aa->bbs[1]->ccs[0]->dd->easy);
        $this->assertEqual(1,$found_first_aa->bbs[1]->ccs[0]->dd->easy->count());
        $this->assertEqual($ee6->name,$found_first_aa->bbs[1]->ccs[0]->dd->ees[0]->name);


    }


    public function test_find_aa_include_bbs_and_ccs_and_dds_and_ees_and_back_to_aa()
    {
        $aa = &$this->Aa->create(array('name'=>'first aa'));
        $this->assertTrue($aa);
        $bb1 = &$this->Bb->create(array('name'=>'first bb'));
        $bb2 = &$this->Bb->create(array('name'=>'second bb'));
        $cc1 = &$this->Cc->create(array('name'=>'first cc'));
        $cc2 = &$this->Cc->create(array('name'=>'second cc'));
        $cc3 = &$this->Cc->create(array('name'=>'third cc'));

        $dd1 = &$this->Dd->create(array('name'=>'first dd'));
        $dd2 = &$this->Dd->create(array('name'=>'second dd'));
        $dd3 = &$this->Dd->create(array('name'=>'third dd'));

        $cc1->dd->assign($dd1);
        $cc2->dd->assign($dd2);
        $cc3->dd->assign($dd3);

        $ee1 = &$this->Ee->create(array('name'=>'first ee'));
        $ee1->something->set($aa);
        $ee2 = &$this->Ee->create(array('name'=>'second ee'));
        $ee2->something->set($aa);
        $ee3 = &$this->Ee->create(array('name'=>'third ee'));
        $ee3->something->set($aa);
        $ee4 = &$this->Ee->create(array('name'=>'fourth ee'));
        $ee4->something->set($aa);
        $ee5 = &$this->Ee->create(array('name'=>'fifth ee'));
        $ee5->something->set($aa);
        $ee6 = &$this->Ee->create(array('name'=>'sixth ee'));
        $ee6->something->set($aa);

        $first_ee_group = array($ee1,$ee2);
        $second_ee_group = array($ee3,$ee4,$ee5);
        $third_ee_group = array($ee6);

        $cc1->dd->easy->set($first_ee_group);
        $cc2->dd->easy->set($second_ee_group);
        $cc3->dd->easy->set($third_ee_group);

        $first_cc_group = array($cc1,$cc2);
        $bb1->cc->set($first_cc_group);
        $bb2->cc->set($cc3);

        $babies = array($bb1,$bb2);
        $aa->babies->set($babies);

        //Ak::debug($aa);

        $this->assertEqual(2,count($aa->bbs));

        /**
         * now find them back in order including bb and cc and dd and ee and AA (alias somethings)!!!!
         */

        $found_first_aa = $this->Aa->findFirstBy('name','first aa',array('include'=>array('bbs'=>array('order' => 'id ASC','include'=>array('ccs'=>array('order'=>'id ASC','include'=>array('dd'=>array('include'=>array('ees'=>array('include'=>'somethings'))))))))));
        $this->assertTrue($found_first_aa);
        $this->assertTrue($found_first_aa->bbs);
        $this->assertEqual(2,$found_first_aa->babies->count());
        $this->assertEqual('first bb',$found_first_aa->bbs[0]->name);
        $this->assertTrue($found_first_aa->bbs[0]->ccs);
        $this->assertEqual(2,$found_first_aa->bbs[0]->cc->count());
        $this->assertTrue($found_first_aa->bbs[0]->ccs[0]->dd);
        $this->assertEqual('first dd',$found_first_aa->bbs[0]->ccs[0]->dd->name);

        $this->assertTrue($found_first_aa->bbs[0]->ccs[0]->dd->easy);
        $this->assertEqual(2,$found_first_aa->bbs[0]->ccs[0]->dd->easy->count());

        if ($this->Aa->_db->type()=='postgre') {
            /**
             * from postgres docs:
             *
             * A value of type name is a string of 63 or fewer characters.
             * A name must start with a letter or an underscore;
             * the rest of the string can contain letters, digits, and underscores.
             *
             * IF a column name here is over 63 characters long, the assoc finder will fail
             */
        } else {
            $this->assertTrue($found_first_aa->bbs[0]->ccs[0]->dd->ees[0]->somethings);
            $this->assertEqual($aa->name,$found_first_aa->bbs[0]->ccs[0]->dd->ees[0]->somethings[0]->name);
        }
        $this->assertTrue($found_first_aa->bbs[0]->ccs[1]->dd);
        $this->assertEqual('second dd',$found_first_aa->bbs[0]->ccs[1]->dd->name);

        $this->assertTrue($found_first_aa->bbs[0]->ccs[1]->dd->easy);
        $this->assertEqual(3,$found_first_aa->bbs[0]->ccs[1]->dd->easy->count());
        if ($this->Aa->_db->type()=='postgre') {
            /**
             * from postgres docs:
             *
             * A value of type name is a string of 63 or fewer characters.
             * A name must start with a letter or an underscore;
             * the rest of the string can contain letters, digits, and underscores.
             *
             * IF a column name here is over 63 characters long, the assoc finder will fail
             */
        } else {
            $this->assertTrue($found_first_aa->bbs[0]->ccs[1]->dd->ees[0]->somethings);
            $this->assertEqual($aa->name,$found_first_aa->bbs[0]->ccs[1]->dd->ees[0]->somethings[0]->name);
        }
        $this->assertEqual('second bb',$found_first_aa->bbs[1]->name);
        $this->assertTrue($found_first_aa->bbs[1]->ccs);
        $this->assertEqual(1,$found_first_aa->bbs[1]->cc->count());
        $this->assertTrue($found_first_aa->bbs[1]->ccs[0]->dd);
        $this->assertEqual('third dd',$found_first_aa->bbs[1]->ccs[0]->dd->name);

        $this->assertTrue($found_first_aa->bbs[1]->ccs[0]->dd->easy);
        $this->assertEqual(1,$found_first_aa->bbs[1]->ccs[0]->dd->easy->count());

        if ($this->Aa->_db->type()=='postgre') {
            /**
             * from postgres docs:
             *
             * A value of type name is a string of 63 or fewer characters.
             * A name must start with a letter or an underscore;
             * the rest of the string can contain letters, digits, and underscores.
             *
             * IF a column name here is over 63 characters long, the assoc finder will fail
             */
        } else {
            $this->assertTrue($found_first_aa->bbs[1]->ccs[0]->dd->ees[0]->somethings);
            $this->assertEqual($aa->name,$found_first_aa->bbs[1]->ccs[0]->dd->ees[0]->somethings[0]->name);
        }


    }


}

ak_test('AkActiveRecord_nested_finders_TestCase',true);

?>
