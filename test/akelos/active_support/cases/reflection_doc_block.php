<?php


require_once(dirname(__FILE__).'/../config.php');

class ReflectionDocBlock_TestCase extends ActiveSupportUnitTest
{
    public function test_set_tag() {
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
        $a = explode("\n", '/**
 * test comment
 *
 * @tag value
 * @test testtag
 * @param $test
 */');
    }
}

ak_test_case('ReflectionDocBlock_TestCase');

