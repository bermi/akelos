<?php

makelos_task('T,tasks', array(
'description' => 'Shows available tasks',
'run' => array(
'php' => <<<PHP
    \$Makelos->displayAvailableTasks();
PHP
)
));

makelos_task('test:case', array(
    'description' => 'Runs a single test case file',
    //'autocompletion' => 'ENVIRONMENT=production'
));

makelos_task('test:units', array(
    'description' => 'Run all unit tests'
    //'autocompletion' => 'ENVIRONMENT=production'
));

makelos_task('doc:akelos', array(
    'description' => 'Build the akelos HTML Files'
));

makelos_task('release:generate', array(
    'description' => 'Generates a new release'
));


