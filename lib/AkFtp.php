<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2006, Akelos Media, S.L.  & Bermi Ferrer Martinez |
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
 * @package AkelosFramework
 * @subpackage Ftp
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

if(!defined('AK_AUTOMATIC_CONFIG_VARS_ENCRYPTION')){
    define('AK_AUTOMATIC_CONFIG_VARS_ENCRYPTION', false);
}
if(!defined('AK_FTP_SHOW_ERRORS')){
    define('AK_FTP_SHOW_ERRORS', true);
}

class AkFtp
{
    function put_contents ($file, $contents)
    {
        $result = false;

        if($ftp = AkFtp::connect()){

            $file = str_replace('\\','/',$file);
            $path = dirname($file);

            if(!AkFtp::is_dir($path)){
                AkFtp::make_dir($path);
            }

            $tmpfname = tempnam('/tmp', 'tmp');

            $temp = fopen($tmpfname, 'a+');
            fwrite($temp, $contents);
            fclose($temp);

            $temp = fopen($tmpfname, 'rb');
            $result = ftp_fput($ftp, $file , $temp, FTP_BINARY);

            fclose($temp);
            unlink($tmpfname);
        }

        return $result;
    }


    function get_contents ($file)
    {
        if($ftp = AkFtp::connect()){

            $file = str_replace('\\','/',$file);

            $tmpfname = tempnam('/tmp', 'tmp');

            ftp_get($ftp, $tmpfname, $file , FTP_BINARY);

            $file_contents = @file_get_contents($tmpfname);

            unlink($tmpfname);

            return $file_contents;
        }
    }

    function connect($base_dir = null)
    {
        static $ftp_conn, $_base_dir, $disconnected = false;
        
        if(!isset($ftp_conn) || $disconnected){
            if(!defined('AK_FTP_PATH')){
                trigger_error(Ak::t('You must set a valid FTP connection on AK_FTP_PATH in your config/config.php file'),E_USER_ERROR);
            }else {
                if(AK_AUTOMATIC_CONFIG_VARS_ENCRYPTION && substr(AK_FTP_PATH,0,10) == 'PROTECTED:'){
                    // You should change the key bellow and encode this file if you are going to distribute applications
                    // The ideal protection would be to encode user configuration file.
                    $AK_FTP_PATH = Ak::decrypt(base64_decode(substr(AK_FTP_PATH,10)),'HR23JHR93JZ0ALi1UvTZ0ALi1UvTk7MD70'); 
                    $_pass_encoded = true;
                }else{
                    $AK_FTP_PATH = AK_FTP_PATH;
                }
                $f = parse_url($AK_FTP_PATH);
                if(@$f['scheme'] != 'ftps'){
                    $ftp_conn = isset($f['port']) ?  ftp_connect($f['host'], $f['port']) : ftp_connect($f['host']);
                }else{
                    $ftp_conn = isset($f['port']) ?  ftp_ssl_connect($f['host'], $f['port']) : ftp_ssl_connect($f['host']);
                }

                $login_result = ftp_login($ftp_conn, @$f['user'], @$f['pass']);

                if(!$ftp_conn || !$login_result){
                    AK_FTP_SHOW_ERRORS ? trigger_error(Ak::t('Could not connect to the FTP server'), E_USER_NOTICE) : null;
                    return false;
                }

                $_base_dir = isset($f['path']) ? '/'.trim($f['path'],'/') : '/';

                if(defined('AK_FTP_AUTO_DISCONNECT') && AK_FTP_AUTO_DISCONNECT){
                    register_shutdown_function(array('AkFtp', 'disconnect'));
                }
                if(AK_AUTOMATIC_CONFIG_VARS_ENCRYPTION && empty($_pass_encoded)){
                    
                    @register_shutdown_function(create_function('',
                    "@Ak::file_put_contents(AK_CONFIG_DIR.DS.'config.php',
                str_replace(AK_FTP_PATH,'PROTECTED:'.base64_encode(Ak::encrypt(AK_FTP_PATH,'HR23JHR93JZ0ALi1UvTZ0ALi1UvTk7MD70')),
                Ak::file_get_contents(AK_CONFIG_DIR.DS.'config.php')));"));
                }
            }
        }

        if(isset($base_dir) && $base_dir === 'AK_DISCONNECT_FTP'){
            $disconnected = true;
            $base_dir = null;
        }else {
            $disconnected = false;
        }


        if(!isset($base_dir) && isset($_base_dir) && ('/'.trim(ftp_pwd($ftp_conn),'/') != $_base_dir)){
            if (!@ftp_chdir($ftp_conn, $_base_dir) && AK_FTP_SHOW_ERRORS) {
                trigger_error(Ak::t('Could not change to the FTP base directory %directory',array('%directory'=>$_base_dir)),E_USER_NOTICE);
            }
        }elseif (isset($base_dir)){
            if (!ftp_chdir($ftp_conn, $base_dir) && AK_FTP_SHOW_ERRORS) {
                trigger_error(Ak::t('Could not change to the FTP directory %directory',array('%directory'=>$base_dir)),E_USER_NOTICE);
            }
        }

        return $ftp_conn;
    }

    function disconnect()
    {
        static $disconnected = false;
        if(!$disconnected && $ftp_conn = AkFtp::connect('AK_DISCONNECT_FTP')){
            $disconnected = ftp_close($ftp_conn);
            return $disconnected;
        }
        return false;
    }

    function make_dir($path)
    {
        if($ftp_conn = AkFtp::connect()){
            $path = str_replace('\\','/',$path);
            if(!strstr($path,'/')){
                $dir = array(trim($path,'.'));
            }else{
                $dir = (array)@split('/', trim($path,'/.'));
            }
            $path = ftp_pwd($ftp_conn).'/';
            $ret = true;
            
            for ($i=0; $i<count($dir); $i++){
                $path .= $i === 0 ? $dir[$i] : '/'.$dir[$i];
                if(!@ftp_chdir($ftp_conn, $path)){
                    $ftp_conn = AkFtp::connect();
                    if(ftp_mkdir($ftp_conn, $path)){
                        if (defined('AK_FTP_DEFAULT_DIR_MOD')){
                            if(!ftp_site($ftp_conn, "CHMOD ".AK_FTP_DEFAULT_DIR_MOD." $path")){
                                trigger_error(Ak::t('Could not set default mode for the FTP created directory %path',array('%path',$path)), E_USER_NOTICE);
                            }
                        }
                    }else {
                        $ret = false;
                        break;
                    }
                }
            }
            return $ret;
        }
        return false;
    }

    function delete($path, $only_files = false)
    {
        $result = false;
        if($ftp_conn = AkFtp::connect()){
            $path = str_replace('\\','/',$path);
            $path = str_replace(array('..','./'),array('',''),$path);
            $keep_parent_dir = substr($path,-2) != '/*';
            $path = trim($path,'/*');
            $list = AK_FTP_SHOW_ERRORS ? ftp_rawlist ($ftp_conn, "-R $path") : @ftp_rawlist ($ftp_conn, "-R $path");
            $dirs = $keep_parent_dir ? array($path) : array();
            $files = array($path);
            $current_dir = $path.'/';
            if(count($list) === 1){
                $dirs = array();
                $files[] = $path;
            }else{
                foreach ($list as $k=>$line){
                    if(substr($line,-1) == ':'){
                        $current_dir = substr($line,0,strlen($line)-1).'/';
                    }
                    if (ereg ("([-d][rwxst-]+).* ([0-9]) ([a-zA-Z0-9]+).* ([a-zA-Z0-9]+).* ([0-9]*) ([a-zA-Z]+[0-9: ]*[0-9]) ([0-9]{2}:[0-9]{2}) (.+)", $line, $regs)){
                        if((substr ($regs[1],0,1) == "d")){
                            if($regs[8] != '.' && $regs[8] != '..'){
                                $dirs[] = $current_dir.$regs[8];
                            }
                        }else {
                            $files[] = $current_dir.$regs[8];
                        }
                    }
                }
            }
            if(count($files) >= 1){
                array_shift($files);
            }
            rsort($dirs);
            foreach ($files as $file){
                if(!$result = @ftp_delete($ftp_conn,$file)){
                    trigger_error(Ak::t('Could not delete FTP file %file_path',array('%file_path'=>$file)), E_USER_NOTICE);
                    return false;
                }
            }
            if(!$only_files){
                foreach ($dirs as $dir){
                    if(!$result = @ftp_rmdir($ftp_conn,$dir)){
                        trigger_error(Ak::t('Could not delete FTP directory %dir_path',array('%dir_path'=>$dir)), E_USER_NOTICE);
                        return false;
                    }
                }
            }
        }
        return $result;
    }


    function is_dir($path)
    {
        if($ftp_conn = AkFtp::connect()){
            $path = str_replace('\\','/',$path);
            $result = @ftp_chdir ($ftp_conn, $path);
            AkFtp::connect();
            return $result;
        }
        return false;
    }

    

}


?>
