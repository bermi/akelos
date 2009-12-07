<?php

class RenderMailer extends AkActionMailer
{
    public function inline_template($recipient) {
        $this->setRecipients($recipient);
        $this->setSubject("using helpers");
        $this->setFrom("tester@example.com");
        $this->setBody($this->render(array('inline' => 'Hello, <?=$who?>', 'body' => array('who' => "World"))));
    }

    public function file_template($recipient) {
        $this->set(array(
        'recipients'    =>  $recipient,
        'subject'       =>  "using helpers",
        'from'          =>  "tester@example.com",
        'body'          =>  $this->render(array('file' => 'signed_up', 'body' => array('recipient' => $recipient)))
        ));
    }


    public function initializeDefaults($method_name) {
        parent::initializeDefaults($method_name);
        $this->setMailerName("test_mailer");
    }

}

