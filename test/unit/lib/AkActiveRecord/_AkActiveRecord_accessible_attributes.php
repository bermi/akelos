<?php

defined('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION') ? null : define('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION', false);
defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);

require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

class test_AkActiveRecord_accessible_attributes extends  AkUnitTest
{
    public function test_start()
    {
        $this->installAndIncludeModels(array('ProtectedPerson'));
    }

    public function test_accessing_protected_attributes_via_find()
    {
        $ProtectedPerson = new ProtectedPerson();
        $this->assertEqual($ProtectedPerson->getTableName(),'protected_people');
        $Person = $ProtectedPerson->create(array('name' => 'Franz','birthday'=>'1956-06-12 09:31:12','created_by' => '12'));

        $this->assertEqual($Person->name,'Franz');
        $this->assertEqual($Person->birthday,'1956-06-12 09:31:12');
        $this->assertNotEqual($Person->created_by,'12');   // protected
        $this->assertNull($Person->created_by);            // therefore NULL
        $this->assertTrue($Person->is_active);             // default values
        $this->assertNotNull($Person->is_active);
        $this->assertNotNull($Person->created_at);
        $this->assertNull($Person->updated_at);

        $Person = $ProtectedPerson->findFirstBy('name','Franz');
        $this->assertEqual($Person->name,'Franz');
        $this->assertEqual($Person->birthday,'1956-06-12 09:31:12');
        $this->assertNotEqual($Person->created_by,'12');
        $this->assertNull($Person->created_by);
        $this->assertTrue($Person->is_active);
        $this->assertNotNull($Person->is_active);
        $this->assertNotNull($Person->created_at);
        $this->assertNull($Person->updated_at);

        $Person = $ProtectedPerson->create(array('name' => 'Heinz'));
        $Person->created_by = 1;
        $Person->save();
        $this->assertEqual($Person->name,'Heinz');
        $this->assertNull($Person->birthday);
        $this->assertNotNull($Person->created_by);
        $this->assertTrue($Person->is_active);
        $this->assertNotNull($Person->is_active);
        $this->assertNotNull($Person->created_at);

        $Person = $ProtectedPerson->findFirstBy('name','Heinz');
        $this->assertEqual($Person->name,'Heinz');
        $this->assertNull($Person->birthday);
        $this->assertNotNull($Person->created_by);
        $this->assertEqual($Person->created_by,1);
        $this->assertTrue($Person->is_active);
        $this->assertNotNull($Person->is_active);
        $this->assertNotNull($Person->created_at);

    }

    public function test_protected_attributes_when_updating()
    {
        $ProtectedPerson = new ProtectedPerson();
        $Franz = $ProtectedPerson->findFirstBy('name','Franz');
        $Franz->updateAttributes(array('name'=> 'Franz Xaver','created_by'=> 15));
        $this->assertNotEqual($Franz->created_by,'15');
        $this->assertEqual($Franz->name,'Franz Xaver');
        $Franz = $ProtectedPerson->update($Franz->getId(),array('name'=> 'Franz Müller','created_by'=> 16));
        $this->assertNotEqual($Franz->created_by,16);
        $this->assertEqual($Franz->name,'Franz Müller');

        $ProtectedPerson->updateAttributes(array('name'=> 'Franz Hinterhofer','created_by'=> 17),$Franz);
        $this->assertNotEqual($Franz->created_by,17);
        $this->assertEqual($Franz->name,'Franz Hinterhofer');

    }

    public function test_using_new_record()
    {
        $ProtectedPerson = new ProtectedPerson();
        $ProtectedPerson->newRecord(array('name'=> 'Ignatz Mentzel','created_by'=> 12));
        $this->assertNull($ProtectedPerson->getId());
        $this->assertEqual($ProtectedPerson->name,'Ignatz Mentzel');
        $this->assertNotEqual($ProtectedPerson->created_by,12);
        $this->assertNull($ProtectedPerson->birthday);
        $this->assertNull($ProtectedPerson->created_at);
        $this->assertTrue($ProtectedPerson->is_active);
        $this->assertEqual($ProtectedPerson->is_active,true);
        $this->assertEqual($ProtectedPerson->credit_points,1000);


        $ProtectedPerson->credit_points  += 100;
        $ProtectedPerson->birthday = '1969-04-12';
        $ProtectedPerson->save();
        $this->assertNotNull($ProtectedPerson->getId());

        $ProtectedPerson->newRecord(array('name'=> 'Werner Huber'));
        $this->assertNull($ProtectedPerson->getId());
        $this->assertEqual($ProtectedPerson->name,'Werner Huber');
        $this->assertNull($ProtectedPerson->created_by);
        $this->assertNull($ProtectedPerson->birthday);
        $this->assertNull($ProtectedPerson->created_at);
        $this->assertTrue($ProtectedPerson->is_active);
        $this->assertEqual($ProtectedPerson->credit_points,1000);

        $ProtectedPerson->save();

        $Ignatz = $ProtectedPerson->findFirstBy('name','Ignatz Mentzel');
        $Werner = $ProtectedPerson->findFirstBy('name','Werner Huber');

        $this->assertEqual($Ignatz->credit_points,1100);
        // checking for defaults
        $this->assertTrue($Ignatz->is_active);
        $this->assertNotNull($Ignatz->created_at);
        $this->assertNull($Ignatz->updated_at);

        $this->assertTrue($Werner->is_active);
        $this->assertNotNull($Werner->created_at);
        $this->assertNull($Werner->updated_at);
        $this->assertNull($Werner->birthday);
        $this->assertEqual($Werner->credit_points,1000);
    }

    public function test_using_instantiating()
    {
        $ProtectedPerson = new ProtectedPerson();
        $this->assertNull($ProtectedPerson->name);
        $Melanie = new ProtectedPerson(array('name'=> 'Melanie Klein','created_by'=> 11));
        $this->assertEqual($Melanie->name,'Melanie Klein');
        $this->assertNull($Melanie->created_by);
        $this->assertNull($Melanie->birthday);

        $this->assertNull($ProtectedPerson->created_at);
        $this->assertTrue($ProtectedPerson->is_active);
        $Melanie->save();

        $Anna = new ProtectedPerson(array('name'=> 'Anna Freud','birthday'=> '1912-04-12'));
        $Anna->created_by = $Melanie->GetId();
        $Anna->save();

        $this->assertNotNull($Anna->GetId());
        $this->assertEqual($Anna->created_by,$Melanie->GetId());

        $PeopleWithUnknownAge = $ProtectedPerson->find('all','birthday IS null');
        $this->assertEqual(count($PeopleWithUnknownAge),3);

    }

    public function _test_datetime_null()
    {
        $ProtectedPerson = new ProtectedPerson();
        $Person = $ProtectedPerson->findFirstBy('name','Heinz');

        $this->assertNull($Person->birthday);

        $Person->birthday = "1932-12-12";
        $Person->save();
        $this->assertEqual($Person->birthday.' 00:00:00','1932-12-12 00:00:00');

        $Person = $ProtectedPerson->findFirstBy('name','Heinz');
        $this->assertEqual($Person->birthday,'1932-12-12 00:00:00');

        $Person->birthday = "";
        $Person->save();
        $this->assertNotNull($Person->birthday); // because birthday is now an empty string

        $Person = $ProtectedPerson->findFirstBy('name','Heinz');
        $this->assertNull($Person->birthday);

        $Person->birthday = "1932-12-12 09:12:12";
        $Person->save();
        $this->assertEqual($Person->birthday,'1932-12-12 09:12:12');

        $Person = $ProtectedPerson->findFirstBy('name','Heinz');
        $this->assertEqual($Person->birthday,'1932-12-12 09:12:12');

        $Person->birthday = "1933-12-12";
        $Person->save();
        $Person = $ProtectedPerson->findFirstBy('name','Heinz');
        $this->assertEqual($Person->birthday,'1933-12-12 00:00:00');

        $Person->birthday = null;
        $Person->save();
        $Person = $ProtectedPerson->findFirstBy('name','Heinz');
        $this->assertNull($Person->birthday);

    }
}

ak_test('test_AkActiveRecord_accessible_attributes',true);

?>
