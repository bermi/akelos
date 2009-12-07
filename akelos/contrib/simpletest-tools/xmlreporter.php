<?php
require_once(AK_VENDOR_DIR.DS.'simpletest'.DS.'reporter.php');
class XmlReporter extends TextReporter {

    var $group_tests = array();
    var $group_depth=0;
    var $current_test;
    var $current_group;
    var $fail_messages = array();
    var $_sizes = array();
    var $_failCounts = array();
    var $_exceptionCounts = array();
    var $phpversion;
    var $backend;
    var $_outstring;
    function XmlReporter($character_set = 'ISO-8859-1', $phpversion='php5', $backend='mysql') {
        $this->SimpleReporter();
        $this->phpversion=$phpversion;
        $this->backend=$backend;
        $this->_character_set = $character_set;

    }
    function paintHeader($test_name) {
        $this->_starttime = time()+microtime(true);
        if ($this->group_depth == 1) {
            ob_start();
        }
    }
    var $testMethodName;
    function paintMethodStart($test_name){

        parent::paintMethodStart($test_name);
        $this->testMethodName = $this->suiteName.'-'.$this->testFileName.'-'.$test_name;
        $this->_method_starttime = time()+microtime(true);
        if (!isset($this->_sizes[$this->suiteName])) {
            $this->_sizes[$this->suiteName] = 0;
        }
        if (!isset($this->_sizes[$this->testFileName])) {
            $this->_sizes[$this->testFileName] = 0;
        }
        $this->_sizes[$this->testFileName]++;
        $this->_sizes[$this->suiteName]++;
    }
    function paintMethodEnd($test_name) {
        $time = time()+microtime(true) - $this->_method_starttime;
        $out="";
        $out.='<testcase name="'.$test_name.'" file="" time="'.$time.'">';
        parent::paintMethodEnd($test_name);
        if (@$this->_failCounts[$this->testMethodName]+@$this->_exceptionCounts[$this->testMethodName]>0) {
            for($i=0;$i<@$this->_failCounts[$this->testMethodName]+@$this->_exceptionCounts[$this->testMethodName];$i++) {
                $message = @array_shift($this->fail_messages);
                if($message!=null) {
                    $out.='<failure type="General">';
                    $out.="<![CDATA[";
                    $out.=$message;
                    $out.="]]>";
                    $out.='</failure>';
                }
            }
        }
        $out.="</testcase>";
        $this->_out[$this->group_depth][] = $out;
    }

    function paintFormattedMessage($message)
    {
        parent::paintFormattedMessage($message);
        if (!isset($this->_exceptionCounts[$this->suiteName])) {
            $this->_exceptionCounts[$this->suiteName] = 0;
        }
        if (!isset($this->_exceptionCounts[$this->testFileName])) {
            $this->_exceptionCounts[$this->testFileName] = 0;
        }
        if (!isset($this->_exceptionCounts[$this->testMethodName])) {
            $this->_exceptionCounts[$this->testMethodName] = 0;
        }
        $this->_exceptionCounts[$this->testMethodName]++;
        $message = 'Debug Message:'."\n".$message;
        $this->_exceptionCounts[$this->testFileName]++;
        $this->_exceptionCounts[$this->suiteName]++;
        $this->fail_messages[] = $message;
    }
    function paintError($message) {
        parent::paintError($message);
        if (!isset($this->_exceptionCounts[$this->suiteName])) {
            $this->_exceptionCounts[$this->suiteName] = 0;
        }
        if (!isset($this->_exceptionCounts[$this->testFileName])) {
            $this->_exceptionCounts[$this->testFileName] = 0;
        }
        if (!isset($this->_exceptionCounts[$this->testMethodName])) {
            $this->_exceptionCounts[$this->testMethodName] = 0;
        }
        $this->_exceptionCounts[$this->testMethodName]++;
        $message = 'Exception:'."\n".$message;
        $this->_exceptionCounts[$this->testFileName]++;
        $this->_exceptionCounts[$this->suiteName]++;
        $this->fail_messages[] = $message;
    }

    function paintFail($message) {
        parent::paintFail($message);
        $breadcrumb = $this->getTestList();
        array_shift($breadcrumb);
        $out = '';
        $out.= implode(" -> ", $breadcrumb);
        $out.= " -> " . $message . "\n";
        $this->fail_messages[] = $out;
        if (!isset($this->_failCounts[$this->suiteName])) {
            $this->_failCounts[$this->suiteName] = 0;
        }
        if (!isset($this->_failCounts[$this->testFileName])) {
            $this->_failCounts[$this->testFileName] = 0;
        }
        if (!isset($this->_failCounts[$this->testMethodName])) {
            $this->_failCounts[$this->testMethodName] = 0;
        }
        $this->_failCounts[$this->testMethodName]++;
        $this->_failCounts[$this->testFileName]++;
        $this->_failCounts[$this->suiteName]++;
    }
    function paintFooter($test_name) {
        parent::paintFooter($test_name);

    }
    var $suiteName = '';
    var $fileName = '';
    var $testFileName;
    var $testName;
    var $_groupSize = 0;
    var $_subGroupSize=0;
    var $output = array();
    var $_out = array();
    /**
     *    Paints the start of a group test. Will also paint
     *    the page header and footer if this is the
     *    first test. Will stash the size if the first
     *    start.
     *    @param string $test_name   Name of test that is starting.
     *    @param integer $size       Number of test cases starting.
     *    @access public
     */
    function paintGroupStart($test_name, $size) {
        parent::paintGroupStart($test_name, $size);
        if ($this->group_depth == 0) {
            //echo $test_name."\n";
            //ob_start();
            $this->_groupSize = $size;
        } else if ($this->group_depth == 1) {
            // echo $test_name."\n";
            //ob_start();
            $this->_out[$this->group_depth] = array();
            $this->_starttime = time()+microtime(true);
            $this->fileName = $test_name;

            //$this->group_tests[] = array('size'=>$size,'passed'=>0,'failed'=>0,'tests'=>array());
            //$this->current_group = &$this->group_tests[count($this->group_tests)-1];
        } else if ($this->group_depth == 2) {
            $this->suiteName = $test_name;


        } else if ($this->group_depth == 3) {
            //echo $test_name."\n";
            //ob_start();

            $this->_startsubtime = time()+microtime(true);
            $this->testFileName = $test_name;

            //$this->current_group['tests'][] = array('size'=>$size,'passed'=>0,'failed'=>0,'tests'=>array());
            //$this->current_group = $this->current_group['tests'][count($this->current_group['tests'])-1];
        } else if ($this->group_depth ==4) {
            $this->testFileName = $test_name;

        } else {
            //$this->testSuiteName = $test_name;
            //$this->testFileName = $test_name;
        }
        $this->group_depth++;
    }
    function getXml()
    {
        return $this->_outstring;
    }
    /**
     *    Paints the end of a group test. Will paint the page
     *    footer if the stack of tests has unwound.
     *    @param string $test_name   Name of test that is ending.
     *    @param integer $progress   Number of test cases ending.
     *    @access public
     */
    function paintGroupEnd($test_name) {
        parent::paintGroupEnd($test_name);
        $this->group_depth--;
        if ($this->group_depth==0) {
            //$contents = ob_get_contents();
            //ob_end_clean();
            $phpversion='php5';
            $backend="mysql";
            $this->_outstring='<?xml version="1.0" encoding="'.$this->_character_set.'"?>
<testsuites php-version="'.$this->phpversion.'" backend="'.$this->backend.'">';
            //foreach ($this->output as $level=>$out) {
            $this->_outstring.=implode("\n",$this->output);
            //}
            $this->_outstring.='</testsuites>';
            unset($this->output);
            $this->output=array();
            unset($this->_out);
            $this->_out=array();
        } else if ($this->group_depth==1) {
            $time = time()+microtime(true) - $this->_starttime;
            //$contents = ob_get_contents();
            //ob_end_clean();
            $out="";
            //package="'.$this->suiteName.'"
            $out.='<testsuite name="'.$this->suiteName.'" package="'.$this->suiteName.'" file="'.$this->fileName.'"  tests="'.$this->_sizes[$this->suiteName].'" failures="'.(isset($this->_failCounts[$this->suiteName])?$this->_failCounts[$this->suiteName]:0).'" errors="'.(isset($this->_exceptionCounts[$this->suiteName])?$this->_exceptionCounts[$this->suiteName]:0).'" time="'.$time.'">';
            if (isset($this->_out[$this->group_depth]) && is_array($this->_out[$this->group_depth])) {
                foreach ($this->_out[$this->group_depth] as $level=>$string) {
                    $out.=$string;
                }
            }
            $out.="</testsuite>";
            $this->_out[$this->group_depth] = array();
            $this->output[]=$out;
        } else if($this->group_depth==3) {
            //$contents = ob_get_contents();
            //ob_end_clean();
            $time = time()+microtime(true) - $this->_startsubtime;
            $out = "";
            $out.='<testsuite name="'.$this->suiteName.'::'.basename($this->testFileName).'" file="'.$this->testFileName.'" package="'.$this->suiteName.'" tests="'.(isset($this->_sizes[$this->testFileName])?$this->_sizes[$this->testFileName]:0).'" failures="'.(isset($this->_failCounts[$this->testFileName])?$this->_failCounts[$this->testFileName]:0).'" errors="'.(isset($this->_exceptionCounts[$this->testFileName])?$this->_exceptionCounts[$this->testFileName]:0).'" time="'.$time.'">';
            if (isset($this->_out[$this->group_depth+1]) && is_array($this->_out[$this->group_depth+1]) && count($this->_out[$this->group_depth+1])>0) {
                foreach ($this->_out[$this->group_depth+1] as $level=>$string) {
                    $out.=$string;
                }
            } else {
                if (@$this->_failCounts[$this->suiteName]+@$this->_exceptionCounts[$this->suiteName]>0) {
                    for($i=0;$i<@$this->_failCounts[$this->suiteName]+@$this->_exceptionCounts[$this->suiteName];$i++) {
                        $message = @array_shift($this->fail_messages);
                        if($message!=null) {
                            $out.='<error>';
                            $out.="<![CDATA[";
                            $out.=$message;
                            $out.="]]>";
                            $out.='</error>';
                        }
                    }
                }
            }
            $out.="</testsuite>";
            if (!isset($this->_out[1])) {
                $this->_out[1] = array();
            }
            $this->_out[1][]=$out;
            $this->_out[$this->group_depth+1] = array();
        }

        //$this->_fails = 0;
        //$this->_passes = 0;
    }



    /**
     *    Character set adjusted entity conversion.
     *    @param string $message    Plain text or Unicode message.
     *    @return string            Browser readable message.
     *    @access protected
     */
    function _htmlEntities($message) {
        return htmlentities($message, ENT_COMPAT, $this->_character_set);
    }

}