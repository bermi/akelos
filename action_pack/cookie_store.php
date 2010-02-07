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
* in config/environment.php. Set your own for old apps you're upgrading.
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
    'secure'       => false,
    'httponly'     => true,
    );

    public $options = array();

    public function __destruct(){
        # Ak::getLogger('sessions')->info(__METHOD__);
        @session_write_close();
    }

    public function init($options){
        # Ak::getLogger('sessions')->info(__METHOD__);
        $this->options = array_merge($this->default_options, AkConfig::getOption('action_controller.session', array()));
        $this->options = array_merge($this->options, $options);
        $this->options['expire_after'] = time()+$this->options['expire_after'];
        $this->ensureSessionKey();
        $this->ensureSecretSecure();
        ini_set('session.use_cookies','0');
        session_set_save_handler(
        array($this, 'open'),
        array($this, 'close'),
        array($this, 'read'),
        array($this, 'write'),
        array($this, 'destroy'),
        array($this, 'gc')
        );
        session_start();
        return true;
    }

    private function ensureSessionKey(){
        if(empty($this->options['key'])){
            throw new ArgumentException(
            Ak::t('A key is required to write a cookie containing the session data. Use ' .
            'AkConfig::setOption(\'action_controller.session\', '.
            'array("key" => "_myapp_session", "secret" => "some secret phrase")); in config/environment.php'));
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
            'phrase of at least %length characters")); in config/environment.php', array('%length' => self::SECRET_MIN_LENGTH)));
        }
        if(strlen($this->options['secret']) < self::SECRET_MIN_LENGTH){
            throw new ArgumentException(
            Ak::t('Secret should be something secure, '.
            'like "%rand". The value you provided "%secret", '.
            'is shorter than the minimum length of %length characters', array('%length' => self::SECRET_MIN_LENGTH, '%rand' => Ak::uuid())));
        }
    }

    public function open($save_path, $session_name) {
        # Ak::getLogger('sessions')->info(__METHOD__);
        $this->session_name = $session_name;
        return true;
    }

    public function close() {
        # Ak::getLogger('sessions')->info(__METHOD__);
        session_write_close();
        return true;
    }

    public function read() {
        $data = empty($_COOKIE[$this->session_name]) ? '' : $this->_decodeData($_COOKIE[$this->session_name]);
        # Ak::getLogger('sessions')->info(__METHOD__.' '.$data);
        return $data;
    }

    public function write($irrelevant_but_needed_session_id, $data) {
        # Ak::getLogger('sessions')->info(__METHOD__.' '.$data.' '.AkNumberHelper::human_size(strlen($data)));
        $data = $this->_encodeData($data);
        setcookie($this->session_name, $data, 0, '/', $this->options['domain']);
        return true;
    }

    public function destroy() {
        # Ak::getLogger('sessions')->info(__METHOD__);
        $this->write($this->session_name, '');
        return true;
    }

    public function gc($lifetime) {
        return true;
    }

    public function _decodeData($encoded_data){
        # Ak::getLogger('sessions')->info(__METHOD__);
        list($checksum, $data) = explode('|',$encoded_data.'|', 2);
        $data = @base64_decode($data);
        if(empty($data)){
            return '';
        }
        if($this->_getChecksumForData($data) != $checksum){
            throw new ControllerException("Cookie data tamper atempt.  Received: \"$data\"\nVisitor IP:".AK_REMOTE_IP);
            return '';
        }
        return $data;
    }

    public function _encodeData($data){
        # Ak::getLogger('sessions')->info(__METHOD__);
        $data = $this->_getChecksumForData($data).'|'.base64_encode($data);
        if(strlen($data) > self::MAX){
            throw new CookieOverflowException('Tried to allocate '.strlen($data).' chars into a session based cookie. The limit is '.self::MAX.' chars. Please try storing smaller sets into cookies or use another session handler.');
        }
        return $data;
    }

    public function _getChecksumForData($data){
        # Ak::getLogger('sessions')->info(__METHOD__);
        return sha1(sha1($this->options['secret'].$this->session_name.$data.$this->options['domain']));
    }
}
