<?php


class AkMailComposer extends AkObject
{
    public $Message;
    public $ActionMailer;
    public $parts = array();
    public $raw_message = '';
    public $composed_message = array();
    public $_boundary_stack = array();
    public $boundary = '';


    public function init(&$ActionMailer)
    {
        $this->ActionMailer =& $ActionMailer;
        $this->Message =& $ActionMailer->Message;
    }

    public function build()
    {
        $args = func_get_args();
        $method_name = array_shift($args);
        $this->ActionMailer->initializeDefaults($method_name);
        $this->_callActionMailerMethod($method_name, $args);
        $this->_prepareInlineBodyParts();
    }


    public function getRawMessage($MessageOrPart = null, $force_overload = false)
    {
        $Message = empty($MessageOrPart) ? $this->Message : $MessageOrPart;
        if($force_overload || empty($Message->raw_message)){
            list($raw_headers, $raw_body) = $this->getRawHeadersAndBody($Message);
            $Message->raw_message = $raw_headers.
            AK_ACTION_MAILER_EOL.AK_ACTION_MAILER_EOL.
            $raw_body;
        }
        return $Message->raw_message;
    }


    public function getRawHeadersAndBody($MessageOrPart = null)
    {
        $Message = empty($MessageOrPart) ? $this->Message : $MessageOrPart;
        $raw_body_or_parts = $this->getRawBodyOrRawParts($Message);

        if(is_array($raw_body_or_parts)){
            $raw_body = '';
            $this->openMultipartBlock();
            if(!$Message->hasContentType()){
                $Message->setContentType('multipart/related');
            }
            $Message->content_type_attributes['boundary'] = $this->getBoundary();
            $Message->_skip_adding_date_to_headers = !$Message->isMainMessage();

            $raw_headers = $Message->getRawHeaders();
            foreach ($raw_body_or_parts as $raw_part_headers=>$raw_part_body){
                $raw_body .=
                AK_ACTION_MAILER_EOL.
                AK_ACTION_MAILER_EOL.
                '--'.
                $this->getBoundary().
                AK_ACTION_MAILER_EOL.
                $raw_part_headers.
                AK_ACTION_MAILER_EOL.
                AK_ACTION_MAILER_EOL.
                $raw_part_body;
            }
            $raw_body .= AK_ACTION_MAILER_EOL.'--'.$this->getBoundary().'--'.AK_ACTION_MAILER_EOL;

            $this->closeMultipartBlock();
        }else{
            $raw_headers = $Message->getRawHeaders();
            $raw_body = $raw_body_or_parts;
        }

        return array($raw_headers, $raw_body);
    }


    public function getRawBodyOrRawParts($MessageOrPart = null)
    {
        $Message = empty($MessageOrPart) ? $this->Message : $MessageOrPart;
        $body = $Message->getBody();
        if(empty($body) && ($Message->hasParts() || $Message->hasAttachments())){
            $result = array();
            foreach (array_keys($Message->parts) as $k){
                $Part = $Message->parts[$k];
                list($raw_headers, $raw_body) = $this->getRawHeadersAndBody($Part);
                $result[$raw_headers] = $raw_body;
            }
            return $result;
        }
        return $body;
    }

    public function openMultipartBlock()
    {
        $this->setBoundary($this->getBoundaryString());
    }

    public function closeMultipartBlock()
    {
        $this->latest_closed_boundary = array_pop($this->_boundary_stack);
    }


    public function setBoundary($boundary)
    {
        $this->boundary = $boundary;
        array_push($this->_boundary_stack, $boundary);
        return $this->boundary;
    }

    public function getBoundary()
    {
        return $this->boundary;
    }


    public function getBoundaryString()
    {
        return md5(Ak::randomString(10).time());
    }




    public function _callActionMailerMethod($method_name, $params = array())
    {
        if(method_exists($this->ActionMailer, $method_name)){
            call_user_func_array(array(&$this->ActionMailer, $method_name), $params);
        }else{
            trigger_error(Ak::t('Could not find the method %method on the model %model', array('%method'=>$method_name, '%model'=>$this->ActionMailer->getModelName())), E_USER_ERROR);
        }
        $this->_setAttributesIfRequired();
    }

    public function _setAttributesIfRequired()
    {
        if(empty($this->ActionMailer->_setter_has_been_called)){
            $attributes = array();
            foreach ((array)$this->ActionMailer as $k=>$v){
                if(gettype($v) != 'object' && $k[0] != '_'){
                    $attributes[$k] = $v;
                }
            }
            $this->ActionMailer->set($attributes);
        }
    }


    public function _prepareInlineBodyParts($message_content_type = 'multipart/alternative')
    {
        if(!$this->_hasRenderedBody()){
            if(!$this->_renderMultiPartViews($message_content_type)){
                $this->_renderMainTemplateIfNeeded();
            }
            $this->_moveBodyToPart();
        }
    }

    public function _renderMainTemplateIfNeeded()
    {
        if($this->_shouldRenderMainTemplate()){
            $this->Message->setBody($this->_renderMainTemplate());
            return true;
        }
        return false;
    }

    public function _renderMainTemplate()
    {
        return $this->ActionMailer->renderMessage($this->ActionMailer->template, $this->Message->body);
    }

    public function _renderMultiPartViews($message_content_type)
    {
        if(empty($this->Message->parts)){
            $parts = $this->_getPartsWithRenderedTemplates();
            $this->Message->setParts($parts, 'append', true);
            if(!empty($this->Message->parts)){
                $this->Message->content_type = $message_content_type;
                $this->Message->sortParts();
            }
        }
        return !empty($parts);
    }

    public function _hasRenderedBody()
    {
        return is_string($this->Message->body);
    }

    public function _moveBodyToPart()
    {
        if (!empty($this->Message->parts) && is_string($this->Message->body)){
            array_unshift($this->Message->parts, array('charset' => $this->Message->charset, 'body' => $this->Message->body));
            $this->ActionMailer->body = null;
        }
    }

    public function _shouldRenderMainTemplate()
    {
        $result = empty($this->Message->parts);
        if(!$result && empty($this->Message->implicit_parts_order) && $this->_hasIndividualTemplate()){
            $result = true;
        }
        return $result;
    }


    public function _hasIndividualTemplate()
    {
        $templates = $this->_getAvailableTemplates();
        foreach ($templates as $template){
            $parts = explode('.',$template);
            if(count($parts) == 2 && $parts[0] == $this->ActionMailer->template){
                return true;
            }
        }
        return false;
    }


    public function &_getPartsWithRenderedTemplates()
    {
        $templates = $this->_getAvailableTemplates();
        $alternative_multiparts = array();
        $parts = array();
        foreach ($templates as $template_name){
            if(preg_match('/^([^\.]+)\.([^\.]+\.[^\.]+)\.(tpl)$/',$template_name, $match)){
                if($this->ActionMailer->template == $match[1]){
                    $content_type = str_replace('.','/', $match[2]);

                    $parts[] = array(
                    'content_type' => $content_type,
                    'disposition' => 'inline',
                    'charset' => @$this->Message->charset,
                    'body' => $this->ActionMailer->renderMessage($this->ActionMailer->getTemplatePath().DS.$template_name, $this->Message->body));
                }
            }
        }
        return $parts;
    }

    public function _getAvailableTemplates()
    {
        $path = $this->ActionMailer->getTemplatePath();
        if(!isset($templates[$path])){
            $templates[$path] = array_map('basename', Ak::dir($path, array('dirs'=>false)));
        }
        return $templates[$path];
    }
    
    


}

?>