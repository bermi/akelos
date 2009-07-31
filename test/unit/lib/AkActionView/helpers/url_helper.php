<?php

require_once('_HelpersUnitTester.php');
require_once(AK_LIB_DIR.DS.'AkActionView'.DS.'helpers'.DS.'url_helper.php');
require_once(AK_LIB_DIR.DS.'AkActionView'.DS.'helpers'.DS.'asset_tag_helper.php');


class UrlHelperTests extends HelpersUnitTester
{
    function setup()
    {
        $this->Controller = &new MockAkActionController($this);
        $this->Controller->setReturnValue('urlFor', '/url/for/test');
        //$this->Controller->setReturnValue('_getCompleteRequestUri','/url/for/test');

        $this->url = new UrlHelper();
        $this->url->setController($this->Controller);
    }

    function test_for_UrlHelper()
    {
        $this->assertReference($this->Controller, $this->url->_controller);


        $input = array('disabled'=>1,'checked'=>false,'selected'=>'');
        $expected = array('disabled'=>'disabled');
        $this->assertEqual($this->url->_convert_boolean_attributes($input, array('disabled','checked','selected')),$expected);

        $input = array('disabled'=>true,'id'=>'hithere');
        $expected = array('disabled'=>'disabled','id'=>'hithere');
        $this->assertEqual($this->url->_convert_boolean_attributes($input, 'disabled'),$expected);

        $this->assertEqual($this->url->url_for(array('action'=>'create')),'/url/for/test');

        $this->assertEqual($this->url->button_to('Edit'),
        '<form method="post" action="/url/for/test" class="button-to"><div>'.
        '<input type="submit" value="Edit" /></div></form>');


        $this->assertEqual($this->url->link_to('Delete this page', array('action' => 'destroy', 'id' => 3), array('confirm' => 'Are you sure?')),'<a href="/url/for/test" onclick="return confirm(\'Are you sure?\');">Delete this page</a>');
        $this->assertEqual($this->url->link_to('Help', array('action' => 'help'), array('popup' => true)),'<a href="/url/for/test" onclick="window.open(this.href);return false;">Help</a>');
        $this->assertEqual($this->url->link_to('Help', array('action' => 'help'), array('popup' => true, 'confirm' => 'Are you sure?')),'<a href="/url/for/test" onclick="if (confirm(\'Are you sure?\')) { window.open(this.href); };return false;">Help</a>');
        $this->assertEqual($this->url->link_to('Help', array('action' => 'help'), array('post' => true)),'<a href="/url/for/test" onclick="var f = document.createElement(\'form\'); document.body.appendChild(f); f.method = \'POST\'; f.action = this.href; f.submit();return false;">Help</a>');
        $this->assertEqual($this->url->link_to('Destroy account', array('action' => 'destroy'), array('confirm' => 'Are you sure?'), array('post' => true)),'<a href="/url/for/test" onclick="return confirm(\'Are you sure?\');">Destroy account</a>');

        $this->assertEqual($this->url->link_to_unless(true,'Destroy account', array('action' => 'destroy'), array('confirm' => 'Are you sure?'), array('post' => true)),'Destroy account');
        $this->assertEqual($this->url->link_to_unless(false,'Destroy account', array('action' => 'destroy'), array('confirm' => 'Are you sure?'), array('post' => true)),'<a href="/url/for/test" onclick="return confirm(\'Are you sure?\');">Destroy account</a>');
        $this->assertEqual($this->url->_popup_javascript_function('A'),'window.open(this.href);');
        $this->assertEqual($this->url->_popup_javascript_function(array('A','B','C')),'window.open(this.href,\'A\',\'C\');');

        $this->assertEqual($this->url->_confirm_javascript_function('Are you sure?'),'confirm(\'Are you sure?\')');


        $this->assertEqual($this->url->mail_to('me@domain.com', 'My email', array('cc' => 'ccaddress@domain.com', 'bcc' => 'bccaddress@domain.com', 'subject' => 'This is an example email', 'body' => 'This is the body of the message.')),'<a href="mailto:me@domain.com?cc=ccaddress%40domain.com&amp;bcc=bccaddress%40domain.com&amp;body=This%20is%20the%20body%20of%20the%20message.&amp;subject=This%20is%20an%20example%20email">My email</a>');
        $this->assertEqual($this->url->mail_to('me@domain.com', 'My email', array('encode' => 'javascript')),'<script type="text/javascript">eval(unescape(\'%64%6f%63%75%6d%65%6e%74%2e%77%72%69%74%65%28%27%3c%61%20%68%72%65%66%3d%22%6d%61%69%6c%74%6f%3a%6d%65%40%64%6f%6d%61%69%6e%2e%63%6f%6d%22%3e%4d%79%20%65%6d%61%69%6c%3c%2f%61%3e%27%29%3b\'))</script>');
        $this->assertEqual($this->url->mail_to('me@domain.com', 'My email', array('encode' => 'hex')),'<a href="mailto:%6d%65%40%64%6f%6d%61%69%6e%2e%63%6f%6d">My email</a>');

    }

    function test_should_encode_utf8_characters_as_entities_when_encoding_mail_to_links()
    {
        $escaped_iacute = '%26%69%61%63%75%74%65%3b';
        $this->assertTrue(strstr($this->url->mail_to('test@example.com', 'mounstro de pulsa aquí', array('encode' => 'javascript')), $escaped_iacute));
    }
    
    function test_link_to_unless()
    {
        $condition = false;
        $this->assertEqual('<a href="http://www.example.com">link</a>',$this->url->link_to_unless($condition,'link','http://www.example.com'));
        
        $condition = true;
        $this->assertEqual('link',$this->url->link_to_unless($condition,'link','http://www.example.com'));
    }
    
    function test_link_to_if()
    {
        $condition = true;
        $this->assertEqual('<a href="http://www.example.com">link</a>',$this->url->link_to_if($condition,'link','http://www.example.com'));
        
        $condition = false;
        $this->assertEqual('link',$this->url->link_to_if($condition,'link','http://www.example.com'));
    }
}

ak_test('UrlHelperTests');

?>