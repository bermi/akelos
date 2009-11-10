<?php

$base_files = array();

$files = glob(AK_TEST_DIR.DS.'unit'.DS.'lib'.DS.'*.php');
$files = array_merge($files, glob(AK_TEST_DIR.DS.'unit'.DS.'lib'.DS.'**'.DS.'*.php'));

$autocomplete_options = array();
foreach ($files as $file){
    if(!is_dir($file)){
        $autocomplete_options[] =
        $base_files[] = str_replace(array(AK_TEST_DIR.DS.'unit'.DS.'lib'.DS, '.php'), '', $file);
    }
}

$autocomplete_options = array_keys($options);
$matched = false;

if(!empty($autocomplete_options[0])){
    foreach ($base_files as $base_file){
        if(preg_match('/^'. str_replace('/','\/', preg_quote($autocomplete_options[0])).'/i', $base_file)){
            $matched = true;
            echo $base_file." \n";
        }
    }
    if(!$matched){
        return ;
    }
}

if(!$matched){
    echo join("\n", $base_files);
}
