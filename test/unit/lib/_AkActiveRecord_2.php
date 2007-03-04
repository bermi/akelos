<?php

defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);
require_once(dirname(__FILE__).'/../../fixtures/config/config.php');

require_once(AK_LIB_DIR.DS.'AkActiveRecord.php');

class test_AkActiveRecord_2 extends  UnitTestCase
{


    function tearDown()
    {
        unset($_SESSION['__activeRecordColumnsSettingsCache']);
    }

    function Test_of_newRecord()
    {
        $User = new AkTestUser();
        $User->newRecord('last_name->','Ferrer','date->','1978-06-16','expires_on->','2120-06-16');
        $User->set('first_name','Bermi');
        $User->set('last_name',$User->get('last_name').' Mart’nez');
        $this->assertEqual($User->getAttributes(), array('id'=>null,'user_name'=>null,'first_name'=>'Bermi','last_name'=>'Ferrer Mart’nez','email'=>null,'country'=>null,'password'=>'*********','created_at'=>null,'updated_at'=>null,'expires_on'=>'2120-06-16'));
        $this->assertTrue($User->isNewRecord());
        
        $User = new AkTestUser();
        $User->addCombinedAttributeConfiguration('name', "%s %s", 'first_name', 'last_name');
        $User->newRecord(array('first_name'=>'Bermi','last_name'=>'Ferrer','date'=>'1978-06-16','expires_on'=>'2120-06-16'));
        $this->assertEqual($User->getAttributes(), array('id'=>null,'user_name'=>null,'first_name'=>'Bermi','last_name'=>'Ferrer','email'=>null,'country'=>null,'password'=>'*********','created_at'=>null,'updated_at'=>null,'expires_on'=>'2120-06-16','name'=>'Bermi Ferrer'));
        $this->assertFalse(empty($User->_newRecord));

    }
    


    function Test_of_isNewRecord()
    {
        $User = new AkTestUser();
        $this->assertTrue($User->isNewRecord());
        
        $User->newRecord('last_name->','Gimeno');
        
        $this->assertTrue($User->save() !== false);

        $this->assertFalse($User->isNewRecord());
        $User->destroy();
        
        $User->newRecord('last_name->','Ferrer','date->','1978-06-16','expires_on->','2120-06-16');
        $this->assertTrue($User->isNewRecord());
    }
    
    function Test_of__getCombinedAttributesWhereThisAttributeIsUsed()
    {
        $User = new AkTestUser();
        $User->addCombinedAttributeConfiguration('name', "%s %s", 'first_name', 'last_name');
        $User->addCombinedAttributeConfiguration('another_name', "Ms/Mr. %s", 'last_name');
        $this->assertEqual($User->_getCombinedAttributesWhereThisAttributeIsUsed('last_name'),array('name','another_name'));
    }
     
    function Test_of_requiredForCombination()
    {
        $User = new AkTestUser();
        $User->addCombinedAttributeConfiguration('name', "%s %s", 'first_name', 'last_name');
        $this->assertIdentical($User->requiredForCombination('last_name'), true);
        $this->assertIdentical($User->requiredForCombination('user_name'), false);
        $this->assertIdentical($User->requiredForCombination('not_valid'), false);
    }

    
    
    function Test_of__create()
    {
        $Users = new AkTestUser('first_name=>','Tim','last_name->','Horton','user_name->','tim','email->','tim@example.com', 'expires_on->','+2 years');
        $Users->_create();
        $User = new AkTestUser($Users->getId());
        $this->assertTrue($User->first_name=='Tim' && $User->last_name == 'Horton' && $User->user_name == 'tim' && $User->email == 'tim@example.com');
        $this->assertFalse(empty($User->created_at) && empty($User->expires_on));
        $this->assertEqual(count($User->getAttributes()) , 10);
        $User->delete($Users->getId());
    }
    
    


    /**
    * @todo Find with associations
    */
    function Test_of_find()
    {
        $User = new AkTestUser('first_name=>','Bermi','last_name->','Ferrer Mart’nez','user_name->','bermi','email->','bermi@example.com');
        $User->_create();
                
        $User = new AkTestUser('first_name=>','Hilario','last_name->','Herv‡s A–—','user_name->','hilario','email->','hilario@example.com');
        $User->_create();
               
        $Users = new AkTestUser();
        $User = $Users->find(3);
        $this->assertTrue($User->first_name=='Bermi' && $User->last_name == 'Ferrer Mart’nez' && $User->user_name == 'bermi' && $User->email == 'bermi@example.com');
        
        $Users = new AkTestUser();
        $FoundUsers = $Users->find(3, 4);
        
        foreach ($FoundUsers as $User){
            $expected1 = ($User->first_name=='Hilario' && $User->last_name == 'Herv‡s A–—' && $User->user_name == 'hilario' && $User->email == 'hilario@example.com');
            $expected2 = ($User->first_name=='Bermi' && $User->last_name == 'Ferrer Mart’nez' && $User->user_name == 'bermi' && $User->email == 'bermi@example.com');
            $this->assertTrue($expected1 || $expected2);
        }
        
        $Users = new AkTestUser();
        $FoundUsers = $Users->find(3, 4, 5, 6);
        $this->assertFalse($FoundUsers);

        // with arrays of ids
        
        $Users = new AkTestUser();
        $GotUser = $Users->find(array(3));
        $User = $GotUser[0];
        $this->assertTrue($User->first_name=='Bermi' && $User->last_name == 'Ferrer Mart’nez' && $User->user_name == 'bermi' && $User->email == 'bermi@example.com');
        
        $Users = new AkTestUser();
        $FoundUsers = $Users->find(array(3, 4));
        
        foreach ($FoundUsers as $User){
            $expected1 = ($User->first_name=='Hilario' && $User->last_name == 'Herv‡s A–—' && $User->user_name == 'hilario' && $User->email == 'hilario@example.com');
            $expected2 = ($User->first_name=='Bermi' && $User->last_name == 'Ferrer Mart’nez' && $User->user_name == 'bermi' && $User->email == 'bermi@example.com');
            $this->assertTrue($expected1 || $expected2);
        }
        
        $Users = new AkTestUser();
        $FoundUsers = $Users->find(array(3, 4, 5, 6));
        $this->assertFalse($FoundUsers);
        
        
        // with conditions of id
        
        $Users = new AkTestUser();
        $User = $Users->find(3, array('conditions' => "user_name = 'bermi'", 'order' => "created_at DESC"));
        $this->assertTrue($User->first_name=='Bermi' && $User->last_name == 'Ferrer Mart’nez' && $User->user_name == 'bermi' && $User->email == 'bermi@example.com');


        $Users = new AkTestUser();
        $User = $Users->find('first', array('conditions' => array("user_name = :user_name", ':user_name' => 'hilario')));
        $this->assertTrue($User->first_name=='Hilario' && $User->last_name == 'Herv‡s A–—' && $User->user_name == 'hilario' && $User->email == 'hilario@example.com');
        
        $User = new AkTestUser('first_name=>','test_name','last_name->','A');
        $User->_create();
        $User = new AkTestUser('first_name=>','test_name','last_name->','Z');
        $User->_create();

        $Users = new AkTestUser();
        $User = $Users->find('first', array('order' => "last_name DESC"));
        $this->assertTrue($User->first_name=='test_name' && $User->last_name == 'Z');
        
        $Users = new AkTestUser();
        $User = $Users->find('first', array('order' => "last_name ASC"));
        $this->assertTrue($User->first_name=='test_name' && $User->last_name == 'A');

        
        $Users = new AkTestUser();
        $this->assertFalse($Users->find('first', array('order' => "last_name DESC", 'offset' => 5)));
        
        $Users = new AkTestUser();
        $User = $Users->find('first', array('order' => "last_name ASC", 'offset' => 2));
        $this->assertTrue($User->first_name=='Hilario' && $User->last_name == 'Herv‡s A–—' && $User->user_name == 'hilario' && $User->email == 'hilario@example.com');

        $Users = new AkTestUser();
        $FoundUsers = $Users->find('all');
        $this->assertEqual(count($FoundUsers), 4);
        foreach ($FoundUsers as $User){
            $this->assertFalse(empty($User->last_name));
        }
        
        $Users = new AkTestUser();
        $FoundUsers = $Users->find();
        $this->assertEqual(count($FoundUsers), 4);
        foreach ($FoundUsers as $User){
            $this->assertFalse(empty($User->last_name));
        }

        $User = new AkTestUser('first_name=>','test_name','last_name->','B');
        $User->_create();
        
        $Users = new AkTestUser();
        $FoundUsers = $Users->find('all', array('conditions' => array("first_name = :first_name", ':first_name' => 'test_name'), 'limit' => 2, 'order' => "last_name DESC"));

        $this->assertEqual(count($FoundUsers), 2);
        $expected = array('Z', 'B');
        foreach ($FoundUsers as $User){
            $this->assertFalse(empty($User->last_name));
            $this->assertTrue(in_array($User->last_name, $expected));            
        }
        
        $Users = new AkTestUser();
        $FoundUsers = $Users->find('all', array('offset' => 2, 'limit' => 2));
        $this->assertEqual(count($FoundUsers), 2);
        $expected = array('A', 'Z');
        foreach ($FoundUsers as $User){
            $this->assertFalse(empty($User->last_name));
            $this->assertTrue(in_array($User->last_name, $expected));            
        }
        
        $Users = new AkTestUser();
        $FoundUsers = $Users->find('all', array('offset' => 3, 'limit' => 2));
        $this->assertEqual(count($FoundUsers), 2);
        $expected = array('B', 'Z');
        foreach ($FoundUsers as $User){
            $this->assertFalse(empty($User->last_name));
            $this->assertTrue(in_array($User->last_name, $expected));            
        }

    }
    
    
    function Test_of_getContentColumns()
    {
        $Comment = new AkTestComment();
        $this->assertEqual(array_keys($Comment->getContentColumns()), array('private_comment','birth_date'));
        
        $Users = new AkTestUser();
        $this->assertEqual(array_keys($Users->getContentColumns()), array('user_name', 'first_name', 'last_name', 'email', 'country', 'password', 'created_at', 'updated_at', 'expires_on'));
        $Users = new AkTestUser();
        $Users->setInheritanceColumn('first_name');
        $this->assertEqual(array_keys($Users->getContentColumns()), array('user_name', 'last_name', 'email', 'country', 'password', 'created_at', 'updated_at', 'expires_on'));

    }


    function Test_of_save()
    {
        $Users = new AkTestUser();
        $User = $Users->find(3);
        $User->setAttribute('country',23);
        $User->save();
        $User = $Users->find(3);
        $this->assertEqual($User->get('country'), 23);

        $User = new AkTestUser();
        $User->addCombinedAttributeConfiguration('name', "%s %s", 'first_name', 'last_name');
        $User->setAttributes(array('name'=>'Alicia MiLuv'));
        $User->save();
        
        $User = $Users->find('first', array('conditions' => "first_name = 'Alicia' AND last_name = 'MiLuv'"));
        $this->assertEqual($User->first_name,'Alicia');
        $this->assertEqual($User->last_name,'MiLuv');
        
        $User = new AkTestUser(array('last_name' => 'Smith', 'first_name' => 'John'));   
        
        $User->save();

        $User = $Users->find("first_name = 'John' AND last_name = 'Smith'");
        $this->assertEqual($User->first_name,'John');
        $this->assertEqual($User->last_name,'Smith');
    }
    
    
    function Test_of_create()
    {
        $Users = new AkTestUser();
        
        $Got = $Users->create(array('first_name'=>'Jane','last_name'=>'Williams'));
        $Expected = $Users->find('first',"last_name = 'Williams'");
        
        $this->assertEqual($Got->first_name, $Expected->first_name);
        $this->assertEqual($Got->last_name, $Expected->last_name);
        $this->assertEqual($Got->created_at, $Expected->created_at);
        
        $Users->transactionStart();
        $Got = $Users->create(array('first_name'=>'Paulo','last_name'=>'Coelho', 'expires_on' => '+2 days'));
        $Expected = $Users->find('first',"last_name = 'Coelho'");
        $this->assertEqual($Got->first_name, $Expected->first_name);
        $this->assertEqual($Got->last_name, $Expected->last_name);
        $this->assertEqual($Got->created_at, $Expected->created_at);
        $this->assertEqual($Got->expires_on, $Expected->expires_on);
        $Users->transactionFail();
        $Users->transactionComplete();
        
        
    }
    
    function Test_of_findFirst()
    {
        $Users = new AkTestUser();
        $Got = $Users->findFirst("last_name = 'Williams'");
        $this->assertEqual($Got->first_name, 'Jane');
        
        $Got = $Users->findFirst("last_name = 'Ferrer Mart’nez'");
        $this->assertEqual($Got->first_name, 'Bermi');
        
        $Got = $Users->findFirst();
        
        $this->assertEqual($Got->first_name, 'Bermi');
        
        
        $Got = $Users->findFirst(array('order'=>'first_name'));
        $this->assertEqual($Got->first_name, 'Alicia');
        
    }
    
    function Test_of_findAll()
    {
        $Users = new AkTestUser();
        
        $Got = $Users->findAll("last_name = 'Williams'");
        $this->assertEqual($Got[0]->first_name, 'Jane');
        
        if($FoundUsers = $Users->findAll()){
            foreach ($FoundUsers as $FoundUser){
                $this->assertTrue(in_array($FoundUser->first_name, array('Bermi', 'Hilario', 'test_name', 'Alicia', 'John', 'Jane')));
            }
        }
        
        if($FoundUsers = $Users->findAll(array('order'=>'first_name'))){
            $this->assertEqual($FoundUsers[0]->first_name , 'Alicia');
        }
    }
   
    function Test_of_update()
    {
        $Users = new AkTestUser();
        
        $Users->update(3, array('last_name'=>'Ferrer'));
        
        $Bermi = $Users->find(3);
        $this->assertEqual($Bermi->last_name,'Ferrer');
        
        $updates = array();
        $updates[5] = array('first_name'=> 'NEWNAME5');
        $updates[6] = array('first_name'=> 'NEWNAME6');
        $updates[7] = array('first_name'=> 'NEWNAME7');
        $Users->update(array_keys($updates), array_values($updates));
        
        $FoundUsers = $Users->find(5, 6, 7);
        
        foreach ($FoundUsers as $FoundUser){
            $this->assertEqual($FoundUser->first_name,'NEWNAME'.$FoundUser->getId());
        }
        

        $Users->update(array(5,6,7), array('first_name'=>'NEW TEST NAME'));
        
        $FoundUsers = $Users->find(5, 6, 7);

        foreach ($FoundUsers as $FoundUser){
            $this->assertEqual($FoundUser->first_name, 'NEW TEST NAME');
        }
    }

    
    function Test_of_createOrUpdate()
    {
        $Users = new AkTestUser();
        $Bermi = $Users->findFirst("first_name = 'Bermi'");
        $Bermi->last_name = 'Ferrer Mart’nez';
        $Bermi->createOrUpdate();
        
        $Bermi = $Users->findFirst("first_name = 'Bermi'");
        $this->assertEqual($Bermi->last_name,'Ferrer Mart’nez');
        
        $User = new AkTestUser();
        $User->first_name = 'Vero';
        $User->last_name = 'Mach’';
        $User->createOrUpdate();
        
        $Vero = $Users->findFirst("last_name = 'Mach’'");
        $this->assertEqual($Vero->first_name,'Vero');
    }

    
    function Test_of_reload()
    {
        $Users = new AkTestUser();
        $Bermi = $Users->findFirst("first_name = 'Bermi'");
        $this->assertEqual($Bermi->last_name,'Ferrer Mart’nez');
        $Bermi->set('last_name','Ferrer');
        $this->assertEqual($Bermi->last_name,'Ferrer');
        $Bermi->reload();
        $this->assertEqual($Bermi->last_name,'Ferrer Mart’nez');
    }
    
    
    
    
    function Test_of_updateAll()
    {
        $Users = new AkTestUser();
        $modified_entries = $Users->updateAll("first_name = 'test_name', country = '50'", "first_name = 'NEW TEST NAME'");
        $this->assertEqual($modified_entries, 3);
        
        $FoundUsers = $Users->find(5, 6, 7);
        
        foreach ($FoundUsers as $FoundUser){
            $this->assertEqual($FoundUser->first_name, 'test_name');
            $this->assertEqual($FoundUser->country, 50);
        }
    }
    

    function Test_of_updateAttribute()
    {
        $Users = new AkTestUser();
        $Alicia = $Users->findFirst("first_name = 'Alicia'");
        $Alicia->updateAttribute('last_name','Sadurn’');
        
        $Alicia = $Users->findFirst("first_name = 'Alicia'");
        $this->assertTrue($Alicia->last_name == 'Sadurn’');
        
    }
    
    function Test_of_updateAttributes()
    {
        $Users = new AkTestUser();
        $Hilario = $Users->findFirst("first_name = 'Hilario'");
        $Hilario->updateAttributes(array('last_name'=>'Herv‡s'));
        
        $Hilario = $Users->findFirst("first_name = 'Hilario'");
        $this->assertTrue($Hilario->last_name == 'Herv‡s');
        
        $Users->updateAttributes(array('last_name'=>'Herv‡s A–—'), $Hilario);
        
        $Hilario = $Users->findFirst("first_name = 'Hilario'");
        $this->assertTrue($Hilario->last_name == 'Herv‡s A–—');
        
    }    

    function Test_of_exists()
    {
        $Users = new AkTestUser();
        $this->assertTrue($Users->exists(5));
        $this->assertFalse($Users->exists(500));
    }
    
    function Test_of_findBySql()
    {
        $Users = new AkTestUser();
        
        $FoundUsers = $Users->findBySql("SELECT * FROM ak_test_users WHERE first_name = 'Hilario'");
        $this->assertTrue($FoundUsers[0]->last_name == 'Herv‡s A–—');
        
        $FoundUsers = $Users->findBySql(array("SELECT * FROM ak_test_users WHERE first_name = ?",array('Hilario')));
        $this->assertTrue($FoundUsers[0]->last_name == 'Herv‡s A–—');
        
        $FoundUsers = $Users->findBySql(array("SELECT * FROM ak_test_users WHERE first_name = ?",'Hilario'));
        $this->assertTrue($FoundUsers[0]->last_name == 'Herv‡s A–—');
        
        $FoundUsers = $Users->findBySql(array("SELECT * FROM ak_test_users WHERE last_name = ?",array('last_name'=>'Ferrer Mart’nez')));
        $this->assertTrue($FoundUsers[0]->first_name == 'Bermi');
        
        $FoundUsers = $Users->findBySql(array("SELECT * FROM ak_test_users WHERE last_name = ? AND first_name = ?",array('Ferrer Mart’nez','Bermi')));
        $this->assertTrue($FoundUsers[0]->first_name == 'Bermi');
        
        $FoundUsers = $Users->findBySql("SELECT * FROM ak_test_users",6);
        $this->assertEqual(count($FoundUsers), 6);
        
        $FoundUsers = $Users->findBySql("SELECT * FROM ak_test_users",6,6);
        $this->assertEqual(count($FoundUsers), 3);

    }
    
}

Ak::test('test_AkActiveRecord_2',true);


?>
