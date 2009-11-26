<?php

defined('AK_UNIT_TEST_SUITE')                   || define('AK_UNIT_TEST_SUITE',true);
defined('AK_TEST_DEFAULT_REPORTER')             || define('AK_TEST_DEFAULT_REPORTER', 'AkelosTextReporter');
defined('AK_UNIT_TEST_SUITE_GLOBAL_NAMESPACE')  || define('AK_UNIT_TEST_SUITE_GLOBAL_NAMESPACE', 'Akelos');
defined('AK_LIB_TESTS_DIRECTORY')               || define('AK_LIB_TESTS_DIRECTORY', AK_TEST_DIR.DS.'unit'.DS.'lib');

require_once(AK_VENDOR_DIR.DS.'simpletest'.DS.'unit_tester.php');
require_once(AK_VENDOR_DIR.DS.'simpletest'.DS.'mock_objects.php');
require_once(AK_VENDOR_DIR.DS.'simpletest'.DS.'reporter.php');
require_once(AK_VENDOR_DIR.DS.'simpletest'.DS.'web_tester.php');
require_once(AK_VENDOR_DIR.DS.'simpletest'.DS.'extensions'.DS.'junit_xml_reporter.php');

class AkUnitTestSuite extends TestSuite
{
    public $baseDir = '';
    public $partial_tests = array();
    public $title = 'Akelos Tests';
    public $running_from_config = false;

    public function __construct($label = false)
    {
        if(!$label){
            $this->_init();
        }else {
            parent::__construct($label);
        }
    }


    static function runFromOptions($options = array())
    {
        $default_options = array(
        'base_path' => AK_LIB_TESTS_DIRECTORY,
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

        $options['description'] = '';
        foreach ($descriptions as $suite => $cases){
            $options['description'] .= "$suite (cases): ".$options['description'].rtrim(join(', ', $cases), ', ')."\n";
        }
        if(empty($options['description'])){
            $options['description'] =  AkInflector::titleize($options['suite']).' (suite)';
            $options['files'] = array_diff(glob($options['base_path'].DS.$options['suite'].DS.'cases'.DS.'*.php'), array(''));
        }

        defined('AK_DATABASE_SETTINGS_NAMESPACE') || define('AK_DATABASE_SETTINGS_NAMESPACE', 'database');

        if(empty($options['title'])){
            $suite_name = empty($options['suite']) ? preg_replace('/.+\/([^\/]+)\/cases.+/', '$1', @$options['files'][0]) : $options['suite'];

            AkConfig::setOption('testing_url', 'http://akelos.tests');
            AkConfig::setOption('memcached_enabled', AkMemcache::isServerUp());
            AkConfig::setOption('webserver_enabled', @file_get_contents(AkConfig::getOption('testing_url').'/'.$suite_name.'/public/ping.php') == 'pong');

            $dabase_settings = AK_DATABASE_SETTINGS_NAMESPACE == 'database' ? Ak::getSetting('database', 'type') : AK_DATABASE_SETTINGS_NAMESPACE;
            $options['title'] =  "PHP ".phpversion().", Environment: ".AK_ENVIRONMENT.", Database: ".$dabase_settings.
            (AkConfig::getOption('memcached_enabled', false)?', Memcached: enabled':'').
            (AkConfig::getOption('webserver_enabled', false)?', Testing URL: '.AkConfig::getOption('testing_url'):'').
            "\n"."Error reporting set to: ".AkConfig::getErrorReportingLevelDescription()."\n".trim($options['description']).'';
        }

        $options['TestSuite'] = new AkUnitTestSuite($options['title']);
        $options['TestSuite']->running_from_config = true;

        if(empty($options['files'])){
            trigger_error('Could not find test cases to run.', E_USER_ERROR);
        }
        foreach ($options['files'] as $file){
            $options['TestSuite']->addFile($file);
        }

        exit ($options['TestSuite']->run(new $options['reporter']()) ? 0 : 1);
    }

    static function runFromConfig($options = array())
    {
        $default_options = array(
        'config'    => AK_TEST_DIR.DS.(AK_UNIT_TEST_SUITE_GLOBAL_NAMESPACE == 'Akelos' ? 'core_tests' : AkInflector::underscore(AK_UNIT_TEST_SUITE_GLOBAL_NAMESPACE)).'.yml',
        'base_path' => AK_LIB_TESTS_DIRECTORY,
        'namespace' => AK_UNIT_TEST_SUITE_GLOBAL_NAMESPACE,
        'TestSuite' => null,
        'reporter'  => AK_TEST_DEFAULT_REPORTER,
        'files'  => array(),
        );

        $options = array_merge($default_options, $options);

        if(empty($options['title'])){
            $options['title'] =  "PHP ".phpversion().", Environment: ".AK_ENVIRONMENT.", Database: ".Ak::getSetting((defined('AK_DATABASE_SETTINGS_NAMESPACE')?AK_DATABASE_SETTINGS_NAMESPACE:'database'), 'type')."\n"."Error reporting set to: ".AkConfig::getErrorReportingLevelDescription()."\n".AkInflector::titleize($options['namespace']).' test suite';
        }

        if(empty($options['TestSuite'])){
            if(isset($this)){
                $options['TestSuite'] = $this;
            }else{
                $options['TestSuite'] = new AkUnitTestSuite($options['title']);
            }
        }

        $options['TestSuite']->running_from_config = true;
        $files = empty($options['files']) ? Ak::convert('yaml', 'array', file_get_contents($options['config'])) : $options['files'];
        if($options['namespace'] == AK_UNIT_TEST_SUITE_GLOBAL_NAMESPACE){
            foreach ($files as $namespace => $file_list){
                $options['TestSuite']->runFromConfig(array_merge($options, array('namespace' => $namespace, 'dont_run' => true)));
            }
        }else{
            $options['namespace'] = AkInflector::camelize($options['namespace']);
            if(empty($files[$options['namespace']])){
                trigger_error('Namespace "'.$options['namespace'].'" not found in "'.$options['config'].'"', E_USER_ERROR);
            }else{
                $files = $files[$options['namespace']];
            }
            foreach ($files as $v){
                $partial_tests = array_diff(glob($options['base_path'].DS.$v.'.php'), array(''));
                if(!empty($partial_tests)){
                    call_user_func_array(array($options['TestSuite'], 'addFile'), $partial_tests);
                }
            }
        }
        if(empty($options['dont_run'])){
            exit ($options['TestSuite']->run(new $options['reporter']()) ? 0 : 1);
        }
    }

    static function getPossibleCases($options = array())
    {
        $default_options = array(
        'config'    => AK_TEST_DIR.DS.(AK_UNIT_TEST_SUITE_GLOBAL_NAMESPACE == 'Akelos' ? 'core_tests' : AkInflector::underscore(AK_UNIT_TEST_SUITE_GLOBAL_NAMESPACE)).'.yml',
        'base_path' => AK_LIB_TESTS_DIRECTORY,
        );
        $options = array_merge($default_options, $options);
        return array_map(array('AkInflector', 'camelize'),  (array)array_keys(Ak::convert('yaml', 'array', file_get_contents($options['config']))));
    }

    public function log($message) {
        if (AK_LOG_EVENTS){
            $this->logger->log('unit-test',$message);
        }
    }

    public function run($reporter)
    {
        if($this->running_from_config){
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

    public function _includeFiles($files)
    {
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
    public function _init()
    {
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

}
