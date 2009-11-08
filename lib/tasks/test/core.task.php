<?php

$core_tests = array();
if($files = empty($options['files']) ? false :  $options['files']){
    $files = explode(',', $files);
    unset($options['files']);
}else{
    $core_tests = Ak::convert('yaml', 'array', file_get_contents(AK_TEST_DIR.DS.'core_tests.yml'));
}

if($component = empty($options['component']) ? false :  $options['component']){
    $component_title = AkInflector::titleize($component);
    if(empty($core_tests[$component_title])){
        trigger_error("No tests found for the component $component ($component_title)", E_USER_ERROR);
    }else{
        $files = $core_tests[$component_title];
    }
    unset($options['component']);
}

if(empty($files)){
    $files = array();
    foreach ($core_tests as $core_test=> $component_files){
        $files = array_merge($files, $component_files);
    }
}


if($reporter = empty($options['reporter']) ? false :  $options['reporter']){
    unset($options['reporter']);
}

if($base_path = empty($options['base_path']) ? false :  $options['base_path']){
    unset($options['base_path']);
}else{
    $base_path = AK_TEST_DIR.DS.'unit'.DS.'lib';
}

$base_path = rtrim($base_path, DS);

if($db_type = empty($options['db']) ? false :  $options['db']){
    define('AK_DATABASE_SETTINGS_NAMESPACE', $db_type);
    unset($options['db']);
}

$____skip_tests = array('Simple','Unit','Web','AkWeb');

foreach ($files as $k =>$file_path){
    if(!strstr($file_path, '.php')){
        unset($files[$k]);
        $files = array_merge(glob($base_path.DS.$file_path.'.php'), $files);
    } elseif (strstr($file_path, '*')){
        unset($files[$k]);
        $files = array_merge(glob($base_path.DS.$file_path), $files);
    } else {
        $files[$k] = $base_path.DS.ltrim($file_path, DS).(!preg_match('/\.php$/', $file_path)?'.php':'');
    }
}
$files = array_unique($files);
$files = array_reverse($files);
$files = array_diff($files, array(''));

include_once(AK_LIB_DIR.DS.'AkUnitTest.php');

$TestSuite = new TestSuite('Unit tests for Akelos'.(empty($component_title)?'':' ('.$component_title.')'));
$Reporter = new TextReporter();

foreach ($files as $file_path){

    if(!file_exists($file_path)){
        $Logger->message('Could not load test file '.$file_path);
        trigger_error('Could not load test file '.$file_path, E_USER_ERROR);
    }else{
        include $file_path;
        foreach(get_declared_classes() as $____class){
            if(preg_match('/(.+)TestCase$/i', $____class, $match)){
                if(!preg_match('/^('.join('|',$____skip_tests).')$/i',$match[1])){
                    $____skip_tests[] = $match[1];
                    $TestSuite->add($match[1].'TestCase');
                }
            }
        }
    }

}

$TestSuite->run($Reporter);
