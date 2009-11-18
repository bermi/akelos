<?php

if($db_type = empty($options['db']) ? false :  $options['db']){
    define('AK_DATABASE_SETTINGS_NAMESPACE', $db_type);
    unset($options['db']);
}

$valid_options = array('config', 'base_path', 'namespace', 'TestSuite', 'reporter'  => 'TextReporter', 'files');

$namespace_candidate = '';

foreach ($options as $k => $v){
    if(!in_array($k, $valid_options)){
        if(!is_bool($v)){
            $namespace_candidate .= $v.' ';
            unset($options[$k]);
        }
    }
}
if(empty($options['namespace']) && !empty($namespace_candidate)){
    $options['namespace'] = trim($namespace_candidate);
}

if(isset($options['ci'])){
    unset($options['ci']);
    $options['reporter'] = 'JUnitXMLReporter';
}

if(isset($options['verbose'])){
    unset($options['verbose']);
    $options['reporter'] = 'AkelosVerboseTextReporter';
}


AkUnitTestSuite::runFromConfig($options);

