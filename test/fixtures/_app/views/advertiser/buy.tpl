<?php

echo $Template->render(array('partial' =>'account','locals'=>array('account'=>$buyer))); 

foreach($advertisements as $ad) :
echo $Template->render(array('partial'=>'ad','locals'=>array('ad'=>$ad)));
endforeach;

?>