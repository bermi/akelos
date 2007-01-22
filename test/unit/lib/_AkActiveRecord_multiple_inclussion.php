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
        $Files =& $File->find($ids, array(
        'include'=>array('tags', 'taggings')
        ));

        foreach ($Files as $File){
            foreach ($File->tags as $Tag){
                $this->assertEqual($Tag->name, $LogTag->name);
            }
            foreach ($File->taggings as $Tagging){
                $this->assertEqual($Tagging->tag_id, $LogTag->id);
            }
        }


        /**
         * @todo Implement eager loading for second-level associations
         */
        $File =& new File();
        $Files =& $File->find('all', array(
        'include'=>array('taggings')
        ));

        foreach ($Files as $File){
            foreach ($File->taggings as $Tagging){
                $Tagging->tag->load();
                $this->assertEqual($Tagging->tag->name, $LogTag->name);
                $this->assertEqual($Tagging->tag_id, $LogTag->id);
            }
        }

        /**
         * @todo Implement eager loading for second-level associations
         */
        $Files =& $File->find('all', array(
        'include'=>array('tags')
        ));

        foreach ($Files as $File){
            foreach ($File->tags as $Tag){
                $this->assertEqual($Tag->name, $LogTag->name);
                $Tag->tagging->load();
                foreach ($Tag->taggings as $Tagging){
                    $this->assertEqual($Tagging->tag_id, $LogTag->id);
                }
            }
        }

        $File =& new File();
        $Files =& $File->find('all', array('include'=>array('tags')));

        $tag_ids = array();
        foreach ($Files as $File){
            foreach ($File->tags as $Tag){
                $tag_ids[] = $Tag->getId();
            }
        }

        $Tag =& new Tag();
        $Tags =& $Tag->find($tag_ids, array('include'=>'taggings'));

        foreach (array_keys($Files) as $k){
            foreach (array_keys($Files[$k]->tags) as $m){
                foreach (array_keys($Tags) as $n){
                    if($Tags[$n]->id == $Files[$k]->tags[$m]->id){
                        $Files[$k]->tags[$m]->taggings =& $Tags[$n]->taggings;
                    }
                }
            }
        }
        
    }
}

Ak::test('test_AkActiveRecord_multiple_inclussion',true);

?>
