<?php

class HelperMailer extends AkActionMailer
{
    var $helpers = 'mailer,example';

    function use_helper($recipient)
    {
        $this->set(array(
        'recipients' => $recipient,
        'subject' => 'using helpers',
        'from' => 'tester@example.com'));
    }

    function use_example_helper($recipient)
    {
        $this->set(array(
        'recipients' => $recipient,
        'subject' => 'using helpers',
        'body' => array('text' => 'emphasize me!' ),
        'from' => 'tester@example.com'));
    }

    function use_mail_helper($recipient)
    {
        $this->helpers = 'mail';
        $this->set(array(
        'recipients' => $recipient,
        'subject' => 'using helpers',
        'from' => 'tester@example.com',
        'body' => array('text' =>
        "But soft! What light through yonder window breaks? It is the east, " .
        "and Juliet is the sun. Arise, fair sun, and kill the envious moon, " .
        "which is sick and pale with grief that thou, her maid, art far more " .
        "fair than she. Be not her maid, for she is envious! Her vestal " .
        "livery is but sick and green, and none but fools do wear it. Cast " .
        "it off!"
        )));
    }

    function use_helper_method($recipient)
    {
        $this->set(array(
        'recipients' => $recipient,
        'subject' => 'using helpers',
        'from' => 'tester@example.com',
        'body' => array('text' => "emphasize me!")));
    }


    function name_of_the_mailer_class()
    {
        return $this->getMailerName();
    }
    
}

?>