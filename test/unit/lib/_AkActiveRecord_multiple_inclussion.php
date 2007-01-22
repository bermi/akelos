<?php

defined('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION') ? null : define('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION', false);
defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);

require_once(dirname(__FILE__).'/../../fixtures/config/config.php');

class test_AkActiveRecord_multiple_inclussion extends  AkUnitTest
{
    function test_start()
    {
        $this->installAndIncludeModels(array('File', 'Tag','Tagging'));
    }


    function test_for_multiple_inclussion()
    {
        $AkelosLogFile =& new File(array('name'=>'akelos.log'));
        $this->assertTrue($AkelosLogFile->save());        

        $LogTag =& $AkelosLogFile->tag->create(array('name'=>'logs'));
        
        $KasteLogFile =& new File(array('name'=>'kaste.log'));
        $this->assertTrue($KasteLogFile->save());
        
        $KasteLogFile->tag->add($LogTag);
        
        
        $BermiLogFile =& new File(array('name'=>'bermi.log'));
        $this->assertTrue($BermiLogFile->save());
        
        $BermiLogFile->tag->add($LogTag);
        
        
        $ids = array($AkelosLogFile->getId(), $KasteLogFile->getId(), $BermiLogFile->getId());
        
        $File =& new File();
        $Files =& $File->find($ids, array('include'=>array('tags', 'taggings')));
        
        foreach ($Files as $File){
            foreach ($File->tags as $Tag){
                $this->assertEqual($Tag->name, $LogTag->name);
            }
            foreach ($File->taggings as $Tagging){
                $this->assertEqual($Tagging->tag_id, $LogTag->id);
            }
        }
    }
}

Ak::test('test_AkActiveRecord_multiple_inclussion',true);

?>
