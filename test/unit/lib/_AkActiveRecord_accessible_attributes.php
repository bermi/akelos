<?php

defined('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION') ? null : define('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION', false);
defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);

require_once(dirname(__FILE__).'/../../fixtures/config/config.php');

class test_AkActiveRecord_accessible_attributes extends  AkUnitTest
{
    function test_start()
    {
        $this->installAndIncludeModels(array('ProtectedPerson'));
    }

    function test_accessing_protected_attributes_via_find()
    {
        $ProtectedPerson =& new ProtectedPerson();
        $this->assertEqual($ProtectedPerson->getTableName(),'protected_people');
        $Person =& $ProtectedPerson->create(array('name' => 'Franz','birthday'=>'1956-06-12 09:31:12','created_by' => '12'));
        
        $this->assertEqual($Person->name,'Franz');
        $this->assertEqual($Person->birthday,'1956-06-12 09:31:12');
        $this->assertNotEqual($Person->created_by,'12');   // protected
        $this->assertNull($Person->created_by);            // therefore NULL
        $this->assertTrue($Person->is_active);             // default values
        $this->assertNotNull($Person->is_active);
        $this->assertNotNull($Person->created_at);
        $this->assertNull($Person->updated_at);

        $Person =& $ProtectedPerson->findFirstBy('name','Franz');
        $this->assertEqual($Person->name,'Franz');
        $this->assertEqual($Person->birthday,'1956-06-12 09:31:12');
        $this->assertNotEqual($Person->created_by,'12');
        $this->assertNull($Person->created_by);
        $this->assertTrue($Person->is_active);
        $this->assertNotNull($Person->is_active);
        $this->assertNotNull($Person->created_at);
        $this->assertNull($Person->updated_at);

        $Person =& $ProtectedPerson->create(array('name' => 'Heinz'));
        $Person->created_by = 1;
        $Person->save();
        $this->assertEqual($Person->name,'Heinz');
        $this->assertNull($Person->birthday);
        $this->assertNotNull($Person->created_by);
        $this->assertTrue($Person->is_active);
        $this->assertNotNull($Person->is_active);
        $this->assertNotNull($Person->created_at);

        $Person =& $ProtectedPerson->findFirstBy('name','Heinz');
        $this->assertEqual($Person->name,'Heinz');
        $this->assertNull($Person->birthday);
        $this->assertNotNull($Person->created_by);
        $this->assertEqual($Person->created_by,1);
        $this->assertTrue($Person->is_active);
        $this->assertNotNull($Person->is_active);
        $this->assertNotNull($Person->created_at);

    }

    function test_datetime_null()
    {
        $ProtectedPerson =& new ProtectedPerson();
        $Person =& $ProtectedPerson->findFirstBy('name','Heinz');
        
        $this->assertNull($Person->birthday);

        $Person->birthday = "1932-12-12";
        $Person->save();
        $this->assertEqual($Person->birthday.' 00:00:00','1932-12-12 00:00:00');

        $Person =& $ProtectedPerson->findFirstBy('name','Heinz');
        $this->assertEqual($Person->birthday,'1932-12-12 00:00:00');
    }
}

Ak::test('test_AkActiveRecord_accessible_attributes',true);

?>
