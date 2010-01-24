<?php

/**
* This cookie-based session store is the Akelos default. Sessions typically
* contain at most a user_id and flash message; both fit within the 4K cookie
* size limit. Cookie-based sessions are dramatically faster than the
* alternatives.
* 
* If you have more than 4K of session data or don't want your data to be
* visible to the user, pick another session store.
* 
* CookieOverflowExceptionException is raised if you attempt to store more 
* than 4K of data.
* 
* A message digest is included with the cookie to ensure data integrity:
* a user cannot alter his +user_id+ without knowing the secret key
* included in the hash. New apps are generated with a pregenerated secret
* in config/boot.php. Set your own for old apps you're upgrading.
* 
* Session options:
* 
* * <tt>'secret'</tt>: An application-wide key string or block returning a
*   string called per generated digest. It's important that the secret
*   is not vulnerable to a dictionary attack. Therefore, you should choose
*   a secret consisting of random numbers and letters and more than 30
*   characters. Examples:
* 
*    'secret' => '449fe2e7daee471bffae2fd8dc02313d'
*    'secret' => $CurrentUser->secret_key
*/
class CookieOverflowException extends ControllerException{}


class AkCookieStore {
    /**
    * Cookies can typically store 4096 bytes.
    */
    const MAX = 4096;
    const SECRET_MIN_LENGTH = 30; # characters
    private $default_options = array(
    'key'          => AK_SESSION_NAME,
    'secret'       => '',
    'domain'       => AK_HOST,
    'path'         => '/',
    'expire_after' => AK_SESSION_EXPIRE,
    'httponly'     => true
    );

    public $options = array();

    public function init($options){
        $this->options = array_merge($this->default_options, $options);
        $this->ensureSessionKey();
        $this->ensureSecretSecure();
    }

    private function ensureSessionKey(){
        if(empty($this->options['key'])){
            throw new ArgumentException(
            Ak::t('A key is required to write a cookie containing the session data. Use ' .
            'AkConfig::setOption(\'action_controller.session\', '.
            'array("key" => "_myapp_session", "secret" => "some secret phrase")); in config/boot.php'));
        }
    }

    /**
     * To prevent users from using something insecure like "Password" we make sure that the
     * secret they've provided is at least 30 characters in length.
     */
    private function ensureSecretSecure(){
        if(empty($this->options['secret'])){
            throw new ArgumentException(
            Ak::t('A secret is required to generate an integrity hash for cookie session data. Use '.
            'AkConfig::setOption(\'action_controller.session\', '.
            'array("key" => "_myapp_session", "secret" => "some secret '.
            'phrase of at least %length characters")); in config/boot.php', array('%length' => self::SECRET_MIN_LENGTH)));
        }
        if(strlen($this->options['secret']) < self::SECRET_MIN_LENGTH){
            throw new ArgumentException(
            Ak::t('Secret should be something secure, '.
            'like "%rand". The value you provided "%secret", '.
            'is shorter than the minimum length of %length characters', array('%length' => self::SECRET_MIN_LENGTH, '%rand' => Ak::uuid())));
        }
    }


    public function get($session_id){
        $data = '';
        try{
            if(isset($_COOKIE[$session_id])){
                $data = rtrim(Ak::blowfishDecrypt(base64_decode($_COOKIE[$session_id]), $this->options['secret']), "\0");
            }
        }catch(Exception $e){}
        return $data;
    }

    public function save($session_id, $data){
        setcookie($this->options['key'], $session_id, $this->options['expire_after'], $this->options['path'], $this->options['domain']);
        $ecrypted_data = base64_encode(Ak::blowfishEncrypt($data, $this->options['secret']));
        if(strlen($ecrypted_data) > self::$MAX){
            throw new CookieOverflowException();
        }
        setcookie($this->options['key'], $ecrypted_data, $this->options['expire_after'], $this->options['path'], '.'.$this->options['domain']);
    }

    public function remove($session_id){
        setcookie($session_id, '');
        return true;
    }

    public function clean(){
        return true;
    }
}
