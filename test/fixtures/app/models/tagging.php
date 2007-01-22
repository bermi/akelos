<?php

class Tagging extends AkActiveRecord  
{
    var $belongs_to = array('file', 'tag');
    
}

?>