<?php

defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);
require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

class HasManyTestCase extends AkUnitTest
{

    public function test_start()
    {
        $this->installAndIncludeModels(array('Picture', 'Thumbnail','Panorama', 'Property', 'PropertyType'));
    }

    public function test_for_has_many()
    {
        $Property = new Property();
        $this->assertEqual($Property->picture->getType(), 'hasMany');
        $this->assertTrue(is_array($Property->pictures) && count($Property->pictures) === 0);

        $Property->picture->load();
        $this->assertEqual($Property->picture->count(), 0);

        $SeaViews = new Picture(array('title'=>'Sea views'));

        $Property->picture->add($SeaViews);
        $this->assertEqual($Property->picture->count(), 1);

        $this->assertReference($Property->pictures[0], $SeaViews);

        $Property->picture->add($SeaViews);
        $this->assertEqual($Property->picture->count(), 1);

        $this->assertNull($Property->pictures[0]->get('property_id'));

        $MountainViews = new Picture(array('title'=>'Mountain views'));
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

        $Property = new Property($Property->getId());
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

        //$this->assertTrue($Property =& $Property->find('first', array('include'=>'pictures')));
        //$this->assertIdentical($Property->picture->count(), 0);

        $Picture = new Picture();
        $Alicia =& $Picture->create(array('title'=>'Alicia'));
        $Bermi =& $Picture->create(array('title'=>'Bermi'));
        $Hilario =& $Picture->create(array('title'=>'Hilario'));

        $Property->picture->setByIds(array($Alicia->getId(),$Bermi->getId(),$Hilario->getId()));

        $Property->set('description', 'Cool house');

        $this->assertTrue($Property->save());

        $this->assertTrue($Property =& $Property->findFirstBy('description', 'Cool house'));

        $Property->picture->load();

        $this->assertEqual($Property->picture->count(), 3);

        $FoundAlicia = $Property->picture->find('first', array('conditions' => array('title = ?',"Alicia")));
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


        $Property = new Property();

        $PoolPicture =& $Property->picture->build(array('title'=>'Pool'));

        $this->assertReference($PoolPicture, $Property->pictures[0]);

        $this->assertTrue($Property->pictures[0]->isNewRecord());

        $this->assertEqual($PoolPicture->getType(), 'Picture');

        $Property->set('description', 'Maui Estate');


        $this->assertTrue($Property->save());

        $this->assertTrue($MauiEstate = $Property->findFirstBy('description', 'Maui Estate', array('include'=>'pictures')));

        $this->assertEqual($MauiEstate->pictures[0]->get('title'), 'Pool');

        $Property = new Property(array('description'=>'Villa Altea'));
        $GardenPicture =& $Property->picture->create(array('title'=>'Garden'));
        $this->assertReference($GardenPicture, $Property->pictures[0]);
        $this->assertTrue($GardenPicture->isNewRecord());

        $Property = new Property(array('description'=>'Villa Altea'));
        $this->assertTrue($Property->save());
        $GardenPicture =& $Property->picture->create(array('title'=>'Garden'));
        $this->assertReference($GardenPicture, $Property->pictures[0]);
        $this->assertFalse($GardenPicture->isNewRecord());

        $this->assertTrue($VillaAltea = $Property->findFirstBy('description', 'Villa Altea', array('include'=>'pictures')));

        $this->assertEqual($VillaAltea->pictures[0]->get('title'), 'Garden');
    }
    public function test_association_create_and_reference_back_to_belongsTo()
    {
        $Property = new Property(array('description'=>'Hollywood Mansion'));
        $this->assertTrue($Property->save());
        $Pool =& $Property->picture->create(array('title'=>'Pool views'));
        $this->assertReference($Pool, $Property->pictures[0]);
        $this->assertReference($Property, $Property->pictures[0]->property);
    }
    public function test_clean_up_dependencies()
    {
        $Property = new Property(array('description'=>'Ruins in Matamon'));
        $this->assertTrue($Property->save());

        $South =& $Property->picture->create(array('title'=>'South views'));
        $this->assertReference($South, $Property->pictures[0]);
        $this->assertFalse($South->isNewRecord());

        $pic_id = $South->getId();

        $Property = new Property($Property->getId());
        $this->assertTrue($Property->destroy());

        $Picture = new Picture();

        $this->assertFalse($Picture->find($pic_id));

    }

    public function _test_should_not_die_on_unincluded_model()
    {
        $this->installAndIncludeModels(array('Post'));
        $Post = new Post();
        $Post->dbug();
        $Post->find('all', array('include' => array('comments')));
    }

    public function test_should_find_owner_even_if_it_has_no_relations()
    {
        $this->installAndIncludeModels(array('Post', 'Comment'));

        $Post = new Post(array('title' => 'Post for unit testing', 'body' => 'This is a post for testing the model'));

        $Post->save();
        $Post->reload();

        $expected_id = $Post->getId();

        $this->assertTrue($Result =& $Post->find($expected_id, array('include' => array('comments'))));
        $this->assertEqual($Result->getId(), $expected_id);
    }

    public function test_should_find_owner_using_related_conditions()
    {
        $this->installAndIncludeModels(array('Post', 'Comment'));

        $Post = new Post(array('title' => 'Post for unit testing', 'body' => 'This is a post for testing the model'));
        $Post->comment->create(array('body' => 'hello', 'name' => 'Aditya'));
        $Post->save();
        $Post->reload();

        $expected_id = $Post->getId();

        $this->assertTrue($Result =& $Post->find($expected_id, array('include' => array('comments'), 'conditions' => "name = 'Aditya'")));

        $this->assertEqual($Result->comments[0]->get('name'), 'Aditya');
    }


    public function test_remove_existing_associates_before_setting_by_id()
    {
        $this->installAndIncludeModels(array('Post', 'Comment'));

        foreach (range(1,10) as $i){
            $Post = new Post(array('title' => 'Post '.$i));
            $Post->comment->create(array('name' => 'Comment '.$i));
            $Post->save();
        }

        $Post11 = new Post(array('name' => 'Post 11'));
        $this->assertTrue($Post11->save());

        $Post->comment->setByIds(1,2,3,4,5);

        $this->assertTrue($Post =& $Post->find(10, array('include' => 'comments')));

        // order cannot be guaranteed!
        $expected_ids = array(1,2,3,4,5);              // on my postgreSQL $Post->comment->associated_ids = array(5,4,3,2,1);
        foreach (array_keys($Post->comments) as $k){
            $this->assertTrue(in_array($Post->comments[$k]->getId(),$expected_ids));
            unset($expected_ids[$Post->comments[$k]->getId()-1]);
        }
        $this->assertTrue(empty($expected_ids));

        // Comment 10 should exist but unrelated to a post
        $this->assertTrue($Comment =& $Post->comments[$k]->find(10));
        $this->assertNull($Comment->get('post_id'));

        $Post11->comment->setByIds(array(10,1));

        $this->assertTrue($Comment =& $Comment->find(10));
        $this->assertEqual($Comment->get('post_id'), 11);
    }

    public function test_find_with_include_on_associated_record()
    {
        $this->installAndIncludeModels('Property','Picture','Landlord');
        $Property =& $this->Property->create(array('description'=>'A Property'));
        $Picture =& $this->Picture->create(array('title'=>'With a picture'));
        $Landlord =& $this->Landlord->create(array('name'=>'and a landlord'));

        $Picture->landlord->assign($Landlord);
        $Property->picture->add($Picture);

        $Property =& $this->Property->find('first',array('description'=>'A Property'));
        $Loaded =& $Property->picture->find('all');
        $this->assertEqual('With a picture',$Loaded[0]->title);

        $Property =& $this->Property->find('first',array('description'=>'A Property'));
        $Loaded =& $Property->picture->find('all',array('include'=>'landlord'));
        $this->assertEqual('and a landlord',$Loaded[0]->landlord->name);
    }
    public function xtest_has_many_finder_sql_with_foreign_key_value_replacement()
    {
        $this->installAndIncludeModels('Group,Location');

        $group = new Group(array('name'=>'Test Group'));
        $location = new Location(array('name'=>'Barcelona'));
        $this->assertTrue($location->save());

        $group->location->set($location);
        $group->save();
        //
        $this->assertEqual(1,count($group->locations));

        $group2 = new Group($group->getId());
        $this->assertEqual(1,$group2->location->count());

        $group3 = $this->Group->find($group->getId(),array('include'=>'locations'));
        $this->assertEqual(1,$group3->location->count());

        $group4 = $this->Group->find($group->getId());
        $group4->location->load();
        $this->assertEqual(1,$group3->location->count());
        $this->assertEqual('Barcelona',$group4->locations[0]->name);
    }
}

ak_test('HasManyTestCase',true);

?>
