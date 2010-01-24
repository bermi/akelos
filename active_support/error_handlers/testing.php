<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

function ak_testing_error_handler($error_number, $error_message, $file, $line) {
    $error_number = $error_number & error_reporting();
    if($error_number == 0){
        return false;
    }
    throw new Exception($error_message);
}

include_once(dirname(__FILE__).DS.'error_functions.php');

set_error_handler('ak_testing_error_handler');
