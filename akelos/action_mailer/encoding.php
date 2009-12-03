<?php

include_once(AK_CONTRIB_DIR.DS.'pear'.DS.'Mail'.DS.'mimeDecode.php');

class AkMailEncoding extends Mail_mimeDecode
{
    public function decode()
    {
        $this->_include_bodies = $this->_decode_bodies = $this->_decode_headers = true;

        $structure = $this->_decode($this->_header, $this->_body);
        if ($structure === false) {
            $structure = $this->raiseError($this->_error);
        }

        return $structure;
    }

    protected function _decodeHeader($encoded_header)
    {
        // PEAR's header decoding function is buggy and is not enough tested, so we
        // override it using the Akelos charset transcoding engine to get the result
        // as UTF-8
        $encoded_header =  str_replace(array('_',"\r","\n =?"),array(' ',"\n","\n=?"),
        preg_replace('/\?\=([^=^\n^\r]+)?\=\?/', "?=$1\n=?",$encoded_header));

        $decoded = $encoded_header;
        if(preg_match_all('/(\=\?([^\?]+)\?([BQ]{1})\?([^\?]+)\?\=?)+/i',$encoded_header,$match)){
            foreach ($match[0] as $k=>$encoded){
                $charset = strtoupper($match[2][$k]);
                $decode_function = strtolower($match[3][$k]) == 'q' ? 'quoted_printable_decode' : 'base64_decode';
                $decoded_part = trim(Ak::recode($decode_function($match[4][$k]), AK_ACTION_MAILER_DEFAULT_CHARSET, $charset, true));

                $decoded = str_replace(trim($match[0][$k]), $decoded_part, $decoded);
            }
        }
        return trim(preg_replace("/(%0A|%0D|\n+|\r+)/i",'',$decoded));
    }

    protected function _encodeAddress($address_string, $header_name = '', $names = true)
    {
        $headers = '';
        $addresses = Ak::toArray($address_string);
        $addresses = array_map('trim', $addresses);
        foreach ($addresses as $address){
            $address_description = '';
            if(preg_match('#(.*?)<(.*?)>#', $address, $matches)){
                $address_description = trim($matches[1]);
                $address = $matches[2];
            }

            if(empty($address) || !$this->_isAscii($address) || !$this->_isValidAddress($address)){
                continue;
            }
            if($names && !empty($address_description)){
                $address = "<$address>";
                if(!$this->_isAscii($address_description)){
                    $address_description = '=?'.AK_ACTION_MAILER_DEFAULT_CHARSET.'?Q?'.$this->quoted_printable_encode($address_description, 0).'?=';
                }
            }
            $headers .= (!empty($headers)?','.AK_MAIL_HEADER_EOL.' ':'').$address_description.$address;
        }

        return empty($headers) ? false : (!empty($header_name) ? $header_name.': '.$headers.AK_MAIL_HEADER_EOL : $headers);
    }

    protected function _isValidAddress($email)
    {
        return preg_match(AK_EMAIL_REGULAR_EXPRESSION, $email);
    }
}

