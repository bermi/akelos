<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

$argv = $GLOBALS['argv'];
array_shift($argv);
array_shift($argv);
$command = join(' ',$argv);

$Generator = new AkelosGenerator();
$Generator->runCommand($command);

echo "\n";


