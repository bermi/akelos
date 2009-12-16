<?php

// Routes define how different parts of your application are accessed via URLs
// if you're new to Akelos the default routes will work for you

$Map->connect('/:controller/:action/:id', array('controller' => 'page', 'action' => 'index'));
$Map->connect('/', array('controller' => 'page', 'action' => 'index'));

