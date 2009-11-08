<?php

if($reporter = empty($options['reporter']) ? false :  $options['reporter']){
    unset($options['reporter']);
}
if($db_type = empty($options['db']) ? false :  $options['db']){
    define('AK_DATABASE_SETTINGS_NAMESPACE', $db_type);
    unset($options['db']);
}

if(empty($options)){
    $Logger->message('Invalid test name');
    echo "Invalid test name\n";
    return false;
}

$____skip_tests = array('Simple','Unit','Web','AkWeb');

foreach ($options as $_test_file){
    if(preg_match('/^Ak/i', $_test_file)){
        $_test_file = 'unit'.DS.'lib'.DS.$_test_file;
    }elseif(!strstr($test_name, DS)){
        $_test_file = 'unit/app/models/'.AkInflector::underscore($_test_file);
    }


    $_test_file = strstr($_test_file,'.php') ? trim($_test_file, '/') : $_test_file.'.php';
    $_test_file = substr($_test_file,0,5) == 'test/' ? substr($_test_file,5) : $_test_file;
    $_test_file = AK_TEST_DIR.DIRECTORY_SEPARATOR.$_test_file;

    if(!file_exists($_test_file)){
        echo "\nCould not load $_test_file test file\n";
    }else{
        include $_test_file;
        foreach(get_declared_classes() as $____class){
            if(preg_match('/(.+)TestCase$/i', $____class, $match)){
                if(!preg_match('/^('.join('|',$____skip_tests).')$/i',$match[1])){
                    $____skip_tests[] = $match[1];
                    ak_test($match[1].'TestCase', false, true, $reporter);
                }
            }
        }
    }
}
