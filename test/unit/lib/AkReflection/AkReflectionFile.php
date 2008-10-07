<?php


require_once(AK_LIB_DIR.DS.'AkReflection'.DS.'AkReflectionFile.php');

class AkReflectionFile_TestCase extends  UnitTestCase
{

    function test_single_class()
    {
        $filename = AK_TEST_DIR.DS.'fixtures'.DS.'data'.DS.'reflection_test_class.php';
        $file = new AkReflectionFile($filename);
        $this->assertEqual(1,count($file->getClasses()));
        $classes = $file->getClasses();
        $this->assertEqual('ReflectionTestClass1',$classes[0]->getName());
    }
    
    function test_multiple_classes()
    {
        $filename = AK_TEST_DIR.DS.'fixtures'.DS.'data'.DS.'reflection_test_classes.php';
        $file = new AkReflectionFile($filename);
        $this->assertEqual(2,count($file->getClasses()));
        $classes = $file->getClasses();
        $this->assertEqual('ReflectionTestClass1',$classes[0]->getName());
        $this->assertEqual('ReflectionTestClass2',$classes[1]->getName());
    }
    
    function test_special1()
    {
        $filename = AK_TEST_DIR.DS.'fixtures'.DS.'data'.DS.'reflection_doc_block_test_class.php';
        $file = new AkReflectionFile($filename);
        $this->assertEqual(1,count($file->getClasses()));
        $classes = $file->getClasses();
        $this->assertEqual('ReflectionDocBlockTestClass',$classes[0]->getName());
        
        $class = $classes[0];
        $this->assertEqual('BaseActiveRecord',$class->getTag('ExtensionPoint'));

    }
}

ak_test('AkReflectionFile_TestCase',true);
?>