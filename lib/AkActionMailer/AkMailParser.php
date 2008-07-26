<?php

class AkMailParser
{
    var $decode_body = true;
    var $content_type = 'text/plain';
    var $recode_messages = true;
    var $recode_to_charset = AK_ACTION_MAILER_DEFAULT_CHARSET;
    var $raw_message = '';
    var $options = array();

    var $html_charset_on_recoding_failure = false;

    var $headers = array();
    var $body;
    var $parts;


    function AkMailParser($options = array())
    {
        $this->options = $options;
        $default_options = array(
        'content_type' => $this->content_type,
        'decode_body' => $this->decode_body,
        );
        $options = array_merge($default_options, $options);
        foreach ($options as $k=>$v){
            if($k[0] != '_'){
                $this->$k = $v;
            }
        }
    }

    function parse($raw_message = '', $options = array(), $object_type = 'AkMailMessage')
    {
        $parser_class = empty($options['parser_class']) ? 'AkMailParser' : $options['parser_class'];
        $Parser =& new $parser_class($options);
        $Mail = new $object_type();
        $raw_message = empty($raw_message) ? $Parser->raw_message : $raw_message;
        if(!empty($raw_message)){
            list($raw_header, $raw_body) = $Parser->_getRawHeaderAndBody($raw_message);

            $Mail->headers = $Parser->headers = $Parser->getParsedRawHeaders($raw_header);
            $Parser->{$Parser->getContentTypeProcessorMethodName()}($raw_body);
        }
        $Parser->_expandHeadersOnMailObject($Mail);
        $Mail->body = $Parser->body;
        $Mail->parts = $Parser->parts;

        return $Mail;
    }

    function getContentTypeProcessorMethodName()
    {
        $content_type = $this->findHeaderValueOrDefaultTo('content-type', $this->content_type);
        $method_name = 'getParsed'.ucfirst(strtolower(substr($content_type,0,strpos($content_type,"/")))).'Body';
        return method_exists($this, $method_name) ? $method_name : 'getParsedTextBody';
    }

    function getContentDisposition()
    {
        return $this->_findHeader('content-disposition');
    }


    function getParsedTextBody($body)
    {
        $this->body = $this->_getDecodedBody($body);
    }

    function getParsedMultipartBody($body)
    {
        static $recursion_protection;

        $boundary = trim($this->_findHeaderAttributeValue('content-type','boundary'));
        $this->content_type = $this->options['content_type'] = (trim(strtolower($this->_findHeaderValue('content-type'))) == 'multipart/digest' ? 'message/rfc822' : 'text/plain');

        if(empty($boundary)){
            trigger_error(Ak::t('Could not fetch multipart boundary'), E_USER_WARNING);
            return false;
        }

        $this->parts = array();
        $raw_parts = array_diff(array_map('trim',(array)preg_split('/([\-]{0,2}'.preg_quote($boundary).'[\-]{0,2})+/', $body)),array(''));
        foreach ($raw_parts as $raw_part){
            $Parser = new AkMailParser($this->options);
            $recursion_protection[$body] = @$recursion_protection[$body]+1;
            if($recursion_protection[$body] > 50){
                trigger_error(Ak::t('Maximum multipart decoding recursion reached.'), E_USER_WARNING);
                return false;
            }else{
                $Part = $Parser->parse($raw_part, array(), 'AkMailPart');
            }
            $this->parts[] = $Part;
        }
    }

    function getParsedMessageBody($body)
    {
        $Parser = new AkMailParser($this->options);
        $this->body = $Parser->parse($body);
    }

    function _getDecodedBody($body)
    {
        $encoding = trim(strtolower($this->_findHeaderValue('content-transfer-encoding')));
        $charset = trim(strtolower($this->_findHeaderAttributeValue('content-type','charset')));

        if($encoding == 'base64'){
            $body = base64_decode($body);
        }elseif($encoding == 'quoted-printable'){
            $body = preg_replace('/=([a-f0-9]{2})/ie', "chr(hexdec('\\1'))", preg_replace("/=\r?\n/", '', $body));
        }
        return empty($charset) ? $body : ($charset && $this->recode_messages ? Ak::recode($body, $this->recode_to_charset, $charset, $this->html_charset_on_recoding_failure) : $body);
    }

    function _findHeaderValue($name)
    {
        $header = $this->_findHeader($name);
        return !empty($header['value']) ? $header['value'] : false;
    }

    function _findHeaderAttributeValue($name, $attribute)
    {
        $header = $this->_findHeader($name);
        return !empty($header['attributes'][$attribute]) ? $header['attributes'][$attribute] : false;
    }

    function findHeaderValueOrDefaultTo($name, $default)
    {
        $value = $this->_findHeaderValue($name);
        return !empty($value) ? $value : $default;
    }

    function _findHeader($name)
    {
        $results = array();
        foreach ($this->headers as $header) {
            if(isset($header['name']) && strtolower($header['name']) == $name){
                $results[] = $header;
            }
        }
        return empty($results) ? false : (count($results) > 1 ? $results : array_shift($results));
    }

    function getParsedRawHeaders($raw_headers)
    {
        $raw_header_lines = array_diff(array_map('trim',explode("\n",$raw_headers."\n")), array(''));
        $headers = array();
        if(!empty($raw_headers)){
            foreach ($raw_header_lines as $header_line){
                $headers[] = $this->_parseHeaderLine($header_line);
            }
        }

        return $headers;
    }

    function _parseHeaderLine($header_line)
    {
        $header = array();
        if(preg_match("/^([A-Za-z\-]+)\: *(.*)$/",$header_line,$match)){
            $header['name'] = $match[1];
            $header['value'] = $match[2];
            $this->_decodeHeader_($header);
            $this->_headerCanHaveAttributes($header) ? $this->_extractAttributesForHeader_($header) : null;
            return $header;
        }

    }

    function _headerCanHaveAttributes($header)
    {
        return !in_array(strtolower($header['name']), array('subject','to','from','cc','bcc'));
    }

    function _extractAttributesForHeader_(&$header)
    {
        $attributes = array();
        if(preg_match_all("/([A-Z\-_ ]+)".
        "(\*[0-9 ]*)?". // RFC 2231
        "=([^;]*);?/i", $header['value'], $matches)){
            $header['value'] = str_replace($matches[0],'', $header['value']);
            foreach ($matches[0] as $k=>$match){
                $attribute_name = trim($matches[1][$k]);
                $value = trim($matches[3][$k],'; "\'');
                if(!empty($matches[2][$k])){ // RFC 2231
                    $value = (empty($attributes[$attribute_name]) ? '' : $attributes[$attribute_name])
                    .$this->_decodeHeaderAttribute($value, $matches[2][$k]);
                }
                $attributes[$attribute_name] = $value;
            }
        }

        $header['value'] = trim($header['value'],'; ');

        if(strstr($header['value'],';') && strtolower($header['name']) != 'date' &&
        preg_match("/([; ])*(?:(Mon|Tue|Wed|Thu|Fri|Sat|Sun),? *)?(\d\d?)".
        " +(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec) +(\d\d\d\d) ".
        "+(\d{2}:\d{2}(?::\d\d)) +([\( ]*(UT|GMT|EST|EDT|CST|CDT|MST|MDT|".
        "PST|PDT|[A-Z]|(?:\+|\-)\d{4})[\) ]*)+/",$header['value'],$match)){
            $header['value'] = str_replace($match[0],'', $header['value']);
            $attributes['Date'] = trim(str_replace('  ',' ',$match[0]),"; ");
        }

        $header['attributes'] = empty($attributes) ? false : $attributes;
    }

    function _decodeHeader_(&$header)
    {
        if(!empty($header['value'])){
            $encoded_header =  preg_replace('/\?\=([^=^\n^\r]+)?\=\?/', "?=$1\n=?",$header['value']);
            $header_value = $header['value'];
            if(preg_match_all('/(\=\?([^\?]+)\?([BQ]{1})\?([^\?]+)\?\=?)+/i', $encoded_header, $match)){
                foreach (array_keys($match[0]) as $k){
                    $charset = strtoupper($match[2][$k]);
                    $decode_function = strtolower($match[3][$k]) == 'q' ? 'quoted_printable_decode' : 'base64_decode';
                    $decoded_part = trim(
                    Ak::recode($decode_function(str_replace('_',' ',$match[4][$k])), $this->recode_to_charset, $charset, $this->html_charset_on_recoding_failure)

                    );

                    $header_value = str_replace(trim($match[0][$k]), $decoded_part, $header_value);
                }
            }
            $header_value = trim(preg_replace("/(%0A|%0D|\n+|\r+)/i",'',$header_value));
            if($header_value != $header['value']){
                $header['encoded'] = $header['value'];
                $header['value'] = $header_value;
                isset($charset) && $header['charset'] = $charset;
            }
        }
    }

    /**
     * RFC 2231 Implementation
     */
    function _decodeHeaderAttribute($header_attribute, $charset = '')
    {
        if(preg_match("/^([A-Z0-9\-]+)(\'[A-Z\-]{2,5}\')?/i",$header_attribute,$match)){
            $charset = $match[1];
            $header_attribute = urldecode(str_replace(array('_','='),array('%20','%'), substr($header_attribute,strlen($match[0]))));
        }
        return Ak::recode($header_attribute, 'UTF-8', $charset);
    }

    function _getRawHeaderAndBody($raw_part)
    {
        return
        array_map('trim',
        preg_split("/\n\n/",
        preg_replace("/(\n[\t ]+)/",'', // Join multiline headers
        str_replace(array("\r\n","\n\r","\r"),"\n", $raw_part."\n") // Lets keep it simple and use only \n for decoding
        )."\n\n",2));
    }

    function _expandHeadersOnMailObject(&$Mail)
    {
        if(!empty($Mail->headers)){
            foreach ($Mail->headers as $details){
                if (empty($details['name'])) {
                    continue;
                }
                $caption = AkInflector::underscore($details['name']);
                if(!in_array($caption, array('headers','body','parts'))){
                    if(!empty($details['value'])){
                        if(empty($Mail->$caption)){
                            $Mail->$caption = $details['value'];
                        }elseif (!empty($Mail->$caption) && is_array($Mail->$caption)){
                            $Mail->{$caption}[] = $details['value'];
                        }else{
                            $Mail->$caption = array($Mail->$caption, $details['value']);
                        }
                        $Mail->header[$caption] =& $Mail->$caption;
                    }
                    if(!empty($details['attributes'])){
                        $Mail->{$caption.'_attributes'} = $details['attributes'];
                    }
                }
            }
        }
    }

    
    function importStructure(&$MailOrPart, $structure = array())
    {
        if(isset($structure['header'])){
            $structure['headers'] = $structure['header'];
            unset($structure['header']);
        }
        foreach ($structure as $attribute=>$value){
            if($attribute[0] != '_'){
                $attribute_setter = 'set'.AkInflector::camelize($attribute);
                if(method_exists($MailOrPart, $attribute_setter)){
                    $MailOrPart->$attribute_setter($value);
                }else{
                    $MailOrPart->{AkInflector::underscore($attribute)} = $value;
                }
            }
        }
        return ;
    }
    
    
    function extractImagesIntoInlineParts(&$Mail, $options = array())
    {
        $html =& $Mail->body;
        require_once(AK_LIB_DIR.DS.'AkActionView'.DS.'helpers'.DS.'text_helper.php');
        $images = TextHelper::get_image_urls_from_html($html);
        $html_images = array();
        if(!empty($images)){
            require_once(AK_LIB_DIR.DS.'AkImage.php');
            require_once(AK_LIB_DIR.DS.'AkActionView'.DS.'helpers'.DS.'asset_tag_helper.php');

            $images = array_diff(array_unique($images), array(''));

            foreach ($images as $image){
                $original_image_name = $image;
                $image = $this->_getImagePath($image);
                if(!empty($image)){
                    $extenssion = substr($image, strrpos('.'.$image,'.'));
                    $image_name = Ak::uuid().'.'.$extenssion;
                    $html_images[$original_image_name] = 'cid:'.$image_name;

                    $Mail->setAttachment('image/'.$extenssion, array(
                    'body' => Ak::file_get_contents($image),
                    'filename' => $image_name,
                    'content_disposition' => 'inline',
                    'content_id' => '<'.$image_name.'>',
                    ));
                }
            }
            $modified_html = str_replace(array_keys($html_images),array_values($html_images), $html);
            if($modified_html != $html){
                $html = $modified_html;
                $Mail->_moveBodyToInlinePart();
            }
        }
    }
    
    function _getImagePath($path)
    {
        if(preg_match('/^http(s)?:\/\//', $path)){
            $path_info = pathinfo($path);
            $base_file_name = Ak::sanitize_include($path_info['basename'], 'paranaoid');
            if(empty($path_info['extension'])){ // no extension, we don't do magic stuff
                $path = '';
            }else{
                $local_path = AK_TMP_DIR.DS.'mailer'.DS.'remote_images'.DS.md5($base_file_name['dirname']).DS.$base_file_name.'.'.$path_info['extension'];
                if(!file_exists($local_path) || (time() > @filemtime($local_path)+7200)){
                    if(!Ak::file_put_contents($local_path, Ak::url_get_contents($path))){
                        return '';
                    }
                }
                return $local_path;
            }
        }

        $path = AK_PUBLIC_DIR.Ak::sanitize_include($path);

        if(!file_exists($path)){
            $path = '';
        }
        return $path;
    }

}

?>