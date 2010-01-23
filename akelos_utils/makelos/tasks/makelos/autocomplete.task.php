<?php 

$tasks = $Makelos->getAvailableTasksForAutocompletion();

$autocomplete_options = array_keys($options);
$matched = false;

if(!empty($autocomplete_options[0])){

    if(in_array($autocomplete_options[0], $tasks)){
        array_shift($options);
        echo join("\n", (array)$Makelos->getAutocompletionOptionsForTask($autocomplete_options[0], (array)@$options));
        return ;
    }

    foreach ($tasks as $task){
        if(preg_match('/^'. preg_quote($autocomplete_options[0]).'/i', $task)){
            $matched = true;
            echo $task." \n";
        }
    }
}

if(!$matched){
    echo join("\n", $tasks);
}

