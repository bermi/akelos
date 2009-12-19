<?php

class AkelosPanel_AkelosDashboardController extends AkelosPanelController
{
    public function index(){
        $this->base_dir          = AK_BASE_DIR;
        $this->akelos_dir       = AK_FRAMEWORK_DIR;
        $this->tasks_dir        = AK_TASKS_DIR;
        $this->has_configuration    = file_exists(AK_CONFIG_DIR.DS.'config.php');
        $this->has_routes           = file_exists(AK_CONFIG_DIR.DS.'routes.php');
        $this->has_database         = file_exists(AK_CONFIG_DIR.DS.'database.yml');
        $this->new_install          =   !$this->has_configuration ||
        !$this->has_routes;
        $this->environment          = AK_ENVIRONMENT;
        $this->memcached_on         = AkMemcache::isServerUp();
        $this->constants            = Ak::get_constants();
        $this->langs                = Ak::langs();
        $this->database_settings    = Ak::getSettings('database', false);

        $this->server_user                 = trim(AK_WIN ? `ECHO %USERNAME%` : `whoami`);

        $this->local_ips = AkConfig::getOption('local_ips', array('localhost','127.0.0.1','::1'));


        $paths = array(
        AK_APP_DIR.DS.'locales',
        );
        $this->invalid_permissions = array();
        foreach($paths as $path){
            if(is_dir($path) && !@file_put_contents($path.DS.'__test_file')){
                $this->invalid_permissions[] = $path;
            }else{
                @unlink($path.DS.'__test_file');
            }
        }

    }

    public function web_terminal(){
        $this->user                 = trim(AK_WIN ? `ECHO %USERNAME%` : `whoami`);
        if(defined('AK_ENABLE_TERMINAL_ON_DEV') && AK_ENABLE_TERMINAL_ON_DEV){
            $this->enabled = true;
            $cwd = empty($_SESSION['last_working_directory']) ? AK_BASE_DIR : $_SESSION['last_working_directory'];
            if (!empty($this->params['cmd'])){
                $result = `cd $cwd;{$this->params['cmd']};echo "----akelos-cmd----";pwd;`;
                list($response, $last_dir) = explode('----akelos-cmd----', $result);
                $_SESSION['last_working_directory'] = trim($last_dir);
                if($response){
                    $this->renderText(AkTextHelper::h($response));
                }else{
                    $this->renderText(AkTextHelper::h($this->t('Error or empty response while running: %command', array('%command' => $this->params['cmd']))));
                }
            }
        }else{
            if (!empty($this->params['cmd'])){
                $this->renderText($this->t('Terminal disabled.'));
            }
        }
    }
    
    public function docs () {
    }
    
    public function guide () {
        $this->layout = 'guides';
        $this->tab = 'docs';
        $this->docs_helper->docs_path = 'akelos'.DS.'guides';
        $this->guide = $this->docs_helper->get_doc_contents(
            empty($this->params['id']) ? 'getting_started' : $this->params['id']);
    }
}