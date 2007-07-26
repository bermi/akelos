<?php

require_once(AK_LIB_DIR.DS.'AkLogger.php');

class FrameworkSetup extends AkObject
{
    var $avaliable_databases = array(
    'mysql' => 'MySQL',
    'pgsql' => 'PostgreSQL',
    'sqlite' => 'SQLite'
    );
    var $available_locales = array('en', 'es');
    var $locales = array('en');

    var $stylesheets = array('scaffold','forms');

    function __construct()
    {
        if(file_exists(AK_CONFIG_DIR.DS.'config.php')){
            echo Ak::t('The framework_setup.php found that you already have a configuration file at config/config.php. You need to remove that file first in order to run the setup.', array(), 'framework_setup');
            die();
        }
    }



    /**
     * Will try to guess the best database on current server.
     * Per example, if current server has MySQL and PostgreSQL it will pick up
     * PostgreSQL if MySQL doens't support InnoDB tables.
     * 
     * If none of the above is
     *
     */
    function suggestDatabaseType()
    {
        /**
         * @todo add database check for postgre
         */
        return $this->_suggestMysql() ? 'mysql' : (function_exists('pg_connect') ? 'pgsql' : (AK_PHP5 ? 'sqlite' : 'mysql'));
    }

    function _suggestMysql()
    {
        if(function_exists('mysql_connect')){
            if($db = @mysql_connect(   $this->getDatabaseHost(),
            $this->getDatabaseUser(),
            $this->getDatabasePassword())){
                return true;
                /**
                 * @todo Checking if innodb is available break on some systems
                 * we should investigate a better way to do this
                 */
                /*
                $result = mysql_query($db, "SHOW VARIABLES LIKE 'have_innodb';");
                return strstr(
                strtolower(
                @array_pop(
                @mysql_fetch_row(
                @mysql_query("SHOW VARIABLES LIKE 'have_innodb';", $db)
                )
                )
                ),
                'yes');
                */
            }
        }
        return false;
    }

    function createDatabase($mode)
    {
        $success = true;

        $db = $this->databaseConnection('admin');

        if($db){
            if($this->getDatabaseType($mode) != 'sqlite'){
                $DataDict = NewDataDictionary($db);
                if($this->getDatabaseType($mode) == 'mysql'){
                    $success = $this->_createMysqlDatabase($db, $mode) ? $success : false;
                }
            }
            return $success;
        }
        return false;
    }

    function _createMysqlDatabase(&$db, $mode)
    {
        $success = true;
        $success = $db->Execute('CREATE DATABASE '.$this->getDatabaseName($mode)) ? $success : false;
        $success = $db->Execute("GRANT SELECT,INSERT,UPDATE,DELETE,CREATE,DROP,ALTER ON ".
        $this->getDatabaseName($mode).".* TO '".$this->getDatabaseUser($mode)."'@'".
        $this->getDatabaseHost($mode)."' IDENTIFIED BY '".$this->getDatabasePassword($mode)."'") ? $success : false;
        return $success;
    }

    function getDatabaseHost($mode = '')
    {
        return !isset($this->{$mode.'_database_host'}) ? $this->suggestDatabaseHost() : $this->{$mode.'_database_host'};
    }

    function getDatabaseUser($mode = '')
    {
        return !isset($this->{$mode.'_database_user'}) ? $this->suggestUserName() : $this->{$mode.'_database_user'};
    }

    function getDatabasePassword($mode = '')
    {
        return !isset($this->{$mode.'_database_password'}) ? '' : $this->{$mode.'_database_password'};
    }

    function getDatabaseType()
    {
        return !isset($this->database_type) ? $this->suggestDatabaseType() : $this->database_type;
    }

    function setDatabaseType($database_type)
    {
        $database_type = strtolower($database_type);
        if(!in_array($database_type, array_keys($this->avaliable_databases))){
            trigger_error(Ak::t('Selected database is not supported yet by the Akelos Framework',array(),'framework_setup'));
        }elseif(!$this->isDatabaseDriverAvalible($database_type)){
            trigger_error(Ak::t('Could not set %database_type as database type. Your current PHP settings do not support %database_type databases', array('%database_type '=>$database_type), 'framework_setup'));
        }else{
            $this->database_type = $database_type;
            return $this->database_type;
        }
        return false;
    }

    function getDatabaseName($mode)
    {
        return !isset($this->{$mode.'_database_name'}) ?
        $this->guessApplicationName().($mode=='development'?'_dev':($mode=='testing'?'_tests':'')) :
        $this->{$mode.'_database_name'};
    }

    function setDatabaseName($database_name, $mode)
    {
        $this->{$mode.'_database_name'} = $database_name;
    }

    function setDatabaseHost($host, $mode)
    {
        $this->{$mode.'_database_host'} = $host;
    }

    function setDatabaseUser($user, $mode)
    {
        $this->{$mode.'_database_user'} = $user;
    }

    function setDatabasePassword($password, $mode)
    {
        $this->{$mode.'_database_password'} = $password;
    }


    function getDatabaseAdminUser()
    {
        return !isset($this->admin_database_user) ? 'root' : $this->admin_database_user;
    }

    function getDatabaseAdminPassword()
    {
        return !isset($this->admin_database_password) ? '' : $this->admin_database_password;
    }


    function setDatabaseAdminUser($user)
    {
        $this->admin_database_user = $user;
    }

    function setDatabaseAdminPassword($password)
    {
        $this->admin_database_password = $password;
    }


    function databaseConnection($mode)
    {
        static $connections = array();
        require_once(AK_CONTRIB_DIR.DS.'adodb'.DS.'adodb.inc.php');

        $dsn = $this->_getDsn($mode);
        if(!isset($connections[$dsn])){
            if(!$connections[$dsn] = @NewADOConnection($dsn)){
                return false;
            }
        }
        return $connections[$dsn];
    }

    function _getDsn($mode)
    {
        if($mode == 'admin'){
            $db_type = $this->getDatabaseType('production');
            return $db_type.'://'.
            $this->getDatabaseAdminUser().':'.
            $this->getDatabaseAdminPassword().'@'.$this->getDatabaseHost('production').($db_type == 'mysql' ? '/mysql' : '');
        }else{
            return $this->getDatabaseType() == 'sqlite' ?
            "sqlite://".urlencode(AK_CONFIG_DIR.DS.'.ht-'.$this->getDatabaseName($mode).'.sqlite') :
            $this->getDatabaseType($mode)."://".$this->getDatabaseUser($mode).":".$this->getDatabasePassword($mode).
            "@".$this->getDatabaseHost($mode)."/".$this->getDatabaseName($mode);
        }
    }


    function getAvailableDatabases()
    {
        $databases = array();
        foreach ($this->avaliable_databases as $type=>$description){
            if($this->isDatabaseDriverAvalible($type)){
                $databases[] = array('type' => $type, 'name' => $description);
            }

        }
        return $databases;
    }


    function isDatabaseDriverAvalible($database_type = null)
    {
        $database_type = empty($database_type) ? $this->getDatabaseType() : $database_type;
        if(strstr($database_type,'mysql')){
            $function = 'mysql_connect';
        }elseif (strstr($database_type,'pg') || strstr($database_type,'gre')){
            $function = 'pg_connect';
        }elseif (strstr($database_type,'lite')){
            $function = 'sqlite_open';
        }else{
            $function = $database_type.'_connect';
        }
        return function_exists($function);
    }

    function runFrameworkInstaller()
    {
        require_once(AK_LIB_DIR.DS.'AkInstaller.php');
        require_once(AK_APP_DIR.DS.'installers'.DS.'framework_installer.php');

        foreach (array('development', 'production', 'testing') as $mode){
            $db_conn = Ak::db($this->_getDsn($mode));
            $installer = new FrameworkInstaller($db_conn);
            $installer->install();
        }

        return true;
    }




    function getUrlSuffix()
    {
        return empty($this->url_suffix) ? AK_SITE_URL_SUFFIX : $this->url_suffix;
    }


    function getConfigurationFile($settings = array())
    {


        $configuration_template = <<<CONFIG
<?php

\$database_settings = array(
    'production' => array(
        'type' => '%production_database_type', // mysql, sqlite or pgsql
        'database_file' => '%production_database_file', // you only need this for SQLite
        'host' => '%production_database_host',
        'port' => '',
        'database_name' => '%production_database_name',
        'user' => '%production_database_user',
        'password' => '%production_database_password',
        'options' => '' // persistent, debug, fetchmode, new
    ),
    
    'development' => array(
        'type' => '%development_database_type',
        'database_file' => '%development_database_file',
        'host' => '%development_database_host',
        'port' => '',
        'database_name' => '%development_database_name',
        'user' => '%development_database_user',
        'password' => '%development_database_password',
        'options' => ''
    ),
    
    // Warning: The database defined as 'testing' will be erased and
    // re-generated from your development database when you run './script/test app'.
    // Do not set this db to the same as development or production.
    'testing' => array(
        'type' => '%testing_database_type',
        'database_file' => '%testing_database_file',
        'host' => '%testing_database_host',
        'port' => '',
        'database_name' => '%testing_database_name',
        'user' => '%testing_database_user',
        'password' => '%testing_database_password',
        'options' => ''
    )
);

// If you want to write/delete/create files or directories using ftp instead of local file
// access, you can set an ftp connection string like:
// \$ftp_settings = 'ftp://username:password@example.com/path/to_your/base/dir';
\$ftp_settings = '%ftp_settings'; 

 // Current environment. Options are: development, testing and production
defined('AK_ENVIRONMENT') ? null : define('AK_ENVIRONMENT', 'development');



// Locale settings ( you must create a file at /config/locales/ using en.php as departure point)
// Please be aware that your charset needs to be UTF-8 in order to edit the locales files
// auto will enable all the locales at config/locales/ dir
define('AK_AVAILABLE_LOCALES', '%locales');

// Use this in order to allow only these locales on web requests
define('AK_ACTIVE_RECORD_DEFAULT_LOCALES', '%locales');
define('AK_APP_LOCALES', '%locales');
define('AK_PUBLIC_LOCALES', '%locales');

%AK_FRAMEWORK_DIR

%AK_ASSET_URL_PREFIX

include_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'boot.php');


?>
CONFIG;
        if(empty($settings)){
            $settings = array();
            foreach (array('production','development','testing') as $mode){
                $settings['%'.$mode.'_database_type'] = $this->getDatabaseType($mode);
                if($settings['%'.$mode.'_database_type'] == 'sqlite'){

                    $settings['%'.$mode.'_database_file'] = AK_CONFIG_DIR.DS.'.ht-'.$this->getDatabaseName($mode).'.sqlite';
                    $settings['%'.$mode.'_database_user'] =
                    $settings['%'.$mode.'_database_password'] =
                    $settings['%'.$mode.'_database_host'] =
                    $settings['%'.$mode.'_database_name'] = '';
                }else{
                    $settings['%'.$mode.'_database_user'] = $this->getDatabaseUser($mode);
                    $settings['%'.$mode.'_database_password'] = $this->getDatabasePassword($mode);
                    $settings['%'.$mode.'_database_host'] = $this->getDatabaseHost($mode);
                    $settings['%'.$mode.'_database_name'] = $this->getDatabaseName($mode);
                    $settings['%'.$mode.'_database_file'] = '';
                }
            }

            $settings['%ftp_settings'] = isset($this->ftp_enabled) ? 'ftp://'.$this->getFtpUser().':'.$this->getFtpPassword().'@'.$this->getFtpHost().$this->getFtpPath() : '';


            $settings['%locales'] = $this->getLocales();
            $settings['%AK_FRAMEWORK_DIR'] = defined('AK_FRAMEWORK_DIR') ?
            "defined('AK_FRAMEWORK_DIR') ? null : define('AK_FRAMEWORK_DIR', '".AK_FRAMEWORK_DIR."');" : '';


            $asset_path = $this->_getAssetBasePath();
            if(!empty($asset_path)){
                $settings['%AK_ASSET_URL_PREFIX'] = "define('AK_ASSET_URL_PREFIX','/".trim($this->getUrlSuffix(),'/').'/'.$asset_path."');";
            }else{
                $settings['%AK_ASSET_URL_PREFIX'] = '';
            }

        }

        return str_replace(array_keys($settings), array_values($settings), $configuration_template);

    }

    function writeConfigurationFile($configuration_details)
    {
        if($this->canWriteConfigurationFile()){
            return Ak::file_put_contents(AK_CONFIG_DIR.DS.'config.php', $configuration_details);
        }
        return false;
    }

    function canWriteConfigurationFile()
    {
        if(isset($this->ftp_enabled)){
            $this->testFtpSettings();
        }
        $file_path = AK_CONFIG_DIR.DS.'config.php';
        return !file_exists($file_path);
    }

    function writeRoutesFile()
    {
        if(isset($this->ftp_enabled)){
            $this->testFtpSettings();
        }
        $file_path = AK_CONFIG_DIR.DS.'routes.php';
        if(!file_exists($file_path)){
            return Ak::file_put_contents($file_path, file_get_contents(AK_CONFIG_DIR.DS.'DEFAULT-routes.php'));
        }
        return false;
    }

    function modifyHtaccessFiles()
    {
        if($this->isUrlRewriteEnabled()){
            return true;
        }

        if(isset($this->ftp_enabled)){
            $this->testFtpSettings();
        }
        $file_1 = AK_BASE_DIR.DS.'.htaccess';
        $file_2 = AK_PUBLIC_DIR.DS.'.htaccess';
        $file_1_content = @Ak::file_get_contents($file_1);
        $file_2_content = @Ak::file_get_contents($file_2);

        $url_suffix = $this->getUrlSuffix();

        $url_suffix = $url_suffix[0] != '/' ? '/'.$url_suffix : $url_suffix;

        empty($file_1_content) ? null : @Ak::file_put_contents($file_1, str_replace('# RewriteBase /framework',' RewriteBase '.$url_suffix, $file_1_content));
        empty($file_2_content) ? null : @Ak::file_put_contents($file_2, str_replace('# RewriteBase /framework',' RewriteBase '.$url_suffix, $file_2_content));
    }

    function isUrlRewriteEnabled()
    {
        return @file_get_contents(AK_SITE_URL.'/framework_setup/url_rewrite_check') == 'url_rewrite_working';
    }

    function getApplicationName()
    {
        if(!isset($this->application_name)){
            $this->setApplicationName($this->guessApplicationName());
        }
        return $this->application_name;
    }


    function setApplicationName($application_name)
    {
        $this->application_name = $application_name;
    }

    function guessApplicationName()
    {
        $application_name = array_pop(explode('/',AK_SITE_URL_SUFFIX));
        $application_name = empty($application_name) ? substr(AK_BASE_DIR, strrpos(AK_BASE_DIR, DS)+1) : $application_name;
        return empty($application_name) ? 'my_app' : $application_name;
    }

    function needsFtpFileHandling()
    {
        return !$this->_writeToTemporaryFile(AK_CONFIG_DIR.DS.'test_file.txt');
    }

    function _writeToTemporaryFile($file_path, $content = '', $mode = 'a+')
    {
        $result = false;
        if(strstr($file_path, AK_BASE_DIR)){
            if(!$fp = @fopen($file_path, $mode)) {
                return false;
            }
            $this->_temporaryFilesCleanUp($file_path);
            $result = @fwrite($fp, $content);
            if (false !== $result){
                $result = true;
            }
            @fclose($fp);
        }
        return $result;
    }

    function _temporaryFilesCleanUp($file_path = null)
    {
        static $file_paths = array();
        if($file_path == null && count($file_paths) > 0){
            foreach ($file_paths as $file_path){
                // we try to prevent removing nothing outside the framework
                if(strstr($file_path, AK_BASE_DIR)){
                    @unlink($file_path);
                }
            }
            return ;
        }elseif (!empty($file_path) &&  count($file_paths) == 0){
            register_shutdown_function(array($this, '_temporaryFilesCleanUp'));
        }
        $file_paths[$file_path] = $file_path;
    }

    function addSetupOptions($options = array())
    {
        $options = array_merge($this->getDefaultOptions(), $options);

        if(!$this->isDatabaseDriverAvalible($options['database']['type'])){
            $this->addError(Ak::t('Your current PHP settings do not have support for %database_type databases.',
            array('%database_type'=>$options['database']['type']),'framework_setup'));
        }elseif(!$db = $this->databaseConnection(
        $options['database']['type'],
        $options['database']['host'],
        $options['database']['user'],
        $options['database']['password'],
        $options['database']['name'])){
            $this->addError(Ak::t('Could not connect to the database using %details', array(), 'framework_setup'),
            array('%details'=>var_dump($options['database'])));
        }

        $options['server']['locales'] = str_replace(' ','', $options['server']['locales']);
        $options['server']['locales']  = empty($options['server']['locales']) ? 'en' : $options['server']['locales'];

        foreach ($options as $group=>$details){
            if(!is_array($details)){
                continue;
            }
            foreach ($details as $detail=>$value){
                $this->{$group.'_'.$detail} = $value;
            }
        }

        $this->options = $options;
    }

    function getSetupOptions()
    {
        return isset($this->options) ? $this->options : array();
    }

    function addError($error)
    {
        $this->errors[] = $error;
    }

    function getErrors()
    {
        return $this->errors;
    }

    function getDefaultOptions()
    {
        return array(
        'production_database_type'=> $this->getDatabaseType('production'),
        'production_database_host'=> $this->getDatabaseHost('production'),
        'production_database_name'=> $this->getDatabaseName('production'),
        'production_database_user'=> $this->getDatabaseUser('production'),
        'production_database_password'=> '',

        'development_database_type'=> $this->getDatabaseType('development'),
        'development_database_host'=> $this->getDatabaseHost('development'),
        'development_database_name'=> $this->getDatabaseName('development'),
        'development_database_user'=> $this->getDatabaseUser('development'),
        'development_database_password'=> '',

        'testing_database_type'=> $this->getDatabaseType('testing'),
        'testing_database_host'=> $this->getDatabaseHost('testing'),
        'testing_database_name'=> $this->getDatabaseName('testing'),
        'testing_database_user'=> $this->getDatabaseUser('testing'),
        'testing_database_password'=> '',

        'admin_database_user' => $this->getDatabaseAdminUser(),
        'admin_database_password' => $this->getDatabaseAdminPassword(),

        'url_suffix'=> trim(AK_SITE_URL_SUFFIX, '/'),
        'locales'=> join(',',$this->suggestLocales()),
        'ftp_user' => $this->getFtpUser(),
        'ftp_host' => $this->getFtpHost(),
        'ftp_path' => $this->getFtpPath()
        );
    }

    function canUseFtpFileHandling()
    {
        return function_exists('ftp_connect');
    }

    function getFtpHost()
    {
        if(!isset($this->ftp_host)){
            return array_shift(explode('/', str_replace(array('http://','https://','www.'),array('','','ftp.'), AK_SITE_URL).'/'));
        }
        return $this->ftp_host;
    }

    function setFtpHost($ftp_host)
    {
        $this->ftp_host = trim($ftp_host, '/');
    }

    function getFtpPath()
    {
        if(!isset($this->ftp_path)){
            return '/'.trim(join('/',array_slice(
            explode('/',
            str_replace(array('http://','https://'),'', AK_SITE_URL).'/'),1)
            ),'/');
        }
        return $this->ftp_path;
    }

    function setFtpPath($ftp_path)
    {
        $this->ftp_path = empty($ftp_path) ? '' : '/'.trim($ftp_path,'/');
    }

    function getFtpUser()
    {
        return !isset($this->ftp_user) ? $this->suggestUserName() : $this->ftp_user;
    }

    function setFtpUser($ftp_user)
    {
        $this->ftp_user = $ftp_user;
    }

    function getFtpPassword()
    {
        return !isset($this->ftp_password) ? '' : $this->ftp_password;
    }

    function setFtpPassword($ftp_password)
    {
        $this->ftp_password = $ftp_password;
    }


    function setDefaultOptions()
    {
        foreach ($this->getDefaultOptions() as $k=>$v){
            $this->$k = $v;
        }
    }

    function hasUrlSuffix()
    {
        return !empty($this->url_suffix) && trim($this->url_suffix,'/') != '';
    }

    function suggestUserName()
    {
        if(AK_OS === 'WINDOWS'){
            return 'root';
        }
        $script_owner = get_current_user();
        return  $script_owner == '' ? 'root' : $script_owner;
    }


    function testFtpSettings()
    {
        if(!$this->canUseFtpFileHandling()){
            return false;
        }

        $ftp_path = 'ftp://'.$this->getFtpUser().':'.$this->getFtpPassword().'@'.
        $this->getFtpHost().$this->getFtpPath();

        @define('AK_UPLOAD_FILES_USING_FTP', true);
        @define('AK_READ_FILES_USING_FTP', false);
        @define('AK_DELETE_FILES_USING_FTP', true);
        @define('AK_FTP_PATH', $ftp_path);
        @define('AK_FTP_AUTO_DISCONNECT', true);

        if(@Ak::file_put_contents(AK_CONFIG_DIR.DS.'test_file.txt','hello from ftp')){
            $text = @Ak::file_get_contents(AK_CONFIG_DIR.DS.'test_file.txt');
            @Ak::file_delete(AK_CONFIG_DIR.DS.'test_file.txt');
        }

        $this->ftp_enabled = (isset($text) && $text == 'hello from ftp');

        return $this->ftp_enabled;
    }

    function getLocales()
    {
        return join(',',!isset($this->locales) ? $this->suggestLocales() : $this->_getLocales($this->locales));
    }

    function setLocales($locales)
    {
        $this->locales = $this->_getLocales($locales);
    }

    function _getLocales($locales)
    {
        return array_map('trim',array_unique(array_diff((is_array($locales) ? $locales : explode(',',$locales.',')), array(''))));
    }

    function suggestLocales()
    {
        require_once(AK_LIB_DIR.DS.'AkLocaleManager.php');

        $LocaleManager = new AkLocaleManager();

        $langs = array('en');
        if(AK_OS === 'WINDOWS'){
            $langs[] = @$_ENV['LANG'];
        }
        $langs = array_merge($langs, $LocaleManager->getBrowserLanguages());

        return array_unique(array_map('strtolower',array_diff($langs,array(''))));
    }

    function suggestDatabaseHost()
    {
        return 'localhost';
    }

    function relativizeStylesheetPaths()
    {
        if($this->hasUrlSuffix()){
            $url_suffix = trim($this->getUrlSuffix(),'/');
            $asset_path = $this->_getAssetBasePath();
            if(!empty($asset_path)){
                $url_suffix = $asset_path.'/'.$url_suffix;
            }
            foreach ($this->stylesheets as $stylesheet) {
                $filename = AK_PUBLIC_DIR.DS.'stylesheets'.DS.$stylesheet.'.css';
                $relativized_css = preg_replace("/url\((\'|\")?\/images/","url($1/$url_suffix/images", @Ak::file_get_contents($filename));
                empty($relativized_css) ? null : @Ak::file_put_contents($filename, $relativized_css);
            }
        }

    }

    function _getAssetBasePath()
    {
        return file_exists(AK_BASE_DIR.DS.'index.php') ? 'public' : '';
    }

    function removeSetupFiles()
    {
        @array_map(array('Ak','file_delete'),  array(
        AK_APP_DIR.DS.'installers'.DS.'database_installer.php',
        AK_APP_DIR.DS.'installers'.DS.'framework_installer.php',
        AK_APP_DIR.DS.'installers'.DS.'database_version.txt',
        AK_APP_DIR.DS.'installers'.DS.'framework_version.txt',
        AK_APP_DIR.DS.'models'.DS.'framework_setup.php',
        AK_APP_DIR.DS.'controllers'.DS.'framework_setup_controller.php',
        AK_APP_DIR.DS.'views'.DS.'framework_setup',
        AK_APP_DIR.DS.'locales'.DS.'framework_setup'
        ));
    }

}

?>