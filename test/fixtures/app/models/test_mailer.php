<?php

class TestMailer extends AkActionMailer
{
    var $delivery_method = 'test';

    function signed_up($recipient)
    {
        $this->set(array(
        'recipients' => $recipient,
        'subject' => "[Signed up] Welcome $recipient",
        'from' => "system@example.com",
        'sent_on' => Ak::getDate(strtotime('2004-12-12')),
        'body' => array('recipient' => $recipient)
        ));
    }

    function cancelled_account($recipient)
    {
        $this->set(array(
        'recipients' => $recipient,
        'subject' => "[Cancelled] Goodbye $recipient",
        'from' => "system@example.com",
        'sent_on' => Ak::getDate(strtotime('2004-12-12')),
        'body' => "Goodbye, Mr. $recipient"
        ));
    }

    function cc_bcc($recipient)
    {
        $this->set(array(
        'recipients' => $recipient,
        'subject' => "testing bcc/cc",
        'from' => "system@example.com",
        'sent_on' => Ak::getDate(strtotime('2004-12-12')),
        'cc' => "nobody@example.com",
        'bcc' => "root@example.com",
        'body' => "Nothing to see here."
        ));
    }

    function iso_charset($recipient)
    {
        $this->set(array(
        'recipients' => $recipient,
        'subject' => Ak::recode('testing isø charsets','ISO-8859-1', 'UTF-8'),
        'from' => "system@example.com",
        'sent_on' => Ak::getDate(strtotime('2004-12-12')),
        'cc' => "nobody@example.com",
        'bcc' => "root@example.com",
        'body' => "Nothing to see here.",
        'charset' => "ISO-8859-1"
        ));
    }

    function unencoded_subject($recipient)
    {
        $this->set(array(
        'recipients' => $recipient,
        'subject' => "testing unencoded subject",
        'from' => "system@example.com",
        'sent_on' => Ak::getDate(strtotime('2004-12-12')),
        'cc' => "nobody@example.com",
        'bcc' => "root@example.com",
        'body' => "Nothing to see here."
        ));
    }


    function extended_headers($recipient)
    {
        $this->set(array(
        'recipients' => $recipient,
        'subject' => "testing extended headers",
        'from' => "Grytøyr <stian1@example.com>",
        'sent_on' => Ak::getDate(strtotime('2004-12-12')),
        'cc' => "Grytøyr <stian2@example.com>",
        'bcc' => "Grytøyr <stian3@example.com>",
        'body' => "Nothing to see here.",
        'charset' => "ISO-8859-1"
        ));
    }

    function utf8_body($recipient)
    {
        $this->set(array(
        'recipients' => $recipient,
        'subject' => "testing utf-8 body",
        'from' => "Foo áëô îü <extended@example.com>",
        'sent_on' => Ak::getDate(strtotime('2004-12-12')),
        'cc' => "Foo áëô îü <extended@example.com>",
        'bcc' => "Foo áëô îü <extended@example.com>",
        'body' => "åœö blah",
        'charset' => "UTF-8"
        ));
    }

    function multipart_with_mime_version($recipient)
    {
        $this->set(array(
        'recipients' => $recipient,
        'subject' => "multipart with mime_version",
        'from' => "test@example.com",
        'sent_on' => Ak::getDate(strtotime('2004-12-12')),
        'mime_version' => "1.1",
        'content_type' => "multipart/alternative",
        'parts' => array(
        array('content_type' => 'text/plain', 'body' => 'blah'),
        array('content_type' => 'text/html', 'body' => '<b>blah</b>'),
        )
        ));

    }

    function multipart_with_utf8_subject($recipient)
    {
        $this->set(array(
        'recipients' => $recipient,
        'subject' => "Foo áëô îü",
        'from' => "test@example.com",
        'charset' => "UTF-8",
        'parts' => array(
        array('content_type' => 'text/plain', 'body' => 'blah'),
        array('content_type' => 'text/html', 'body' => '<b>blah</b>'),
        )
        ));

    }

    function explicitly_multipart_example($recipient, $content_type = null)
    {
        empty($content_type) ? null : $this->setContentType($content_type);
        $this->set(array(
        'recipients' => $recipient,
        'subject' => "multipart example",
        'from' => "test@example.com",
        'body' => "plain text default",
        'parts' => array('content_type' => 'text/html', 'body' => 'blah', 'charset' => 'iso-8859-1'),
        'attachment' => array('content_type' => 'image/jpeg', 'filename'=> 'foo.jpg', 'body' => '123456789'),
        ));

    }

    function implicitly_multipart_example($recipient, $charset = null, $order = null)
    {
        empty($charset) ? null : $this->setCharset($charset);
        empty($order) ? null : $this->setImplicitPartsOrder($order);

        $this->set(array(
        'recipients' => $recipient,
        'subject' => "multipart example",
        'from' => "test@example.com",
        'body' => array('recipient' => $recipient)
        ));
    }

    function implicitly_multipart_with_utf8()
    {
        empty($charset) ? null : $this->setCharset($charset);
        empty($order) ? null : $this->setImplicitPartsOrder($order);

        $this->set(array(
        'recipients' => 'no.one@example.com',
        'subject' => "Foo áëô îü",
        'from' => "some.one@example.com",
        'body' => array('recipient' => "no.one@example.com"),
        'template' => 'implicitly_multipart_example'
        ));
    }

    function html_mail($recipient)
    {
        $this->set(array(
        'recipients' => $recipient,
        'subject' => "html mail",
        'from' => "test@example.com",
        'body' => "<em>Emphasize</em> <strong>this</strong>",
        'content_type' => 'text/html'
        ));
    }


    function html_mail_with_underscores()
    {
        $this->set(array(
        'subject' => "html mail with underscores",
        'body' => '<a href="http://google.com" target="_blank">_Google</a>'
        ));
    }


    function custom_template($recipient)
    {
        $this->set(array(
        'recipients' => $recipient,
        'subject' => "[Signed up] Welcome $recipient",
        'from' => "system@example.com",
        'body' => array('recipient' => $recipient),
        'template' => 'signed_up'
        ));
    }

    function various_newlines($recipient)
    {
        $this->set(array(
        'recipients' => $recipient,
        'subject' => "various newlines",
        'from' => "test@example.com",
        'body' => "line #1\nline #2\rline #3\r\nline #4\r\r" .
        "line #5\n\nline#6\r\n\r\nline #7"
        ));
    }

    function various_newlines_multipart($recipient)
    {
        $this->set(array(
        'recipients' => $recipient,
        'subject' => "various newlines multipart",
        'from' => "test@example.com",
        'content_type' => "multipart/alternative",
        'parts' => array(
        array('content_type' => 'text/plain', 'body' => "line #1\nline #2\rline #3\r\nline #4\r\r"),
        array('content_type' => 'text/html', 'body' => "<p>line #1</p>\n<p>line #2</p>\r<p>line #3</p>\r\n<p>line #4</p>\r\r"))
        ));
    }

    function nested_multipart($recipient)
    {
        $this->set(array(
        'recipients' => $recipient,
        'subject' => "nested multipart",
        'from' => "test@example.com",
        'content_type' => "multipart/mixed",

        'parts' => array(
        array(
        'content_type' => 'multipart/alternative',
        'content_disposition' => 'inline',
        'headers' => array("Foo" => "bar"),
        'parts' => array(
        array('content_type' => 'text/plain', 'body' => "test text\nline #2"),
        array('content_type' => 'text/html', 'body' => "<b>test</b> HTML<br/>\nline #2")
        )
        ),
        ),

        'attachment' => array(
        'content_type' => 'application/octet-stream','filename' => 'test.txt', 'body' => "test abcdefghijklmnopqstuvwxyz"
        )
        ));
    }

    function attachment_with_custom_header($recipient)
    {
        $this->set(array(
        'recipients' => $recipient,
        'subject' => "custom header in attachment",
        'from' => "test@example.com",
        'content_type' => "multipart/related",

        'part' => array('content_type' => 'text/html', 'body' => 'yo'),

        'attachment' => array(
        'content_type' => 'image/jpeg','filename' => 'test.jpeg', 'body' => "i am not a real picture", 'headers' => array('Content-ID' => '<test@test.com>')
        )
        ));
    }

    function unnamed_attachment($recipient)
    {
        $this->set(array(
        'recipients' => $recipient,
        'subject' => "unnamed attachment",
        'from' => "test@example.com",
        'content_type' => "multipart/mixed",

        'part' => array('content_type' => 'text/plain', 'body' => 'hullo'),

        'attachment' => array(
        'content_type' => 'application/octet-stream','body' => "test abcdefghijklmnopqstuvwxyz"
        )
        ));
    }

    function headers_with_nonalpha_chars($recipient)
    {
        $this->set(array(
        'recipients' => $recipient,
        'subject' => "nonalpha chars",
        'from' => "One: Two <test@example.com>",
        'cc' => "Three: Four <test@example.com>",
        'bcc' => "Five: Six <test@example.com>",
        'body' => "testing"));
    }


    function custom_content_type_attributes()
    {
        $this->set(array(
        'recipients' => "no.one@example.com",
        'subject' => "custom content types",
        'from' => "some.one@example.com",
        'content_type' => "text/plain; format=flowed",
        'body' => "testing"
        ));
    }



    function alternative_message_from_templates($recipient, $include_logo = false, $include_external_image = false, $add_attachment = false)
    {
        $this->set(array(
        'recipients' => $recipient,
        'subject' => "Alternative message from template",
        'from' => "some.one@example.com",
        'body' => array('include_logo' => $include_logo, 'include_external_image' => $include_external_image)
        ));
        
        
        /**
         * @todo Adding an attachment overides rendered views
         */
        if($add_attachment){
           
            $this->addAttachment(array(
            
                'content_type' => 'text/php',
                'body' => '<?php ?>',
                'filename' => 'test_mailer.php'
                
                ));
        }
    }
    
    function message_with_helpers($recipient)
    {
        $this->set(array(
        'recipients' => $recipient,
        'subject' => "message_with_helpers",
        'from' => "system@example.com"
        ));
    }
    
    
    function message_from_first_name($recipient)
    {
        $this->set(array(
        'recipients' => $recipient,
        'subject' => "from includes name",
        'from' => array('Some "One' => "some.one@example.com"),
        'body' => "testing"
        ));
    }



    function &receive($raw_email)
    {
        $Mail =& parent::receive($raw_email);
        $this->received_body = $Mail->body;
        return $Mail;
    }

}

?>