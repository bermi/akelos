<?php

$total_args = count($GLOBALS['argv']);

if($total_args > 5){
    return;
}

$Generator = new AkelosGenerator();
echo "--help\n";
if($total_args <= 4){
    echo join("\n", $Generator->getAvailableGenerators());
}
