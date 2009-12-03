<?php

array_shift($argv);
$command = join(' ',$argv);

$Generator = new AkelosGenerator();
$Generator->runCommand($command);

echo "\n";

