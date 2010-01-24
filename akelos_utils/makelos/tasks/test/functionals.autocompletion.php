<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

$base_files = array();

$base_path   = AK_TEST_DIR.DS.'functional'.DS.'controllers';

$controllers = glob($base_path.DS.'*_controller_test.php');
foreach ($controllers as $k => $controller){
    if(is_file($controller)){
        $suggestions[] = trim(str_replace(array($base_path, DS, '_controller_test.php'), '', $controller), DS);
    }
}

echo join("\n", $suggestions);
