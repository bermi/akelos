<?php


class AkHttpClient extends AkObject
{
    var $HttpRequest;
    var $error;
    var $Response;
    var $_cookie_path;
    var $_cookie_jar = 'default';

    function get($url, $options = array())
    {
        return $this->customRequest($url, 'GET', $options);
    }

    function post($url, $options = array(), $body = '')
    {
        return $this->customRequest($url, 'POST', $options, $body);
    }

    function put($url, $options = array(), $body = '')
    {
        return $this->customRequest($url, 'PUT', $options, $body);
    }

    function delete($url, $options = array())
    {
        return $this->customRequest($url, 'DELETE', $options);
    }

    // prefix_options, query_options = split_options(options)

    function customRequest($url, $http_verb = 'GET', $options = array(), $body = '')
    {
        $this->getRequestInstance($url, $http_verb, $options, $body);
        return empty($options['cache']) ? $this->sendRequest() : $this->returnCustomRequestFromCache($url,$options);
    }

    function returnCustomRequestFromCache($url, $options)
    {
        $Cache = Ak::cache();
        $Cache->init(is_numeric($options['cache']) ? $options['cache'] : 86400, !isset($options['cache_type']) ? 1 : $options['cache_type']);
        if (!$data = $Cache->get('AkHttpClient_'.md5($url))) {
            $data = $this->sendRequest();
            $Cache->save($data);
        }
        return $data;
    }

    function urlExists($url)
    {
        $this->getRequestInstance($url, 'GET');
        $this->sendRequest(false);
        return $this->code == 200;
    }

    function getRequestInstance($url, $http_verb = 'GET', $options = array(), $body = '')
    {
        $default_options = array(
        'header' => array(),
        'params' => array(),
        );

        $options = array_merge($default_options, $options);

        $options['header']['user-agent'] = empty($options['header']['user-agent']) ?
        'Akelos PHP Framework AkHttpClient (http://akelos.org)' : $options['header']['user-agent'];

        list($user_name, $password) = $this->_extractUserNameAndPasswordFromUrl($url);

        require_once(AK_VENDOR_DIR.DS.'pear'.DS.'HTTP'.DS.'Request.php');

        $this->{'_setParamsFor'.ucfirst(strtolower($http_verb))}($url, $options['params']);

        $this->HttpRequest =& new HTTP_Request($url);

        $user_name ? $this->HttpRequest->setBasicAuth($user_name, $password) : null;

        $this->HttpRequest->setMethod(constant('HTTP_REQUEST_METHOD_'.$http_verb));

        if(!empty($body)){
            $this->setBody($body);
        }elseif ($http_verb == 'PUT' && !empty($options['params'])){
            $this->setBody($options['params']);
        }

        !empty($options['params']) && $this->addParams($options['params']);

        isset($options['cookies']) &&  $this->addCookieHeader($options, $url);

        $this->addHeaders($options['header']);

        return $this->HttpRequest;
    }

    function addHeaders($headers)
    {
        foreach ($headers as $k=>$v){
            $this->addHeader($k, $v);
        }
    }

    function addHeader($name, $value)
    {
        $this->HttpRequest->removeHeader($name);
        $this->HttpRequest->addHeader($name, $value);
    }

    function getResponseHeader($name)
    {
        return $this->HttpRequest->getResponseHeader($name);
    }

    function getResponseHeaders()
    {
        return $this->HttpRequest->getResponseHeader();
    }

    function getResponseCode()
    {
        return $this->HttpRequest->getResponseCode();
    }

    function addParams($params = array())
    {
        if(!empty($params)){
            foreach (array_keys($params) as $k){
                $this->HttpRequest->addPostData($k, $params[$k]);
            }
        }
    }

    function setBody($body)
    {
        Ak::compat('http_build_query');
        $this->HttpRequest->setBody(http_build_query((array)$body));
    }

    function sendRequest($return_body = true)
    {
        $this->Response = $this->HttpRequest->sendRequest();
        $this->code = $this->HttpRequest->getResponseCode();
        $this->persistCookies();
        if (PEAR::isError($this->Response)) {
            $this->error = $this->Response->getMessage();
            return false;
        } else {
            return $return_body ? $this->HttpRequest->getResponseBody() : true;
        }
    }


    function addCookieHeader(&$options, $url)
    {
        if(isset($options['cookies'])){
            $url_details = parse_url($url);
            $jar = Ak::sanitize_include((empty($options['jar']) ? $this->_cookie_jar : $options['jar']), 'paranoid');
            $this->setCookiePath(AK_TMP_DIR.DS.'cookies'.DS.$jar.DS.Ak::sanitize_include($url_details['host'],'paranoid'));
            if($options['cookies'] === false){
                $this->deletePersistedCookie();
                return;
            }
            if($cookie_value = $this->getPersistedCookie()){
                $this->_persisted_cookie = $cookie_value;
                $options['header']['cookie'] = $cookie_value;
            }
        }
    }

    function setCookiePath($path)
    {
        $this->_cookie_path = $path;
    }

    function getPersistedCookie()
    {
        if(file_exists($this->_cookie_path)){
            return Ak::file_get_contents($this->_cookie_path);
        }
        return false;
    }

    function deletePersistedCookie()
    {
        if(file_exists($this->_cookie_path)){
            Ak::file_delete($this->_cookie_path);
            $this->_cookie_path = false;
            return;
        }
        return false;
    }

    function persistCookies()
    {
        if($this->_cookie_path){
            $cookies_from_response = $this->HttpRequest->getResponseCookies();
            if(!empty($this->_persisted_cookie)){
                $this->HttpRequest->_cookies = array();
                $persisted_cookies = $this->HttpRequest->_response->_parseCookie($this->_persisted_cookie);
                $this->HttpRequest->_cookies = $cookies_from_response;
            }
            if(!empty($cookies_from_response)){
                $all_cookies = array_merge(isset($persisted_cookies)?$persisted_cookies:array(), $cookies_from_response);
                $cookies = array();
                foreach($all_cookies as $cookie){
                    if(!empty($cookie['value'])){
                        $cookies[$cookie['name']] = "{$cookie['name']}={$cookie['value']}";
                    }
                }
                $cookie_string = trim(join($cookies, '; '));
                Ak::file_put_contents($this->_cookie_path, $cookie_string);
            }
        }
    }

    function _extractUserNameAndPasswordFromUrl(&$url)
    {
        return array(null,null);
    }

    function getParamsOnUrl($url)
    {
        $parts = parse_url($url);
        if($_tmp = (empty($parts['query']) ? false : $parts['query'])){
            unset($parts['query']);
            $url = $this->_httpRenderQuery($parts);
        }
        $result = array();
        !empty($_tmp) && parse_str($_tmp, $result);
        return $result;
    }

    function getUrlWithParams($url, $params)
    {
        $parts = parse_url($url);
        Ak::compat('http_build_query');
        $parts['query'] = http_build_query($params);
        return $this->_httpRenderQuery($parts);
    }

    function _setParamsForGet(&$url, &$params)
    {
        $url_params = $this->getParamsOnUrl($url);
        if(!count($url_params) && !empty($params)){
            $url = $this->getUrlWithParams($url, $params);
        }else{
            $params = $url_params;
        }
    }

    function _setParamsForPost(&$url, &$params)
    {
        empty($params) && $params = $this->getParamsOnUrl($url);
    }

    function _setParamsForPut(&$url, &$params)
    {
        empty($params) && $params = $this->getParamsOnUrl($url);
    }

    function _setParamsForDelete(&$url, &$params)
    {
        if(!$this->getParamsOnUrl($url) && !empty($params)){
            $url = $this->getUrlWithParams($url, $params);
        }
    }

    function _httpRenderQuery($parts)
    {
        return is_array($parts) ? (
        (isset($parts['scheme']) ? $parts['scheme'].':'.((strtolower($parts['scheme']) == 'mailto') ? '' : '//') : '').
        (isset($parts['user']) ? $parts['user'].(isset($parts['pass']) ? ':'.$parts['pass'] : '').'@' : '').
        (isset($parts['host']) ? $parts['host'] : '').
        (isset($parts['port']) ? ':'.$parts['port'] : '').
        (isset($parts['path'])?((substr($parts['path'], 0, 1) == '/') ? $parts['path'] : ('/'.$parts['path'])):'').
        (isset($parts['query']) ? '?'.$parts['query'] : '').
        (isset($parts['fragment']) ? '#'.$parts['fragment'] : '')
        ) : false;
    }
}


?>
