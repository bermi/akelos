<?php


class AkTestDelivery extends AkObject
{
    function deliver(&$Mailer, $settings = array())
    {
        $settings['ActionMailer']->deliveries[] = $Mailer->Message->getEncoded();
    }
}


?>