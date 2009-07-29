<?php

include_once(AK_CONTRIB_DIR.DS.'pear'.DS.'Mail.php');

class AkMailBase extends Mail
{

    var $raw_message = '';
    var $charset = AK_ACTION_MAILER_DEFAULT_CHARSET;
    var $content_type;
    var $body;

    var $parts = array();
    var $attachments = array();

    var $_attach_html_images = true;

    function AkMailBase()
    {
        $args = func_get_args();
        if(isset($args[0])){
            if(count($args) == 1 && is_string($args[0])){
                $this->raw_message = $args[0];
            }elseif(is_array($args[0])){
                AkMailParser::importStructure($this, $args[0]);
            }
        }
    }

    function &parse($raw_email = '')
    {
        if(empty($raw_email)){
            trigger_error(Ak::t('Cannot parse an empty message'), E_USER_ERROR);
        }
        $Mail =& new AkMailMessage((array)AkMailParser::parse($raw_email));
        return $Mail;
    }

    function &load($email_file)
    {
        if(!file_exists($email_file)){
            trigger_error(Ak::t('Cannot find mail file at %path',array('%path'=>$email_file)), E_USER_ERROR);
        }
        $Mail =& new AkMail((array)AkMailParser::parse(file_get_contents($email_file)));
        return $Mail;
    }


    function setBody($body)
    {
        if(is_string($body)){
            $content_type = @$this->content_type;
            $this->body = stristr($content_type,'text/') ? str_replace(array("\r\n","\r"),"\n", $body) : $body;

            if($content_type == 'text/html'){
                $Parser = new AkMailParser();
                $Parser->applyCssStylesToTags($this);
                $Parser->addBlankTargetToLinks($this);
                if($this->_attach_html_images) {
                    $Parser->extractImagesIntoInlineParts($this);
                }
            }
        }else{
            $this->body = $body;
        }
    }


    function getBody()
    {
        if(!is_array($this->body)){
            $encoding = $this->getContentTransferEncoding();
            $charset = $this->getCharset();
            switch ($encoding) {
                case 'quoted-printable':
                    return trim(AkActionMailerQuoting::chunkQuoted(AkActionMailerQuoting::quotedPrintableEncode($this->body, $charset)));
                case 'base64':
                    return $this->_base64Body($this->body);
                default:
                    return trim($this->body);
            }
        }
    }

    function _base64Body($content)
    {
        $Cache =& Ak::cache();
        $cache_id = md5($content);
        $Cache->init(3600);
        if (!$encoded_content = $Cache->get($cache_id)) {
            $encoded_content = trim(chunk_split(base64_encode($content)));
            unset($content);
            $Cache->save($encoded_content);
        }
        return $encoded_content;
    }

    /**
    * Specify the CC addresses for the message.
    */
    function setCc($cc)
    {
        $this->cc = $cc;
    }

    /**
    * Specify the BCC addresses for the message.
    */
    function setBcc($bcc)
    {
        $this->bcc = $bcc;
    }

    /**
     * Specify the charset to use for the message.
     */
    function setCharset($charset, $append_to_content_type_as_attribute = true)
    {
        $this->charset = $charset;
        if($append_to_content_type_as_attribute){
            $this->setContenttypeAttributes(array('charset'=>$charset));
        }
    }

    function getCharset($default_to = null)
    {
        return empty($this->charset) ? AK_ACTION_MAILER_DEFAULT_CHARSET : $this->charset;
    }

    /**
     * Specify the content type for the message. This defaults to <tt>text/plain</tt>
     * in most cases, but can be automatically set in some situations.
     */
    function setContentType($content_type)
    {
        list($this->content_type, $ctype_attrs) = $this->_getContentTypeAndAttributes($content_type);
        $this->setContenttypeAttributes($ctype_attrs);
    }


    function getContentType()
    {
        return empty($this->content_type) ? ($this->isMultipart()?'multipart/alternative':null) : $this->content_type.$this->getContenttypeAttributes();
    }

    function hasContentType()
    {
        return !empty($this->content_type);
    }

    function setContenttypeAttributes($attributes = array())
    {
        foreach ($attributes as $key=>$value){
            if(strtolower($key) == 'charset'){
                $this->setCharset($value, false);
            }
            $this->content_type_attributes[$key] = $value;
        }
    }

    function getContentTypeAttributes()
    {
        return $this->_getAttributesForHeader('content_type');
    }

    function bodyToString($Mail = null, $only_first_text_part = false)
    {
        $Mail = empty($Mail) ? $this : $Mail;
        $result = '';
        foreach ((array)$Mail as $field => $value){
            if(!empty($value) && is_string($value)){
                if($Mail->isMainMessage() && $field=='body'){
                    $result .= $value."\n";
                }elseif(empty($Mail->data) && $field=='body'){
                    $result .= $value."\n";
                }elseif(!empty($Mail->data) && $field=='original_filename'){
                    $result .= $value;
                }
            }
            if($only_first_text_part && !empty($result)){
                return $result;
            }
            if($field == 'parts' && !empty($value) && is_array($value)){
                foreach ($value as $part){
                    if(!empty($part->data) && !empty($part->original_filename)){
                        $result .= "Attachment: ";
                        $result .= $Mail->bodyToString($part)."\n";
                    }else{
                        $result .= $Mail->bodyToString($part)."\n";
                    }
                    if($only_first_text_part && !empty($result)){
                        return $result;
                    }
                }
            }
        }

        return $result;
    }

    function getTextPlainPart($Mail = null)
    {
        $Mail = empty($Mail) ? $this : $Mail;
        return $Mail->bodyToString($Mail, true);
    }

    function isMainMessage()
    {
        return strtolower(get_class($this)) == 'akmailmessage';
    }

    function isPart()
    {
        return strtolower(get_class($this)) == 'akmailpart';
    }

    function _getAttributesForHeader($header_index, $force_reload = false)
    {
        if(empty($this->_header_attributes_set_for[$header_index]) || $force_reload){
            $header_index = strtolower(AkInflector::underscore($header_index)).'_attributes';
            if(!empty($this->$header_index)){
                $attributes = '';
                if(!empty($this->$header_index)){
                    foreach ((array)$this->$header_index as $key=>$value){
                        $attributes .= ";$key=$value";
                    }
                }
                $this->_header_attributes_set_for[$header_index] = $attributes;
            }
        }
        if (!empty($this->_header_attributes_set_for[$header_index])){
            return $this->_header_attributes_set_for[$header_index];
        }
    }

    /**
     * Specify the content disposition for the message.
     */
    function setContentDisposition($content_disposition)
    {
        $this->content_disposition = $content_disposition;
    }

    /**
     * Specify the content transfer encoding for the message.
     */
    function setContentTransferEncoding($content_transfer_encoding)
    {
        $this->content_transfer_encoding = $content_transfer_encoding;
    }

    /**
     * Alias for  setContentTransferEncoding
     */
    function setTransferEncoding($content_transfer_encoding)
    {
        $this->setContentTransferEncoding($content_transfer_encoding);
    }

    function getContentTransferEncoding()
    {
        if(empty($this->content_transfer_encoding)){
            return null;
        }
        return $this->content_transfer_encoding;
    }

    function getTransferEncoding()
    {
        return $this->getTransferEncoding();
    }

    function _getContentTypeAndAttributes($content_type = null)
    {
        if(empty($content_type)){
            return array($this->getDefault('content_type'), array());
        }
        $attributes = array();
        if(strstr($content_type,';')){
            list($content_type, $attrs) = split(";\\s*",$content_type);
            if(!empty($attrs)){
                foreach ((array)$attrs as $s){
                    if(strstr($s,'=')){
                        list($k,$v) = array_map('trim',split("=", $s, 2));
                        if(!empty($v)){
                            $attributes[$k] = $v;
                        }
                    }
                }
            }
        }

        $attributes = array_diff(array_merge(array('charset'=> (empty($this->_charset)?$this->getDefault('charset'):$this->_charset)),$attributes), array(''));
        return array(trim($content_type), $attributes);
    }


    function getDefault($field)
    {
        $field = AkInflector::underscore($field);
        $defaults = array(
        'charset' => $this->getCharset(),
        'content_type' => 'text/plain',
        );
        return isset($defaults[$field]) ? $defaults[$field] : null;
    }


    function _addHeaderAttributes()
    {
        foreach($this->getHeaders() as $k=>$v){
            $this->headers[$k] .= $this->_getAttributesForHeader($k);
        }
    }

    function getRawHeaders($options = array())
    {
        if(empty($this->raw_headers)){

            $this->headers = $this->getHeaders(true);

            if($this->isPart()){
                $this->prepareHeadersForRendering(array(
                'skip' => (array)@$options['skip'],
                'only' => (array)@$options['only']
                ));
            }
            unset($this->headers['Charset']);
            $headers = $this->prepareHeaders($this->headers);
            if(!is_array($headers)){
                trigger_error($headers->message, E_USER_NOTICE);
                return false;
            }else{
                $this->raw_headers = array_pop($headers);
            }
        }
        return $this->raw_headers;
    }

    function getHeaders($force_reload = false)
    {
        if(empty($this->headers) || $force_reload){
            $this->loadHeaders();
            $this->_addHeaderAttributes();

        }
        return $this->headers;
    }

    function getHeader($header_name)
    {
        $headers = $this->getHeaders();
        return isset($headers[$header_name]) ? $headers[$header_name] : null;
    }

    function loadHeaders()
    {
        if(empty($this->date) && $this->isMainMessage()){
            $this->setDate();
        }
        $new_headers = array();
        $this->_moveMailInstanceAttributesToHeaders();
        foreach (array_map(array('AkActionMailerQuoting','chunkQuoted'), $this->headers) as $header=>$value){
            if(!is_numeric($header)){
                $new_headers[$this->_castHeaderKey($header)] = $value;
            }
        }
        $this->headers = $new_headers;
        $this->_sanitizeHeaders($this->headers);
    }

    function _moveMailInstanceAttributesToHeaders()
    {
        foreach ((array)$this as $k=>$v){
            if($k[0] != '_' && $this->_belongsToHeaders($k)){
                $attribute_getter = 'get'.ucfirst($k);
                $attribute_name = AkInflector::underscore($k);
                $header_value = method_exists($this,$attribute_getter) ? $this->$attribute_getter() : $v;
                is_array($header_value) ? null : $this->setHeader($attribute_name, $header_value);
            }
        }
    }

    function _belongsToHeaders($attribute)
    {
        return !in_array(strtolower($attribute),array('body','recipients','part','parts','raw_message','sep','implicit_parts_order','header','headers'));
    }

    function _castHeaderKey($key)
    {
        return str_replace(' ','-',ucwords(str_replace('_',' ',AkInflector::underscore($key))));
    }

    /**
     * Specify additional headers to be added to the message.
     */
    function setHeaders($headers, $options = array())
    {
        foreach ((array)$headers as $name=>$value){
            $this->setHeader($name, $value, $options);
        }
    }


    function setHeader($name, $value = null, $options = array())
    {
        if(is_array($value)){
            $this->setHeaders($value, $options);
        }elseif($this->headerIsAllowed($name)){
            $this->headers[$name] = $value;
        }
    }



    /**
     * Generic setter
     *
     * Calling $this->set(array('body'=>'Hello World', 'subject' => 'First subject'));
     * is the same as calling $this->setBody('Hello World'); and $this->setSubject('First Subject');
     *
     * This simplifies creating mail objects from datasources.
     *
     * If the method does not exists the parameter will be added to the header.
     */
    function set($attributes = array())
    {
        foreach ((array)$attributes as $key=>$value){
            if($key[0] != '_' && $this->headerIsAllowed($key)){
                $attribute_setter = 'set'.AkInflector::camelize($key);
                if(method_exists($this, $attribute_setter)){
                    $this->$attribute_setter($value);
                }else{
                    $this->setHeader($key, $value);
                }
            }
        }
    }


    function getSortedParts($parts, $order = array())
    {
        $this->_parts_order = array_map('strtolower', empty($order) ? $this->implicit_parts_order : $order);
        usort($parts, array($this,'_contentTypeComparison'));
        return array_reverse(&$parts);
    }

    function sortParts()
    {
        if(!empty($this->parts)){
            $this->parts = $this->getSortedParts($this->parts);
        }
    }

    function _contentTypeComparison($a, $b)
    {
        if(!isset($a->content_type) || !isset($b->content_type)){
            if (!isset($a->content_type) && !isset($b->content_type)) {
                return 0;
            } else if (!isset($a->content_type)) {
                return -1;
            } else {
                return 1;
            }
        }

        $a_ct = strtolower($a->content_type);
        $b_ct = strtolower($b->content_type);
        $a_in = in_array($a_ct, $this->_parts_order);
        $b_in = in_array($b_ct, $this->_parts_order);
        if($a_in && $b_in){
            $a_pos = array_search($a_ct, $this->_parts_order);
            $b_pos = array_search($b_ct, $this->_parts_order);
            return (($a_pos == $b_pos) ? 0 : (($a_pos < $b_pos) ? -1 : 1));
        }
        return $a_in ? -1 : ($b_in ? 1 : (($a_ct == $b_ct) ? 0 : (($a_ct < $b_ct) ? -1 : 1)));
    }




    function setParts($parts, $position = 'append', $propagate_multipart_parts = false)
    {
        foreach ((array)$parts as $k=>$part){
            if(is_numeric($k)){
                $this->setPart((array)$part, $position, $propagate_multipart_parts);
            }else{
                $this->setPart($parts, $position, $propagate_multipart_parts);
                break;
            }
        }
    }


    /**
     * Add a part to a multipart message, with an array of options like
     * (content-type, charset, body, headers, etc.).
     *
     *   function my_mail_message()
     *   {
     *     $this->setPart(array(
     *       'content-type' => 'text/plain',
     *       'body' => "hello, world",
     *       'transfer_encoding' => "base64"
     *     ));
     *   }
     */
    function setPart($options = array(), $position = 'append', $propagate_multipart_parts = false)
    {
        $default_options = array('content_disposition' => 'inline', 'content_transfer_encoding' => 'quoted-printable');
        $options = array_merge($default_options, $options);
        $Part =& new AkMailPart($options);
        $position == 'append' ? array_push($this->parts, $Part) : array_unshift($this->parts, $Part);
        empty($propagate_multipart_parts) ? $this->_propagateMultipartParts() : null;
    }

    function _propagateMultipartParts()
    {
        if(!empty($this->parts)){
            foreach (array_keys($this->parts) as $k){
                $Part =& $this->parts[$k];
                if(empty($Part->_propagated)){
                    $Part->_propagated = true;
                    if(!empty($Part->content_disposition)){
                        // Inline bodies
                        if(isset($Part->content_type) && stristr($Part->content_type,'text/') && $Part->content_disposition == 'inline'){
                            if((!empty($this->body) && is_string($this->body))
                            ||  (!empty($this->body) && is_array($this->body) && ($this->isMultipart() || $this->content_type == 'text/plain'))
                            ){
                                $this->_moveBodyToInlinePart();
                            }
                            $type = strstr($Part->content_type, '/') ? substr($Part->content_type,strpos($Part->content_type,"/")+1) : $Part->content_type;
                            $Part->_on_body_as = $type;
                            $this->body[$type] = $Part->body;

                        }

                        // Attachments
                        elseif ($Part->content_disposition == 'attachment' || ($Part->content_disposition == 'inline' && !preg_match('/^(text|multipart)\//i',$Part->content_type)) || !empty($Part->content_location)){
                            $this->_addAttachment($Part);
                        }
                    }
                }
            }
        }
    }

    function _moveBodyToInlinePart()
    {
        $options = array(
        'content_type' => @$this->content_type,
        'body' => @$this->body,
        'charset' => @$this->charset,
        'content_disposition' => 'inline'
        );
        foreach (array_keys($options) as $k){
            unset($this->$k);
        }

        $this->setAsMultipart();
        $this->setPart($options, 'preppend');
    }

    function setAsMultipart()
    {
        $this->_multipart_message = true;
    }

    function isMultipart()
    {
        return !empty($this->_multipart_message);
    }
    function isAttachment()
    {
        return $this->content_disposition == 'attachment';
    }

    function _addAttachment(&$Part)
    {
        $Part->original_filename = !empty($Part->content_type_attributes['name']) ? $Part->content_type_attributes['name'] :
        (!empty($Part->content_disposition_attributes['filename']) ? $Part->content_disposition_attributes['filename'] :
        (empty($Part->filename) ? @$Part->content_location : $Part->filename));

        $Part->original_filename = preg_replace('/[^A-Z^a-z^0-9^\-^_^\.]*/','',$Part->original_filename);

        if(!empty($Part->body)){
            $Part->data =& $Part->body;
        }
        if(empty($Part->content_disposition_attributes['filename'])){
            $Part->content_disposition_attributes['filename'] = $Part->original_filename;
        }
        if(empty($Part->content_type_attributes['name'])){
            $Part->content_type_attributes['name'] = $Part->original_filename;
        }
        unset($Part->content_type_attributes['charset']);
        $this->attachments[] =& $Part;
    }

    function hasAttachments()
    {
        return !empty($this->attachments);
    }

    function hasParts()
    {
        return !empty($this->parts);
    }

    function hasNonAttachmentParts()
    {
        return (count($this->parts) - count($this->attachments)) > 0;
    }

    /**
     * Add an attachment to a multipart message. This is simply a part with the
     * content-disposition set to "attachment".
     *
     *     $this->setAttachment("image/jpg", array(
     *       'body' => Ak::file_get_contents('hello.jpg'),
     *       'filename' => "hello.jpg"
     *     ));
     */
    function setAttachment()
    {
        $args = func_get_args();
        $options = array();
        if(count($args) == 2){
            $options['content_type'] = array_shift($args);
        }

        $arg_options = @array_shift($args);
        $options = array_merge($options, is_string($arg_options) ? array('body'=>$arg_options) : (array)$arg_options);
        $options = array_merge(array('content_disposition' => 'attachment', 'content_transfer_encoding' => 'base64'), $options);

        $this->setPart($options);
    }

    function setAttachments($attachments = array())
    {
        foreach ($attachments as $attachment){
            $this->setAttachment($attachment);
        }
    }


    function setMessageId($id)
    {
        $this->messageId = $id;
    }


    /**
    * Specify the order in which parts should be sorted, based on content-type.
    * This defaults to the value for the +default_implicit_parts_order+.
    */
    function setImplicitPartsOrder($implicit_parts_order)
    {
        $this->implicit_parts_order = $implicit_parts_order;
    }



    function getEncoded()
    {
        $header = $this->getRawHeaders();
        return $header ? $header.AK_ACTION_MAILER_EOL.AK_ACTION_MAILER_EOL.$this->getBody() : false;
    }

    function headerIsAllowed($header_name)
    {
        return preg_match('/default.?|template.?|.?deliver.?|server_settings|base_url|mailerName/', $header_name) != true;
    }

}

?>