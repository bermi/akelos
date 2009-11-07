<?php

class AkTestDelivery extends AkObject
{
    public function deliver(&$Mailer, $settings = array())
    {
        $encoded_message = $Mailer->getRawMessage();
        $settings['ActionMailer']->deliveries[] = $encoded_message;
        if(AK_DEV_MODE){
            $Logger = Ak::getLogger('mail');
            $Logger->message($encoded_message);
        }
    }
}


?>