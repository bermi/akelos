<?php

require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

class AkReflectionFile_TestCase extends AkUnitTest
{
    public function test_single_class()
    {
        $filename = AK_TEST_DIR.DS.'fixtures'.DS.'data'.DS.'reflection_test_class.php';
        $file = new AkReflectionFile($filename);
        $this->assertEqual(1,count($file->getClasses()));
        $classes = $file->getClasses();
        $this->assertEqual('ReflectionTestClass1',$classes[0]->getName());
    }

    public function test_multiple_classes()
    {
        $filename = AK_TEST_DIR.DS.'fixtures'.DS.'data'.DS.'reflection_test_classes.php';
        $file = new AkReflectionFile($filename);
        $this->assertEqual(2,count($file->getClasses()));
        $classes = $file->getClasses();
        $this->assertEqual('ReflectionTestClass1',$classes[0]->getName());
        $this->assertEqual('ReflectionTestClass2',$classes[1]->getName());
    }

    public function test_special1()
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

ak_test_run_case_if_executed('AkReflectionFile_TestCase');
