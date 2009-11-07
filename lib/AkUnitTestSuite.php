<?php
require_once(AK_LIB_DIR.DS.'AkUnitTest.php');

if (!defined('AK_UNIT_TEST_SUITE')) { define('AK_UNIT_TEST_SUITE',true);

class AkUnitTestSuite extends GroupTest
{
    var $baseDir = '';
    var $partial_tests = array();
    var $title = 'Akelos Tests';

    function AkUnitTestSuite()
    {
        $this->_init();
        
    }
    
    function log($message) {
        if (AK_LOG_EVENTS){
            $this->logger->log('unit-test',$message);
        }
    }
    function _includeFiles($files)
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
    function _init()
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
    
function run(&$reporter) {
            $reporter->paintGroupStart($this->getLabel(), $this->getSize());
            for ($i = 0, $count = count($this->_test_cases); $i < $count; $i++) {
                if (is_string($this->_test_cases[$i])) {
                    $class = $this->_test_cases[$i];
                    $test = &new $class();
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
}

}