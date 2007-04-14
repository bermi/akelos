<?php

require_once('_HelpersUnitTester.php');
require_once(AK_LIB_DIR.DS.'AkActionView'.DS.'helpers'.DS.'javascript_helper.php');


class JavaScriptHelperTests extends HelpersUnitTester 
{    
    function test_for_JavascriptHelper()
    {
        require_once(AK_LIB_DIR.DS.'AkActionView'.DS.'helpers'.DS.'javascript_helper.php');
        
        $javascript = new JavaScriptHelper();
        
        $this->assertEqual($javascript->link_to_function('Greeting', "alert('Hello world!')"),'<a href="#" onclick="alert(\'Hello world!\'); return false;">Greeting</a>');
        $this->assertEqual($javascript->link_to_function('my link', "if confirm('Really?'){ do_delete(); }",array('href'=>'http://www.akelos.com')),'<a href="http://www.akelos.com" onclick="if confirm(\'Really?\'){ do_delete(); }; return false;">my link</a>');

        
        $this->assertEqual($javascript->button_to_function("Greeting", "alert('Hello world!')"),'<input onclick="alert(\'Hello world!\');" type="button" value="Greeting" />');
        $this->assertEqual($javascript->button_to_function("Delete", "if confirm('Really?'){ do_delete(); }",array('id'=>'confirm')),'<input id="confirm" onclick="if confirm(\'Really?\'){ do_delete(); };" type="button" value="Delete" />');
        
        
        $this->assertEqual($javascript->javascript_tag("alert('All is good')"), "<script type=\"text/javascript\">\n//<![CDATA[\nalert('All is good')\n//]]>\n</script>");
        
        $input = "
        <div id='meesage'
        
         class=\"hisghtlight\" />
        ";
        $expected = "\\n        <div id=\'meesage\'\\n        \\n         class=\\\"hisghtlight\\\" />\\n        ";
        $this->assertEqual($javascript->escape_javascript($input), $expected);
    }
    
    function test_javascript_tag()
    {
        $javascript = new JavaScriptHelper();

        //static call
        $this->AssertEqual(
            JavascriptHelper::javascript_tag("alert('test akelos');"),
            "<script type=\"text/javascript\">\n//<![CDATA[\nalert('test akelos');\n//]]>\n</script>"
        );

        //object call
        $this->AssertEqual(
            $javascript->javascript_tag("alert('test akelos');"),
            "<script type=\"text/javascript\">\n//<![CDATA[\nalert('test akelos');\n//]]>\n</script>"
        );
    }

    
}


Ak::test('JavaScriptHelperTests');

?>