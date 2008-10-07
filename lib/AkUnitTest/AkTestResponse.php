<?php
require_once(AK_LIB_DIR.DS.'AkResponse.php');
class AkTestResponse extends AkResponse
{
    function __construct()
    {
        $this->_headers = array();
        $this->_headers_sent = array();
    }
    function sendHeaders($terminate_if_redirected = true)
    {
        /**
         * Fix a problem with IE 6.0 on opening downloaded files:
         * If Cache-Control: IE removes the file it just downloaded from 
         * its cache immediately 
         * after it displays the "open/save" dialog, which means that if you 
         * hit "open" the file isn't there anymore when the application that 
         * is called for handling the download is run, so let's workaround that
         */
        if(isset($this->_headers['Cache-Control']) && $this->_headers['Cache-Control'] == 'no-cache'){
            $this->_headers['Cache-Control'] = 'private';
        }
        if(!empty($this->_headers['Status'])){
            $status = $this->_getStatusHeader($this->_headers['Status']);
            array_unshift($this->_headers,  $status ? $status : (strstr('HTTP/1.1 '.$this->_headers['Status'],'HTTP') ? $this->_headers['Status'] : 'HTTP/1.1 '.$this->_headers['Status']));
            //unset($this->_headers['Status']);
        } else {
            $this->_headers['Status'] = $this->_default_status;
        }
        
        if(!empty($this->_headers) && is_array($this->_headers)){
            $this->addHeader('Connection: close');
            foreach ($this->_headers as $k=>$v){
                if ($k == 'Status') continue;
                $header = trim((!is_numeric($k) ? $k.': ' : '').$v);
                $this->_headers_sent[] = $header;
                if(strtolower(substr($header,0,9)) == 'location:'){
                    $_redirected = true;
                    if(AK_DESKTOP){
                        $javascript_redirection = '<title>'.Ak::t('Loading...').'</title><script type="text/javascript">location = "'.substr($header,9).'";</script>';
                        continue;
                    }
                }
                if(strtolower(substr($header,0,13)) == 'content-type:'){
                    $_has_content_type = true;
                }
                AK_LOG_EVENTS && !empty($this->_Logger) ? $this->_Logger->message("Sending header:  $header") : null;
                //header($header);
            }
        }
        
        if(empty($_has_content_type) && defined('AK_CHARSET') && (empty($_redirected) || (!empty($_redirected) && !empty($javascript_redirection)))){
            //header('Content-Type: text/html; charset='.AK_CHARSET);
            $this->_headers_sent[] = 'Content-Type: text/html; charset='.AK_CHARSET;
        }
        
        if(!empty($javascript_redirection)){
            echo $javascript_redirection;
        }
        
        $terminate_if_redirected ? (!empty($_redirected) ? $this->isRedirected(true) : null) : null;
    }
    function _parseHeaders()
    {
        $headers = array();
        foreach($this->_headers_sent as $header) {
            $parts = preg_split('/:\s+/',$header);
            
            $headers[strtolower($parts[0])] = isset($parts[1])?trim($parts[1]):trim($parts[0]);
        }
        return $headers;
    }
    function getHeader($name)
    {
        $headers = $this->_parseHeaders();
        $sentHeader = isset($headers[strtolower($name)])?$headers[strtolower($name)]:false;
        if (!$sentHeader) {
            $preparedHeader = isset($this->_headers[$name])?$this->_headers[$name]:false;
            return $preparedHeader;
        }
        return $sentHeader;
    }
    function isRedirected($set=false)
    {
        static $isRedirected;
        if ($set) {
            $isRedirected = true;
        }
        return $isRedirected;
    }
    
    function redirect ($url)
    {
        $this->autoRender = false;
        if(substr(@$this->_headers['Status'],0,3) != '301'){
            $this->_headers['Status'] = 302;    
        }
        $this->addHeader('Location', $url);
        $this->sendHeaders();
    }
}

function &AkTestResponse()
{
    $null = null;
    $AkResponse =& Ak::singleton('AkTestResponse', $null);
    return $AkResponse;
}