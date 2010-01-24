<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

$total_args = count($GLOBALS['argv']);

if($total_args > 5){
    return;
}

$Generator = new AkelosGenerator();
echo "--help\n";
if($total_args <= 4){
    echo join("\n", $Generator->getAvailableGenerators());
}
