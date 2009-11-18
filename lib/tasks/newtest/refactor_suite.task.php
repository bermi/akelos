<?php

$files = glob(AK_TEST_DIR.'/unit/lib/AkActiveRecord/*');
foreach ($files as $k => $file){
    $content = file_get_contents($file);
    if(preg_match('/class (ActiveRecord_([\w\d_]+)_TestCase) /', $content, $matches)){
        $case_name = AkInflector::underscore(strtolower($matches[2]));
        $old_test_name = $matches[1];
        $new_test_name = AkInflector::camelize($case_name).'_TestCase';

        $content = str_replace($old_test_name, $new_test_name, $content);
        $content = str_replace("require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');", "require_once(dirname(__FILE__).'/../config.php');", $content);

        $new_path = AK_TEST_DIR.'/akelos/active_record/cases/'.$case_name.'.php';
        Ak::file_put_contents($new_path, $content);
    }
}