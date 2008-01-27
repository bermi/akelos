<?php
defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);
require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

require_once(AK_LIB_DIR.DS.'AkActiveRecord.php');
require_once(AK_LIB_DIR.DS.'AkActiveRecord'.DS.'AkObserver.php');

/* Create Mocks */
$callbacks_during_save = array('beforeCreate','beforeValidation','beforeValidationOnUpdate','beforeValidationOnCreate','beforeSave','beforeUpdate','beforeDestroy',
                   'afterCreate','afterValidation','afterValidationOnUpdate','afterValidationOnCreate','afterSave','afterUpdate','afterDestroy');

function createClass ($classname, $parent, $functions, $function_body){
    $class_code = "class $classname extends $parent {";
    if (!AK_PHP5 && $parent=='ActiveRecord') $class_code .= __fix_for_PHP4($classname);
    foreach ($functions as $function){
        $class_code .= "function $function(){".$function_body."}";
    }
    $class_code .= "}";
    eval($class_code);
}
function __fix_for_PHP4($model_name)
{
    $table_name = AkInflector::tableize($model_name);
    return "function $model_name()
{
    \$this->setModelName('$model_name');
    \$attributes = (array)func_get_args();
    \$this->setTableName('$table_name');
    \$this->init(\$attributes);
}";
    
}


createClass('TestCallback','ActiveRecord',$callbacks_during_save,'$this->__called[]=__FUNCTION__;return true;');
createClass('TestObserver','AkObserver',$callbacks_during_save,'$this->__called[]=__FUNCTION__;return true;');

/* Test */
class TestCase_AkActiveRecord_callbacks extends  AkUnitTest
{
    function test_start()
    {
        $this->installAndIncludeModels(array('TestCallback'=>'id,name'));
        if (!isset($this->Observer)) $this->Observer =& new TestObserver(&$this->TestCallback);
    }
    
    function tearDown()
    {
        $this->Observer->__called = array();
    }

    function test_implementation_of_the_singleton_pattern()
    {
        $ObservedModel =& new TestCallback();
        $observers =& $ObservedModel->getObservers();
        $this->assertReference($this->Observer,$observers[0]);
    }

    function test_callbacks_on_create()
    {
        $expected = array ('beforeSave','beforeCreate','afterCreate','afterSave');
        if (!AK_PHP5) $expected = array_map('strtolower',$expected);
        
        $CreateTest =& new TestCallback(array('name'=>'A Name'));
        $CreateTest->save(false);

        $this->assertEqual($CreateTest->__called,$expected);
        $this->assertEqual($this->Observer->__called,$expected);
    }
    
    function test_callbacks_on_create_with_validation()
    {
        $expected = array ('beforeSave','beforeValidation','afterValidation','beforeValidationOnCreate','afterValidationOnCreate','beforeCreate','afterCreate','afterSave');
        if (!AK_PHP5) $expected = array_map('strtolower',$expected);
        
        $CreateTest =& new TestCallback(array('name'=>'Another Name'));
        $CreateTest->save(true);

        $this->assertEqual($CreateTest->__called,$expected);
        $this->assertEqual($this->Observer->__called,$expected);
    }

    function test_callbacks_on_update()
    {
        $expected = array ('beforeSave','beforeUpdate','afterUpdate','afterSave');
        if (!AK_PHP5) $expected = array_map('strtolower',$expected);
        
        $UpdateTest =& $this->TestCallback->find('first',array('name'=>'A Name'));
        $UpdateTest->save(false);

        $this->assertEqual($UpdateTest->__called,$expected);
        $this->assertEqual($this->Observer->__called,$expected);
    }

    function test_callbacks_on_update_with_validation()
    {
        $expected = array ('beforeSave','beforeValidation','afterValidation','beforeValidationOnUpdate','afterValidationOnUpdate','beforeUpdate','afterUpdate','afterSave');
        if (!AK_PHP5) $expected = array_map('strtolower',$expected);
        
        $UpdateTest =& $this->TestCallback->find('first',array('name'=>'Another Name'));
        $UpdateTest->save(true);

        $this->assertEqual($UpdateTest->__called,$expected);
        $this->assertEqual($this->Observer->__called,$expected);
    }
    
    function test_callbacks_on_destroy()
    {
        $expected = array('beforeDestroy','afterDestroy');
        if (!AK_PHP5) $expected = array_map('strtolower',$expected);
        
        $DestroyTest =& $this->TestCallback->find('first',array('name'=>'Another Name'));
        $DestroyTest->destroy();
        
        $this->assertEqual($DestroyTest->__called,$expected);
        $this->assertEqual($this->Observer->__called,$expected);
    }
}

ak_test('TestCase_AkActiveRecord_callbacks', true);
?>