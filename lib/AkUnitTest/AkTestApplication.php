<?php
require_once(AK_LIB_DIR.DS.'AkUnitTest.php');

class AkTestApplication extends AkUnitTest
{
    var $Dispatcher;
    var $_response;
    var $_cacheHeaders = array();
    
    function assertWantedText($text, $message = '%s')
    {
        $this->assertWantedPattern('/'.preg_quote($text).'/', $message);
    }

    /**
     * Asserts only if the whole response matches $text
     */
    function assertTextMatch($text, $message = '%s')
    {
        $this->assertWantedPattern('|^'.$text.'$|', $message);
    }
    
    function assertText($text, $message = '%s') {
            return $this->assert(
                    new TextExpectation($text),
                    strip_tags($this->_response),
                    $message);
    }
    function assertNoText($text, $message = '%s') {
        return $this->assert(
                new NoTextExpectation($text),
                strip_tags($this->_response),
                $message);
    }
    function assertHeader($header, $content = null)
    {
        if (is_array($this->_cacheHeaders)) {
            foreach ($this->_cacheHeaders as $ch) {
                $parts = split(': ', $ch);
                if ($parts[0] == $header) {
                    if ($content != null) {
                        $this->assertEqual($content, $parts[1],'1 Header content does not match: '.$parts[1].'!='.$content.':'.var_export($this->_cacheHeaders,true)."\n".var_export($this->Dispatcher->Request->_format,true));
                        return;
                    } else {
                        $this->assertTrue(true);
                        return;
                    }
                }
            }
        }
        if ($this->Dispatcher) {
            $value = $this->Dispatcher->Response->getHeader($header);
            $this->assertTrue($value!=false,'Header "'.$header.'" not found');
            if ($content != null) {
                $this->assertEqual($value, $content,'2 Header content does not match: '.$content.'!='.$value.':'.var_export($this->Dispatcher->Response->_headers,true).':'.var_export($this->Dispatcher->Response->_headers_sent,true)."\n".var_export($this->Dispatcher->Request->_format,true));;
            }
        } else {
            $this->assertTrue(false,'Header "'.$header.'" not found');
        }
    }
    
    function _testXPath($xpath_expression)
    {
        if (!class_exists('DOMDocument') || !class_exists('DOMXPath')) {
            if (function_exists('domxml_open_mem')) {
                $dom = domxml_open_mem($this->_response);
                if (!$dom) {
                    $this->fail('Error parsing doc');
                    return false;
                }
                var_dump($dom);
                $xpath = $dom->xpath_init();
                var_dump($xpath);
                $ctx = $dom->xpath_new_context(); 
                var_dump($xpath_expression);
                $result = $ctx->xpath_eval($xpath_expression);
                var_dump($result);
                $return = new stdClass();
                $return->length = count($result->nodeset);
                return $return;
            }
            $this->fail('No xpath support built in');
            return false;
        } else if (extension_loaded('domxml')) {
            $this->fail('Please disable the domxml extension. Only php5 builtin domxml is supported');
            return false;
        }
       
        $dom = new DOMDocument();
        $dom->loadHtml($this->_response);
        $xpath = new DOMXPath($dom);
        $node = $xpath->query($xpath_expression);
        return $node;
    }
    
    function assertXPath($xpath_expression, $message = null)
    {
        $node = $this->_testXPath($xpath_expression);
        if ($node->length<1) {
            $message = empty($message)?'Element not found using xpath: %xpath':$message;
            $message = str_replace('%xpath',$xpath_expression,$message);
            $this->fail($message);
        } else {
            $message = empty($message)?'Element found using xpath: %xpath':$message;
            $this->pass($message);
        }
    }
    function assertNoXPath($xpath_expression, $message = null)
    {
        $node = $this->_testXPath($xpath_expression);
        if ($node->length>0) {
            $message = empty($message)?'Element found using xpath: %xpath':$message;
            $message = str_replace('%xpath',$xpath_expression,$message);
            $this->fail($message);
        } else {
            $message = empty($message)?'Element not found using xpath: %xpath':$message;
            $this->pass($message);
        }
    }
    function assertValidXhtml($message = null)
    {
        require_once(AK_LIB_DIR.DS.'AkXhtmlValidator.php');
        $response = $this->_response;
        $validator = new AkXhtmlValidator();
        $valid = $validator->validate($response);

        if (!$valid) {
            $message = empty($message)?'Non valid Xhtml: %errors':$message;
            $message = str_replace('%errors',strip_tags(join("\n- ",$validator->getErrors())),$message);
            $this->fail($message);
        } else {
            $message = empty($message)?'XHtml valid':$message;
            $this->pass($message);
        }
    }
    
    function &getController()
    {
        if (isset($this->Dispatcher)) {
            $controller = &$this->Dispatcher->Controller;
            return $controller;
        } else {
            $false = false;
            return $false;
        }
    }
    function _setConstants($constants = array())
    {
        foreach ($constants as $constant=>$value) {
            !defined($constant)?define($constant,$value):null;
        }
    }
    function setIp($ip)
    {
        $_SERVER['HTTP_CLIENT_IP'] = $ip;
        $_SERVER['REMOTE_ADDR'] = $ip;
    }
    
    function assertResponse($code)
    {
        $this->assertHeader('Status',$code);
    }

    function setForwaredForIp($ip)
    {
        $_SERVER['HTTP_X_FORWARDED_FOR'] = $ip;
    }
    function addIfModifiedSince($gmtDateString)
    {
        $_SERVER['HTTP_IF_MODIFIED_SINCE'] = $gmtDateString;
    }
    function setXmlHttpRequest()
    {
        $_SERVER['HTTP_X_REQUESTED_WITH']='xmlhttprequest';
    }
    function setAcceptEncoding($encoding)
    {
        $_SERVER['HTTP_ACCEPT_ENCODING']=$encoding;
    }
    function &getHeader($name)
    {
        if ($this->Dispatcher) {
            $sentHeader = $this->Dispatcher->Response->getHeader($name);
        } else {
            $sentHeader=false;
        }
        if (!$sentHeader) {
            if (is_array($this->_cacheHeaders)) {
                foreach ($this->_cacheHeaders as $ch) {
                    $parts = split(': ', $ch);
                    if ($parts[0] == $name) {
                        $return=@$parts[1];
                        return $return;
                    }
                }
            }
        }
        return $sentHeader;
    }

    function _reset()
    {
        $_REQUEST = array();
        $_POST = array();
        $_SESSION = array();
        $_GET = array();
        $_POST = array();
    }
    
    function _init($url, $constants = array(), $controllerVars = array())
    {
        $this->_reset();
        $this->_response = null;
        $this->_cacheHeaders = array();
        $this->_setConstants($constants);
        $parts = parse_url($url);
        $_REQUEST['ak'] = isset($parts['path'])?$parts['path']:'/';
        $_SERVER['AK_HOST']= isset($parts['host'])?$parts['host']:'localhost';
        $cache_settings = Ak::getSettings('caching', false);
        if ($cache_settings['enabled']) {
    
            require_once(AK_LIB_DIR . DS . 'AkActionController'.DS.'AkCacheHandler.php');
            $null = null;
            $pageCache = &Ak::singleton('AkCacheHandler',$null);
            
            $pageCache->init($null, $cache_settings);
            if ($cachedPage = $pageCache->getCachedPage()) {
                static $_cachedHeaders = array();
                ob_start();
                global $sendHeaders, $returnHeaders, $exit;
                $sendHeaders = false;
                $returnHeaders = true; 
                $exit = false;
                $headers = include $cachedPage;
                //$headers = $cachedPage->render(false,false,true);
                $this->_response = ob_get_clean();
                if (is_array($headers)) {
                    $this->_cacheHeaders = $headers;
                }
                return true;
            }
        }
        require_once(AK_LIB_DIR.DS.'AkUnitTest'.DS.'AkTestDispatcher.php');
        $this->Dispatcher =& new AkTestDispatcher($controllerVars);
    }
    
    
    function get($url,$constants = array(), $controllerVars = array())
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        ob_start();
        $rendered = $this->_init($url, $constants, $controllerVars);
        if (!$rendered) {
            $res = $this->Dispatcher->get($url);
            $this->_response = ob_get_clean();
        } else {
            $res=true;
        }
        $this->_cleanUp();
        return $res;
    }
    function _cleanUp()
    {
        unset($_SERVER['HTTP_IF_MODIFIED_SINCE']);
        unset($_SERVER['HTTP_X_REQUESTED_WITH']);
        unset($_SERVER['HTTP_ACCEPT_ENCODING']);
    }
    function post($url, $data = null, $constants = array(), $controllerVars = array())
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        ob_start();
        
        $rendered = $this->_init($url, $constants, $controllerVars);
        if (!$rendered) {
            $res = $this->Dispatcher->post($url, $data);
            $this->_response = ob_get_clean();
        } else {
            $res=true;
        }
        return $res;
    }
    
    function put($url,$data = null, $constants = array(), $controllerVars = array())
    {
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        ob_start();
        $rendered = $this->_init($url, $constants, $controllerVars);
        if (!$rendered) {
            $res = $this->Dispatcher->put($url,$data);
            $this->_response = ob_get_clean();
        } else {
            $res = true;
        }
        return $res;
    }
    function assertWantedPattern($pattern, $message = '%s') {
        return $this->assertPattern($pattern, $message);
    }
    function assertPattern($pattern, $message = '%s') {
        return $this->assert(
                new PatternExpectation($pattern),
                $this->_response,
                $message);
    }
    function delete($url, $constants = array(), $controllerVars = array())
    {
        $_SERVER['REQUEST_METHOD'] = 'DELETE';
        ob_start();
        $rendered = $this->_init($url, $constants, $controllerVars);
        if (!$rendered) {
            $res = $this->Dispatcher->delete($url);
            $this->_response = ob_get_clean();
        } else {
            $res= true;
        }
        return $res;
    }
    
}