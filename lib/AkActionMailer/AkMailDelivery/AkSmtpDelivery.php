<?php


class AkSmtpDelivery extends AkObject
{
    function deliver(&$Mailer, $settings = array())
    {
        $Message =& $Mailer->Message;
        
        $SmtpClient =& Mail::factory('smtp', $settings);

        include_once 'Net/SMTP.php';

        if (!($smtp = &new Net_SMTP($SmtpClient->host, $SmtpClient->port, $SmtpClient->localhost))) {
            return PEAR::raiseError('unable to instantiate Net_SMTP object');
        }

        if ($SmtpClient->debug) {
            $smtp->setDebug(true);
        }

        if (PEAR::isError($smtp->connect($SmtpClient->timeout))) {
            trigger_error('unable to connect to smtp server '.$SmtpClient->host.':'.$SmtpClient->port, E_USER_NOTICE);
            return false;
        }

        if ($SmtpClient->auth) {
            $method = is_string($SmtpClient->auth) ? $SmtpClient->auth : '';

            if (PEAR::isError($smtp->auth($SmtpClient->username, $SmtpClient->password, $method))) {
                trigger_error('unable to authenticate to smtp server', E_USER_ERROR);
            }
        }

        if (PEAR::isError($smtp->mailFrom($Message->getFrom()))) {
            trigger_error('unable to set sender to [' . $from . ']', E_USER_ERROR);
        }

        $recipients = $SmtpClient->parseRecipients($Message->getRecipients());
        if (PEAR::isError($recipients)) {
            return $recipients;
        }

        foreach ($recipients as $recipient) {
            if (PEAR::isError($res = $smtp->rcptTo($recipient))) {
                return PEAR::raiseError('unable to add recipient [' .
                $recipient . ']: ' . $res->getMessage());
            }
        }

        if (PEAR::isError($smtp->data($Mailer->getRawMessage()))) {
            return PEAR::raiseError('unable to send data');
        }

        $smtp->disconnect();
    }
}


?>