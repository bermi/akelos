<?php

class AkTestDelivery extends AkObject
{
    function deliver(&$Mailer, $settings = array())
    {
        $encoded_message = $Mailer->Message->getEncoded();
        $settings['ActionMailer']->deliveries[] = $encoded_message;
        if(AK_DEV_MODE){
            $Logger = Ak::getLogger('mail');
            $Logger->message($encoded_message);
        }
    }
}


?>