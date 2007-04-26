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
        $this->assertEqual(TagHelper::tag('hr',array('style'=>null,1234=>'This is not possible')),'<hr />');

        $this->assertEqual(TagHelper::content_tag('p','Have a look "HERE"'),
        '<p>Have a look "HERE"</p>');

        $this->assertEqual(TagHelper::content_tag('textarea','Have a look "HERE"',array('name'=>'details')),
        '<textarea name="details">Have a look "HERE"</textarea>');

        $this->assertEqual(TagHelper::cdata_section('Have a look "HERE"'),
        '<![CDATA[Have a look "HERE"]]>');
    }

    function test_for_not_double_escaping_entities()
    {
        $this->assertEqual(TagHelper::escape_once("1 > 2 &amp; 3"), "1 &gt; 2 &amp; 3");
    }

}


ak_test('TagHelperTests');

?>