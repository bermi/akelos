<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2006, Akelos Media, S.L.  & Bermi Ferrer Martinez |
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
 * @package ActiveSupport
 * @subpackage Scripts
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

if(preg_match('/^model/i', $argv[0])){
    array_shift($argv);
    foreach ($argv as $k=>$v){
        $argv[$k] = 'unit/app/models/'.AkInflector::underscore($v);
    }
}elseif(preg_match('/^Ak[a-zA-Z]+/', $argv[0]) && is_dir(AK_BASE_DIR.DS.'test'.DS.'unit'.DS.'lib')){
    foreach ($argv as $k=>$v){
        $argv[$k] = 'unit/lib/'.$v;
    }
}

$____skip_tests = array('Simple','Unit','Web','AkWeb');

foreach ($argv as $_test_file){
    $_test_file = strstr($_test_file,'.php') ? trim($_test_file, '/') : $_test_file.'.php';
    $_test_file = substr($_test_file,0,5) == 'test/' ? substr($_test_file,5) : $_test_file;
    $_test_file = $tests_dir.DIRECTORY_SEPARATOR.$_test_file;
    if(!file_exists($_test_file)){
        echo "\nCould not load $_test_file test file\n";
    }else{
        require($_test_file);
            foreach(get_declared_classes() as $____class){
                if(preg_match('/(.+)TestCase$/i', $____class, $match)){
                    if(!preg_match('/^('.join('|',$____skip_tests).')$/i',$match[1])){
                        $____skip_tests[] = $match[1];
                        ak_test($match[1].'TestCase', true);
                    }
                }
            }
        echo $_test_file."\n";
    }
}




?>
