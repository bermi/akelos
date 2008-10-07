<?php


require_once(AK_LIB_DIR.DS.'AkReflection'.DS.'AkReflectionDocBlock.php');

class AkReflectionDocBlock_TestCase extends  UnitTestCase
{
    function test_set_tag()
    {
        $string ='/**
                   * test comment
                   *
                   * @param $test value
                   * @tag value
                   */';
        $docblock = new AkReflectionDocBlock($string);
        $docblock->setTag('test','testtag');
        $this->assertEqual('/**
 * test comment
 *
 * @tag value
 * @test testtag
 * @param $test 
 */',$docblock->toString());
    }
    
    
}

ak_test('AkReflectionDocBlock_TestCase',true);
?>