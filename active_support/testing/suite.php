<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

defined('AK_UNIT_TEST_SUITE')                   || define('AK_UNIT_TEST_SUITE',true);
defined('AK_TEST_DEFAULT_REPORTER')             || define('AK_TEST_DEFAULT_REPORTER', 'AkelosTextReporter');
defined('AK_UNIT_TEST_SUITE_GLOBAL_NAMESPACE')  || define('AK_UNIT_TEST_SUITE_GLOBAL_NAMESPACE', 'Akelos');
defined('AK_TESTING_URL')                       || define('AK_TESTING_URL',   'http://akelos.tests');
defined('AK_DATABASE_SETTINGS_NAMESPACE')       || define('AK_DATABASE_SETTINGS_NAMESPACE', 'database');

require_once(AK_CONTRIB_DIR.DS.'simpletest'.DS.'unit_tester.php');
require_once(AK_CONTRIB_DIR.DS.'simpletest'.DS.'mock_objects.php');
require_once(AK_CONTRIB_DIR.DS.'simpletest'.DS.'reporter.php');
require_once(AK_CONTRIB_DIR.DS.'simpletest'.DS.'web_tester.php');
require_once(AK_CONTRIB_DIR.DS.'simpletest'.DS.'extensions'.DS.'junit_xml_reporter.php');

class AkUnitTestSuite extends TestSuite
{
    public $baseDir = '';
    public $partial_tests = array();
    public $title = 'Akelos Tests';
    public $running_from_config = false;

    public function __construct($label = false) {
        if(!$label){
            $this->_init();
        }else {
            parent::__construct($label);
        }
    }


    static function runFromOptions($options = array()) {
        $default_options = array(
        'base_path' => AK_TEST_DIR,
        'TestSuite' => null,
        'reporter'  => AK_TEST_DEFAULT_REPORTER,
        'files'  => array(),
        );
        $options = array_merge($default_options, $options);

        $descriptions = array();
        if(!empty($options['files'])){
            $full_paths = array();
            foreach ($options['files'] as $file){
                list($suite, $case) = explode('/', $file.'/');
                $case = str_replace('.php', '', $case);
                $full_paths[] = $options['base_path'].DS.$suite.DS.'cases'.DS.$case.'.php';
                $descriptions[AkInflector::titleize($suite)][] = AkInflector::titleize($case);
            }
            $options['files'] = $full_paths;
        }

        AkUnitTestSuite::createTestingDatabaseIfNotAvailable();

        if(!empty($options['component'])){
            $components = Ak::toArray($options['component']);

            $real_base_path = $options['base_path'];

            $options['description'] = '';
            $options['files'] = array();

            foreach ($components as $component) {

                $options['base_path'] = $real_base_path.DS.$component;
                if(empty($options['suites'])){
                    $options['suites'] = array_diff(glob($options['base_path'].DS.'*'), array(''));
                }else{
                    $options['suites'] = Ak::toArray($options['suites']);
                }
                $options['description'] .= AkInflector::titleize($component)." unit tests: Suites(";
                foreach ($options['suites'] as $k=>$suite){
                    $suite_name = $options['suites'][$k] = trim(str_replace($options['base_path'].DS, '', $suite), DS);
                    if(is_dir($options['base_path'].DS.$suite_name)){
                        $options['description'] .= $suite_name.',';
                        $options['files'] = array_merge($options['files'], array_diff(glob($options['base_path'].DS.$suite_name.DS.'cases'.DS.'*.php'), array('')));
                    }else{
                        unset($options['suites'][$k]);
                    }
                }
                $options['description'] = str_replace(' Suites():','', trim($options['description'], ', ')."):\n");

                if(empty($options['title'])){
                    $options['title'] = AkUnitTestSuite::getTestTitle($options);
                }
            }
        }else{

            $options['description'] = '';
            foreach ($descriptions as $suite => $cases){
                $options['description'] .= "$suite (cases): ".$options['description'].rtrim(join(', ', $cases), ', ')."\n";
            }
            if(empty($options['description'])){
                $options['description'] =  AkInflector::titleize($options['suite']).' (suite)';
                $options['files'] = array_diff(glob($options['base_path'].DS.$options['suite'].DS.'cases'.DS.'*.php'), array(''));
            }

            if(empty($options['title'])){
                $suite_name = empty($options['suite']) ? preg_replace('/.+\/([^\/]+)\/cases.+/', '$1', @$options['files'][0]) : $options['suite'];

                AkConfig::setOption('testing_url', AK_TESTING_URL);
                AkConfig::setOption('memcached_enabled', AkMemcache::isServerUp());

                AkUnitTestSuite::checkIfTestingWebserverIsAccesible($options);

                $dabase_settings = AK_DATABASE_SETTINGS_NAMESPACE == 'database' ? Ak::getSetting('database', 'type') : AK_DATABASE_SETTINGS_NAMESPACE;
                $options['title'] =  "PHP ".phpversion().", Environment: ".AK_ENVIRONMENT.", Database: ".$dabase_settings.
                (AkConfig::getOption('memcached_enabled', false)?', Memcached: enabled':'').
                (AkConfig::getOption('webserver_enabled', false)?', Testing URL: '.AkConfig::getOption('testing_url'):', Testing URL: DISABLED!!!').
                "\n"."Error reporting set to: ".AkConfig::getErrorReportingLevelDescription()."\n".trim($options['description']).'';
            }
        }

        $options['TestSuite'] = new AkUnitTestSuite($options['title']);
        $options['TestSuite']->running_from_config = true;

        if(empty($options['files'])){
            $component = AkInflector::underscore($options['component']);
            if(AkInflector::underscore(AK_APP_NAME) == $component){
                $options['files'] = glob(AK_TEST_DIR.DS.'unit'.DS.'*.php');
            }else{
                $options['files'] = glob(AK_TEST_DIR.DS.'unit'.DS.$component.'*.php');
            }
            if(empty($options['files'])){
                trigger_error('Could not find test cases to run.', E_USER_ERROR);
            }
        }
        foreach ($options['files'] as $file){
            $options['TestSuite']->addFile($file);
        }

        //($options['TestSuite']->run(new $options['reporter']()) ? 0 : 1); file_put_contents(AK_LOG_DIR.DS.'included_files.php', var_export(get_included_files(), true)); return;
        exit ($options['TestSuite']->run(new $options['reporter']()) ? AkUnitTestSuite::runOnFailure(@$options['on_failure']) : AkUnitTestSuite::runOnSuccess(@$options['on_success']));
    }

    static function runFunctionalFromOptions($options){
        $default_options = array(
        'base_path' => AK_TEST_DIR,
        'TestSuite' => null,
        'description' => null,
        'reporter'  => AK_TEST_DEFAULT_REPORTER,
        'files'  => array(),
        );
        $options = array_merge($default_options, $options);

        if(empty($options['title'])){
            $options['title'] = AkUnitTestSuite::getTestTitle($options);
        }

        $options['TestSuite'] = new AkUnitTestSuite($options['title']);
        $options['TestSuite']->running_from_config = true;

        foreach ($options['files'] as $file){
            $options['TestSuite']->addFile($file);
        }

        exit ($options['TestSuite']->run(new $options['reporter']()) ? AkUnitTestSuite::runOnFailure(@$options['on_failure']) : AkUnitTestSuite::runOnSuccess(@$options['on_success']));
    }

    static function getTestTitle($options){
        AkConfig::setOption('testing_url', AK_TESTING_URL);
        AkConfig::setOption('memcached_enabled', AkMemcache::isServerUp());
        AkUnitTestSuite::checkIfTestingWebserverIsAccesible($options);
        $dabase_settings = AK_DATABASE_SETTINGS_NAMESPACE == 'database' ? Ak::getSetting('database', 'type') : AK_DATABASE_SETTINGS_NAMESPACE;
        return "PHP ".phpversion().", Environment: ".AK_ENVIRONMENT.", Database: ".$dabase_settings.
        (AkConfig::getOption('memcached_enabled', false)?', Memcached: enabled':'').
        (AkConfig::getOption('webserver_enabled', false)?', Testing URL: '.AkConfig::getOption('testing_url'):', Testing URL: DISABLED!!!').
        "\n"."Error reporting set to: ".AkConfig::getErrorReportingLevelDescription()."\n".trim($options['description']).'';
    }

    static function createTestingDatabaseIfNotAvailable() {
        if(!file_exists(AkConfig::getDir('config').DS.'database.yml')){
            $Config = new AkConfig();
            $Config->readConfigYaml('database', 'default:
    type: sqlite
    host:
    database_name:
    database_file: '.AK_TMP_DIR.DS.'akelos.sqlite
    user:
    password:
    options: '
    );
        }
    }

    static function checkIfTestingWebserverIsAccesible($options = array()) {
        if(AkConfig::getOption('webserver_enabled', false)){
            return ;
        }
        if(!AK_WEB_REQUEST && file_exists($options['base_path'].DS.'ping.php')){
            $uuid = Ak::uuid();
            file_put_contents($options['base_path'].DS.'akelos_test_ping_uuid.txt', $uuid);
            AkConfig::setOption('webserver_enabled', @file_get_contents(AkConfig::getOption('testing_url').'/'.basename($options['base_path']).'/ping.php') == $uuid);
            unlink($options['base_path'].DS.'akelos_test_ping_uuid.txt');
        }else{
            AkConfig::setOption('webserver_enabled', false);
        }
    }

    static function getPossibleCases($options = array()) {
        $default_options = array(
        'config'    => AK_TEST_DIR.DS.(AK_UNIT_TEST_SUITE_GLOBAL_NAMESPACE == 'Akelos' ? 'core_tests' : AkInflector::underscore(AK_UNIT_TEST_SUITE_GLOBAL_NAMESPACE)).'.yml',
        'base_path' => AK_TEST_DIR,
        );
        $options = array_merge($default_options, $options);
        return array_map(array('AkInflector', 'camelize'),  (array)array_keys(Ak::convert('yaml', 'array', file_get_contents($options['config']))));
    }

    static function ensureTmpDirPermissions(){
        defined('AK_TMP_DIR')   ||  define('AK_TMP_DIR', AK_BASE_DIR.DS.'tmp');
        if(!is_dir(AK_TMP_DIR)){
            !mkdir(AK_TMP_DIR);
        }
        if(!AK_WIN){
            $cmd = 'chmod 777 '.AK_TMP_DIR;
            echo `$cmd`;
        }
    }

    static function cleanupTmpDir() {

        if(strstr(AK_BASE_DIR, AK_TMP_DIR)){
            return;
        }

        clearstatcache();
        $files = glob(AK_TMP_DIR.DS.'*');
        $files = array_diff($files, array(''));

        foreach ($files as $file){
            if(!is_dir($file)){
                if($file != '.gitignore'){
                    unlink($file);
                }
            }else{
                AkFileSystem::rmdir_tree($file);
            }
        }

        $framework_testing_tmp = AK_FRAMEWORK_DIR.DS.'app_layout'.DS.'tmp'.DS.'testing'.DS.'web';
        if(is_dir($framework_testing_tmp)){
            AkFileSystem::directory_delete($framework_testing_tmp);
        }
    }

    public function log($message) {
        if (AK_LOG_EVENTS){
            $this->logger->log('unit-test',$message);
        }
    }

    public function run($reporter) {
        if($this->running_from_config || empty($this->_test_cases)){
            return parent::run($reporter);
        }
        $reporter->paintGroupStart($this->getLabel(), $this->getSize());
        for ($i = 0, $count = count($this->_test_cases); $i < $count; $i++) {
            if (is_string($this->_test_cases[$i])) {
                $class = $this->_test_cases[$i];
                $test = new $class();
                //$this->log('Running test-class:'.$class);
                $test->run($reporter);
            } else {
                //$this->log('Running test-class:'.$this->_test_cases[$i]->_label);
                $this->_test_cases[$i]->run($reporter);
            }
        }
        $reporter->paintGroupEnd($this->getLabel());
        return $reporter->getStatus();
    }

    public function _includeFiles($files) {
        foreach ($files as $test) {
            if (!is_dir($test)) {
                if (!in_array($test,$this->excludes)) {
                    $this->log('Including testfile:'.$test);
                    $this->addTestFile($test);
                }
            } else {
                $dirFiles = glob($test.DS.'*');
                $this->_includeFiles($dirFiles);
            }
        }
    }
    public function _init() {
        $this->logger = &Ak::getLogger();
        $base = AK_TEST_DIR.DS.'unit'.DS.'lib'.DS;
        $this->GroupTest($this->title);
        $allFiles = glob($base.$this->baseDir);
        if (isset($this->excludes)) {
            $excludes = array();
            $this->excludes = @Ak::toArray($this->excludes);
            foreach ($this->excludes as $pattern) {
                $excludes = array_merge($excludes,glob($base.$pattern));
            }
            $this->excludes = $excludes;
        } else {
            $this->excludes = array();
        }
        if (count($allFiles)>=1 && $allFiles[0]!=$base.$this->baseDir && $this->partial_tests === true) {
            $this->_includeFiles($allFiles);
        } else if (is_array($this->partial_tests)){
            foreach ($this->partial_tests as $test) {
                //$this->log('Including partial testfile:'.$test);
                $this->addTestFile($base.$this->baseDir.DS.$test.'.php');
            }
        } else {
            echo "No files in : ".$this->title."\n";

        }
    }

    static function runOnSuccess($command){
        if(!empty($command)){
        `$command`;
        }
        return 1;
    }
    static function runOnFailure($command){
        if(!empty($command)){
        `$command`;
        }
        return 0;
    }
}
