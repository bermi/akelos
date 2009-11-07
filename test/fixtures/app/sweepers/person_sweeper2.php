<?php
require_once(AK_LIB_DIR.DS.'AkActionController'.DS.'AkCacheSweeper.php');

class PersonSweeper2 extends AkCacheSweeper
{
    var $observe = 'Person';
    
    function afterCreate(&$record)
    {
        $this->expirePage(array('controller'=>'cache_sweeper2','action'=>'listing'));
    }
    
    function afterSave(&$record)
    {
        $this->expirePage(array('controller'=>'cache_sweeper2','action'=>'listing'),'*');
        $this->expireAction(array('controller'=>'cache_sweeper2','action'=>'show','id'=>$record->id,'lang'=>'*'));
    }
    
    function beforeDestroy(&$record)
    {
        $this->expirePage(array('controller'=>'cache_sweeper2','action'=>'show','id'=>$record->id));
    }
}