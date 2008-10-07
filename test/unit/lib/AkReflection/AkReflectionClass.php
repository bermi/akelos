<?php


require_once(AK_LIB_DIR.DS.'AkReflection'.DS.'AkReflectionClass.php');

class AkReflectionClass_TestCase extends  UnitTestCase
{
    function test_string_constructor()
    {
        $string ='class Test {
        
            function method1($param1) {
            
            }
            /**
             * comment
             * @return void
             * @param $param1
             * @param $param2
             */
            function &method2(&$param1,$param2) {
            
            }
        }';
        $class = new AkReflectionClass($string);
        $this->assertEqual('Test',$class->getName());
        $methods = $class->getMethods();
        $this->assertEqual(2,count($methods));
        
        $this->assertEqual('method1',$methods[0]->getName());
        $this->assertFalse($methods[0]->returnByReference());
        $this->assertEqual('method2',$methods[1]->getName());
        $this->assertTrue($methods[1]->returnByReference());
        $docBlock = $methods[1]->getDocBlock();
        $this->assertTrue(is_a($docBlock,'AkReflectionDocBlock'));
        $this->assertEqual('comment',$docBlock->getComment());
        $params =$docBlock->getParams();
        $this->assertEqual(2,count($params));
        $this->assertEqual('void',$docBlock->getTag('return'));
    }
    
    function test_array_constructor()
    {
        $string ='class Test {
        
            function method1($param1) {
            
            }
            /**
             * comment
             * @return void
             * @param $param1
             * @param $param2
             */
            function &method2(&$param1,$param2) {
            
            }
        }';
        $class = new AkReflectionClass($string);
        $array = $class->_definition;
        $class = new AkReflectionClass($array);
        $this->assertEqual('Test',$class->getName());
        $methods = $class->getMethods();
        $this->assertEqual(2,count($methods));
        
        $this->assertEqual('method1',$methods[0]->getName());
        $this->assertFalse($methods[0]->returnByReference());
        $this->assertEqual('method2',$methods[1]->getName());
        $this->assertTrue($methods[1]->returnByReference());
        $docBlock = $methods[1]->getDocBlock();
        $this->assertTrue(is_a($docBlock,'AkReflectionDocBlock'));
        $this->assertEqual('comment',$docBlock->getComment());
        $params =$docBlock->getParams();
        $this->assertEqual(2,count($params));
        $this->assertEqual('void',$docBlock->getTag('return'));
        
        
        $voidMethods = $class->getMethods(array('tags'=>array('return'=>'void')));

        $this->assertEqual(1,count($voidMethods));
    }
    
    function test_get_methods_filtered()
    {
        $file = AK_TEST_DIR.DS.'fixtures'.DS.'data'.DS.'reflection_test_class.php';
        $class = new AkReflectionClass(file_get_contents($file));
        $filteredMethods = $class->getMethods(array('tags'=>array('WingsPluginInstallAs'=>'.*')));
        $this->assertEqual(1,count($filteredMethods));
        $this->assertEqual('testFunction2',$filteredMethods[0]->getName());
        
    }
}

ak_test('AkReflectionClass_TestCase',true);
?>