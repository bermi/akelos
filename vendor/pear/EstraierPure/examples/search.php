<?php
define('ESTRAIERPURE_DEBUG', 1);
//define('ESTRAIERPURE_USE_HTTP_STREAM', 1);
error_reporting(E_ALL);
require_once 'estraierpure.php';

// create and configure the node connecton object
$node = &new EstraierPure_Node;
$node->set_url('http://localhost:1978/node/test');

// create a search condition object
$cond = &new EstraierPure_Condition;
$cond->set_phrase('water AND mind');

// get the result of search
$nres = &$node->search($cond, 0);
if ($nres) {
    // for each document in the result
    for ($i = 0; $i < $nres->doc_num(); $i++) {
        // get a result document object
        $rdoc = &$nres->get_doc($i);
        // display attributes
        if (($value = $rdoc->attr('@uri')) !== null) {
            fputs(STDOUT, sprintf("URI: %s\n", $value));
        }
        if (($value = $rdoc->attr('@title')) !== null) {
            fputs(STDOUT, sprintf("Title: %s\n", $value));
        }
        // display the snippet text
        fputs(STDOUT, sprintf("%s", $rdoc->snippet()));
    }
    if ($i == 0) {
        fputs(STDERR, "not found.\n");
    }
    // debug output
    //var_dump($i, $j, $nres->doc_num());
} else {
    fputs(STDERR, sprintf("error: %d\n", $node->status()));
    $stack = &EstraierPure_Utility::errorstack();
    if ($stack->hasErrors()) {
        fputs(STDERR, print_r($stack->getErrors(), true));
    }
}
?>
