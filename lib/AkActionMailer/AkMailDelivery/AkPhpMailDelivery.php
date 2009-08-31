<?php


class AkPhpMailDelivery extends AkObject
{
    public function deliver(&$Mailer, $settings = array())
    {
        $Message =& $Mailer->Message;
        $to = $Message->getTo();
        $subject = $Message->getSubject();
                
        list($header, $body) = $Message->getRawHeadersAndBody();

        $header = preg_replace('/(To|Subject): [^\r]+\r\n/', '', $header);
        return mail($to, $subject, $body, $header);
    }
}


?>