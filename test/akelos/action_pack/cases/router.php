<?php

require_once(dirname(__FILE__).'/../config.php');

class RouterUnitTest extends AkRouterUnitTest
{

}

$_router_files = glob(dirname(__FILE__).DS.'router'.DS.'*.php');
$_included_files = get_included_files();
if(count($_included_files) == count(array_diff($_included_files, $_router_files))){
    foreach ($_router_files as $file){
        include $file;
    }
}

unset($_router_files);
unset($_included_files);
