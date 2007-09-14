<?php

if(!defined('AK_ENVIRONMENT') || AK_ENVIRONMENT != 'setup'){
    die();
}

error_reporting(E_ALL);

define('AK_WEB_REQUEST_CONNECT_TO_DATABASE_ON_INSTANTIATE', false);

define('AK_URL_REWRITE_ENABLED', false);

define('AK_AVAILABLE_LOCALES','en,ja,es');
define('AK_APP_LOCALES','en,ja,es');

defined('DS') ? null : define('DS', DIRECTORY_SEPARATOR);
defined('AK_BASE_DIR') ? null : define('AK_BASE_DIR', str_replace(DS.'app'.DS.'controllers'.DS.'framework_setup_controller.php','',__FILE__));
defined('AK_CONFIG_DIR') ? null : define('AK_CONFIG_DIR', AK_BASE_DIR.DS.'config');
// defined('AK_FRAMEWORK_DIR') ? null : define('AK_FRAMEWORK_DIR', '/path/to/the/framework'); 
	
define('AK_AUTOMATICALLY_UPDATE_LANGUAGE_FILES', true);
define('AK_AUTOMATIC_CONFIG_VARS_ENCRYPTION', false);

define('AK_FTP_SHOW_ERRORS', false);

include_once(AK_CONFIG_DIR.DS.'boot.php');
require_once(AK_LIB_DIR.DS.'AkObject.php');
require_once(AK_LIB_DIR.DS.'AkInflector.php');
require_once(AK_LIB_DIR.DS.'Ak.php');
require_once(AK_LIB_DIR.DS.'AkActionController.php');

$_GET['controller'] = 'framework_setup';


class FrameworkSetupController extends AkActionController
{
    var $layout = 'page';

    function __construct()
    {
        parent::__construct();
        $this->beforeFilter('initFrameworkSetup');
    }

    function initFrameworkSetup()
    {
        if(isset($_SESSION['__framework_setup'])){
            $this->FrameworkSetup = unserialize($_SESSION['__framework_setup']);
        }else{
            $this->FrameworkSetup->setDefaultOptions();
        }
    }

    function __destruct()
    {
        if(isset($this->FrameworkSetup)){
			$_SESSION['__framework_setup'] = serialize($this->FrameworkSetup);
		}
        //Ak::debug($this->FrameworkSetup);  Ak::debug($this->params);
    }

    function index()
    {
        // We need to avoid infinite loop if mod rewrite is disabled
        if(strstr(@$this->params['ak'], 'url_rewrite_check')){
            die();
        }
    }

    function select_database()
    {
        $this->databases = $this->FrameworkSetup->getAvailableDatabases();
    }

    function set_database_details()
    {
        if($this->Request->isPost()){
            foreach (array('development','production','testing') as $mode){
                $this->FrameworkSetup->setDatabaseName(@$this->params[$mode.'_database_name'], $mode);

                if($this->FrameworkSetup->getDatabaseType($mode) != 'sqlite'){
                    $this->FrameworkSetup->setDatabaseHost(@$this->params[$mode.'_database_host'], $mode);
                    $this->FrameworkSetup->setDatabaseUser(@$this->params[$mode.'_database_user'], $mode);
                    $this->FrameworkSetup->setDatabasePassword(@$this->params[$mode.'_database_password'], $mode);
                }

                if(!$this->FrameworkSetup->databaseConnection($mode)){
                    $this->FrameworkSetup->setDatabaseAdminUser(@$this->params['admin_database_user']);
                    $this->FrameworkSetup->setDatabaseAdminPassword(@$this->params['admin_database_password']);
                    // if(!$this->FrameworkSetup->createDatabase($mode)){
                        $this->flash_now = $this->t('Could not connect to %database database',
                        array('%database'=>$this->FrameworkSetup->getDatabaseName($mode)));
                    // }
                }
            }

            if(empty($this->flash_now)){
                $this->redirectToAction('file_settings');
            }

            return ;
        }elseif(!empty($this->params['database_type'])){
            if(!$this->FrameworkSetup->setDatabaseType($this->params['database_type'])){
                $this->flash = $this->t('%database_type is not supported yet. '.
                ' You must select one of the following databases: mysql, pgsql or sqlite',
                array('%database_type'=>$this->params['database_type']));
                $this->redirectToAction('file_settings');
                return;
            }
            if(!$this->FrameworkSetup->isDatabaseDriverAvalible()){
                $this->flash = $this->t('Seems that your current PHP settings '.
                'do not support for %database_type databases',
                array('%database_type'=>$this->params['database_type']));
                $this->redirectToAction('file_settings');
                return ;
            }
        }

    }

    function file_settings()
    {
        if($this->FrameworkSetup->needsFtpFileHandling()){
            $this->redirectToAction('configure_ftp_details');
        }else{
            $this->redirectToAction('set_locales');
        }
    }


    function configure_ftp_details()
    {
        if($this->Request->isPost()){
            $this->FrameworkSetup->setFtpHost($this->params['ftp_host']);
            $this->FrameworkSetup->setFtpUser($this->params['ftp_user']);
            $this->FrameworkSetup->setFtpPassword($this->params['ftp_password']);
            $this->FrameworkSetup->setFtpPath($this->params['ftp_path']);

            if($this->FrameworkSetup->testFtpSettings()){
                $this->redirectToAction('set_locales');
                return ;
            }else{
                $this->flash_now = $this->t('Could not connect to selected ftp server');
                return ;
            }
        }

        if(!empty($this->params['check'])){
            if($this->FrameworkSetup->needsFtpFileHandling()){
                $this->flash_now = $this->t('Bad file permission. Please change file system privileges or set up a FTP account below');
            }else{
                $this->redirectToAction('set_locales');
                return ;
            }
        }

        if(!empty($this->params['skip'])){
            $this->redirectToAction('set_locales');
            return ;
        }
    }


    function set_locales()
    {
        if($this->Request->isPost()){
            if(!empty($this->params['locales'])){
                $this->FrameworkSetup->setLocales($this->params['locales']);
                $this->redirectToAction('perform_setup');

            }else{
                $this->flash_now = $this->t('You must supply at least one locale');
            }
        }
        $this->locales = $this->FrameworkSetup->getLocales();
    }




    function perform_setup()
    {
        $this->configuration_file = $this->FrameworkSetup->getConfigurationFile();
        if($this->FrameworkSetup->canWriteConfigurationFile()){
            if( $this->FrameworkSetup->writeConfigurationFile($this->configuration_file) &&
            $this->FrameworkSetup->writeRoutesFile() &&
            $this->FrameworkSetup->runFrameworkInstaller()){

                if($this->FrameworkSetup->hasUrlSuffix()){
                    $this->FrameworkSetup->modifyHtaccessFiles();
                }
                
                $this->FrameworkSetup->relativizeStylesheetPaths();
                $this->FrameworkSetup->removeSetupFiles();
                unset($_SESSION);
                $this->redirectTo(array('controller'=>'page'));
            }

        }
    }


    function url_rewrite_check()
    {
        $this->layout = false;
        $this->renderText('url_rewrite_working');
    }


}

require_once(AK_LIB_DIR.DS.'AkDispatcher.php');
$Dispatcher =& new AkDispatcher();
$Dispatcher->dispatch();

?>