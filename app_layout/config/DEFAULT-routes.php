<?php

// Routes define how different parts of your application are accessed via URLs
// if you're new to Akelos the default routes will work for you


/**
 * This route will enable the Akelos development panel at /dev_panel
 * when browsing from localhost
 * /
$Map->connect('/dev_panel/:controller/:action/:id', array(
              'controller' => 'akelos_dashboard', 
              'action' => 'index', 
              'module' => 'akelos_panel',
              'rebase' => AK_AKELOS_UTILS_DIR.DS.'akelos_panel'
            ));
/* */

$Map->connect('/:controller/:action/:id', array('controller' => 'page', 'action' => 'index'));
$Map->connect('/', array('controller' => 'page', 'action' => 'index'));

