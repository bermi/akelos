<?php

// You can find more about routes on /lib/AkRouters.php and /test/test_AkRouter.php

$Map->connect('/intranet/:controller/:action/:id', array('controller' => 'login', 'action' => 'index', 'module'=>'intranet'));
$Map->connect('/:controller/:action/:id', array('controller' => 'page', 'action' => 'index'));
$Map->connect('/', array('controller' => 'page', 'action' => 'index'));

?>