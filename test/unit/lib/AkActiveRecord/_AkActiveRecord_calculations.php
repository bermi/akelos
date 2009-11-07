<?php

defined('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION') ? null : define('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION', false);
defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);

require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

class test_AkActiveRecord_calculations extends  AkUnitTest
{
    public function test_start()
    {
        $this->installAndIncludeModels('Account', array('populate'=>true,'instantiate'=>true));
    }

    public function test_should_sum_field()
    {
        $this->assertEqual(318, $this->Account->sum('credit_limit'));
    }

    public function test_should_average_field()
    {
        $average = $this->Account->average('credit_limit');
        $this->assertTrue(is_float($average));
        $this->assertEqual(53.0, $average);
    }

    public function test_should_get_maximum_of_field()
    {
        $this->assertEqual(60, $this->Account->maximum('credit_limit'));
    }

    public function test_should_get_minimum_of_field()
    {
        $this->assertEqual(50, $this->Account->minimum('credit_limit'));
    }

    public function test_should_group_by_field()
    {
        $credit = $this->Account->sum('credit_limit',array('group'=>'firm_id'));
        foreach (array(1 => 50,2 => 60, 6 => 105, 9 => 53) as $k=>$v){
            $this->assertTrue(array_key_exists($k,$credit));
            $this->assertEqual($credit[$k], $v);
        }
    }

    public function test_should_order_by_grouped_field()
    {
        $credit = $this->Account->sum('credit_limit',array('group'=>'firm_id','order'=>'firm_id DESC'));
        $this->assertEqual(join(array_diff(array_keys($credit),array(''))), join(array(9,6,2,1)));
    }

    public function test_should_order_by_calculation()
    {
        $credit = $this->Account->sum('credit_limit',array('group'=>'firm_id','order'=>'sum_credit_limit desc, firm_id'));
        $this->assertEqual(array_values($credit), array(105, 60, 53, 50, 50));
        $this->assertEqual(join(array_keys($credit)), join(array(6,2,9,null,1)));
    }

    public function test_should_limit_calculation()
    {
        $credit = $this->Account->sum('credit_limit',array('conditions' => "firm_id IS NOT NULL", 'group'=>'firm_id','order'=>'firm_id','limit'=>2));
        $this->assertEqual(array_keys($credit), array(1,2));
    }

    public function test_should_limit_calculation_with_offset()
    {
        $credit = $this->Account->sum('credit_limit',array('conditions' => "firm_id IS NOT NULL", 'group'=>'firm_id','order'=>'firm_id','limit'=>2, 'offset'=>1));
        $this->assertEqual(array_keys($credit), array(2,6));
    }

    public function test_should_group_by_summed_field_having_condition()
    {
        $credit = $this->Account->sum('credit_limit',array('conditions' => "firm_id IS NOT NULL", 'group'=>'firm_id','having' => 'sum(credit_limit) > 50'));
        foreach (array(1 => null, 6 => 105, 2 => 60) as $k=>$v){
            $this->assertEqual(@$credit[$k], $v);
        }
    }

    public function test_should_sum_field_with_conditions()
    {
        $this->assertEqual(105, $this->Account->sum('credit_limit',array('conditions' => "firm_id = 6")));
    }

    public function test_should_group_by_summed_field_with_conditions()
    {
        $credit = $this->Account->sum('credit_limit',array('conditions' => "firm_id > 1", 'group'=>'firm_id'));
        foreach (array(1 => null, 6 => 105, 2 => 60) as $k=>$v){
            $this->assertEqual(@$credit[$k], $v);
        }
    }

    public function test_should_group_by_summed_field_with_conditions_and_having()
    {
        $credit = $this->Account->sum('credit_limit',array('conditions' => "firm_id > 1", 'group'=>'firm_id', 'having' => 'sum(credit_limit) > 60'));
        foreach (array(1 => null, 6 => 105, 2 => null) as $k=>$v){
            $this->assertEqual(@$credit[$k], $v);
        }
    }

    public function test_should_group_by_fields_with_table_alias()
    {
        $credit = $this->Account->sum('credit_limit',array('group'=>'accounts.firm_id'));
        foreach (array(1 => 50, 6 => 105, 2 => 60) as $k=>$v){
            $this->assertEqual($credit[$k], $v);
        }
    }

    public function test_should_calculate_with_invalid_field()
    {
        $this->assertEqual(6, $this->Account->calculate('count','*'));
        $this->assertEqual(6, $this->Account->calculate('count','all'));
    }

    public function test_should_calculate_grouped_with_invalid_field()
    {
        $credit = $this->Account->count('all',array('group'=>'accounts.firm_id'));
        foreach (array(1 => 1, 6 => 2, 2 => 1) as $k=>$v){
            $this->assertEqual($credit[$k], $v);
        }
    }

}

ak_test('test_AkActiveRecord_calculations',true);

?>