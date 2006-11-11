<?php

if(!defined('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION')){
    define('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION',false);
}

defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);
require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');


class test_AkActiveRecord_hasMany_Associations extends  UnitTestCase
{

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
        $models = array('Picture', 'Thumbnail','Panorama', 'Property', 'PropertyType');
        foreach ($models as $model){
            require_once(AK_APP_DIR.DS.'installers'.DS.AkInflector::underscore($model).'_installer.php');
            require_once(AK_MODELS_DIR.DS.AkInflector::underscore($model).'.php');
            $installer_name = $model.'Installer';
            $installer = new $installer_name();
            $installer->uninstall();
            $installer->install();
        }
        unset($_SESSION['__activeRecordColumnsSettingsCache']);
    }

    /**/
    function test_for_has_many()
    {
        $Property =& new Property();
        $this->assertEqual($Property->picture->getType(), 'hasMany');
        $this->assertTrue(is_array($Property->pictures) && count($Property->pictures) === 0);

        $Property->picture->load();
        $this->assertEqual($Property->picture->count(), 0);

        $SeaViews =& new Picture(array('title'=>'Sea views'));

        $Property->picture->add($SeaViews);
        $this->assertEqual($Property->picture->count(), 1);

        $this->assertReference($Property->pictures[0], $SeaViews);

        $Property->picture->add($SeaViews);
        $this->assertEqual($Property->picture->count(), 1);

        $this->assertNull($Property->pictures[0]->get('property_id'));

        //$Property->dbug();
        $MountainViews =& new Picture(array('title'=>'Mountain views'));
        $this->assertTrue($MountainViews->isNewRecord());
        $Property->picture->add($MountainViews);

        $this->assertEqual($Property->picture->count(), 2);

        $this->assertTrue($Property->save());

        $this->assertFalse($SeaViews->isNewRecord());
        $this->assertFalse($MountainViews->isNewRecord());


        $this->assertEqual($SeaViews->get('property_id'), $Property->getId());
        $this->assertEqual($MountainViews->get('property_id'), $Property->getId());

        $this->assertReference($SeaViews, $Property->pictures[0]);
        $this->assertReference($MountainViews, $Property->pictures[1]);

        $Property =& new Property($Property->getId());
        $Property->picture->load();

        $this->assertEqual($Property->picture->association_id, 'pictures');
        $this->assertEqual($Property->picture->count(), 2);

        $Property->pictures = array();
        $this->assertEqual($Property->picture->count(), 0);

        $Property->picture->load();
        $this->assertEqual($Property->picture->count(), 0);

        $Property->picture->load(true);
        $this->assertEqual($Property->picture->count(), 2);

        $this->assertEqual($Property->pictures[1]->getType(), 'Picture');

        $Property->picture->delete($Property->pictures[1]);

        $this->assertEqual($Property->picture->count(), 1);

        $Property->picture->load(true);
        $this->assertEqual($Property->picture->count(), 1);

        $Property = $Property->find('first');

        $Picture = new Picture();
        $Pictures = $Picture->find();

        $Property->picture->set($Pictures);
        $this->assertEqual($Property->picture->count(), count($Pictures));

        $Property = $Property->find('first');
        $Property->picture->load();
        $this->assertEqual($Property->picture->count(), count($Pictures));

        $Picture = $Picture->find('first');

        $Property->picture->set($Picture);

        $this->assertEqual($Property->picture->count(), 1);

        $this->assertTrue(in_array('pictures', $Property->getAssociatedIds()));

        $Property = $Property->find('first', array('include'=>'pictures'));

        $this->assertIdentical($Property->picture->count(), 1);

        $this->assertEqual($Property->pictures[0]->getId(), $Picture->getId());

        $this->assertTrue($Property->picture->delete($Property->pictures[0]));

        $this->assertIdentical($Property->picture->count(), 0);

        $Property =& $Property->find('first');
        $this->assertIdentical($Property->picture->count(), 0);

        $this->assertFalse($Property->find('first', array('include'=>'pictures')));

        $Picture =& new Picture();
        $Alicia =& $Picture->create(array('title'=>'Alicia'));
        $Bermi =& $Picture->create(array('title'=>'Bermi'));
        $Hilario =& $Picture->create(array('title'=>'Hilario'));


        $Property->picture->setByIds(array($Alicia->getId(),$Bermi->getId(),$Hilario->getId()));

        $Property->set('description', 'Cool house');

        $this->assertTrue($Property->save());

        $this->assertTrue($Property =& $Property->findFirstBy('description', 'Cool house'));

        $Property->picture->load();

        $this->assertEqual($Property->picture->count(), 3);

        $FoundAlicia = $Property->picture->find('first', array('title'=>'Alicia'));
        $this->assertEqual($Alicia->get('title').$Alicia->getId(), $FoundAlicia->get('title').$FoundAlicia->getId());

        $FoundPals = $Property->picture->find();

        $this->assertEqual(count($FoundPals), $Property->picture->count());

        $titles = array();
        foreach ($FoundPals as $FoundPal){
            $titles[] = $FoundPal->get('title');
        }
        sort($titles);

        $this->assertEqual($titles, array('Alicia','Bermi','Hilario'));

        $this->assertFalse($Property->picture->isEmpty());

        $this->assertEqual($Property->picture->getSize(), 3);

        $this->assertTrue($Property->picture->clear());

        $this->assertTrue($Property->picture->isEmpty());

        $this->assertEqual($Property->picture->getSize(), 0);


        $Property =& new Property();

        $PoolPicture =& $Property->picture->build(array('title'=>'Pool'));

        $this->assertReference($PoolPicture, $Property->pictures[0]);

        $this->assertTrue($Property->pictures[0]->isNewRecord());

        $this->assertEqual($PoolPicture->getType(), 'Picture');

        $Property->set('description', 'Maui Estate');


        $this->assertTrue($Property->save());

        $this->assertTrue($MauiEstate = $Property->findFirstBy('description', 'Maui Estate', array('include'=>'pictures')));

        $this->assertEqual($MauiEstate->pictures[0]->get('title'), 'Pool');

        $Property =& new Property(array('description'=>'Villa Altea'));
        $GardenPicture =& $Property->picture->create(array('title'=>'Garden'));
        $this->assertReference($GardenPicture, $Property->pictures[0]);
        $this->assertTrue($GardenPicture->isNewRecord());

        $Property =& new Property(array('description'=>'Villa Altea'));
        $this->assertTrue($Property->save());
        $GardenPicture =& $Property->picture->create(array('title'=>'Garden'));
        $this->assertReference($GardenPicture, $Property->pictures[0]);
        $this->assertFalse($GardenPicture->isNewRecord());

        $this->assertTrue($VillaAltea = $Property->findFirstBy('description', 'Villa Altea', array('include'=>'pictures')));

        $this->assertEqual($VillaAltea->pictures[0]->get('title'), 'Garden');
    }
    
    /**/

    function test_clean_up_dependencies()
    {
        $Property =& new Property(array('description'=>'Ruins in Matamon'));
        $this->assertTrue($Property->save());
        
        $South =& $Property->picture->create(array('title'=>'South views'));
        $this->assertReference($South, $Property->pictures[0]);
        $this->assertFalse($South->isNewRecord());
        
        $pic_id = $South->getId();

        $Property =& new Property($Property->getId());
        $this->assertTrue($Property->destroy());
        
        $Picture =& new Picture();

        $this->assertFalse($Picture->find($pic_id));
        
    }

}

Ak::test('test_AkActiveRecord_hasMany_Associations', true);


?>
