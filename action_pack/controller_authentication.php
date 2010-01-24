<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

/**
                        HTTP Authentication
====================================================================
*
* Simple Basic example:
*
*   class PostsController extends ApplicationController
*   {
*       private $_authorized_users = array('bermi' => 'secret');
*
*       public function __construct(){
*           $this->beforeFilter(array('authenticate' => array('except' => array('index'))));
*       }
*
*       public function index() {
*           $this->renderText("Everyone can see me!");
*       }
*
*       public function edit(){
*           $this->renderText("I'm only accessible if you know the password");
*       }
*
*       public function authenticate(){
*           return $this->authenticateOrRequestWithHttpBasic('App name', $this->_authorized_users);
*       }
*   }
*
* Here is a more advanced Basic example where only Atom feeds and the XML API is protected by HTTP authentication,
* the regular HTML interface is protected by a session approach:
*
*   class ApplicationController extends AkActionController
*   {
*       public $models = 'account';
*
*       public function __construct() {
*         $this->beforeFilter(array('_setAccount', 'authenticate'));
*       }
*
*       public function _setAccount() {
*         $this->Account = $this->account->findFirstBy('url_name', array_pop($this->_Controller->Request->getSubdomains()));
*       }
*
*       public function authenticate() {
*           if($this->_Controller->Request->isFormat('XML', 'ATOM')){
*               if($User = $this->authenticateWithHttpBasic($Account)){
*                   $this->CurrentUser = $User;
*               }else{
*                   $this->_Controller->requestHttpBasicAuthentication();
*               }
*           }else{
*               if($this->isSessionAuthenticated()){
*                   $this->CurrentUser = $Account->user->find($_SESSION['authenticated']['user_id']);
*               }else{
*                   $this->redirectTo(array('controller'=>'login'));
*                   return false;
*               }
*           }
*       }
*   }
*
* On shared hosts, Apache sometimes doesn't pass authentication headers to
* FCGI instances. If your environment matches this description and you cannot
* authenticate, try this rule in public/.htaccess (replace the plain one):
*
*   RewriteRule ^(.*)$ index.php [E=X-HTTP_AUTHORIZATION:%{HTTP:Authorization},QSA,L]
*/

class AkControllerAuthentication
{
    private $_Controller;

    public function authenticateOrRequestWithHttpBasic($realm = AK_APP_NAME, $login_procedure) {
        if($Result = $this->authenticateWithHttpBasic($login_procedure)){
            return $Result;
        }
        return $this->requestHttpBasicAuthentication($realm);
    }

    public function authenticateWithHttpBasic($login_procedure) {
        return $this->_authenticate($login_procedure);
    }

    public function requestHttpBasicAuthentication($realm = AK_APP_NAME) {
        return $this->_authenticationRequest($realm);
    }

    public function authenticateOrRequestWithHttpDigest($realm = AK_APP_NAME, $login_procedure) {
        if($Result = $this->authenticateWithHttpDigest($realm, $login_procedure)){
            return $Result;
        }
        return $this->requestHttpDigestAuthentication($realm);
    }

    public function authenticateWithHttpDigest($realm, $login_procedure) {
        return $this->_authenticateDiggest($realm, $login_procedure);
    }

    public function requestHttpDigestAuthentication($realm = AK_APP_NAME) {
        return $this->_authenticationDigestRequest($realm);
    }

    /**
     * This is method takes a $login_procedure for performing access authentication.
     *
     * If an array is given, it will check the key for a user and the value will be verified to match given password.
     *
     * You can pass and array like array('handler' => $Account, 'method' => 'verifyCredentials'), which will call
     *
     *      $Account->verifyCredentials($user_name, $password, $Controller)
     *
     * You can also pass an object which implements an "authenticate" method. when calling
     *
     *     $this->_authenticate(new User());
     *
     * It will call the $User->authenticate($user_name, $password, $Controller)
     *
     * In both cases the authentication method should return true for valid credentials or false is invalid.
     *
     * @return bool
     */
    private function _authenticate($login_procedure) {
        if(!$this->_authorization()){
            return false;
        }else{
            list($user_name, $password) = $this->_getUserNameAndPassword();
            if(is_array($login_procedure)){
                if(!isset($login_procedure['handler'])){
                    return isset($login_procedure[$user_name]) && $login_procedure[$user_name] == $password;
                }elseif(is_object($login_procedure['handler']) && method_exists($login_procedure['handler'], $login_procedure['method'])){
                    return $login_procedure['handler']->$login_procedure['method']($user_name, $password, $this->_Controller);
                }
            }elseif(method_exists($login_procedure, 'authenticate')){
                return $login_procedure->authenticate($user_name, $password, $this->_Controller);
            }
        }
        return false;
    }

    private function _getUserNameAndPassword() {
        $credentials = $this->_decodeCredentials();
        return !is_array($credentials) ? explode(':', $credentials , 2) : $credentials;
    }

    private function _authorization() {

        return
        empty($this->_Controller->Request->env['PHP_AUTH_DIGEST']) ? (
        empty($this->_Controller->Request->env['PHP_AUTH_USER']) ? (
        empty($this->_Controller->Request->env['HTTP_AUTHORIZATION']) ? (
        empty($this->_Controller->Request->env['X-HTTP_AUTHORIZATION']) ? (
        empty($this->_Controller->Request->env['X_HTTP_AUTHORIZATION']) ? (
        isset($this->_Controller->Request->env['REDIRECT_X_HTTP_AUTHORIZATION']) ?
        $this->_Controller->Request->env['REDIRECT_X_HTTP_AUTHORIZATION'] : null
        ) : $this->_Controller->Request->env['X_HTTP_AUTHORIZATION']
        ) : $this->_Controller->Request->env['X-HTTP_AUTHORIZATION']
        ) : $this->_Controller->Request->env['HTTP_AUTHORIZATION']
        ) : array($this->_Controller->Request->env['PHP_AUTH_USER'], $this->_Controller->Request->env['PHP_AUTH_PW'])) :
        $this->_parseHttpDiggest($this->_Controller->Request->env['PHP_AUTH_DIGEST']);
    }

    private function _decodeCredentials() {
        $authorization = $this->_authorization();
        if(is_array($authorization)){
            return $authorization;
        }
        $credentials = (array)explode(' ', $authorization);
        return base64_decode(array_pop($credentials));
    }

    private function _encodeCredentials($user_name, $password) {
        return 'Basic '.base64_encode("$user_name:$password");
    }

    private function _authenticationRequest($realm) {
        header('WWW-Authenticate: Basic realm="' . str_replace('"','',$realm) . '"');

        if(method_exists($this, 'access_denied')){
            $this->_Controller->access_denied();
        }else{
            header('HTTP/1.0 401 Unauthorized');
            echo "HTTP Basic: Access denied.\n";
            exit;
        }
    }

    private function _authenticateDiggest($realm, $login_procedure){
        if(!$data = $this->_authorization()){
            return false;
        }else{
            $user_name = $data['username'];
            if(is_array($login_procedure)){
                if(!isset($login_procedure['handler'])){
                    $password = isset($login_procedure[$user_name]) ? $login_procedure[$user_name] : false;
                }elseif(is_object($login_procedure['handler']) && method_exists($login_procedure['handler'], $login_procedure['method'])){
                    $password = $login_procedure['handler']->$login_procedure['method']($user_name, $this->_Controller);
                }
                if($password){
                    return $data['response'] == md5(
                    md5($user_name.':'.$realm.':'.$password).
                    ':'.$data['nonce'].':'.$data['nc'].':'.$data['cnonce'].':'.$data['qop'].':'.
                    md5($this->_Controller->Request->env['REQUEST_METHOD'].':'.$data['uri']));
                }
            }

        }
        return false;
    }

    private function _authenticationDigestRequest($realm) {
        header('WWW-Authenticate: Digest realm="'.str_replace('"','',$realm).'",qop="auth",nonce="'.uniqid().'",opaque="'.md5($realm).'"');
        if(method_exists($this, 'access_denied')){
            $this->_Controller->access_denied();
        }else{
            header('HTTP/1.0 401 Unauthorized');
            echo "HTTP Basic: Access denied.\n";
            exit;
        }
    }

    private function _parseHttpDiggest($digest){
        $matches = array();
        $data = array();
        if(preg_match_all('@(username|nonce|uri|nc|cnonce|qop|response)=[\'"]?([^\'",]+)@', $digest, $matches)){
            $data = array_combine($matches[1], $matches[2]);
        }
        return (count($data)==7) ? $data : false;
    }

    public function setExtendedBy(&$Controller) {
        $this->_Controller = $Controller;
    }
}
