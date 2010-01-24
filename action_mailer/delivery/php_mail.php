<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

class AkPhpMailDelivery
{
    public function deliver(&$Mailer, $settings = array()) {
        $Message = $Mailer->Message;
        $to = $Message->getTo();
        $subject = $Message->getSubject();

        list($header, $body) = $Message->getRawHeadersAndBody();

        $header = preg_replace('/(To|Subject): [^\r]+\r\n/', '', $header);
        return mail($to, $subject, $body, $header);
    }
}

