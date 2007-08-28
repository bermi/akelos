<?php

if(!defined('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION')){
    define('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION',false);
}

defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);
require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');


class test_AkActiveRecord_belongsTo_Associations extends  AkUnitTest 
{
    /**/
    function test_start()
    {
        require_once(AK_LIB_DIR.DS.'AkActiveRecord.php');
        require_once(AK_LIB_DIR.DS.'AkInstaller.php');
        require_once(AK_LIB_DIR.DS.'AkActiveRecord'.DS.'AkHasOne.php');
        require_once(AK_LIB_DIR.DS.'AkActiveRecord'.DS.'AkBelongsTo.php');
        require_once(AK_APP_DIR.DS.'shared_model.php');
        require_once(AK_APP_DIR.DS.'installers'.DS.'framework_installer.php');
        $installer = new FrameworkInstaller();
        $installer->uninstall();
        $installer->install();
        $models = array('Picture', 'Thumbnail','Panorama', 'Property', 'PropertyType', 'Person', 'Account');
        $this->installAndIncludeModels(array('Picture', 'Thumbnail','Panorama', 'Property', 'PropertyType', 'Person', 'Account'));
        unset($_SESSION['__activeRecordColumnsSettingsCache']);
    }

    /**/
    function test_for_single_has_one_association()
    {
        $Picture =& new Picture(array('title'=>'The Akelos Media Team at SIMO'));
        $this->assertReference($Picture, $Picture->hasOne->Owner);
        $this->assertEqual($Picture->main_thumbnail->getAssociationId(), 'main_thumbnail');
        $this->assertEqual($Picture->main_thumbnail->getType(), 'hasOne');
        $this->assertFalse($Picture->main_thumbnail->getId());

        $Thumbnail =& new Thumbnail(array('caption'=>'SIMO 2005'));

        $Picture->main_thumbnail->assign($Thumbnail);

        $this->assertEqual($Picture->main_thumbnail->getAssociationId(), 'main_thumbnail');
        $this->assertEqual($Picture->main_thumbnail->getType(), 'Thumbnail');
        $this->assertEqual($Picture->main_thumbnail->getAssociationType(), 'hasOne');
        $this->assertFalse($Picture->main_thumbnail->getId());

        $this->assertTrue($Picture->save());
        $this->assertFalse($Picture->isNewRecord());
        $this->assertFalse($Thumbnail->isNewRecord());

        $this->assertReference($Thumbnail, $Picture->main_thumbnail);

        $SimoPic = $Picture->findFirstBy('title:has','SIMO');
        $this->assertTrue(empty($SimoPic->main_thumbnail->caption));

        $this->assertEqual($SimoPic->main_thumbnail->getAssociatedType(), 'hasOne');

        $this->assertEqual($Picture->main_thumbnail->getAssociatedType(), 'hasOne');

        $SimoPic = $Picture->findFirstBy('title:has','SIMO', array('include'=>'main_thumbnail'));

        $this->assertEqual($SimoPic->title, 'The Akelos Media Team at SIMO');
        $this->assertEqual($SimoPic->main_thumbnail->caption, 'SIMO 2005');

        $Picture =& new Picture(array('title'=>'The Akelos Media Team at CeBIT'));
        $Picture->main_thumbnail->build(array('caption' => 'CeBIT 2005'));
        $this->assertTrue($Picture->save());
        $this->assertFalse($Picture->isNewRecord());
        $this->assertFalse($Picture->main_thumbnail->isNewRecord());

        $CeBitPic =& $Picture->findFirstBy('title:has','CeBIT', array('include'=>'main_thumbnail'));

        $this->assertEqual($CeBitPic->title, 'The Akelos Media Team at CeBIT');
        $this->assertEqual($CeBitPic->main_thumbnail->caption, 'CeBIT 2005');

        $Picture =& new Picture(array('title'=>'The Akelos Media Team at Carlet'));

        $this->assertTrue($Picture->save());

        $this->assertFalse($Picture->findFirstBy('title:has','Carlet', array('include'=>'main_thumbnail')));

        $this->assertTrue($CarletPic =& $Picture->findFirstBy('title:has','Carlet', array('include'=>array('main_thumbnail'=>array('conditions'=>false)))));
        $this->assertEqual($CarletPic->title,'The Akelos Media Team at Carlet');

        ///////////
        $this->assertReference($CarletPic->main_thumbnail->_AssociationHandler->Owner, $CarletPic);

        $CarletPic =& $Picture->findFirstBy('title:has','Carlet');

        ///////////
        $this->assertReference($CarletPic->main_thumbnail->_AssociationHandler->Owner, $CarletPic);

        $this->assertEqual($CarletPic->title, 'The Akelos Media Team at Carlet');
        $this->assertEqual($CarletPic->main_thumbnail->getType(), 'hasOne');

        $CarletPic->main_thumbnail->create(array('caption'=>'Carlet'));

        ///////////
        $this->assertReference($CarletPic->main_thumbnail->_AssociationHandler->Owner, $CarletPic);

        $this->assertFalse($CarletPic->main_thumbnail->isNewRecord());

        $CarletPic =& $Picture->findFirstBy('title:has','Carlet', array('include'=>'main_thumbnail'));
        $this->assertEqual($CarletPic->main_thumbnail->caption, 'Carlet');

        ///////////
        $this->assertReference($CarletPic->main_thumbnail->_AssociationHandler->Owner, $CarletPic);

        $this->assertTrue($SimoPic->destroy());

        $this->assertFalse($Picture->findFirstBy('title:has','SIMO', array('include'=>'main_thumbnail')));

        $this->assertFalse($Thumbnail->findFirstBy('caption','SIMO 2005'));

        $Thumbnail =& new Thumbnail(array('caption'=>'Our Office'));

        ///////////
        $this->assertReference($CarletPic->main_thumbnail->_AssociationHandler->Owner, $CarletPic);

        $this->assertReference($CarletPic->main_thumbnail->replace($Thumbnail), $Thumbnail);
        $this->assertReference($CarletPic->main_thumbnail, $Thumbnail);
        //$this->assertReference($CarletPic->prueba, $Thumbnail);

        $this->assertTrue($CarletPic->save());
        
        $this->assertEqual($CarletPic->main_thumbnail->caption, 'Our Office');

        $this->assertFalse($Thumbnail->findFirstBy('caption','Carlet'));
                
        $this->assertTrue($OfficeThumbnail =& $Thumbnail->findFirstBy('caption', 'Our Office'));
        $this->assertEqual($OfficeThumbnail->getId(), $CarletPic->main_thumbnail->getId());
        $Thumbnail =& new Thumbnail(array('caption'=>'Lucky (our pet)'));

        $CarletPic->main_thumbnail->replace($Thumbnail);
        $this->assertTrue($CarletPic->save());

        $CarletPic =& $Picture->findFirstBy('title:has','Carlet', array('include'=>'main_thumbnail'));
        $this->assertEqual($CarletPic->main_thumbnail->caption, 'Lucky (our pet)');

        $CarletPic =& $Picture->findFirstBy('title:has','Carlet');
        $CarletPic->main_thumbnail->load();
        $this->assertEqual($CarletPic->main_thumbnail->caption, 'Lucky (our pet)');

        $this->assertFalse($Thumbnail->findFirstBy('caption','Our Office'));

    }

    function test_for_belongs_to_association()
    {
        $Thumbnail =& new Thumbnail();
        $Thumbnail =& $Thumbnail->findFirstBy('caption','Lucky (our pet)');
        $Thumbnail =& new Thumbnail($Thumbnail->getId());

        $this->assertEqual($Thumbnail->picture->getType(), 'belongsTo');

        $Thumbnail =& $Thumbnail->findFirstBy('caption:has','Lucky', array('include'=>'picture'));

        $this->assertEqual($Thumbnail->picture->getType(), 'Picture');
        $this->assertEqual($Thumbnail->picture->title, 'The Akelos Media Team at Carlet');

        $Alicia =& $Thumbnail->create('caption->','Alicia');
        $this->assertTrue(!$Alicia->isNewRecord());

        $this->assertEqual($Alicia->picture->getType(), 'belongsTo');

        $MyGirl =& new Picture(array('title'=>'Alicia Sadurní'));

        $Alicia->picture->assign($MyGirl);

        $this->assertEqual($Alicia->picture->getType(), 'Picture');

        $this->assertReference($Alicia->picture, $MyGirl);
        $this->assertFalse($MyGirl->isNewRecord());
        $this->assertEqual($Alicia->get('photo_id'), $MyGirl->getId());
        $this->assertTrue($Alicia->save());

        $Thumbnail =& new Thumbnail();
        $Thumbnail->caption = 'Party 2005';

        $Picture =& $Thumbnail->picture->build(array('title'=>'Akelos Party 2005'));
        $this->assertReference($Thumbnail->picture, $Picture);
        $this->assertEqual($Picture->getType(), 'Picture');

        $this->assertTrue($Picture->isNewRecord() && $Thumbnail->isNewRecord());

        $this->assertTrue($Thumbnail->save());

        $this->assertFalse($Picture->isNewRecord());
        $this->assertFalse($Thumbnail->isNewRecord());


        $Thumbnail =& new Thumbnail();
        $Thumbnail->caption = 'Party 2006';

        $Picture =& $Thumbnail->picture->create(array('title'=>'Akelos Party 2006'));
        $this->assertReference($Thumbnail->picture, $Picture);
        $this->assertEqual($Picture->getType(), 'Picture');
        $this->assertFalse($Picture->isNewRecord());
        $this->assertTrue($Thumbnail->isNewRecord());


        $Thumbnail =& new Thumbnail(array('title'=>'Akelos new office'));
        $Thumbnail->loadAssociations();
        $Thumbnail->picture->assign($Picture);
        $this->assertTrue($Thumbnail->save());

        $this->assertEqual($Thumbnail->photo_id, $Picture->id);

    }

    function test_for_multiple_hasone_and_belongsto()
    {
        $Altea =& new Picture(array('title'=>'Altea Cupula de Mediterraneo, Costa Blanca'));
        $Altea->main_thumbnail->build(array('caption'=>'Altea'));
        $this->assertTrue($Altea->main_thumbnail->isNewRecord());
        $this->assertEqual($Altea->main_thumbnail->getType(), 'Thumbnail');
        $this->assertTrue($Altea->save());
        $this->assertFalse($Altea->main_thumbnail->isNewRecord());


        $Altea =& new Picture(array('title'=>'Altea2'));
        $Altea->main_thumbnail->create(array('caption'=>'Altea2'));
        $this->assertFalse($Altea->main_thumbnail->isNewRecord());
        $this->assertEqual($Altea->main_thumbnail->getType(), 'Thumbnail');
        $this->assertTrue($Altea->save());

        $Altea =& new Picture(array('title'=>'Altea3'));
        $Altea->main_thumbnail->assign(new Thumbnail(array('caption'=>'Altea3')));
        
        $this->assertTrue($Altea->main_thumbnail->isNewRecord());
        $this->assertEqual($Altea->main_thumbnail->getType(), 'Thumbnail');
        $this->assertTrue($Altea->save());

        $this->assertFalse($Altea->main_thumbnail->isNewRecord());

        $Altea->main_thumbnail->replace(new  Thumbnail(array('caption'=>'3rd Altea pic')));
        $this->assertFalse($Altea->main_thumbnail->isNewRecord());

        $Thumbnail = new Thumbnail();
        $this->assertFalse($Thumbnail->findFirstBy('caption','Altea3'));

        $Panorama =& new Panorama(array('title'=>'Views from the old town'));
        $this->assertTrue($Panorama->save());
        $Panorama->thumbnail->build(array('caption'=>'Altea paronamic views from the Old town'));
        $this->assertEqual($Panorama->thumbnail->getType(), 'Thumbnail');
        $this->assertTrue($Panorama->thumbnail->isNewRecord());
        $this->assertTrue($Panorama->save());
        $this->assertFalse($Panorama->thumbnail->isNewRecord());

        $Thumbnail =& new Thumbnail();
        $Thumbnail = $Thumbnail->findFirstBy('caption:has', 'Old town', array('include'=>'panorama'));
        $this->assertEqual($Thumbnail->panorama->title, 'Views from the old town');
    }
    

    function test_primary_key_setting()
    {
        $Hilario =& new Person('first_name->','Hilario','last_name->','Hervás','email->','hilario@example.com');
        $Jose =& new Person('first_name->','Jose','last_name->','Salavert','email->','salavert@example.com');
        $Vero =& new Person('first_name->','Vero','last_name->','Machí','email->','vero@example.com');
        $Bermi =& new Person('first_name->','Bermi','last_name->','Ferrer','email->','bermi@example.com');

        $this->assertTrue($Hilario->save() && $Bermi->save());

        $BermisAccount =& new Account('username->','bermi','password->','pass');
        $Bermi->account->assign($BermisAccount);

        $this->assertEqual($BermisAccount->person_id,$Bermi->id);

        $SalavertsAccount =& new Account('username->','salavert','password->','pass');
        $Jose->account->assign($SalavertsAccount);

        $Jose->save();

        $this->assertEqual($SalavertsAccount->person_id,$Jose->id);

        $VerosAccount =& new Account('username->','vero','password->','pass');

        $this->assertTrue($VerosAccount->save());

        $VerosAccount->person->assign($Vero);

        $VerosAccount->save();
        $this->assertEqual($VerosAccount->person_id, $Vero->id);

        $HilariosAccount =& new Account('username->','hilario','password->','pass');
        $Hilario->account->assign($HilariosAccount);
        $Hilario->save();

        $this->assertEqual($HilariosAccount->id, $Hilario->account->id);

        $Hilario =& $Hilario->findFirstBy('first_name','Hilario');
        $Hilario->account->load();

        $this->assertEqual($HilariosAccount->id, $Hilario->account->id);

    }
    
    function test_should_load_resquested_list()
    {
        $this->installAndIncludeModels(array('TodoList', 'TodoTask'));

        $ListA =& new TodoList(array('name' => 'A'));
        $this->assertTrue($ListA->save());
        
        $ListB =& new TodoList(array('name' => 'B'));
        $this->assertTrue($ListB->save());
        
        $Task1 =& $ListB->task->create(array('details' => 1));
        
        $Task1->todo_list->load(true);
        
        $this->assertEqual($Task1->todo_list->getId(), $ListB->getId());
    }
        /**/
}


ak_test('test_AkActiveRecord_belongsTo_Associations', true);


?>
