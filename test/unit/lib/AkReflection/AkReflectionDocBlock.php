<?php

require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

class AkReflectionDocBlock_TestCase extends AkUnitTest
{
    public function test_set_tag()
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
        $a = explode("\n", '/**
 * test comment
 *
 * @tag value
 * @test testtag
 * @param $test
 */');
    }
}

ak_test_run_case_if_executed('AkReflectionDocBlock_TestCase');

