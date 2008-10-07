<?php
require_once(AK_LIB_DIR.DS.'AkType.php');

class Test_AkString extends AkUnitTest
{
    function test_constructor_default()
    {
        $string = new AkString('test');
        $this->assertEqual('test',$string->toString());
    }
    
    function test_constructor_magic_string()
    {
        $string = &AkT('test');
        $this->assertEqual('test',$string->toString());
    }

    function test_at()
    {
        $string = &AkT('test');
        $this->assertEqual('t',$string->at(0));
        
        $char = &AkT('test','at(0)');
        $this->assertEqual('t',$char);
        
        $char = &AkT('test','at(2)');
        $this->assertEqual('s',$char);
    }
    
    function test_ends_with()
    {
        $string = &AkT("test ends with\nstring");
        $this->assertTrue($string->endsWith("h\nstring"));
        
        $endswith = &AkT('test ends with\nstring','endswith(string)');
        $this->assertTrue($endswith);
        
        $endswith = &AkT('test ends with\nstring','endswith(test)');
        $this->assertFalse($endswith);
        
    }
    function test_starts_with()
    {
        $string = &AkT("test ends with\nstring");
        $this->assertTrue($string->startsWith("test ends with\n"));
        
        $startswith = &AkT('test ends with\nstring','startswith(test)');
        $this->assertTrue($startswith);
        
        $startswith = &AkT('test ends with\nstring','startswith(ends)');
        $this->assertFalse($startswith);
        
    }
    function test_first()
    {
        $string = &AkT("test string");
        $this->assertEqual('t',$string->first());
        $this->assertEqual('te',$string->first(2));
        $this->assertEqual('tes',$string->first(3));
    }
    function test_last()
    {
        $string = &AkT("test string");
        $this->assertEqual('g',$string->last());
        $this->assertEqual('ng',$string->last(2));
        $this->assertEqual('ing',$string->last(3));
        $this->assertEqual('test string',$string->last(20));
    }
    function test_from()
    {
        $string = &AkT("test string");
        $this->assertEqual('est string',$string->from(1));
        
        $this->assertEqual('',$string->from(11));
    }
    function test_to()
    {
        $string = &AkT("test string");
        $this->assertEqual('t',$string->to(1));
        
        $this->assertEqual('test ',$string->to(5));
    }
    function test_humanize()
    {
        $string = &AkT("this_is_a_test");
        $this->assertEqual('This is a test',$string->humanize());
    }
    function test_tableize()
    {
        $string = &AkT("This is a test");
        $this->assertEqual('this_is_a_tests',$string->tableize());
    }
    
    function test_titleize()
    {
        $string = &AkT("This is a test");
        $this->assertEqual('This Is A Test',$string->titleize());
        
        $string = &AkT("This_is_a_test");
        $this->assertEqual('This Is A Test',$string->titleize());
        
        $string = &AkT("ThisIsATest");
        $this->assertEqual('This Is A Test',$string->titleize());
    }
    function test_length()
    {
        $text = "this_is_a_test";
        $string = &AkT($text);
        $this->assertEqual(strlen($text),$string->length());
    }
    
    function test_pluralize()
    {
        $text = "Comment";
        $string = &AkT($text);
        $this->assertEqual('Comments',$string->pluralize());
        
        $string = &AkT($text,'pluralize');
        $this->assertEqual('Comments',$string);
        
        $string = AkT('inglés','pluralize(es)');
        $this->assertEqual('ingleses',$string);
    }
    
    function test_singularize()
    {
        $text = "Comments";
        $string = &AkT($text);
        $this->assertEqual('Comment',$string->singularize());
        
        $string = &AkT($text,'singularize');
        $this->assertEqual('Comment',$string);
    }
}
ak_test('Test_AkString');