<?php

class AkTestDelivery
{
    public function deliver(&$Mailer, $settings = array()) {
        $encoded_message = $Mailer->getRawMessage();
        $settings['ActionMailer']->deliveries[] = $encoded_message;
        if(!AK_PRODUCTION_MODE){
            $Logger = Ak::getLogger('mail');
            $Logger->message($encoded_message);
        }
        if(AK_TEST_MODE){
            Ak::setStaticVar('last_mail_delivered', $encoded_message);
        }
    }
}


