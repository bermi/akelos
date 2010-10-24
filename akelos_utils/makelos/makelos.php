<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

defined('DS') || define('DS', DIRECTORY_SEPARATOR);

if(!defined('AK_BASE_DIR')){
    $__ak_base_dir = array_slice(get_included_files(),-2,1);
    $__ak_base_dir = dirname($__ak_base_dir[0]);
    if(is_dir($__ak_base_dir.DS.'app_layout')){
        defined('AK_FRAMEWORK_DIR') || define('AK_FRAMEWORK_DIR', $__ak_base_dir);
        defined('AK_TEST_DIR')      || define('AK_TEST_DIR', AK_FRAMEWORK_DIR.DS.'test');
        defined('AK_CORE_DIR')      || define('AK_CORE_DIR', AK_FRAMEWORK_DIR);
        define('AK_SKIP_ENV_CONFIG', false);
        $__ak_base_dir .= DS.'app_layout';
    }
    define('AK_BASE_DIR', $__ak_base_dir);
    unset($__ak_base_dir);
    defined('AK_SKIP_ENV_CONFIG') || define('AK_SKIP_ENV_CONFIG', true);
}

defined('MAKELOS_BASE_DIR') || define('MAKELOS_BASE_DIR', AK_BASE_DIR);
defined('MAKELOS_RUN')      || define('MAKELOS_RUN', preg_match('/makelos$/', $_SERVER['PHP_SELF']));

class MakelosRequest
{
    public
    $attributes,
    $tasks,
    $constants = array();

    public function __construct() {
        if(php_sapi_name() == 'cli'){
            $this->useCommandLineArguments();
        }
    }

    public function useCommandLineArguments() {
        $arguments = $GLOBALS['argv'];
        array_shift($arguments);
        $this->parse($arguments);
    }

    public function parse($arguments) {
        $task_set = false;
        $argv = $arguments;
        while(!empty($arguments)){
            $argument = array_shift($arguments);
            if($argument == '--hide-makelos-folder'){
                define('MAKELOS_SHOW_FOLDER', false);
                continue;
            }
            /**
             *  Captures assignments even if there are blank spaces before or after the equal symbol.
             */
            if(isset($arguments[0][0]) && $arguments[0][0] == '='){
                $argument .= $arguments[0];
                array_shift($arguments);
            }
            if(preg_match('/^
                                (-{0,2})
                                (
                                     (?![\w\d\.\-_:\/\\\]+\/\/) # If its not a protocol
                                        [\w\d\.\-_:\/\\\]+\s?
                                )
                                (=?)
                                (\s?.*)
                            $/x', $argument, $matches)){
                $constant_or_attribute = ((strtoupper($matches[2]) === $matches[2]) ? 'constants' : 'attributes');
                $is_constant = $constant_or_attribute == 'constants';

                if(($matches[3] == '=' || ($matches[3] == '' && $matches[4] != ''))){
                    $matches[4] = ($matches[4] === '') ? array_shift($arguments) : $matches[4];
                    if(!empty($task) && !$is_constant){
                        $this->tasks[$task]['attributes'][trim($matches[2], ' :')] = $this->_castValue(trim($matches[4], ' :'));
                    }else{
                        $this->{$constant_or_attribute}[trim($matches[2], ' :')] = $this->_castValue(trim($matches[4], ' :'));
                    }
                }elseif(!$task_set && (empty($matches[1]) || $matches[1] != '-')){
                    $task = trim($matches[2], ' :');
                    $this->tasks[$task] = array();
                    $task_set = true;
                }elseif($matches[1] == '-' && isset($task)){
                    foreach (str_split($matches[2]) as $k){
                        $this->tasks[$task]['attributes'][$k] = true;
                    }
                }elseif($task_set && isset($task)){
                    if($matches[1] == '--'){
                        $this->tasks[$task]['attributes'][trim($matches[2], ' :')] = true;
                    }else{
                        $this->tasks[$task]['attributes'][trim($matches[0], ' :')] = $this->_castValue(trim($matches[0], ' :'));
                    }
                }
            }elseif ($task_set) {
                $this->tasks[$task]['attributes'][trim($argument, ' :')] = $this->_castValue(trim($argument, ' :'));
            }
        }
    }

    public function get($name, $type = null) {
        if(!empty($type)){
            return isset($this->{$type}[$name]) ? $this->{$type}[$name] : false;
        }else{
            foreach (array('constants', 'attributes') as $type){
                return $this->get($name, $type);
            }
        }
    }

    public function flag($name) {
        return $this->get($name, __FUNCTION__);
    }

    public function constant($name) {
        return $this->get($name, __FUNCTION__);
    }

    public function attribute($name) {
        return $this->get($name, __FUNCTION__);
    }

    public function defineConstants() {
        foreach ($this->constants as $constant => $value){
            if(!preg_match('/^AK_/', $constant)){
                define('AK_'.$constant, $value);
            }
            define($constant, $value);
        }
    }

    private function _castValue($value) {
        if(in_array($value, array(true,1,'true','True','TRUE','1','y','Y','yes','Yes','YES'), true)){
            return true;
        }
        if(in_array($value, array(false,0,'false','False','FALSE','0','n','N','no','No','NO'), true)){
            return false;
        }
        return $value;
    }
}

$MakelosRequest = new MakelosRequest();

if(MAKELOS_RUN){
    // Setting constants from arguments before including configurations
    $MakelosRequest->defineConstants();

    $_config_file = AK_BASE_DIR.DS.'config'.DS.'config.php';

    if(!@include $_config_file){
        defined('AK_ENVIRONMENT')   || define('AK_ENVIRONMENT', 'testing');
        if(!@include AK_BASE_DIR.DS.'config'.DS.'environment.php'){
            defined('AK_SKIP_ENV_CONFIG') || define('AK_SKIP_ENV_CONFIG', true);
            include AK_BASE_DIR.DS.'autoload.php';
        }
    }

    Ak::setStaticVar('dsn', $dsn);
    defined('AK_RECODE_UTF8_ON_CONSOLE_TO') ? null : define('AK_RECODE_UTF8_ON_CONSOLE_TO', false);

    @ini_set('memory_limit', -1);
    set_time_limit(0);
    //error_reporting(E_ALL);
}

class Makelos
{
    public $tasks = array();
    public $task_files = array();
    public $task_paths = array(AK_TASKS_DIR);
    public $current_task;
    public $settings = array(
    'app_name' => 'Akelos application name'
    );
    public $Request;
    public $Installer;

    public function __construct(&$Request) {
        $this->Request = $Request;
        $this->Installer = new AkInstaller();
        $this->makefiles = $this->getMakeFiles();
    }

    public function loadMakefiles() {
        foreach ($this->makefiles as $makefile){
            if(file_exists($makefile)){
                include($makefile);
            }
        }
    }

    public function runTasks() {
        if(isset($this->Request->tasks['makelos:autocomplete'])){
            $this->runTask('makelos:autocomplete', $this->Request->tasks['makelos:autocomplete'], false);
            return;
        }
        if(!defined('MAKELOS_SHOW_FOLDER') || MAKELOS_SHOW_FOLDER){
            $this->message('(in '.MAKELOS_BASE_DIR.')');
        }
        if(!empty($this->Request->tasks)){
            foreach ($this->Request->tasks as $task => $arguments){
                $this->runTask($task, $arguments);
            }
        }else{
            $this->runTask('T', array());
        }
    }

    public function runTask($task_name, $options = array(), $only_registered_tasks = true) {
        $this->removeAutocompletionOptions($task_name);
        if(!empty($this->tasks[$task_name]['with_defaults'])){
            $options['attributes'] = array_merge((array)$this->tasks[$task_name]['with_defaults'], (array)@$options['attributes']);
            unset($this->tasks[$task_name]['with_defaults']);
        }
        if(!empty($options['attributes']['daemon'])){
            unset($options['attributes']['daemon']);
            $this->runTaskAsDaemon($task_name, $options);
            return;
        }elseif(!empty($options['attributes']['background'])){
            unset($options['attributes']['background']);
            $this->runTaskInBackground($task_name, $options);
            return;
        }
        $this->current_task = $task_name;
        if($only_registered_tasks && !isset($this->tasks[$task_name])){
            if(!$this->showBaseTaskDocumentation($task_name)){
                $this->error("\nInvalid task $task_name, use \n\n   $ ./makelos -T\n\nto show available tasks.\n");
            }
        }else{
            //$this->message(@$this->tasks[$task_name]['description']);
            $parameters = $this->getParameters(@$this->tasks[$task_name]['parameters'], @(array)$options['attributes']);
            $this->runTaskFiles($task_name, $parameters);
            $this->runTaskCode(@$this->tasks[$task_name]['run'], $parameters);
        }
    }



    public function showBaseTaskDocumentation($task_name) {
        $success = false;
        $this->message(' ');
        foreach ($this->tasks as $task => $details){
            if(preg_match("/^$task_name/", $task)){
                $this->showTaskDocumentation($task);
                $success = true;
            }
        }
        return $success;
    }

    public function showTaskDocumentation($task) {
        $this->message(sprintf("%-30s",$task).'  '.@$this->tasks[$task]['description']);
    }

    public function run($task_name, $options = array()) {
        return $this->runTask($task_name, $options);
    }

    public function runTaskCode($code_snippets = array(), $options = array()) {
        foreach (@(array)$code_snippets as $language => $code_snippets){
            $code_snippets = is_array($code_snippets) ? $code_snippets : array($code_snippets);
            $language_method = AkInflector::camelize('run_'.$language.'_snippet');

            if(method_exists($this, $language_method)){
                foreach ($code_snippets as $code_snippet){
                    $this->$language_method($code_snippet, $options);
                }
            }else{
                $this->error("Could not find a handler for running $language code on $this->current_task task", true);
            }
        }
    }

    public function runTaskFiles($task_name, $options = array()) {
        $files = $this->_getTaskFiles($task_name);
        $task_name = str_replace(':', DS, $task_name);
        $Makelos = $this;
        $Logger = Ak::getLogger('makelos'.DS.AkInflector::underscore($task_name));
        foreach ($files as $file){
            $pathinfo = @pathinfo($file);
            if(@$pathinfo['extension'] == 'php'){
                include($file);
            }else{
                echo `$file`;
            }
        }
    }

    public function getParameters($parameters_settings, $request_parameters) {
        $parameters_settings = Ak::toArray($parameters_settings);

        if(empty($parameters_settings)){
            return $request_parameters;
        }
        $parameters = array();
        foreach ($parameters_settings as $k => $v){
            $options = array();
            $required = true;
            if(is_numeric($k)){
                $parameter_name = $v;
            }else{
                $parameter_name = $k;
                if(is_array($v) && !empty($v['optional'])){
                    $required = false;
                    unset($v['optional']);
                }
            }
            if($required && !isset($request_parameters[$parameter_name])){
                $this->error("\nMissing \"$parameter_name\" parameter on $this->current_task\n", true);
            }
        }
    }

    public function runPhpSnippet($code, $options = array()) {
        $fn = create_function('$options, $Makelos', $code.';');
        return $fn($options, $this);
    }

    public function runSystemSnippet($code, $options = array()) {
        $code = trim($code);
        return $this->message(`$code`);
    }

    public function defineTask($task_name, $options = array()) {
        $default_options = array();
        $task_names = strstr($task_name, ',') ? array_map('trim', explode(',', $task_name)) : array($task_name);
        foreach ($task_names as $task_name) {
            $this->tasks[$task_name] = $options;
        }
    }

    public function addSettings($settings) {
        $this->settings = array_merge($this->settings, $settings);
    }

    public function displayAvailableTasks() {

        $this->message("\nYou can perform taks by running:\n");
        $this->message("    ./makelos task:name");
        $this->message("\nOptionally you can define contants or pass attributes to the tasks:\n");
        $this->message("    ./makelos task:name ENVIROMENT=production parameter=value param -abc --param=value");

        $this->message("\nShowing tasks avalable at ".AK_TASKS_DIR.":\n");

        ksort($this->tasks);

        foreach ($this->tasks as $task => $details){
            $this->showTaskDocumentation($task);
        }
    }


    public function error($message, $fatal = false) {
        $this->message($message);
        if($fatal){
            die();
        }
    }
    public function message($message) {
        if(!empty($message)){
            echo $message."\n";
        }
    }

    public function runTaskInBackground($task_name, $options = array()) {
        $this->_ensurePosixAndPcntlAreAvailable();
        $pid = Ak::pcntl_fork();
        if($pid == -1){
            $this->error("Could not run background task.\n Call with --background=false to avoid backgrounding.", true);
        }elseif($pid == 0){
            $dsn = Ak::getStaticVar('dsn');
            defined('AK_SKIP_DB_CONNECTION') && AK_SKIP_DB_CONNECTION ? null : Ak::db($dsn);
            $this->runTask($task_name, $options);
            posix_kill(getmypid(),9);
        }else{
            $this->message("\nRunning background task $task_name with pid $pid");
        }
    }


    public function runTaskAsDaemon($task_name, $options = array()) {
        $this->_ensurePosixAndPcntlAreAvailable();

        require_once 'System/Daemon.php';

        $app_name = AkInflector::underscore($task_name);
        $pid_file = AK_BASE_DIR.DS.'run'.DS.$app_name.DS.$app_name.'.pid';
        $log_file = AK_LOG_DIR.DS.'daemons'.DS.$app_name.'.log';

        if(!file_exists($pid_file)){
            if(empty($options['attributes']['kill'])){
                AkFileSystem::file_put_contents($pid_file, '');
                AkFileSystem::file_delete($pid_file);
            }else{
                $this->error("Could not kill process for $task_name", true);
            }
        }else{
            $pid = (int)file_get_contents($pid_file);
            if($pid > 0){
                if(!empty($options['attributes']['kill'])){
                    $this->message("Killing process $pid");
                    `kill $pid`;
                    AkFileSystem::file_delete($pid_file);
                    die();
                }elseif(!empty($options['attributes']['restart'])){
                    $this->message("Restarting $task_name.");
                    $this->message(`kill $pid`);
                }else{
                    $this->error("Daemon for $task_name still running ($pid_file).\nTask aborted.", true);
                }
            }
        }

        if(!empty($options['attributes']['kill']) && empty($pid)){
            $this->error("No daemon running for task $task_name", true);
        }
        unset($options['attributes']['restart']);

        if(!file_exists($log_file)){
            AkFileSystem::file_put_contents($log_file, '');
        }

        System_Daemon::setOption('appName', $app_name);
        System_Daemon::setOption('appDir', AK_BASE_DIR);
        System_Daemon::setOption('logLocation', $log_file);
        System_Daemon::setOption('appRunAsUID', posix_geteuid());
        System_Daemon::setOption('appRunAsGID', posix_getgid());
        System_Daemon::setOption('appPidLocation', $pid_file);
        $this->message("Staring daemon. ($log_file)");
        System_Daemon::start();
        $dsn = Ak::getStaticVar('dsn');
        defined('AK_SKIP_DB_CONNECTION') && AK_SKIP_DB_CONNECTION ? null : Ak::db($dsn);
        $this->runTask($task_name, $options);
        System_Daemon::stop();
        AkFileSystem::file_delete($pid_file);
        die();
    }


    // Autocompletion handling

    public function getAvailableTasksForAutocompletion() {
        return array_keys($this->tasks);
    }

    public function getAutocompletionOptionsForTask($task, $options = array(), $level = 1) {
        $task_name = str_replace(':', DS, $task);
        $Makelos = $this;
        $autocompletion_options = array();
        $autocomplete_accessor = 'autocompletion'.($level === 1 ? '' : '_'.$level);
        $autocompletion_executables = $this->multiGlob(array(
        $task_name.'*.'.$autocomplete_accessor.'.*',
        $task_name.DS.$task_name.'.'.$autocomplete_accessor.'.*'
        ));

        if(!empty($autocompletion_executables)){
            ob_start();
            foreach ($autocompletion_executables as $file){
                $pathinfo = @pathinfo($file);
                if(@$pathinfo['extension'] == 'php'){
                    include($file);
                }else{
                    echo `$file`;
                }
            }
            echo "\n";
            $autocompletion_options = array_diff(explode("\n", ob_get_clean()), array(''));
        }
        $autocomplete_accessor = 'autocompletion'.($level === 1 ? '' : '_'.$level);
        if(isset($this->tasks[$task][$autocomplete_accessor])){
            $autocompletion_options = array_merge(Ak::toArray($this->tasks[$task][$autocomplete_accessor]), $autocompletion_options);
        }
        array_unique($autocompletion_options);
        $autocompletion_options = array_diff($autocompletion_options, array_merge(array($task), $options));
        return $autocompletion_options;
    }

    public function removeAutocompletionOptions($task_name) {
        if (!empty($this->tasks[$task_name])) {
            foreach ($this->tasks[$task_name] as $k => $v){
                if(preg_match('/^autocompletion/', $k)){
                    unset($this->tasks[$task_name][$k]);
                }
            }
        }
    }

    public function getMakeFiles(){
        return $this->multiGlob(array(
        'makefile.php',
        '*/makefile.php',
        '*/*/makefile.php',
        '*/*/*/makefile.php',
        '*/*/*/*/makefile.php'));
    }

    public function multiGlob($patterns = array(), $task_path = null){
        $task_paths = empty($task_path) ? AkConfig::getOption('makelos_task_paths', array_merge($this->task_paths, array(dirname(__FILE__).DS.'tasks'))) : (array)$task_path;

        $glob_result = array();
        foreach ($patterns as $pattern){
            foreach ($task_paths as $task_path){
                $glob_result = array_merge($glob_result, glob($task_path.DS.$pattern));
            }
        }
        array_unique($glob_result);
        return array_diff($glob_result, array(''));
    }


    protected function _getTaskFiles($task_name, $bark_on_error = true){
        $task_parts = array_diff(explode(':', $task_name.':'), array(''));
        $task_part_count = count($task_parts);
        $search_patterns = array();

        $search_patterns[] = join(DS, $task_parts).'.task*.*';
        if($task_part_count == 1){
            $search_patterns[] = str_replace(':',DS, $task_name).DS.join(DS, $task_parts).'.task*.*';
        }
        $subtask = array_pop($task_parts);
        if(!empty($subtask)){
            $search_patterns[] = join(DS, $task_parts).'.'.$subtask.'.task*.*';
            if($task_part_count == 1){
                $search_patterns[] = str_replace(':',DS, $task_name).DS.join(DS, $task_parts).'.'.$subtask.'.task*.*';
            }
        }
        $task_files = $this->multiGlob($search_patterns);

        //
        $task_files = array_diff($task_files, array(''));
        if($bark_on_error && empty($this->tasks[$task_name]['run']) && empty($task_files)){
            $this->error("No task file found for $task_name in ".AK_TASKS_DIR, true);
        }
        return $task_files;
    }


    private function _ensurePosixAndPcntlAreAvailable() {
        if(!function_exists('posix_geteuid')){
            trigger_error('POSIX functions not available. Please compile PHP with --enable-posix', E_USER_ERROR);
        }elseif(!function_exists('pcntl_fork')){
            trigger_error('pcntl functions not available. Please compile PHP with --enable-pcntl', E_USER_ERROR);
        }
    }

}

Ak::setStaticVar('Makelos', new Makelos($MakelosRequest));

function makelos_task($task_name, $options = array()){
    Ak::getStaticVar('Makelos')->defineTask($task_name, $options);
}

function makelos_setting($settings = array()){
    Ak::getStaticVar('Makelos')->addSettings($settings);
}


/**
 * @todo
 *
 *  Task
 *      prequisites
 *      actions
 *      expected parameters
 *
 *
 *  Directory functions
 *  Parallel tasks
 *

 ./makelos db:fixtures:load         # Load fixtures into the current environment&#8217;s database.
                                    # Load specific fixtures using FIXTURES=x,y
./makelos db:migrate                # Migrate the database through scripts in db/migrate. Target
                                    # specific version with VERSION=x
./makelos db:structure:dump         # Dump the database structure to a SQL file
./makelos db:test:clone             # Recreate the test database from the current environment&#8217;s
                                    # database schema
./makelos db:test:clone_structure   # Recreate the test databases from the development structure
./makelos db:test:prepare           # Prepare the test database and load the schema
./makelos db:test:purge             # Empty the test database

./makelos doc:app                   # Build the app documetation Files into docs/app/api
./makelos doc:plugins               # Generate documation for all installed plugins in docs/plugins
./makelos doc:akelos                # Build the akelos documentation files into docs/akelos/api
./makelos doc:website               # Add a new controller at /docs to browse avaliable documentation
./makelos doc:website:remove        # Removed the files added by ./makelos doc:website

./makelos log:clear                 # Truncates all *.log files in log/ to zero bytes

./makelos akelos:update             # Update both scripts and public/javascripts from Akelos
./makelos akelos:update:javascripts # Update your javascripts from your current akelos install
./makelos akelos:update:scripts     # Add new scripts to the application script/ directory

./makelos stats                     # Report code statistics (KLOCs, etc) from the application

./makelos test                      # Test all units and functionals
./makelos test:functionals          # Run tests for functionals db:test:prepare
./makelos test:integration          # Run tests for integrationdb:test:prepare
./makelos test:plugins              # Run tests for pluginsenvironment
./makelos test:recent               # Run tests for recentdb:test:prepare
./makelos test:uncommitted          # Run tests for uncommitteddb:test:prepare
./makelos test:units                # Run tests for unitsdb:test:prepare

./makelos tmp:cache:clear           # Clears all files and directories in tmp/cache
./makelos tmp:clear                 # Clear session, cache, and socket files from tmp/
./makelos tmp:create                # Creates tmp directories for sessions, cache, and sockets
./makelos tmp:sessions:clear        # Clears all files in tmp/sessions
./makelos tmp:sockets:clear         # Clears all ruby_sess.* files in tmp/sessions
 */

if(MAKELOS_RUN){
    Ak::getStaticVar('Makelos')->loadMakefiles();
    Ak::getStaticVar('Makelos')->runTasks();
    echo "\n";
}
