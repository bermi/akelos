<?php

/**
 * Loggin in your Akelos applications
 * 
 * Akelos provides a flexible loggin system wich includes five loggin levels
 * and four log handlers.
 * 
 * In order to log events you'll have to define in your configuration file:
 *
 *    define('AK_LOG_EVENTS', true);
 *
 * Then you can get a singleton copy of a logger prepared for current
 * environment by calling: 
 *
 *     Ak::getLogger();
 * 
 * wich will log to ./log/development.log if the environment id development.
 *
 * You can get loggers under a different namespace by calling:
 *
 *     Ak::getLogger('my_app');
 *
 * and the output for that logger will be saved in:
 *
 *     ./log/my_app.log
 *
 * Once you have an instance of your logger you can emit these events:
 *
 *  * debug => file
 *  * info  => file
 *  * warn  => file, display,
 *  * error => file, display, mail,
 *  * fatal => file, display, mail, fatal
 *
 * All error levels have the same interface
 *
 *     Ak::getLogger()->debug('Debuggin foo', optional_array)
 * 
 * where +optional_array+ will be converted into json and added to the log
 *
 *
 * You can customize handlers for each level by setting the
 * +handlers_for_levels+ option in your configuration like:
 *
 *     AkConfig::setOption('handlers_for_levels',= array(
 *        'debug' => array('file'),
 *        'info'  => array('file'),
 *        'warn'  => array('file', 'display'),
 *        'error' => array('file', 'display', 'mail'),
 *        'fatal' => array('file', 'display', 'mail', 'fatal'),
 *        ));
 *
 * ## Log handlers:
 * 
 * ### file
 *  
 * Files are logged by default to ./log (AK_LOG_DIR) and upon the
 * environment, so while you're on developing your app you can find it at:
 *
 *     ./log/development.log
 *  
 * No file will be written into the log directory if the user does not have
 * the right set of permissions.
 * 
 *  ### display 
 * 
 * Messages will be rendered as html and will show the file and line where the
 * log was requested.
 * 
 * The display handler is disabled on production.
 *
 * ### mail
 * 
 * You can get a mail of critical issues when not running on testing mode.
 * 
 * You'll have to define an address where events will be received:
 *
 *     define('AK_LOGER_DEFAULT_MAIL_DESTINATION', 'dev@example.com');
 *
 * ## fatal
 *
 *     will run exit(0);
 *
 * 
 * # Customizing the way the logger work
 *
 * You can customize init options for each environment or namespace by
 * setting:
 *
 *      AkConfig::setOption('production_logger_options', array(
 *              'mail_destination' => 'warnings@example.com',
 *              ));
 *
 * If you want to write your own handler, you can use set it like:
 *
 *      AkConfig::setOption('production_logger', 'MyCustomLogger');
 *
 * or
 *
 *      AkConfig::setOption('production_logger', $LoggerInstance);
 *
 * If you want to use your custom logger for all environments, use:
 * 
 *     AkConfig::setOption('logger', 'MyCustomLogger');
 *
 * Your custom loggers need to implement AkLoggerInterface
 *
 *
 * 
 * ## Rotating your logs
 * 
 * Akelos does not provide a mechanism to rotate file logs.
 * 
 * Anyhow, you can rotate logs by adding to your +logrotate+ configutation 
 * 
 *     /etc/logrotate.conf
 * 
 *     /path/to/your/akelos/applicaton/log/*.log {
 *        daily
 *        missingok
 *        rotate 7
 *        compress
 *        delaycompress
 *        notifempty
 *        copytruncate
 *      }
 */

defined('AK_LOG_DIR')                || define('AK_LOG_DIR', (defined('MAKELOS_BASE_DIR') ? MAKELOS_BASE_DIR : AK_BASE_DIR).DS.'log');
defined('AK_LOGER_DEFAULT_LOG_FILE') || define('AK_LOGER_DEFAULT_LOG_FILE', AK_LOG_DIR.DS.AK_ENVIRONMENT.'.log');

defined('AK_LOG_LEVEL')     || define('AK_LOG_LEVEL', AK_PRODUCTION_MODE ? 'info,warn,error,fatal' : 'debug,info,warn,error,fatal');
defined('AK_LOG_HANDLERS')  || define('AK_LOG_HANDLERS', 'file,display,mail,fatal');

// Default mail logger settings
defined('AK_LOGER_DEFAULT_MAIL_DESTINATION') || define('AK_LOGER_DEFAULT_MAIL_DESTINATION', false);
defined('AK_LOGER_DEFAULT_MAIL_SENDER')      || define('AK_LOGER_DEFAULT_MAIL_SENDER',   AK_HOST);
defined('AK_LOGER_DEFAULT_MAIL_SUBJECT')     || define('AK_LOGER_DEFAULT_MAIL_SUBJECT', '[%error_level %namespace] '.AK_APP_NAME.' logger');

defined('AK_LOG_ENABLE_COLORING') || define('AK_LOG_ENABLE_COLORING', true);

interface AkLoggerInterface
{
    public function debug($message, $parameters = array());
    public function info($message, $parameters = array());
    public function warn($message, $parameters = array());
    public function error($message, $parameters = array());
    public function fatal($message, $parameters = array());
}

class AkLogger implements AkLoggerInterface
{
    public $options = array(
    'namespace'         => AK_ENVIRONMENT,
    'log_level'         => AK_LOG_LEVEL,
    'log_handlers'      => AK_LOG_HANDLERS,
    'print'             => AK_DEV_MODE,

    // By default no mails are sent unless AK_LOGER_DEFAULT_MAIL_DESTINATION
    // is defined
    'mail_destination'  => AK_LOGER_DEFAULT_MAIL_DESTINATION,
    'mail_sender'       => AK_LOGER_DEFAULT_MAIL_SENDER,
    'mail_subject'      => AK_LOGER_DEFAULT_MAIL_SUBJECT,
    );


    // Log levels
    protected $_log_levels = array(
    'debug' => 1,
    'info'  => 2,
    'warn'  => 4,
    'error' => 8,
    'fatal' => 16
    );

    // Log handlers
    protected $_log_handlers = array(
    'file'      => 1,
    'display'   => 2,
    'mail'      => 4,
    'fatal'     => 8
    );

    protected $_handlers_for_levels = array(
    'debug' => array('file'),
    'info'  => array('file'),
    'warn'  => array('file', 'display'),
    'error' => array('file', 'display', 'mail'),
    'fatal' => array('file', 'display', 'mail', 'fatal'),
    );

    public $default_mail_destination   = AK_LOGER_DEFAULT_MAIL_DESTINATION;
    public $default_mail_sender        = AK_LOGER_DEFAULT_MAIL_SENDER;
    public $default_mail_subject       = AK_LOGER_DEFAULT_MAIL_SUBJECT;

    public $error_file                 = AK_LOGER_DEFAULT_LOG_FILE;
    public $log_type;

    private $_log_level;
    private $_handling_mode;
    private $_level_handlers = array();

    public function __construct($options = array()) {
        $this->init($options);
    }

    public function init($options = array()){
        $this->setOptions($options);
        $this->setLogLevels($this->options['log_level']);
        $this->setLogHandlers($this->options['log_handlers']);
        $this->setNamespace($this->options['namespace']);
        $this->setupHandlersForLevels($this->options['handlers_for_levels']);
    }

    public function setOptions($options = array()){
        $default_options = array(
        'namespace'             => AK_ENVIRONMENT,
        'log_level'             => Ak::toArray(AkConfig::getOption('log_level', AK_LOG_LEVEL)),
        'log_handlers'          => Ak::toArray(AkConfig::getOption('log_handlers', AK_LOG_HANDLERS)),
        'handlers_for_levels'   => Ak::toArray(AkConfig::getOption('handlers_for_levels', $this->_handlers_for_levels)),
        'log_handling_methods'  => AkConfig::getOption('log_handling_methods', $this->getLogHandlingMethods($this->_log_handlers)),
        'print'                 => !AK_PRODUCTION_MODE,
        'send_mails'            => !AK_TEST_MODE,
        'mail_destination'      => AK_LOGER_DEFAULT_MAIL_DESTINATION,
        'mail_sender'           => AK_LOGER_DEFAULT_MAIL_SENDER,
        'mail_subject'          => AK_LOGER_DEFAULT_MAIL_SUBJECT,
        );
        $this->options = array_merge($default_options, $options);
    }

    public function debug($message, $parameters = array()) {
        $this->_log(__FUNCTION__, $message, $parameters);
    }

    public function info($message, $parameters = array()) {
        $this->_log(__FUNCTION__, $message, $parameters);
    }

    public function warn($message, $parameters = array()) {
        $this->_log(__FUNCTION__, $message, $parameters);
    }

    public function error($message, $parameters = array()) {
        $this->_log(__FUNCTION__, $message, $parameters);
    }

    public function fatal($message, $parameters = array()) {
        $this->_log(__FUNCTION__, $message, $parameters);
    }

    public function _log($error_level, $error_message, $parameters = array()) {
        if(isset($this->_log_levels[$error_level]) && !($this->_log_levels[$error_level] & $this->_log_level)){
            return;
        }

        foreach ($this->_log_handlers as $handler => $mode){
            if($this->_handling_mode & $mode){
                if(($this->_level_handlers[$error_level] & $mode) && isset($this->options['log_handling_methods'][$handler])){
                    $this->{$this->options['log_handling_methods'][$handler]}($error_level, $error_message, $parameters);
                }
            }
        }
    }

    public function setNamespace($namespace){
        $this->options['namespace'] = $namespace;
        $this->setErrorFileForNamespace($namespace);
    }

    public function setErrorFileForNamespace($namespace){
        $file_name = AkConfig::getDir('log').DS.$namespace.'.log';
        if(!is_file($file_name)){
            Ak::file_put_contents($file_name, '', array('base_path' => AkConfig::getDir('log')));
        }
        $this->error_file = $file_name;
    }

    public function setLogLevels($levels){
        $log_level = 0;
        foreach ($levels as $level){
            if(isset($this->_log_levels[$level])){
                $log_level += $this->_log_levels[$level];
            }
        }
        $this->options['log_level'] = $log_level;
        $this->_log_level = $log_level;
    }

    public function setLogHandlers($handlers){
        $handling_mode = 0;
        foreach ($handlers as $handler){
            if(isset($this->_log_handlers[$handler])){
                $handling_mode += $this->_log_handlers[$handler];
            }
        }
        $this->options['handling_mode'] = $handling_mode;
        $this->_handling_mode = $handling_mode;
    }

    public function setupHandlersForLevels($options){
        $options = array_merge($this->_handlers_for_levels, $options);
        foreach ($options as $level => $handlers){
            $this->_level_handlers[$level] = 0;
            $handlers = Ak::toArray($handlers);
            foreach ($handlers as $handler){
                $this->_level_handlers[$level] += $this->_log_handlers[$handler];
            }
        }
    }

    public function getLogHandlingMethods($log_handlers = array()){
        $methods = array();
        foreach ($log_handlers as $handler => $mode){
            $method_name = 'handle'.AkInflector::camelize($handler).'Message';
            if(method_exists($this, $method_name)){
                $methods[$handler] = $method_name;
            }
        }
        return $methods;
    }

    public function handleFileMessage($error_level, $message, $parameters = array()){
        $filename = $this->error_file;
        if(!is_writable($filename)){
            return;
        }
        $message = $this->getMessageFormatedAsString($error_level, $message, $parameters);
        if(!$fp = fopen($filename, 'a')) {
            trigger_error('Cannot start logging to file '.$filename, E_USER_NOTICE);
        }
        flock($fp, LOCK_EX);
        if (fwrite($fp, $message) === FALSE) {
            flock ($fp, LOCK_UN);
            trigger_error('Error writing file: '.$filename, E_USER_NOTICE);
        }
        flock ($fp, LOCK_UN);
        fclose($fp);
    }

    public function handleDisplayMessage($error_level, $message, $parameters = array()){
        if(!empty($this->options['print'])){
            list($file,$line,$method) = Ak::getLastFileAndLineAndMethod(false, 3);
            Ak::trace("<strong>[$error_level]</strong> - ".AkTextHelper::h($message), $line, $file, $method, false);
            if(!empty($parameters)) {
                Ak::trace($parameters, $line, $file, $method);
            }
        }
    }

    public function handleMailMessage($error_level, $message, $parameters = array()){
        if(!empty($this->options['send_mails']) && !empty($this->options['mail_destination'])){
            $message = $this->getMessageFormatedAsString($error_level, $message, $parameters, true);
            $subject = str_replace(array('%error_level','%namespace'), array($error_level, $this->options['namespace']), $this->options['mail_subject']);
            Ak::mail($this->options['mail_sender'], $this->options['mail_destination'], $subject, $message);
        }
    }

    public function handleFatalMessage($error_level, $message, $parameters = array()){
        exit(0);
    }

    public function getMessageFormatedAsString($error_level, $error_message, $parameters = array(), $prevent_color = false) {
        $flag = $prevent_color ? $error_level : $this->getFlagForError($error_level);
        $message = date('r')."\t[$flag]\t$error_message";
        if(!empty($parameters)){
            $message .= "\n". json_encode($parameters)."\n";
        }
        return $message."\n";
    }

    public function formatText($text, $style = 'normal') {
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

        return "\033".(isset($colors[$style]) ? $colors[$style] : '[0m').$text."\033[0m";
    }

    public function getFlagForError($error_type){
        $colors = array(
        'debug'   => 'magenta',
        'info'    => 'cyan',
        'warn'    => 'blue',
        'error'   => 'red',
        'fatal'   => 'red',
        );
        return $this->formatText($error_type, isset($colors[$error_type]) ? $colors[$error_type] : 'normal');
    }

    /**
     * @deprecated Alias for info
     */
    public function message($message, $parameters = array()) {
        $this->info($message, $parameters);
    }
    /**
     * @deprecated Alias for info
     */
    public function notice($message, $parameters = array()) {
        Ak::deprecateMethod(__METHOD__, __CLASS__.'->info()');
        $this->info($message, $parameters);
    }

    /**
     * @deprecated Alias for info
     */
    public function log($log_level, $message, $parameters = array()) {
        Ak::deprecateMethod(__METHOD__, __CLASS__.'->info()');
        $this->_log($log_level, $message, $parameters);
    }
}

