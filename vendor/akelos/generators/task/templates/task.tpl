<?php echo '<?php '; ?>

// Command line options are accesible via $options

if(!empty($options['help'])){
    die(<<<HELP
Describe your task.

Valid options are:

    --help      Shows this message

HELP
);
}


// Your task code