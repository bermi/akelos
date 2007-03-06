<?php

defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);
require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

require_once(AK_LIB_DIR.DS.'AkActionController.php');

defined('AK_SESSION_HANDLER') ? null : define('AK_SESSION_HANDLER', 1);

$testing_object = '';
for ($i = 65; $i <= 90; $i++){
    $char = chr($i);
    $testing_object .= "\n    function $char(){
        \$this->message .= '$char';
    }";
}
$testing_object = "class TestingFiltersUtitlityClass extends AkActionController
{
    var \$message = '';
    function returnFalse(){return false;}
    $testing_object
}
";

eval($testing_object);
unset($testing_object);

class TestingFiltersUtitlityClassWithFilterMethod extends TestingFiltersUtitlityClass
{
    var $_char = '';
    function TestingFiltersUtitlityClassWithFilterMethod($char = ''){
        $this->_char = $char;
    }
    function filter(&$target){
        $target->message = 'filtered:'.$target->message;
    }
    function before(&$target){
        $target->message = 'before-'.$this->_char.':'.$target->message;
    }
    function after(&$target){
        $target->message = $target->message.':'.$this->_char.'-after';
    }
}


class Test_of_AkActionControllerFilters extends  UnitTestCase
{

    function setUp()
    {
        $this->Object =& new TestingFiltersUtitlityClass();
    }

    function tearDown()
    {
        unset($this->Object);
    }

    function test_of_before_filter()
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

    function test_of_before_filter_mixed_filter_types()
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

    function test_of_before_filter_mixed_filter_with_default_type()
    {
        $this->Object->beforeFilter('A');
        $this->Object->beforeFilter('B');
        $this->Object->beforeFilter(new TestingFiltersUtitlityClassWithFilterMethod());
        $this->Object->beforeAction();
        $this->assertEqual($this->Object->message, 'filtered:AB');
    }

    function test_of_before_filter_mixed_filter_with_prepend_filter()
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

    function test_of_before_filter_skiping_filters()
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


    function test_of_before_filter_with_conditions()
    {
        $this->Object->beforeFilter(array('A'=>array('only'=>array('edit','delete'))));
        $this->Object->beforeFilter(array('B'=>array('except'=>array('edit'))));
        $this->assertEqual($this->Object->includedActions(), array('A'=>array('edit','delete')));
        $this->assertEqual($this->Object->excludedActions(), array('B'=>array('edit')));

        $this->Object->beforeAction('edit');
        $this->assertEqual($this->Object->message, 'A');

        $this->Object->message = '';
        $this->Object->beforeAction('delete');
        $this->assertEqual($this->Object->message, 'AB');

        $this->Object->message = '';
        $this->Object->beforeAction('move');
        $this->assertEqual($this->Object->message, 'B');
    }


    function test_of_before_filter_with_conditions_skiping_filters()
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



    function test_of_after_filter()
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

    function test_of_after_filter_mixed_filter_types()
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

    function test_of_after_filter_mixed_filter_with_default_type()
    {
        $this->Object->afterFilter('A');
        $this->Object->afterFilter('B');
        $this->Object->afterFilter(new TestingFiltersUtitlityClassWithFilterMethod());
        $this->Object->afterAction();
        $this->assertEqual($this->Object->message, 'filtered:AB');
    }

    function test_of_after_filter_mixed_filter_with_prepend_filter()
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

    function test_of_after_filter_skiping_filters()
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


    function test_of_after_filter_with_conditions()
    {
        $this->Object->afterFilter(array('A'=>array('only'=>array('edit','delete'))));
        $this->Object->afterFilter(array('B'=>array('except'=>array('edit'))));
        $this->assertEqual($this->Object->includedActions(), array('A'=>array('edit','delete')));
        $this->assertEqual($this->Object->excludedActions(), array('B'=>array('edit')));

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


    function test_of_after_filter_with_conditions_skiping_filters()
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


    function test_of_around_filters()
    {
        $this->Object->aroundFilter(
        new TestingFiltersUtitlityClassWithFilterMethod('A'),
        new TestingFiltersUtitlityClassWithFilterMethod('B'));

        $this->Object->performAction();
        $this->assertEqual($this->Object->message, 'before-B:before-A::A-after:B-after');
    }

    function test_of_appendAroundFilter()
    {
        $this->Object->appendAroundFilter(new TestingFiltersUtitlityClassWithFilterMethod('A'), new TestingFiltersUtitlityClassWithFilterMethod('B'));

        $this->Object->performAction();
        $this->assertEqual($this->Object->message, 'before-B:before-A::A-after:B-after');
    }


    function test_of_prependAroundFilter()
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

if(!defined('ALL_TESTS_CALL')){
    ob_start();
    Ak::test('Test_of_AkActionControllerFilters');
    ob_end_flush();
}


?>
