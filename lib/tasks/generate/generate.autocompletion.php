<?php

$total_args = count($GLOBALS['argv']);
if($total_args >= 4){
    return;
}

$Generator = new AkelosGenerator();
echo join("\n", $Generator->getAvailableGenerators());