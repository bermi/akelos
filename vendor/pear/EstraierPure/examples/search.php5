<?php
define('ESTRAIERPURE_DEBUG', 1);
//define('ESTRAIERPURE_USE_HTTP_STREAM', 1);
error_reporting(E_ALL & ~E_STRICT);
require_once 'estraierpure.php5';

// create and configure the node connecton object
$node = new EstraierPure_Node;
$node->set_url('http://localhost:1978/node/test');

// create a search condition object
$cond = new EstraierPure_Condition;
$cond->set_phrase('water AND mind');

// get the result of search
$nres = $node->search($cond, 0);
if ($nres) {
    // for each document in the result without iteration
    /*for ($i = 0; $i < $nres->doc_num(); $i++) {
        // get a result document object
        $rdoc = $nres->get_doc($i);
        // display attributes
        if (($value = $rdoc->attr('@uri')) !== null) {
            fprintf(STDOUT, "URI: %s\n", $value);
        }
        if (($value = $rdoc->attr('@title')) !== null) {
            fprintf(STDOUT, "Title: %s\n", $value);
        }
        // display the snippet text (with getter method)
        fprintf(STDOUT, "%s", $rdoc->snippet());
    }
    if ($i == 0) {
        fputs(STDERR, "not found.\n");
    }*/
    // for each document in the result as an iterator
    $j = 0;
    foreach ($nres as $rdoc) {
        $j++;
        // display attributes
        if (($value = $rdoc->attr('@uri')) !== null) {
            fprintf(STDOUT, "URI: %s\n", $value);
        }
        if (($value = $rdoc->attr('@title')) !== null) {
            fprintf(STDOUT, "Title: %s\n", $value);
        }
        // display the snippet text (with property overloading)
        fprintf(STDOUT, "%s", $rdoc->snippet);
    }
    if ($j == 0) {
        fputs(STDERR, "not found.\n");
    }
    // debug output
    //var_dump($i, $j, $nres->doc_num());
} else {
    fprintf(STDERR, "error: %d\n", $node->status());
    if (EstraierPure_Utility::errorstack()->hasErrors()) {
        fputs(STDERR, print_r(EstraierPure_Utility::errorstack()->getErrors(), true));
    }
}
?>
