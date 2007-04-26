<?php

require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

class test_Ak_convert extends  AkUnitTest
{
    function test_html_to_text()
    {
        $html = <<<EOF
<h1 id="creating_a_simple_application_using_the_akelos_framework">Creating a simple application using the Akelos Framework</h1>

<h2 id="introduction">Introduction</h2>

<p>This tutorial teaches you how to create an application using the Akelos Framework. </p>

<p>The application will be used for managing books and their authors and will be named <strong>booklink</strong></p>

<h2 id="requisites_for_this_tutorial">Requisites for this tutorial</h2>

<ul>
<li>A MySQL or SQLite Database</li>
<li>Apache web server</li>
<li>Shell access to your server</li>
</ul>

<p>You can checkout a working copy of the Akelos source code with the command:</p>

<pre><code>svn co http://akelosframework.googlecode.com/svn/trunk/ akelos
</code></pre>
EOF;
        $markdown = <<<EOF
Creating a simple application using the Akelos Framework
========================================================

Introduction
------------

This tutorial teaches you how to create an application using the Akelos Framework. 

The application will be used for managing books and their authors and will be named **booklink**

Requisites for this tutorial
----------------------------

*  A MySQL or SQLite Database 
*  Apache web server 
*  Shell access to your server 

You can checkout a working copy of the Akelos source code with the command:

    svn co http://akelosframework.googlecode.com/svn/trunk/ akelos
EOF;

        $this->assertEqual(Ak::convert('html','text',$html), $markdown);
    }
    
    function test_html_to_text_with_entities()
    {
        $html = <<<EOF
&&lt;b&gt;Hi there&lt;/b&gt;
EOF;
        $markdown = <<<EOF
&<b>Hi there</b>
EOF;

        $converted = Ak::convert('html','text',$html);
        $this->assertEqual($converted, $markdown);
    }
    
    function test_html_to_text_custom_tags()
    {
        $html = <<<EOF
<table><tr><td><rare><b>Hi</b></rare></td></tr>
EOF;
        $markdown = <<<EOF
**Hi**
EOF;
        $this->assertEqual(Ak::convert('html','text',$html), $markdown);
    }
    
    function test_html_to_text_removing_js()
    {
        $html = <<<EOF
<script type="text/javascript">something_really_bad()</script><em>Hola</em>
EOF;
        $markdown = <<<EOF
_Hola_
EOF;
        $this->assertEqual(Ak::convert('html','text',$html), $markdown);
    }
    
    function test_html_to_with_text_using_quotes()
    {
        $html = <<<EOF
&#8220;I&#8217;m completelly agree&#8221;
EOF;
        $markdown = <<<EOF
“I’m completelly agree”
EOF;
        $this->assertEqual(Ak::convert('html','text',$html), $markdown);
    }
    
    function test_html_to_text_using_smartipants()
    {
        $html = <<<EOF
"I'm completelly agree"
EOF;
        $markdown = <<<EOF
“I’m completelly agree”
EOF;
        $this->assertEqual(Ak::convert('html','text',$html), $markdown);
    }
}

ak_test('test_Ak_convert',true);

?>
