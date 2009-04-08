<?php
defined('DS')          ? null : define('DS',DIRECTORY_SEPARATOR);
defined('AK_BASE_DIR') ? null : define('AK_BASE_DIR',preg_replace('@\\'.DS.'(test|script)($|\\'.DS.'.*)@','',getcwd()));
if (!@include_once(AK_BASE_DIR.DS.'config'.DS.'config.php')) {
    require_once(AK_BASE_DIR.DS.'config'.DS.'DEFAULT-config.php');
}
define('AK_CI_CONFIG_FILE',AK_BASE_DIR.DS.'config'.DS.'ci-config.yaml');

class CI_Tests
{
    var $options = array(
    'break_on_errors'=>false,
    'test_mode'      =>false,
    'repeat'         =>1
    );

    var $settings = array('executables'=>array(),'environments'=>array(),'default_executables'=>array());
    var $configured = array('ci-config.yaml'=>false,'environments'=>false);
    var $target_files;
    var $test_files;
    var $target_executables;
    var $target_environments;
    var $debug=false;
    var $report_environments = array();

    function main($args=array())
    {
        if (empty($args)){
            $args = $_SERVER['argv'];
        }
        $self = new CI_Tests($args);
        $self->run();
        $self->hadError() ? exit(1) : exit(0);
    }

    function CI_Tests($args)
    {
        
        
        
        $this->args = $args;
        if (in_array('-d',$this->args)) {
            $this->debug = true;
        }
        if (!$this->_isConfigured()) {

            $this->setup();

        }
        
        $this->setDefaults();
        $this->info('Ci-Tests configured properly.');
        
    }
    
    function setup()
    {
        foreach ($this->configured as $type=>$ok) {
            switch($type) {
                case 'environments':
                    if (!is_array($ok)) {
                        $ok = array('mysql'=>false,'postgres'=>false,'sqlite'=>false);
                    }
                    foreach ($ok as $env=>$res) {
                        if (!$res) {
                            $this->_createDbConfig($env);
                        }
                    }
                    break;
                case 'ci-config.yaml':
                    break;
                case 'ci-config.php':
                    if (!$ok) {
                        $this->_createCiPhpConfigFile();
                        $this->loadSettings();
                    }
                    break;
            }
        }
    }
    function _promptForDbConfig($env)
    {
        
        $env=='sqlite'?$host='':$host = $this->promptUserVar('['.$env.'] Host',  array('default'=>'localhost'));
        $env=='sqlite'?$dbname='':$dbname = $this->promptUserVar('['.$env.'] Database name',  array('default'=>'akelos'));
        $dbfile = ($env=='sqlite'?$this->promptUserVar('['.$env.'] Database file:',  array('default'=>'/tmp/akelos.sqlite')):null);
        $env=='sqlite'?$username='':$username = $this->promptUserVar('['.$env.'] Username');
        $env=='sqlite'?$password='':$password = $this->promptUserVar('['.$env.'] Password',array('optional'=>true));
        $env=='sqlite'?$options='':$options = $this->promptUserVar('['.$env.'] Options',array('optional'=>true));
        
        return array('type'=>$env,'host'=>$host,'database_file'=>$dbfile,'database_name'=>$dbname,'user'=>$username,'password'=>$password,'options'=>$options);
    }
    function _createDbConfig($env)
    {
        $file = AK_BASE_DIR.DS.'config'.DS.$env.'.yml';
        $templateFile = AK_BASE_DIR.DS.'script'.DS.'extras'.DS.'TPL-database.yml';
        if (in_array($env,array('postgres','postgresql','postgressql'))) {
        	$env = 'pgsql';
        }
        $this->info('Creating environment configuration for:'.$env);
        $dbConfig = $this->_promptForDbConfig($env);
        while (!($res = $this->_checkDbConfig($env,$dbConfig))) {
            
            $this->info('Try again:');
            $dbConfig = $this->_promptForDbConfig($env);
        }
        $replacements = array();
        foreach($dbConfig as $key=>$val) {
            $replacements['${'.$key.'}'] = $val;
        }
        return file_put_contents($file,str_replace(array_keys($replacements),array_values($replacements),file_get_contents($templateFile)))>0;
        
    }
    /**
     * Promts for a variable on console scripts
     */
    function promptUserVar($message, $options = array())
    {
        $f = fopen("php://stdin","r");
        $default_options = array(
        'default' => null,
        'optional' => false,
        );

        $options = array_merge($default_options, $options);

        echo "\n".$message.(empty($options['default'])?'': ' ['.$options['default'].']').': ';
        $user_input = fgets($f, 25600);
        $value = trim($user_input,"\n\r\t ");
        $value = empty($value) ? $options['default'] : $value;
        if(empty($value) && empty($options['optional'])){
            echo "\n\nThis setting is not optional.";
            fclose($f);
            return $this->promptUserVar($message, $options);
        }
        fclose($f);
        return empty($value) ? $options['default'] : $value;
    }
    function _isConfigured()
    {
        $returnVal = true;
        // check if AK_CI_CONFIG_FILE exists
        if(file_exists(AK_CI_CONFIG_FILE)) {
            $this->debug('File '.AK_CI_CONFIG_FILE.' exists');
            $this->loadSettings();
            // check if the ci-test installation is there
            if (!$this->_checkTestInstallation()) {
                $this->error('Check of test installation failed');
                return false;
            }
            if (isset($this->settings['test-installation'])) {
                $this->_createTestInstallation($this->settings['test-installation']);
            }
            // check if php executables are runnable and if they are really php4 and php5
            if (!$this->_checkExecutables()) {
                $this->error('Check of executables failed');
                return false;
            }
            
            if (!$this->_checkMemcacheInstallation()) {
                $this->error('Check of memcached installation failed');
                return false;
            }
            
        } else {
            $this->debug('File '.AK_CI_CONFIG_FILE.' does not exist');
            $res = $this->_createCiConfigFile();
            if (!$res) {
                $this->error('Could not create: '.AK_CI_CONFIG_FILE,true);
            }
            $this->loadSettings();
            $this->_setupMemcache();
        }
        
        $this->_fixTestInstallationPermissions();
        $this->configured['ci-config.yaml'] = true;
        $this->configured['environments'] = array();
        foreach ($this->settings['environments'] as $type) {
            $this->debug('Checking environment: '.$type);
            $this->configured['environments'][$type] = false;
            if (file_exists(AK_BASE_DIR.DS.'config'.DS.$type.'.yml')) {
                // check environment database configs - access database to verify it works
                $res = $this->_checkDbConfig($type);
                $this->configured['environments'][$type] = $res;
                $returnVal = $res && $returnVal;
            } else {
                $this->info('Environment config for '.$type.' does not exist');
                $this->_createDbConfig($type);
            }
        }
        copy(AK_BASE_DIR.DS.'config'.DS.'sqlite.yml',AK_CI_TEST_DIR.DS.'config'.DS.'database.yml');
        if (file_exists(AK_BASE_DIR.DS.'config'.DS.'ci-config.php')) {
            /**$ciConfigContents = file_get_contents(AK_BASE_DIR.DS.'config'.DS.'ci-config.php');
            // check the config.php file (needs the webroot), check if the webserver is reachable
            preg_match("/define\('AK_TESTING_URL', '(.*?)'\);/",$ciConfigContents,$matches);
            $testing_url = '';
            if(isset($matches[1])) {
                $testing_url = $matches[1];
            }*/
            $testing_url = $this->settings['test-url'];
            $res = $this->_checkWebServer($testing_url);
            $this->configured['ci-config.php'] = $res;
            $returnVal = $res && $returnVal;
        } else {
            $this->_createCiPhpConfigFile();
        }
        
        return $returnVal;
        // if all passes return true
    }
    function _checkTestInstallation()
    {
        $this->info('Checking test-installation');
        $command = $this->settings['test-installation'].'/akelos -v';
        $this->debug('Checking test-installation:'.$command);
        $out = array();
        exec($command,$out,$ret);
        $version = isset($out[0])? $out[0]:0;
        if (version_compare($version,0,'>')) {
            $this->info('Test installation ok, running version:'.$version);
        } else {
            $this->error('Test installation not ok:'.var_export($out,true), true);
        }
        return true;
    }
    function _checkWebserver2($url)
    {
        $return = true;
        $testIndexPage = @file_get_contents($url.'/');
        if ($testIndexPage!='Test::page::index') {
            $this->error('Webserver Configuration problems:'."\n" .$testIndexPage."\n\n");
            $return = false;
        } else {
            $this->info('Web Server is configured correctly');
        }
        return $return;
    }
    
    function _checkWebServer($url, $test_installation = null)
    {
        $return = true;
        if ($test_installation == null && defined('AK_CI_TEST_DIR')) {
            $test_installation = AK_CI_TEST_DIR;
        }
        $parts = parse_url($url);
        if (!isset($parts['host'])) {
            $this->error('No host found in: '.$url);
            return false;
        }
        $rewritebase = isset($parts['path'])?$parts['path']:'/';
        $htaccessTemplateFile = AK_BASE_DIR.DS.'script'.DS.'extras'.DS.'TPL-htaccess';
        $htaccessFile = $test_installation.DS.'test'.DS.'fixtures'.DS.'public'.DS.'.htaccess';
        $htaccessRes = file_put_contents($htaccessFile,str_replace('${rewrite-base}',$rewritebase,file_get_contents($htaccessTemplateFile)))>0;
        
        $this->debug('Copying '.$htaccessTemplateFile.' template to '.$htaccessFile.':'.(($htaccessRes)?'Success':'failure'));
        
        //$htaccess = AK_CI_TEST_DIR.DS.'test'.DS.'fixtures'.DS.'public'.DS.'.htaccess';
        //$htaccess_backup =AK_CI_TEST_DIR.DS.'test'.DS.'fixtures'.DS.'public'.DS.'.htaccess.backup';
        $test_htaccess = $test_installation.DS.'test'.DS.'.htaccess';
        $test_htaccess_backup =$test_installation.DS.'test'.DS.'.htaccess.backup'; 
        //copy($htaccess,$htaccess_backup);
        //unlink($htaccess);
        copy($test_htaccess,$test_htaccess_backup);
        $this->debug('Copying '.$test_htaccess.' to '.$test_htaccess_backup);
        unlink($test_htaccess);
        $this->debug('Unlinking '.$test_htaccess);
        $this->debug('Checking if webserver is reachable at: '.$url.'/ci-setup-test.html');
        $content = time().' - '.rand(0,10000);
        $file = $test_installation.DS.'test'.DS.'fixtures'.DS.'public'.DS.'ci-setup-test.html';
        file_put_contents($file,$content);
        $res = @file_get_contents($url.'/ci-setup-test.html');
        if ($res!=$content) {
            if (strlen($res)>0) {
                $this->error('Web Server is not pointing to the test-installation at '.dirname($file));
            } else {
                $this->error('Web Server is not reachable at '.$url);
            }
            $return = false;
        }
        $this->info('Web Server is reachable at '.$url);
        
        

        return $return;
    }
    
    function _checkDbConfig($type, $settings = array())
    {
        $this->debug('Checking if database config for "'.$type.'" is available');
        require_once(AK_BASE_DIR.DS.'lib'.DS.'AkActiveRecord'.DS.'AkDbAdapter.php');
        require_once(AK_LIB_DIR.DS.'Ak.php');
        
        if (empty($settings)) {
            require_once(AK_BASE_DIR.DS.'lib'.DS.'AkConfig.php');
            $config = new AkConfig();
            $settings = $config->get($type,'testing');
        }
        $db = new AkDbAdapter($settings);
        $db->connect(false);
        if (!($res=$db->connected())) {
            $this->error("[$type] ".'Cannot connect to DB');
        } else {
            $this->info("[$type] ".'Successfully connected to database '.($settings['type'] == 'sqlite'?$settings['database_file']:$settings['database_name']));
            $createRes = $db->execute('CREATE table ci_test_dummy(id integer)');
            if (!$createRes) {
                $res = false;
                $this->error("[$type] ".'Could not create test table: ci_test_dummy');
            } else {
                $res = true;
                $this->info("[$type] ".'Successfully created test table: ci_test_dummy');
                $db->execute('DROP table ci_test_dummy');
            }
        }
        return $res;
    }
    function debug($message)
    {
        if ($this->debug) {
            echo "[DEBUG]\t$message\n";
        }
    }
    function error($message, $fatal = false)
    {
        echo "[ERROR]\t$message\n";
        if ($fatal) {
            die("[FATAL-ERROR]\texiting.\n");
        }
    }
    function _checkExecutables($executables = array())
    {

        $executables = empty($executables)?$this->settings['executables']:$executables;
        if (count($executables)>1) {
            $this->debug('Checking executables:');
        }
        foreach ($executables as $executable) {
            $command = $executable.' -r "echo 1;"';
            $this->debug('Checking executable "'.$executable.'" with: '.$command);
            $return=array();
            exec($command,$return,$returnVal);
            $this->debug('Return :'.var_export($return,true));
            if ($return[0] !== "1") {
                $this->error("$executable should have echoed '1' but did echo '" . var_export($return,true));
                return false;
            }
        }
        return true;
    }
    function _createCiPhpConfigFile()
    {
        $this->info('Creating ci-config.php');
        $templateFile = AK_BASE_DIR.DS.'script'.DS.'extras'.DS.'TPL-ci-config.php';
        $file = AK_BASE_DIR.DS.'config'.DS.'ci-config.php';
        $settings = array();
        $settings['${testing-url}'] = $this->settings['test-url'];
        return file_put_contents($file,str_replace(array_keys($settings),array_values($settings),file_get_contents($templateFile)))>0;
    }
    function _createCiConfigFile()
    {
        $this->info('Creating ci-configuration');
        $templateFile = AK_BASE_DIR.DS.'script'.DS.'extras'.DS.'TPL-ci-config.yaml';
        $file = AK_BASE_DIR.DS.'config'.DS.'ci-config.yaml';
        $settings = array();
        $settings['${test-installation}'] = $this->_createTestInstallation();
        $settings['${php4}'] = $this->_promptForPhp('php4');
        $settings['${php5}'] = $this->_promptForPhp('php5');
        $settings['${test-url}'] = $this->_promptForTestingUrl($settings['${test-installation}']);
        $settings['${memcached-socket}'] = '';
        return file_put_contents($file,str_replace(array_keys($settings),array_values($settings),file_get_contents($templateFile)))>0;
    }
    
    function _promptForPhp($php)
    {
        while ((($executable = $this->promptUserVar('Please provide the path to a valid '.$php.' executable')) && !$this->_checkExecutables(array($executable)))) {
            $this->error('Could not verify that '.$executable.' is a valid php executable');
        }
        return $executable;
    }
    function _promptForTestingUrl($test_installation)
    {
        while ((($testingUrl = $this->promptUserVar("Please provide the testing url of the webserver.
        
        Example:
        
        In case you are running apache on localhost you can add the following in the apache conf:
        
        Alias /akelos-ci-tests \"$test_installation\"

        <Directory \"$test_installation\">
            Options Indexes FollowSymLinks
            AllowOverride All
            Order allow,deny
            Allow from all
        </Directory>
        
        If you are running apache on localhost, the testing url would be:
        
        http://localhost/akelos-ci-tests/test/fixtures/public
        
        You need to restart your webserver after making the change and before confirming
        the testing url.
        ")) && !$this->_checkWebServer($testingUrl,$test_installation))) {
            $this->error('Could not verify the testing url. Please make sure a webserver is running and handling that request.');
        }
        return $testingUrl;
    }
    function info($message)
    {
        echo "[INFO]\t$message \n";
    }
    function _createTestInstallation($testDir = null)
    {
        if ($testDir == null) {
            while ((($testDir = $this->promptUserVar('Please insert the path for installing the CI tests for Akelos')) && ((file_exists($testDir) && !is_writable($testDir)) || (!is_writable(dirname(dirname($testDir))))))) {
                $this->error('Directory '.$testDir.' is not writable');
            }
        }
        $testDir = rtrim($testDir,DS).DS;
        $this->debug('Chosen directory '.$testDir);
        $out = array();
        $command = AK_BASE_DIR.DS.'akelos -d '.$testDir.' -deps --force';
        $this->debug('Installing akelos testing app: '.$command);
        if ($this->debug) {
            passthru($command, $ret);
        } else {
            exec($command,$out,$ret);
        }
        
        $routing = copy(AK_BASE_DIR.DS.'config'.DS.'DEFAULT-routes.php',$testDir.DS.'config'.DS.'routes.php');
        
        if (!$routing) {
            $this->error('Could not copy routing file to '.$testDir.DS.'config'.DS.'routes.php',true);
        }
        
        if ($ret!=0) {
            $this->error('Could not install akelos testing app in: '.$testDir,true);
        }
        return $testDir;
    }
    function _fixTestInstallationPermissions()
    {
        $dirs = array('/test/tmp','/config/cache','/log');
        foreach ($dirs as $dir) {
            exec('chmod -Rf 777 '.AK_CI_TEST_DIR.$dir);
        }
    }
    
    function _setupMemcache()
    {
        $memcachedInstalled = $this->promptUserVar('Certain tests need a memcached running. Do you have a memcached installation?',array('default'=>'No'));
        
        if (!in_array(strtolower($memcachedInstalled),array('y','yes','si','ja','1'))) {
            $installation = $this->settings['test-installation'];
            $memcacheTestConfigFile = $installation.DS.'test'.DS.'unit'.DS.'config'.DS.'memcached';
            file_put_contents($memcacheTestConfigFile,'0');
            return;
        } else {
            $socket = $this->_configureMemcache();
            if ($socket !== false) {
                $res = $this->_createMemcachedConfig($socket);
                if (!$res) {
                    $this->error('Could not create caching.yml. Disabling memcached support.');
                    $installation = $this->settings['test-installation'];
                    $memcacheTestConfigFile = $installation.DS.'test'.DS.'unit'.DS.'config'.DS.'memcached';
                    file_put_contents($memcacheTestConfigFile,'0');
                    return;
                } else {
                    
                }
            }
        }
    }
    
    function _createMemcachedConfig($socket)
    {
        $file1 = AK_CI_TEST_DIR.DS.'test'.DS.'fixtures'.DS.'config'.DS.'caching.yml';
        $file2 = AK_CI_TEST_DIR.DS.'config'.DS.'caching.yml';
        $templateFile = AK_BASE_DIR.DS.'script'.DS.'extras'.DS.'TPL-caching.yml';
        $this->info('Creating caching configuration: ' .$file1);
        $res1 = file_put_contents($file1,str_replace('${memcached_server}',$socket,file_get_contents($templateFile)))>0;
        $this->info('Creating caching configuration: ' .$file2);
        $res2 = file_put_contents($file1,str_replace('${memcached_server}',$socket,file_get_contents($templateFile)))>0;
        $res3 = file_put_contents(AK_CI_CONFIG_FILE,str_replace('memcached-socket: ','memcached-socket: '.$socket,file_get_contents(AK_CI_CONFIG_FILE)));
        return $res1 && $res2 && $res3;
    }
    
    function _checkMemcacheInstallation($socket = null)
    {
        if ($socket == null) {
            $socket = $this->settings['memcached-socket'];
            if (empty($socket)) return true;
        }
        require_once(AK_BASE_DIR.DS.'lib'.DS.'AkCache'.DS.'AkMemcache.php');
        $memcache = new AkMemcache();
        return @$memcache->init(array('servers'=>array($socket)));
    }
    function _configureMemcache()
    {
        require_once(AK_BASE_DIR.DS.'lib'.DS.'AkCache'.DS.'AkMemcache.php');
        $memcache = new AkMemcache();
        while ((($socket = $this->promptUserVar('Please provide the socket memcached is running on',array('default'=>'localhost:11211'))) && !$this->_checkMemcacheInstallation($socket))) {
            $this->error('Could not connect to memcached at socket: '.$socket);
            $tryAgain = $this->promptUserVar('Want to try again configuring memcached support?',array('default'=>'Yes'));
            if (!in_array(strtolower($tryAgain),array('y','yes','si','ja','1'))) {
                return false;
            }
        }
        return $socket;
    }
    function loadSettings($filename=AK_CI_CONFIG_FILE)
    {
        require_once dirname(__FILE__).DS.'..'.DS.'..'.DS.'..'.DS.'vendor'.DS.'TextParsers'.DS.'spyc.php';
        $this->debug('Trying to load settings from: '.$filename);
        if (!is_file($filename)){
            die ('Could not find ci configuration file in '.AK_CI_CONFIG_FILE.'.');
        }
        $yaml = file_get_contents($filename);
        $this->settings = Spyc::YAMLLoad($yaml);
        defined('AK_CI_TEST_DIR')?null:define('AK_CI_TEST_DIR',$this->settings['test-installation']);
        $this->parseArgs();
    }

    function parseArgs($args = null)
    {
        if ($args == null) {
            $args = $this->args;
        }
        array_shift($args);
        while (count($args) > 0){
            $arg = array_shift($args);
            $arg = strtolower($arg);
            if (in_array($arg,array('postgresql','postgressql','pgsql','pg','postgres'))) {
                $arg = 'pgsql';
            }
            if (array_key_exists(strtolower($arg),$this->settings['executables'])){
                $this->target_executables[] = $arg;
            }elseif (in_array(strtolower($arg),$this->settings['environments'])){
                $this->target_environments[] = $arg;
            }elseif ($filename = $this->constructTestFilename($arg)){
                $this->test_files[] = $filename;
            }else{
                switch ($arg){
                    case '-b':
                        $this->options['break_on_errors'] = true;
                        break;
                    case '-t':
                        $this->options['test_mode'] = true;
                        break;
                    case '-d':
                        $this->debug=true;
                        break;
                    case '-?':
                    case '?':
                        $this->drawHelp();
                        break;
                    case '-n':
                        $timesToRepeat = array_shift($args);
                        $this->options['repeat'] = $timesToRepeat;
                        break;
                }
            }
        }
        
    }

    function setDefaults()
    {
        if (!$this->target_executables)  $this->target_executables  = $this->settings['default_executables'];
        if (!$this->target_files)        $this->target_files[]      = AK_CI_TEST_DIR.DS.'test'.DS.'ci-unit.php';
        if (!$this->target_environments) $this->target_environments = $this->settings['environments'];
        if (!$this->test_files) $this->test_files = array();
    }

    function constructTestFilename($filename)
    {
        if (is_file($filename)) return $filename;

        $target_file = getcwd().DIRECTORY_SEPARATOR.$filename;
        if (is_file($target_file)) return $target_file;

        return false;
    }


    function config_file()
    {
        return AK_CI_TEST_DIR.DS.'config'.DS.'config.php';
    }
    function fix_htaccess_file()
    {
        return AK_CI_TEST_DIR.DS.'config'.DS.'fix_htaccess.php';
    }
    function ci_fix_htaccess_file()
    {
        return AK_BASE_DIR.DS.'script'.DS.'extras'.DS.'fix_htaccess.php';
    }
    function ci_config_file()
    {
        return AK_BASE_DIR.DS.'config'.DS.'ci-config.php';
    }
    
    function environment_file()
    {
        return AK_CI_TEST_DIR.DS.'config'.DS.'database.yml';
    }
    function config_backup_file()
    {
        return AK_CI_TEST_DIR.DS.'config'.DS.'config-backup.php';
    }

    function config_file_for($environment)
    {
        return AK_BASE_DIR.DS.'config'.DS.$this->settings['environments'][$environment].'.php';
    }
    function environment_file_for($environment)
    {
        return AK_BASE_DIR.DS.'config'.DS.$environment.'.yml';
    }
    function run()
    {
        $this->drawHeader();

        $this->beforeRun();
        for ($i=1; $i <= $this->timesToRun(); $i++){
            $this->drawRepeatIndicator($i);
            foreach ($this->filesToRun() as $file){
                $this->info('Running test file: '.$file);
                foreach ($this->executablesToRun() as $php_version){
                    $this->info('Running tests for: '.$php_version);
                    foreach ($this->environmentsToRun() as $environment){
                        if ($this->isValidCombination($environment,$php_version)){
                            
                            $return_value = $this->runCommand($php_version,$file,$environment);
                            if ($return_value !== 0) {
                                $this->markError();
                                if ($this->options['break_on_errors']) break 4;
                            } else {
                                
                            }
                        }
                    }
                }
            }
        }
        $this->_generateSummary();
        $this->afterRun();

        $this->drawFooter();
    }

    function markError()
    {
        $this->errors = true;
    }

    function hadError()
    {
        return isset($this->errors);
    }

    function filesToRun()
    {
        return $this->target_files;
    }

    function executablesToRun()
    {
        return $this->target_executables;
    }

    function environmentsToRun()
    {
        return $this->target_environments;
    }

    function timesToRun()
    {
        return $this->options['repeat'];
    }

    function isValidCombination($environment,$php_version)
    {
        return in_array($environment,$this->settings['valid_combinations'][$php_version]);
    }

    function beforeRun()
    {
        $res = copy($this->config_file(),$this->config_backup_file());
        $res = copy($this->ci_config_file(),$this->config_file()) && $res;
        return $res;
    }

    function afterRun()
    {
        if (copy($this->config_backup_file(),$this->config_file())){
            return unlink($this->config_backup_file());
        }
        return false;
    }

    function prepareEnvironment($environment)
    {
        if (!is_file($this->environment_file_for($environment))){
            echo "Can't find environment settings for $environment. Skipping...\n\r";
            return false;
        }
        return copy($this->environment_file_for($environment),$this->environment_file());
    }
    function _generateSummary()
    {
        $summaryFile = AK_BASE_DIR.DS.'test'.DS.'report'.DS.'index.html';
        $environments = $this->report_environments;
        ob_start();
        include_once(AK_BASE_DIR.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'summary.php');
        $contents = ob_get_clean();
        
        $res = file_put_contents($summaryFile, $contents) > 0;
        if ($res) {
            $this->info('Summary Report available at: '.$summaryFile);
        } else {
            $this->error('Could not generate Summary Report: '.$summaryFile);
        }
    }
    function _generateReport($xmlFile,$php,$env)
    {
        if (version_compare(PHP_VERSION,'5','>=')&&extension_loaded('xsl')) {
            $this->debug('Including php5 xslt wrapper');
            require_once(AK_BASE_DIR.DS.'vendor'.DS.'xslt-php4-php5'.DS.'xslt-php4-php5.php');
        }
        
        // Fail if XSLT extension is not available
        if( ! function_exists( 'xslt_create' ) ) {
            $this->error('Cannot generate report: xslt extension missing');
            return FALSE;
        }
        $currentDir = getcwd();
        chdir(AK_BASE_DIR . DIRECTORY_SEPARATOR . 'resources'.DIRECTORY_SEPARATOR . 'xsl');
        $xsl_file = AK_BASE_DIR . DIRECTORY_SEPARATOR . 'resources'.DIRECTORY_SEPARATOR . 'xsl' .DIRECTORY_SEPARATOR  . 'phpunit2-noframes.xsl';
        
        // look for xsl
        if( !is_readable( $xsl_file ) ) {
            $this->error('Cannot generate report: '.$xsl_file.' missing');
            chdir($currentDir);
            return FALSE;
        }
        if (!is_readable($xmlFile)) {
            return false;
        }

            
        $schema = file_get_contents( $xmlFile );
        
        $xml = new SimpleXMLElement($schema);
        $suites=$xml->xpath("/testsuites/testsuite");
        $tests=0;
        $failures=0;
        $errors=0;
        $time=0;
        
        foreach($suites as $suite){
            $attributes = $suite->attributes();
            $tests += (int)$attributes->tests;
            $failures += (int)$attributes->failures;
            $errors += (int)$attributes->errors;
            $time += (float)$attributes->time;
        }
        $environment = array();
        $environment['php']=$php;
        $environment['class']=$failures>0?'failure':$errors>0?'error':'';
        $environment['backend']=$env;
        $environment['tests']=$tests;
        $environment['failures']=$failures;
        $environment['errors']=$errors;
        $environment['time']=round($time,3);
        $link = AK_BASE_DIR.DS.'test'.DS.'report'.DS.$php.DS.$env.DS.'index.html';
        $environment['details']='file://'.$link;
        $this->report_environments[]=$environment;
        $arguments = array (
            '/_xml' => $schema,
            '/_xsl' => file_get_contents( $xsl_file )
        );
        
        // create an XSLT processor
        $xh = xslt_create ();
        
        // set error handler
        xslt_set_error_handler ($xh, array (&$this, 'xslt_error_handler'));
        
        // process the schema
        $result = xslt_process ($xh, 'arg:/_xml', 'arg:/_xsl', NULL, $arguments); 
        
        xslt_free ($xh);
        
        $targetFile = AK_BASE_DIR.DS.'test'.DS.'report'.DS.$php.DS.$env.DS.'index.html';
        if (!file_exists(dirname($targetFile))) {
            mkdir(dirname($targetFile),0777,true);
        }
        file_put_contents($targetFile,$result);
        chdir($currentDir);
    }
    function runCommand($php,$filename,$environment)
    {
        $this->drawBox(array($filename,strtoupper($environment),$php));
        $returnVal = $this->_checkWebserver2($this->settings['test-url']);
        if (!$returnVal) {
            $this->error('Webserver is not configured properly. Exiting',true);
        }
        if ($this->prepareEnvironment($environment)){
            $xmlFile = getcwd().DIRECTORY_SEPARATOR.'test-results-'.$php.'-'.str_replace(' ','-',$environment).'.xml';
            $command = $this->settings['executables'][$php].' '.$filename.' --xml '.$xmlFile.' "'.$php.'" "'.$environment.'"';
            if (!empty($this->test_files)) {
                $command.=' "'.implode('" "',$this->test_files).'" ';
            }
            if ($this->options['test_mode']){
                echo "Executing: ".$command."\n\r";
                $return_value = 0;
            }else{
                echo "Executing: ".$command."\n\r";
                passthru($command,$return_value);
            }
            $this->_generateReport($xmlFile,$php,$environment);
            return $return_value;
        }
    }

    function drawBox($message)
    {
        $this->drawNewline();
        $this->drawLine();
        $this->drawNewline();
        echo " TARGET: ".join(', ',$message)."\n\r";
        $this->drawLine();
        $this->drawNewline();
    }

    function drawHeader()
    {
        #$this->drawLine('+');
    }

    function drawFooter()
    {
        $this->drawNewline();
        $this->drawLine('+');
        $this->drawNewline();
        echo "FINISHED. ";
        $this->drawNewline();
        if (!$this->hadError()) echo " All fine.";
    }

    function drawRepeatIndicator($actual)
    {
        if ($this->timesToRun() == 1) return;

        $this->drawNewline(2);
        echo str_pad('# '.$actual.'. ',80,'#');
    }

    function drawLine($char='-',$num=80)
    {
        echo str_pad('',$num,$char);
    }

    function drawNewline($multiplier=1)
    {
        echo str_repeat("\n\r",$multiplier);
    }

    function drawHelp()
    {
        echo <<<BANNER
Usage:

ci_tests [php4|php5] [mysql|postgres|sqlite] [-b] [test-files]
   -b   break on first error
   -t   test-mode, don't run the commands actually
   -n x repeat tests x times
   -?   this help

Examples:
> ci_tests 
run all unit tests in any combination.

> ci_tests php5 postgres mysql AkHasMany AkBelongsTo
run AkHasMany and AkBelongsTo on PHP5 using the postgres and mysql-db.

Setup:
1.  Copy DEFAULT-ci-config.yaml to config/ci-config.yaml and set it up  

2.  Copy config/config.php to config/mysql-testing.php, config/postgres-testing.php [...] and modify the database settings at least for the testing environment. You can configure the filename for these config-files in the script directly if you must.

3.  Expects to be run from inside the test folder structure. So to speak your current directory must be */test or a subdir. The script itself can be placed whereever you want. You can define a (shell-)macro and quickly swap between different installations and test again. ;-) 

This script backups config/config.php to config-backup.php (and restores it after run).

BANNER;
        exit;
    }
}
$test_args = array(
'Myself_will_be_thrown_away',
"all",
#"-b",
#"-?",
"-t",
#"-n","2",
#'AkHasMany.php',
#'postgres'
);
#CI_Tests::main($test_args);
CI_Tests::main();

?>