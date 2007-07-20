<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// WARNING. This is experimental. We might replace this by Logger4PHP

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2006, Akelos Media, S.L.  & Bermi Ferrer Martinez |
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
 * @package ActiveSupport
 * @subpackage Reporting
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

require_once(AK_LIB_DIR.DS.'Ak.php');

defined('AK_LOG_DIR') ? null : define('AK_LOG_DIR', AK_BASE_DIR.DS.'log');

// Default mail logger settings
defined('AK_LOGER_DEFAULT_MAIL_DESTINATION')    ? null : define('AK_LOGER_DEFAULT_MAIL_DESTINATION', false);
defined('AK_LOGER_DEFAULT_MAIL_SENDER')         ? null : define('AK_LOGER_DEFAULT_MAIL_SENDER', AK_HOST);
defined('AK_LOGER_DEFAULT_MAIL_SUBJECT')        ? null : define('AK_LOGER_DEFAULT_MAIL_SUBJECT', 'Log message');

// Default file logger settings
defined('AK_LOGER_DEFAULT_LOG_FILE')            ? null : define('AK_LOGER_DEFAULT_LOG_FILE', AK_LOG_DIR.DS.AK_ENVIRONMENT.'.log');

// Loggin events for log types
defined('AK_LOGGER_DEBUG')      ? null : define('AK_LOGGER_DEBUG',      AK_MODE_FILE    | AK_MODE_DISPLAY);
defined('AK_LOGGER_INFO')       ? null : define('AK_LOGGER_INFO',       AK_MODE_DISPLAY);
defined('AK_LOGGER_MESSAGE')    ? null : define('AK_LOGGER_MESSAGE',    AK_MODE_FILE);
defined('AK_LOGGER_NOTICE')     ? null : define('AK_LOGGER_NOTICE',     AK_MODE_DISPLAY | AK_MODE_FILE | AK_MODE_DIE);
defined('AK_LOGGER_WARNING')    ? null : define('AK_LOGGER_WARNING',    AK_MODE_DISPLAY | AK_MODE_FILE | AK_MODE_DIE);
defined('AK_LOGGER_ERROR')      ? null : define('AK_LOGGER_ERROR',      AK_MODE_DISPLAY | AK_MODE_FILE | AK_MODE_DIE);
defined('AK_LOGGER_CRITICAL')   ? null : define('AK_LOGGER_CRITICAL',   AK_MODE_FILE    | AK_MODE_DIE);

// Error loggin settings
defined('AK_LOG_'.E_USER_ERROR)     ? null : define('AK_LOG_'.E_USER_ERROR, AK_MODE_FILE | AK_MODE_DIE);
defined('AK_LOG_'.E_USER_WARNING)   ? null : define('AK_LOG_'.E_USER_WARNING, AK_MODE_DISPLAY | AK_MODE_FILE | AK_MODE_DIE);
defined('AK_LOG_'.E_USER_NOTICE)    ? null : define('AK_LOG_'.E_USER_NOTICE, AK_MODE_DISPLAY | AK_MODE_FILE | AK_MODE_DIE);
defined('AK_LOG_'.E_WARNING)        ? null : define('AK_LOG_'.E_WARNING, AK_MODE_FILE);
defined('AK_LOG_'.E_NOTICE)         ? null : define('AK_LOG_'.E_NOTICE, AK_MODE_FILE);

defined('AK_LOG_ENABLE_COLORING') ? null : define('AK_LOG_ENABLE_COLORING', true);

class AkLogger
{
    var $_log_params                = array();
    var $print_display_message      = true;
    var $extended_details           = false;
    var $default_mail_destination   = AK_LOGER_DEFAULT_MAIL_DESTINATION;
    var $default_mail_sender        = AK_LOGER_DEFAULT_MAIL_SENDER;
    var $default_mail_subject       = AK_LOGER_DEFAULT_MAIL_SUBJECT;
    var $error_file                 = AK_LOGER_DEFAULT_LOG_FILE;
    var $log_type;

    function AkLogger($mode = AK_LOGGER_MESSAGE)
    {
        $this->default_log_settings = $mode;
    }

    function log($type, $message, $vars = array(), $event_code = null)
    {
        $type = strtoupper($type);
        $event_code = empty ($event_code) ? (defined('AK_LOGGER_'.$type) ? 'AK_LOGGER_'.$type : AK_LOGGER_INFO) : $event_code;
        $this->_log($type, $message, $vars, $event_code);
    }

    function debug($message, $vars = array(), $event_code = null)
    {
        $this->log(__FUNCTION__, $message, $vars, $event_code);
    }

    function info($message, $vars = array(), $event_code = null)
    {
        $this->log(__FUNCTION__, $message, $vars, $event_code);
    }

    function message($message, $vars = array(), $event_code = null)
    {
        $this->log(__FUNCTION__, $message, $vars, $event_code);
    }

    function notice($message, $vars = array(), $event_code = null)
    {
        $this->log(__FUNCTION__, $message, $vars, $event_code);
    }

    function warning($message, $vars = array(), $event_code = null)
    {
        $this->log(__FUNCTION__, $message, $vars, $event_code);
    }

    function error($message, $vars = array(), $event_code = null)
    {
        $this->log(__FUNCTION__, $message, $vars, $event_code);
    }

    function critical($message, $vars = array(), $event_code = null)
    {
        $this->log(__FUNCTION__, $message, $vars, $event_code);
    }

    function _log($error_mode, $error_message, $vars=array(), $event_code = null)
    {
        $this->setLogParams($vars);
        $this->mode = defined('AK_LOG_'.$error_mode) ? constant('AK_LOG_'.$error_mode) : $this->default_log_settings;
        $type = $this->log_type;
        $this->mode & AK_MODE_DISPLAY ? $this->_displayLog($type, $error_mode, $error_message) : null;
        $this->mode & AK_MODE_FILE ? $this->_appendLogToFile($type, $error_mode, $error_message) : null;
        $this->mode & AK_MODE_DATABASE ? $this->_saveLogInDatabase($type, $error_mode, $error_message) : null;
        $this->mode & AK_MODE_MAIL ? $this->_mailLog($type, $error_mode, $error_message) : null;
        $this->mode & AK_MODE_DIE ? exit : null;
    }

    function _displayLog($type, $error_mode, $error_message)
    {
        $message = $this->_getLogFormatedAsHtml($type, $error_mode, $error_message);
        if($this->print_display_message){
            Ak::trace($message);
        }
        return $message;
    }
    function _mailLog($type, $error_mode, $error_message)
    {
        if(!empty($this->default_mail_destination)){
            $message = $this->_getLogFormatedAsString($type, $error_mode, $error_message);
            $message = strip_tags(str_replace('<li>',' - ',$message));
            Ak::mail($this->default_mail_sender, $this->default_mail_destination, $this->default_mail_subject, $message);
        }
    }
    function _appendLogToFile($type, $error_mode, $error_message)
    {
        $filename = $this->error_file;
        if(!is_writable($filename)){
            clearstatcache();
            Ak::file_put_contents($filename,'');
            if(!is_writable($filename)){
                trigger_error($this->internalError($this->t('Error writing file: %filename Description:',array('%filename'=>$filename)).$error_message,__FILE__,__LINE__), E_USER_NOTICE);
                return ;
            }
        }

        $message = $this->_getLogFormatedAsString($type, $error_mode, $error_message);
        if(!$fp = fopen($filename, 'a')) {
            die($this->internalError($this->t('Cannot open file (%file)', array('%file'=>$filename)),__FILE__,__LINE__));
        }
        @flock($fp, LOCK_EX);
        if (@fwrite($fp, $message) === FALSE) {
            @flock ($fp, LOCK_UN);
            die($this->internalError($this->t('Error writing file: %filename Description:',array('%filename'=>$filename)).$error_message,__FILE__,__LINE__));
        }
        @flock ($fp, LOCK_UN);
        @fclose($fp);
    }

    function _saveLogInDatabase($type, $error_mode, $error_message)
    {
        $db =& Ak::db();
        $message = $this->_getLogFormatedAsRawText($type, $error_mode, $error_message);
        $sql = 'INSERT INTO log (user_id, type, message, severity, location, hostname, created) '.
        " VALUES (0, ".$db->qstr($type).", ".$db->qstr($message).', '.($this->mode & AK_MODE_DIE ? 100 : 0).', '.
        $db->qstr(AK_CURRENT_URL).', '.$db->qstr($_SERVER['REMOTE_ADDR']).', '.$db->qstr(Ak::getTimestamp()).');';
        if ($db->Execute($sql) === false) {
            die($this->internalError($this->t('Error inserting: ').$db->ErrorMsg(),__FILE__,__LINE__));
        }
    }

    function _getLogFormatedAsHtml($type, $error_mode, $error_message)
    {
        $error_type = $error_mode ? 'error' : 'info';
        $message = "\n<div id='logger_$error_type'>\n<p>".$this->t(ucfirst($error_type)).": [$error_mode] - $error_message</p>\n";
        $params = array_merge($this->_log_params, ($this->extended_details ? array('remote_address'=>$_SERVER['REMOTE_ADDR'], 'browser'=>$_SERVER['HTTP_USER_AGENT']) : array() ));
        $details = '';
        foreach ($params as $k=>$v){
            $details .= "<li><span>".$k.":</span> $v</li>\n";
        }
        return empty($details) ? $message.'</div>' : $message."<ul>\n$details\n</ul>\n</div>";
    }

    function _getLogFormatedAsString($type, $error_mode, $error_message, $serialized = false)
    {
        $message = date('r')."\t[$error_mode]\t$error_message";
        $params = array_merge($this->_log_params, ($this->extended_details ? array('remote_address'=>$_SERVER['REMOTE_ADDR'], 'browser'=>$_SERVER['HTTP_USER_AGENT']) : array() ));

        if($serialized){
            $message .= (count($params) ? "\t".serialize($params) : '');
        }else{
            $details = '';
            foreach ($params as $k=>$v){
                $details .= "\n\t\t- ".$k.": $v";
            }
            $message .= empty($details) ? "\n" : "\n\t".'PARAMS{'.$details."\t\n}\n";
        }
        return $message;
    }

    function _getLogFormatedAsRawText($type, $error_mode, $error_message)
    {
        return $this->_getLogFormatedAsString($type, $error_mode, $error_message, $filename, $line_number, true);
    }


    function setLogParams($log_params)
    {
        $this->_log_params = $log_params;
    }

    function getLogParams()
    {
        return is_array($this->_log_params) ? $this->_log_params : array();
    }


    function internalError($message, $file, $line)
    {
        return "<div id='internalError'><p><b>Error:</b> [internal] - $message<br /><b>File:</b> $file at line $line</p></div>";
    }

    function t($string, $array = null)
    {
        return Ak::t($string, $array, 'error');
    }

    function formatText($text, $color = 'normal')
    {
        if(!AK_LOG_ENABLE_COLORING){
            return $text;
        }
        
        $colors = array(
        'light_red '      => '[1;31m',
        'light_green'      => '[1;32m',
        'yellow'      => '[1;33m',
        'light_blue'      => '[1;34m',
        'magenta'      => '[1;35m',
        'light_cyan'      => '[1;36m',
        'white'      => '[1;37m',
        'normal'      => '[0m',
        'black'      => '[0;30m',
        'red'      => '[0;31m',
        'green'      => '[0;32m',
        'brown'      => '[0;33m',
        'blue'      => '[0;34m',
        'cyan'      => '[0;36m',
        'bold'      => '[1m',
        'underscore'      => '[4m',
        'reverse'      => '[7m'
        );

        return "\033".(isset($colors[$color]) ? $colors[$color] : '[0m').$text."\033[0m";
    }
}

?>