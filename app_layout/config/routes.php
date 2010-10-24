<?php

// Routes define how different parts of your application are accessed via URLs
// if you're new to Akelos the default routes will work for you

// $Map->root(array('controller' => 'home'));


/**
 * This route will enable the Akelos development panel at / on fresh installs
 * when browsing from localhost.
 * 
 * You need to comment this route or point it to a different base in order to accept
 * Requests in your application.
 * /
 // $Map->connect('/dev_panel/:controller/:action/:id', array(
 $Map->connect('/:controller/:action/:id', array(
              'controller' => 'akelos_dashboard', 
              'action' => 'index', 
              'module' => 'akelos_panel',
              'rebase' => AK_AKELOS_UTILS_DIR.DS.'akelos_panel'
            ), array('module' => 'akelos_panel'));
/* */


$Map->connect(':controller/:action/:id');
$Map->connect(':controller/:action/:id.:format');