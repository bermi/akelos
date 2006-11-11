<?php

require_once('_HelpersUnitTester.php');
require_once(AK_LIB_DIR.DS.'AkActionView'.DS.'helpers'.DS.'asset_tag_helper.php');


class TextHelperTests extends HelpersUnitTester 
{
    
    function test_for_TextHelper()
    {
        require_once(AK_LIB_DIR.DS.'AkActionView'.DS.'helpers'.DS.'text_helper.php');

        $text = new TextHelper();
        
        $this->assertEqual($text->truncate('truncates the last ten characters',10,'...'),'truncates the...');

        $this->assertEqual($text->highlight('I am highlighting the phrase','highlighting'),'I am <strong class="highlight">highlighting</strong> the phrase');
        $this->assertEqual($text->highlight('I am highlighting the phrase',array('highlighting','the')),'I am <strong class="highlight">highlighting</strong> <strong class="highlight">the</strong> phrase');

        $this->assertEqual($text->excerpt("hello my world", "my", 3),'...lo my wo...');
        $this->assertEqual($text->excerpt("hello my world", "my", 5,'---'),'---ello my worl---');

        $this->assertEqual($text->pluralize(0, 'Property', 'Properties'),'Properties');
        $this->assertEqual($text->pluralize(1, 'Property'),'Property');
        $this->assertEqual($text->pluralize(2, 'Property'),'Properties');

        $this->assertEqual($text->word_wrap('Wraps a string to a given number of characters', 20),"Wraps a string to a\ngiven number of\ncharacters");

        $this->assertEqual($text->textilize('__Wraps__'),'<p><i>Wraps</i></p>');
        $this->assertEqual($text->textilize('_Wraps_'),'<p><em>Wraps</em></p>');
        $this->assertEqual($text->textilize('p[no]. paragraph'),'<p lang="no">paragraph</p>');
        $this->assertEqual($text->textilize('h3{color:red}. header 3'),'<h3 style="color:red;">header 3</h3>');

        $this->assertEqual($text->textilize_without_paragraph('__Wraps__'),'<i>Wraps</i>');
        $this->assertEqual($text->textilize_without_paragraph('p[no]. paragraph'),'paragraph');

        require_once(AK_LIB_DIR.DS.'AkActionView'.DS.'helpers'.DS.'tag_helper.php');

        $this->assertEqual($text->simple_format("Test\r\n"),"<p>Test\n</p>");
        $this->assertEqual($text->simple_format("Test\n"),"<p>Test\n</p>");
        $this->assertEqual($text->simple_format("Test\r"),"<p>Test\n</p>");
        $this->assertEqual($text->simple_format("Test\n\nTest"),"<p>Test</p>\n<p>Test</p>");
        $this->assertEqual($text->simple_format("Test\n\n"),"<p>Test</p><br /><br />");
        $this->assertEqual($text->simple_format("Test\n\n\n\n\n\n"),"<p>Test</p><br /><br />");

        $this->assertEqual($text->auto_link_email_addresses('sending an email to salavert@example.com and to hilario@example.com'),'sending an email to <a href=\'mailto:salavert@example.com\'>salavert@example.com</a> and to <a href=\'mailto:hilario@example.com\'>hilario@example.com</a>');
        $this->assertEqual($text->auto_link_email_addresses('salavert@@example.com'),'salavert@@example.com');
        $this->assertEqual($text->auto_link_email_addresses('email sent to salavert@example.c'),'email sent to <a href=\'mailto:salavert@example.c\'>salavert@example.c</a>');

        $this->assertEqual($text->auto_link_urls('http://www.thebigmover.com'),'<a href="http://www.thebigmover.com">http://www.thebigmover.com</a>');
        $this->assertEqual($text->auto_link_urls('www.thebigmover.com'),'<a href="http://www.thebigmover.com">www.thebigmover.com</a>');
        //$this->assertEqual($text->auto_link_urls('www.thebigmover.com nested www.thebigmover.com/search'),'<a href="http://www.thebigmover.com">www.thebigmover.com</a> nested <a href="http://www.thebigmover.com/search">www.thebigmover.com/search</a>');//, 'Failed auto_link_urls nested url');
        $this->assertEqual($text->auto_link_urls('Visit http://www.thebigmover.com now'),'Visit <a href="http://www.thebigmover.com">http://www.thebigmover.com</a> now');
        $this->assertEqual($text->auto_link_urls('Visit http://www.thebigmover.com now and later http://www.akelos.com'),'Visit <a href="http://www.thebigmover.com">http://www.thebigmover.com</a> now and later <a href="http://www.akelos.com">http://www.akelos.com</a>');
        
        $this->assertEqual($text->strip_links('email sent to <a href=\'mailto:salavert@example.c\'>salavert@example.c</a>'),'email sent to salavert@example.c');
        $this->assertEqual($text->strip_links('sending an email to <a href="mailto:salavert@example.com">salavert@example.com</a> and to <a href="mailto:hilario@example.com">hilario@example.com</a>'),'sending an email to salavert@example.com and to hilario@example.com');//, 'Failed auto_link_email_addresses test');
        
        $this->assertEqual($text->strip_selected_tags('sending <b>email</b> to <a href="mailto:salavert@example.com">salavert@example.com</a>','a','b'),'sending email to salavert@example.com');//, 'Failed auto_link_email_addresses test');
        $this->assertEqual($text->strip_selected_tags('sending <b>email</b> to <a href="mailto:salavert@example.com">salavert@example.com</a>',array('a','b')),'sending email to salavert@example.com');//, 'Failed auto_link_email_addresses test');
        $this->assertEqual($text->strip_selected_tags('sending <b>email</b> to <a href="mailto:salavert@example.com">salavert@example.com</a>','a'),'sending <b>email</b> to salavert@example.com');//, 'Failed auto_link_email_addresses test');

        $this->assertEqual($text->auto_link('email sent to salavert@example.com from http://www.thebigmover.com','all'),'email sent to <a href=\'mailto:salavert@example.com\'>salavert@example.com</a> from <a href="http://www.thebigmover.com">http://www.thebigmover.com</a>');
        $this->assertEqual($text->auto_link('email sent to salavert@example.com','email_addresses'),'email sent to <a href=\'mailto:salavert@example.com\'>salavert@example.com</a>');
        $this->assertEqual($text->auto_link('email sent from http://www.thebigmover.com','urls'),'email sent from <a href="http://www.thebigmover.com">http://www.thebigmover.com</a>');

        $this->assertEqual($text->strip_tags('<a href="nowhere" onclick="javascript:alert(\'oops\');">link</a>'),'link');        
        
    }
}


Ak::test('TextHelperTests');

?>