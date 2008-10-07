<?php

//
// Simple MemCached client library example
//
require_once('class_MemCachedClient.php');

$hosts = array('127.0.0.1:1234','127.0.0.2:1234');
$mc = &new MemCachedClient($hosts);

// try to get a value
if (!$mc->get("myvalue")) {

// if an error occurred, exit
if ($mc->errno==ERR_NO_SOCKET) {
die("Could not connect to MemCache daemon\n");
}

// set a value
$mc->set("myvalue",1);
}

// increment a counter
$mc->incr('counter');
// decrement a counter
$mc->decr('counter');

// delete a value
$mc->delete("myvalue");



?>
