<?php

require_once(dirname(__FILE__).'/../config.php');

class TestClassUsedViaProxyByALazyObject extends AkLazyObject
{
    public $allowed = 'yes';
    public $not_allowed = 'no';
    public $concatenated_string;

    public $_private_attribute = 'private';
    private $private_attribute = 'private';

    public function concatenate($value = null) {
        $this->concatenated_string .= $value;
        return $this->concatenated_string;
    }

    public function explicit() {
        return 'French';
    }

    public function _PrivateByConvention() {
        return '_PrivateByConvention';
    }

    public function findAll() {
        return 'findAll';
    }

    public function findOne() {
        return 'findOne';
    }
}

class TestLazyObject extends AkLazyObject
{
    public $on_controller = 'on_proxy';
}


class LazyObject_TestCase extends  ActiveSupportUnitTest
{
    public function test_should_extend_a_class_given_its_name() {
        $Lazy = new TestLazyObject();
        $Lazy->extendClassLazily('TestClassUsedViaProxyByALazyObject');

        $Proxy = new TestClassUsedViaProxyByALazyObject();
        $this->assertTrue($Lazy->isExtendedBy($Proxy));
    }

    public function test_should_remove_extensions_giving_its_name() {
        $Lazy = new TestLazyObject();
        $Proxy = new TestClassUsedViaProxyByALazyObject();
        $this->assertTrue($Lazy->isExtendedBy($Proxy));

        $Lazy->unregisterExtenssion('TestClassUsedViaProxyByALazyObject');

        $this->assertFalse($Lazy->isExtendedBy($Proxy));
    }

    public function test_should_be_extended_using_implicit_methods() {
        $Lazy = new TestLazyObject();
        $Lazy->extendClassLazily('TestClassUsedViaProxyByALazyObject', array('methods' => array('concatenate')));
        $Proxy = $Lazy->getExtendedClassInstance('TestClassUsedViaProxyByALazyObject');
        $this->assertEqual( $Lazy->concatenate('a'), 'a');
        $this->assertEqual($Proxy->concatenate('b'), 'ab');
        $this->assertEqual( $Lazy->concatenate('c'), 'abc');
        $Lazy->unregisterExtenssion('TestClassUsedViaProxyByALazyObject');
        $Lazy->extendClassLazily('TestClassUsedViaProxyByALazyObject', array('methods' => array('concatenate')));
        $this->assertEqual( $Lazy->concatenate('z'), 'z');
        $Lazy->unregisterExtenssion('TestClassUsedViaProxyByALazyObject');
    }

    public function test_should_report_error_if_unregistered_methods_are_called() {
        $Lazy = new TestLazyObject();
        $Lazy->extendClassLazily('TestClassUsedViaProxyByALazyObject', array('methods' => array('concatenate')));

        $this->expectError(new PatternExpectation('/undefined method TestLazyObject::invalid.+lazy_object\.php .+'.(__LINE__+1).'/'));
        $Lazy->invalid();

        $this->expectError(new PatternExpectation('/undefined method TestLazyObject::explicit.+lazy_object\.php .+'.(__LINE__+1).'/'));
        $Lazy->explicit();

        $Lazy->unregisterExtenssion('TestClassUsedViaProxyByALazyObject');
    }

    public function test_should_be_extended_using_instance() {
        $Lazy = new TestLazyObject();
        $Lazy->extendClass(new TestClassUsedViaProxyByALazyObject());

        $this->assertEqual($Lazy->explicit(), 'French');

        $Proxy = $Lazy->getExtendedClassInstance('TestClassUsedViaProxyByALazyObject');
        $this->assertEqual( $Lazy->concatenate('a'), 'a');
        $this->assertEqual($Proxy->concatenate('b'), 'ab');
        $this->assertEqual( $Lazy->concatenate('c'), 'abc');

        $Lazy->unregisterExtenssion('TestClassUsedViaProxyByALazyObject');
    }

    public function test_should_allow_using_proxy_attributes_if_set_implicitly_only() {
        $Lazy = new TestLazyObject();
        $Lazy->extendClassLazily('TestClassUsedViaProxyByALazyObject', array('attributes' => array('allowed')));
        $this->assertEqual($Lazy->allowed, 'yes');

        $this->expectError(new PatternExpectation('/undefined attribute TestLazyObject::not_allowed.+lazy_object\.php .+'.(__LINE__+1).'/'));
        $this->assertNotEqual($Lazy->not_allowed, 'no');
        $Lazy->unregisterExtenssion('TestClassUsedViaProxyByALazyObject');
    }

    public function test_should_allow_using_proxy_attributes_when_using_instance() {
        $Lazy = new TestLazyObject();
        $Lazy->extendClass(new TestClassUsedViaProxyByALazyObject());
        $this->assertEqual($Lazy->allowed, 'yes');
        $this->assertEqual($Lazy->not_allowed, 'no');
        $Lazy->unregisterExtenssion('TestClassUsedViaProxyByALazyObject');
    }

    public function test_should_respect_attribute_visibility() {
        $Lazy = new TestLazyObject();
        $Lazy->extendClass(new TestClassUsedViaProxyByALazyObject());
        $this->expectError(new PatternExpectation('/undefined attribute TestLazyObject::private_attribute.+lazy_object\.php .+'.(__LINE__+1).'/'));
        $this->assertNotEqual($Lazy->private_attribute, 'private');
        $this->expectError(new PatternExpectation('/undefined attribute TestLazyObject::_private_attribute.+lazy_object\.php .+'.(__LINE__+1).'/'));
        $this->assertNotEqual($Lazy->_private_attribute, 'private');
        $Lazy->unregisterExtenssion('TestClassUsedViaProxyByALazyObject');
    }


    public function test_should_add_methods_by_pattern() {
        $Lazy = new TestLazyObject();
        $Lazy->extendClassLazily('TestClassUsedViaProxyByALazyObject', array('methods_match' => '/find.+/'));

        $this->expectError(new PatternExpectation('/undefined method TestLazyObject::explicit.+lazy_object\.php .+'.(__LINE__+1).'/'));
        $Lazy->explicit();

        $this->assertEqual($Lazy->findAll(), 'findAll');
        $this->assertEqual($Lazy->findOne(), 'findOne');
        $Lazy->unregisterExtenssion('TestClassUsedViaProxyByALazyObject');
    }



    public function test_should_not_allow_extending_by_class_using_by_name() {
        $Lazy = new TestLazyObject();
        $this->expectError(new PatternExpectation('/expects a string, object given.+lazy_object\.php .+'.(__LINE__+1).'/'));
        $Lazy->extendClassLazily(new TestClassUsedViaProxyByALazyObject());
    }

    public function test_should_not_register_twice_unless_forced() {
        $Lazy = new TestLazyObject();
        $Lazy->extendClassLazily('TestClassUsedViaProxyByALazyObject', array('methods_match' => '/find.+/'));

        $Lazy->extendClass(new TestClassUsedViaProxyByALazyObject());

        $this->expectError(new PatternExpectation('/undefined method TestLazyObject::explicit.+lazy_object\.php .+'.(__LINE__+1).'/'));
        $Lazy->explicit();

        $Lazy->extendClass(new TestClassUsedViaProxyByALazyObject(), array('force' => true));
        $this->assertEqual($Lazy->explicit(), 'French');
        $Lazy->unregisterExtenssion('TestClassUsedViaProxyByALazyObject');
    }

    public function test_should_return_instance_being_extended_by_name() {
        $Lazy = new TestLazyObject();
        $Lazy->extendClassLazily('TestClassUsedViaProxyByALazyObject');
        $Proxy = $Lazy->getExtendedClassInstance('TestClassUsedViaProxyByALazyObject');
        $Proxy->getInstanceBeingExtended('TestLazyObject');
        $this->assertReference($Proxy->getInstanceBeingExtended('TestLazyObject'), $Lazy);
        $Lazy->unregisterExtenssion('TestClassUsedViaProxyByALazyObject');

    }

    public function test_should_return_instance_being_extended() {
        $Lazy = new TestLazyObject();
        $Lazy->extendClass(new TestClassUsedViaProxyByALazyObject());
        $Proxy = $Lazy->getExtendedClassInstance('TestClassUsedViaProxyByALazyObject');
        $this->assertReference($Proxy->getInstanceBeingExtended('TestLazyObject'), $Lazy);
        $Lazy->unregisterExtenssion('TestClassUsedViaProxyByALazyObject');
    }

    public function test_should_report_if_lazy_objects_are_now_active() {
        $Lazy = new TestLazyObject();
        $Lazy->extendClassLazily('TestClassUsedViaProxyByALazyObject');
        $this->assertFalse($Lazy->hasInstantiatedClass('TestClassUsedViaProxyByALazyObject'));
        $Proxy = $Lazy->getExtendedClassInstance('TestClassUsedViaProxyByALazyObject');
        $this->assertTrue($Lazy->hasInstantiatedClass('TestClassUsedViaProxyByALazyObject'));
        $this->assertReference($Proxy->getInstanceBeingExtended('TestLazyObject'), $Lazy);
        $Lazy->unregisterExtenssion('TestClassUsedViaProxyByALazyObject');
    }
}

ak_test_case('LazyObject_TestCase');

