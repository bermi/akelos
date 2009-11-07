<?php

// You can find more about routes on /lib/AkRouters.php and /test/test_AkRouter.php

$Map->connect('/:controller/:action/:id', array('controller' => 'page', 'action' => 'index'));
$Map->connect('/', array('controller' => 'page', 'action' => 'index'));

?>
