<?php

if(!isset($options['base_path'])){
    $options['base_path'] = AK_TEST_DIR.DS.AK_TESTING_NAMESPACE;
}

if(isset($options['ci'])){
    unset($options['ci']);
    $options['reporter'] = 'JUnitXMLReporter';
}

if(isset($options['verbose'])){
    unset($options['verbose']);
    $options['reporter'] = 'AkelosVerboseTextReporter';
}

if($reporter = empty($options['reporter']) ? false :  $options['reporter']){
    unset($options['reporter']);
}

if($db_type = empty($options['db']) ? false :  $options['db']){
    define('AK_DATABASE_SETTINGS_NAMESPACE', $db_type);
    unset($options['db']);
}



$valid_options = array('config', 'base_path', 'namespace', 'TestSuite', 'reporter'  => 'TextReporter', 'files');

$options['files'] = array();
$suite = '';
foreach ($options as $k => $v){
    if(!in_array($k, $valid_options)){
        if(!is_bool($v)){
            $v = rtrim($v, DS);
            if(strstr($v, DS)){
                $options['files'][] = $v.'.php';
            }else{
                $suite .= $v.' ';
            }
            unset($options[$k]);
        }
    }
}

if(empty($options['component']) && !empty($suite)){
    $options['component'] = trim($suite);
}

$options = array_diff($options, array(''));

AkUnitTestSuite::runFromOptions($options);
