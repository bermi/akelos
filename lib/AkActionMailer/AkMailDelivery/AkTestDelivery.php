<?php

class AkTestDelivery extends AkObject
{
    function deliver(&$Mailer, $settings = array())
    {
        $encoded_message = $Mailer->Message->getEncoded();
        $settings['ActionMailer']->deliveries[] = $encoded_message;
        if(AK_DEV_MODE){
            $Logger = Ak::getLogger();
            $original_error_file = $Logger->error_file;
            $Logger->error_file = AK_LOG_DIR.DS.'mail.log';
            $Logger->message($encoded_message);
            $Logger->error_file = $original_error_file;
        }
    }
}


?>