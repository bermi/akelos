<?php

require_once(dirname(__FILE__).'/../../fixtures/config/config.php');



class TestClassUsedViaProxyByALazyObject extends AkLazyObject
{
    public $on_filter = 'on_lazy';
    public $concatenated_string;

    public function concatenate($value = null)
    {
        $this->concatenated_string .= $value;
        return $this->concatenated_string;
    }

    public function explicit()
    {
        return 'French';
    }
}

class TestLazyObject extends AkLazyObject
{
    public $on_controller = 'on_proxy';
}

/*
$Lazy = new TestLazyObject();
$Lazy->extendClassByName('TestClassUsedViaProxyByALazyObject', array('methods' => array('remember')));

echo $Lazy->on_filter;

echo $Lazy->remember(':) ');

echo "\n";
echo $Lazy->on_filter;
echo "\n";

$Proxy = $Lazy->getExtendedClassInstance('TestClassUsedViaProxyByALazyObject');

echo $Proxy->remember('a');
echo $Lazy->remember('b');
echo $Proxy->remember('c');
*/

class AkLazyObject_TestCase extends  AkUnitTest
{
    public function tearDown()
    {

    }

    public function test_should_extend_a_class_given_its_name()
    {
        $Lazy = new TestLazyObject();
        $Lazy->extendClassByName('TestClassUsedViaProxyByALazyObject');

        $Proxy = new TestClassUsedViaProxyByALazyObject();
        $this->assertTrue($Proxy->isExtending($Lazy));
        $this->assertTrue($Lazy->isExtendedBy($Proxy));
    }

    public function test_should_remove_extensions_giving_its_name()
    {
        $Lazy = new TestLazyObject();
        $Proxy = new TestClassUsedViaProxyByALazyObject();
        $this->assertTrue($Proxy->isExtending($Lazy));
        $this->assertTrue($Lazy->isExtendedBy($Proxy));

        $Lazy->unregisterExtenssion('TestClassUsedViaProxyByALazyObject');

        $this->assertFalse($Proxy->isExtending($Lazy));
        $this->assertFalse($Lazy->isExtendedBy($Proxy));
    }

    public function test_should_be_extended_using_implicit_methods()
    {
        $Lazy = new TestLazyObject();
        $Lazy->extendClassByName('TestClassUsedViaProxyByALazyObject', array('methods' => array('concatenate')));
        $Proxy = $Lazy->getExtendedClassInstance('TestClassUsedViaProxyByALazyObject');
        $this->assertEqual( $Lazy->concatenate('a'), 'a');
        $this->assertEqual($Proxy->concatenate('b'), 'ab');
        $this->assertEqual( $Lazy->concatenate('c'), 'abc');
        $Lazy->unregisterExtenssion('TestClassUsedViaProxyByALazyObject');
        $Lazy->extendClassByName('TestClassUsedViaProxyByALazyObject', array('methods' => array('concatenate')));
        $this->assertEqual( $Lazy->concatenate('z'), 'z');
        $Lazy->unregisterExtenssion('TestClassUsedViaProxyByALazyObject');
    }

    public function test_should_report_error_if_unregistered_methods_are_called()
    {
        $Lazy = new TestLazyObject();
        $Lazy->extendClassByName('TestClassUsedViaProxyByALazyObject', array('methods' => array('concatenate')));

        $this->expectError(new PatternExpectation('/undefined method TestLazyObject::invalid.+AkLazyObject\.php .+'.(__LINE__+1).'/'));
        $Lazy->invalid();

        $this->expectError(new PatternExpectation('/undefined method TestLazyObject::explicit.+AkLazyObject\.php .+'.(__LINE__+1).'/'));
        $Lazy->explicit();

        $Lazy->unregisterExtenssion('TestClassUsedViaProxyByALazyObject');
    }

    public function test_should_be_extended_using_instance()
    {
        $Lazy = new TestLazyObject();
        $Lazy->extendClass(new TestClassUsedViaProxyByALazyObject());
        $Proxy = $Lazy->getExtendedClassInstance('TestClassUsedViaProxyByALazyObject');
        $this->assertEqual($Lazy->explicit(), 'French');

        $this->assertEqual( $Lazy->concatenate('a'), 'a');
        $this->assertEqual($Proxy->concatenate('b'), 'ab');
        $this->assertEqual( $Lazy->concatenate('c'), 'abc');

        $Lazy->unregisterExtenssion('TestClassUsedViaProxyByALazyObject');
    }

}

ak_test_run_case_if_executed('AkLazyObject_TestCase');

