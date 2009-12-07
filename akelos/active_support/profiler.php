<?php

if(!function_exists('memory_get_usage')){
    function memory_get_usage() {
        if ( substr(PHP_OS,0,3) == 'WIN') {
            $tmp = explode(',"'.getmypid().'",',`TASKLIST /FO "CSV"`);
            $tmp = explode("\n",$tmp[1]);
            $tmp = explode('"',trim($tmp[0],'"KB '));
            return intval(str_replace(array('.',','),array(''),$tmp[count($tmp)-1]))*1024;
        }else{
            $pid = getmypid();
            exec("ps -o rss -p $pid", $output);
            return $output[1] *1024;
        }
        return false;
    }
}

class AkProfiler
{
    public $_timeStart;
    public $report = '';
    public $_timer = array();

    public function init($message='Initializing profiler') {
        $this->_timeStart = $this->getMicrotime();
        $this->setFlag($message);
    }

    public function getMicrotime() {
        return array_sum(explode(' ',microtime()));
    }

    public function setFlag($flag) {
        $memory = AK_PROFILER_GET_MEMORY ? memory_get_usage() : 1;
        $this->_timer[] = array($this->getMicrotime(), $flag, $memory);
    }

    public function renderReport() {
        $this->setFlag('end');
        $end_time = $this->getMicrotime();
        $report = array();
        $this->report = '';
        $prev_time = $this->_timeStart;
        foreach($this->_timer as $k=>$timer ){
            $initial_memory = !isset($initial_memory) ? $timer[2] : $initial_memory;
            $average = number_format(100*(($timer[0]-$prev_time)/($end_time-$this->_timeStart)),4).' %';

            $memory = ($timer[2]-$initial_memory)/1024;

            $report[] =
            "<li>$average (".($k+1).") {$timer[1]}\t".
            number_format($timer[0]-$this->_timeStart,6)."\t".
            number_format(($timer[0] - $prev_time),6)."\t".
            "$average\t".
            "{$memory} KB (".number_format($timer[2]/1024,2)." KB)\n</li>";

            $prev_time = $timer[0];
        }
        natsort($report);
        $report = array_reverse($report);
        $this->report .= "flag\tstarted\telapsed\taverage\n\n\nTotal time: <ul>".join("\n",$report).number_format($end_time-$this->_timeStart,6)."</ul>\n";
    }

    public function saveReport() {
        if($this->report == ''){
            $this->renderReport();
        }
        Ak::file_put_contents('profiler_results.txt',$this->report);
    }

    public function showReport() {
        if($this->report == ''){
            $this->renderReport();
        }
        echo $this->report;
        $this->saveReport();
    }

}
