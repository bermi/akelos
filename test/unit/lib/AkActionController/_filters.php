<?php

require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

$testing_object = '';
for ($i = 65; $i <= 90; $i++){
    $char = chr($i);
    $testing_object .= "\n    public function $char(\$a = null, \$b = null, \$c = null){
        \$this->message .= '$char';
    }";
}
$testing_object = "class TestingFiltersUtitlityClass extends AkActionController
{
    public \$message = '';
    public function returnFalse(){return false;}
    $testing_object
}
";

eval($testing_object);
unset($testing_object);


class TestingFiltersUtitlityClassWithFilterMethod extends TestingFiltersUtitlityClass
{
    public $_char = '';
    public function __construct($char = ''){
        $this->_char = $char;
    }
    public function filter(&$target){
        $target->message = 'filtered:'.$target->message;
    }
    public function before(&$target){
        $target->message = 'before-'.$this->_char.':'.$target->message;
    }
    public function after(&$target){
        $target->message = $target->message.':'.$this->_char.'-after';
    }
}


class AkActionControllerFilters_TestCase extends  AkUnitTest
{

    public function setUp()
    {
        $this->Object = new TestingFiltersUtitlityClass();
    }

    public function tearDown()
    {
        unset($this->Object);
    }

    public function test_of_before_filter()
    {
        $this->Object->beforeFilter('A');
        $this->Object->beforeFilter('B');
        $this->Object->beforeFilter('C');

        $this->Object->beforeAction();

        $this->assertEqual($this->Object->message, 'ABC');

        $this->Object->message = '';
        $this->Object->beforeFilter('returnFalse');
        $this->Object->beforeFilter('D');
        $this->Object->beforeAction();
        $this->assertEqual($this->Object->message, 'ABC');
    }

    public function test_of_before_filter_mixed_filter_types()
    {
        $this->Object->beforeFilter('A');
        $this->Object->beforeFilter(array(&$this->Object,'B'));
        $this->Object->beforeFilter('C');

        $this->Object->beforeAction();
        $this->assertEqual($this->Object->message, 'ABC');

        $this->Object->message = '';
        $this->Object->beforeFilter(array(&$this->Object,'returnFalse'));
        $this->Object->beforeFilter('D');
        $this->Object->beforeAction();
        $this->assertEqual($this->Object->message, 'ABC');
    }

    public function test_of_before_filter_mixed_filter_with_default_type()
    {
        $this->Object->beforeFilter('A');
        $this->Object->beforeFilter('B');
        $this->Object->beforeFilter(new TestingFiltersUtitlityClassWithFilterMethod());

        $this->Object->beforeAction();
        $this->assertEqual($this->Object->message, 'filtered:AB');
    }

    public function test_of_before_filter_mixed_filter_with_prepend_filter()
    {
        $this->Object->beforeFilter('A');
        $this->Object->beforeFilter('B');
        $this->Object->prependBeforeFilter('C');
        $this->Object->beforeAction();
        $this->assertEqual($this->Object->message, 'CAB');

        $this->Object->message = '';
        $this->Object->beforeFilter('D','E','F');
        $this->Object->prependBeforeFilter('G','H');
        $this->Object->beforeAction();
        $this->assertEqual($this->Object->message, 'GHCABDEF');
    }

    public function test_of_before_filter_skiping_filters()
    {
        $this->Object->beforeFilter('A');
        $this->Object->beforeFilter('B');
        $this->Object->beforeFilter('C');
        $this->Object->beforeFilter('D');

        $this->Object->skipBeforeFilter('B');

        $this->Object->beforeAction();
        $this->assertEqual($this->Object->message, 'ACD');

        $FilterObject = new TestingFiltersUtitlityClassWithFilterMethod();

        $this->Object->beforeFilter($FilterObject);

        $this->Object->beforeFilter('E');

        $this->Object->skipBeforeFilter($FilterObject);

        $this->Object->message = '';
        $this->Object->beforeAction();
        $this->assertEqual($this->Object->message, 'ACDE');

    }

    public function test_of_before_filter_with_conditions()
    {
        $this->Object->beforeFilter(array('A'=>array('only'=>array('edit','delete'))));
        $this->Object->beforeFilter(array('B'=>array('except'=>array('edit'))));
        $this->assertEqual($this->Object->getFilterIncludedActions(), array('A'=>array('edit','delete')));
        $this->assertEqual($this->Object->getFilterExcludedActions(), array('B'=>array('edit')));

        $this->Object->beforeAction('edit');
        $this->assertEqual($this->Object->message, 'A');

        $this->Object->message = '';
        $this->Object->beforeAction('delete');
        $this->assertEqual($this->Object->message, 'AB');

        $this->Object->message = '';
        $this->Object->beforeAction('move');
        $this->assertEqual($this->Object->message, 'B');
    }

    public function test_of_before_filter_with_conditions_skiping_filters()
    {
        $this->Object->beforeFilter('A');
        $this->Object->beforeFilter('B');
        $this->Object->beforeFilter(new TestingFiltersUtitlityClassWithFilterMethod());
        $this->Object->beforeFilter(array('C'=>array('except'=>array(new TestingFiltersUtitlityClassWithFilterMethod()))));

        $this->Object->beforeAction();
        $this->assertEqual($this->Object->message, 'filtered:ABC');

        $this->Object->message = '';
        $this->Object->beforeAction(new TestingFiltersUtitlityClassWithFilterMethod());
        $this->assertEqual($this->Object->message, 'filtered:AB');
    }

    public function test_of_after_filter()
    {
        $this->Object->afterFilter('A');
        $this->Object->afterFilter('B');
        $this->Object->afterFilter('C');

        $this->Object->afterAction();
        $this->assertEqual($this->Object->message, 'ABC');

        $this->Object->message = '';
        $this->Object->afterFilter('returnFalse');
        $this->Object->afterFilter('D');
        $this->Object->afterAction();
        $this->assertEqual($this->Object->message, 'ABC');
    }

    public function test_of_after_filter_mixed_filter_types()
    {
        $this->Object->afterFilter('A');
        $this->Object->afterFilter(array(&$this->Object,'B'));
        $this->Object->afterFilter('C');

        $this->Object->afterAction();
        $this->assertEqual($this->Object->message, 'ABC');

        $this->Object->message = '';
        $this->Object->afterFilter(array(&$this->Object,'returnFalse'));
        $this->Object->afterFilter('D');
        $this->Object->afterAction();
        $this->assertEqual($this->Object->message, 'ABC');
    }

    public function test_of_after_filter_mixed_filter_with_default_type()
    {
        $this->Object->afterFilter('A');
        $this->Object->afterFilter('B');
        $this->Object->afterFilter(new TestingFiltersUtitlityClassWithFilterMethod());
        $this->Object->afterAction();
        $this->assertEqual($this->Object->message, 'filtered:AB');
    }

    public function test_of_after_filter_mixed_filter_with_prepend_filter()
    {
        $this->Object->afterFilter('A');
        $this->Object->afterFilter('B');
        $this->Object->prependAfterFilter('C');
        $this->Object->afterAction();
        $this->assertEqual($this->Object->message, 'CAB');

        $this->Object->message = '';
        $this->Object->afterFilter('D','E','F');
        $this->Object->prependAfterFilter('G','H');
        $this->Object->afterAction();
        $this->assertEqual($this->Object->message, 'GHCABDEF');
    }

    public function test_of_after_filter_skiping_filters()
    {
        $this->Object->afterFilter('A');
        $this->Object->afterFilter('B');
        $this->Object->afterFilter('C');
        $this->Object->afterFilter('D');

        $this->Object->skipAfterFilter('B');

        $this->Object->afterAction();
        $this->assertEqual($this->Object->message, 'ACD');

        $this->Object->afterFilter(new TestingFiltersUtitlityClassWithFilterMethod());

        $this->Object->afterFilter('E');

        $this->Object->skipAfterFilter(new TestingFiltersUtitlityClassWithFilterMethod());

        $this->Object->message = '';
        $this->Object->afterAction();
        $this->assertEqual($this->Object->message, 'ACDE');

    }

    public function test_of_after_filter_with_conditions()
    {
        $this->Object->afterFilter(array('A'=>array('only'=>array('edit','delete'))));
        $this->Object->afterFilter(array('B'=>array('except'=>array('edit'))));
        $this->assertEqual($this->Object->getFilterIncludedActions(), array('A'=>array('edit','delete')));
        $this->assertEqual($this->Object->getFilterExcludedActions(), array('B'=>array('edit')));

        $this->Object->afterAction('edit');
        $this->assertEqual($this->Object->message, 'A');

        $this->Object->message = '';
        $this->Object->afterAction('delete');
        $this->assertEqual($this->Object->message, 'AB');

        $this->Object->message = '';
        $this->Object->afterAction('move');
        $this->assertEqual($this->Object->message, 'B');
        //$this->assertEqual($this->Object->message, 'ACD');

    }

    public function test_of_after_filter_with_conditions_skiping_filters()
    {
        $this->Object->afterFilter('A');
        $this->Object->afterFilter('B');
        $this->Object->afterFilter(new TestingFiltersUtitlityClassWithFilterMethod());
        $this->Object->afterFilter(array('C'=>array('except'=>array(new TestingFiltersUtitlityClassWithFilterMethod()))));

        $this->Object->afterAction();
        $this->assertEqual($this->Object->message, 'filtered:ABC');

        $this->Object->message = '';
        $this->Object->afterAction(new TestingFiltersUtitlityClassWithFilterMethod());
        $this->assertEqual($this->Object->message, 'filtered:AB');
    }

    public function test_of_around_filters()
    {
        $this->Object->aroundFilter(
        new TestingFiltersUtitlityClassWithFilterMethod('A'),
        new TestingFiltersUtitlityClassWithFilterMethod('B'));

        $this->Object->performAction();
        $this->assertEqual($this->Object->message, 'before-B:before-A::A-after:B-after');
    }

    public function test_of_appendAroundFilter()
    {
        $this->Object->appendAroundFilter(new TestingFiltersUtitlityClassWithFilterMethod('A'), new TestingFiltersUtitlityClassWithFilterMethod('B'));

        $this->Object->performAction();
        $this->assertEqual($this->Object->message, 'before-B:before-A::A-after:B-after');
    }


    public function test_of_prependAroundFilter()
    {
        $this->Object->prependAroundFilter(new TestingFiltersUtitlityClassWithFilterMethod('A'), new TestingFiltersUtitlityClassWithFilterMethod('B'));

        $this->Object->performAction();
        $this->assertEqual($this->Object->message, 'before-A:before-B::B-after:A-after');

        $this->Object->message = '';
        $this->Object->beforeFilter('X');
        $this->Object->afterFilter('Z');

        $this->Object->performAction();
        $this->assertEqual($this->Object->message, 'before-A:before-B:X:B-after:A-afterZ');
    }
}

ak_test_run_case_if_executed('AkActionControllerFilters_TestCase');

