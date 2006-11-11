<?php

require_once('_HelpersUnitTester.php');
require_once(AK_LIB_DIR.DS.'AkActionView'.DS.'helpers'.DS.'tag_helper.php');


class TagHelperTests extends HelpersUnitTester 
{
    function test_TagHelper()
    {
        $this->assertEqual(TagHelper::tag('br'),'<br />');
        $this->assertEqual(TagHelper::tag('input',array('type'=>'text','value'=>'Insert your text >> "HERE"')),
        '<input type="text" value="Insert your text &gt;&gt; &quot;HERE&quot;" />');
        $this->assertEqual(TagHelper::tag('hr',array('style'=>'',1234=>'This is not possible')),'<hr />');

        $this->assertEqual(TagHelper::content_tag('p','Have a look "HERE"'),
        '<p>Have a look "HERE"</p>');

        $this->assertEqual(TagHelper::content_tag('textarea','Have a look "HERE"',array('name'=>'details')),
        '<textarea name="details">Have a look "HERE"</textarea>');

        $this->assertEqual(TagHelper::cdata_section('Have a look "HERE"'),
        '<![CDATA[Have a look "HERE"]]>');
    }
    
}


Ak::test('TagHelperTests');

?>