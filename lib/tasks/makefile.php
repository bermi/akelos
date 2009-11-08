<?php

makelos_task('T,tasks', array(
'description' => 'Shows available tasks',
'run' => array(
'php' => <<<PHP
    \$Makelos->displayAvailableTasks();
PHP
)
));

makelos_task('test:units', array(
    'description' => 'Run all unit tests.',
));

makelos_task('test:case', array(
    'description' => 'Runs a single test case file'
));


makelos_task('test:core', array(
    'description' => 'Runs Akelos core test suite'
));

