<?php

defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);
require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

class test_AkActiveRecord_3 extends  AkUnitTest
{
    function _test_AkActiveRecord()  // dont reinstall, this test relies on _AkActiveRecord_1.php
    {
        $this->installAndIncludeModels(array(
        'AkTestUser'=>'id I AUTO KEY, user_name C(32), first_name C(200), last_name C(200), email C(150), country I, password C(32), created_at T, updated_at T, expires_on T',
        'AkTestMember'=>'ak_test_user_id I, role C(25)',
        'AkTestComment'=>'id I AUTO KEY, ak_test_user_id I, private_comment L(1), birth_date T',
        'AkTestField'=>'id I AUTO KEY,varchar_field C(255),longtext_field XL,text_field X,logblob_field B,date_field D,
                    datetime_field T,tinyint_field L(2),integer_field I,smallint_field I2,bigint_field I8,double_field F,
                    numeric_field N,bytea_field B,timestamp_field T,
                    boolean_field boolean,int2_field I2,int4_field I4,int8_field I8,foat_field F,varchar4000_field X, 
                    clob_field XL,nvarchar2000_field X2,blob_field B,nvarchar_field C2(255),
                    decimal1_field L(2),decimal3_field I1,decimal5_field I2,decimal10_field I4,decimal20_field I8,decimal_field N,
                    created_at T,updated_at T,expires_on T'));
    }

    function Test_of_toggleAttributeAndSave()
    {
        $AkTestFields = new AkTestField();

        $AkTestFields->transactionStart();
        //$AkTestFields->_db->debug();
        for ($i=1; $i <= 10; $i++){
            $this->assertTrue($AkTestFields->create(array('varchar_field' => 'test field '.$i)));
        }
        $AkTestFields->transactionComplete();

        $AkTestField = new AkTestField();
        $AkTestField = $AkTestField->find(2);
        $this->assertEqual($AkTestField->boolean_field,null);

        $AkTestField->set('boolean_field', false);
        $this->assertEqual($AkTestField->boolean_field,false);
        $AkTestField->toggleAttributeAndSave('boolean_field');
        $this->assertEqual($AkTestField->boolean_field,true);

        $AkTestField = $AkTestField->find(2);
        $this->assertEqual($AkTestField->boolean_field,true);
        $this->assertTrue($AkTestField->boolean_field);

        $AkTestField->toggleAttributeAndSave('boolean_field');

        $AkTestField = $AkTestField->find(2);
        $this->assertFalse($AkTestField->boolean_field);
    }


    function Test_of_delete()
    {
        $AkTestFields = new AkTestField();
        $AkTestField = $AkTestFields->find(2, 3, 4, 5, 6);
        $this->assertEqual(count($AkTestField), 5);

        $this->assertEqual($AkTestFields->delete(2), 1);
        $this->assertEqual($AkTestFields->delete(3, 4), 2);
        $this->assertEqual($AkTestFields->delete(array(5, 6)), 2);

        $this->assertFalse($AkTestFields->find(2, 3, 4, 5, 6));
    }

    function Test_of_deleteAll()
    {
        $AkTestFields = new AkTestField();
        $this->assertEqual(count($AkTestFields->findAll()), 5);

        $AkTestFields->transactionStart();
        for ($i=1; $i < 10; $i++){
            $AkTestFields->create(array('varchar_field' => 'new test field '.$i));
        }
        $AkTestFields->transactionComplete();

        $this->assertEqual($AkTestFields->deleteAll("varchar_field LIKE 'new%'"), 9);

        $this->assertEqual(count($AkTestFields->findAll()), 5);

        $this->assertEqual($AkTestFields->deleteAll(), 5);

        $this->assertFalse($AkTestFields->findAll());
    }


    function Test_of_destroy()
    {
        /**
        * @todo check persistance of destroyed objects
        */
        $AkTestFields = new AkTestField();

        $AkTestFields->transactionStart();
        for ($i=0; $i <= 10; $i++){
            $AkTestFields->create(array('varchar_field' => 'field to destroy '.$i));
        }
        $AkTestFields->transactionComplete();

        $AkTestFields->destroy(20);
        $this->assertEqual(count($AkTestFields->findAll()), 10);

        $AkTestFields->destroy(21);
        $this->assertEqual(count($AkTestFields->findAll()), 9);

        $AkTestFields->destroy(22, 23, 24);
        $this->assertEqual(count($AkTestFields->findAll()), 6);

        $AkTestFields->destroy(array(25, 26, 27));
        $this->assertEqual(count($AkTestFields->findAll()), 3);

        // 20 - 30
        //Ak::debug($ids);

    }

    function Test_of_destroyAll()
    {
        $AkTestFields = new AkTestField();

        $AkTestFields->transactionStart();
        for ($i=0; $i <= 10; $i++){
            $AkTestFields->create(array('varchar_field' => 'field to destroyAll '.$i));
        }
        $AkTestFields->transactionComplete();

        $AkTestFields->destroyAll("varchar_field  LIKE '%destroyAll%'");
        $this->assertEqual(count($AkTestFields->findAll()), 3);

        $AkTestFields->destroyAll('');
        $this->assertFalse($AkTestFields->findAll());
    }

    function Test_of_transactions()
    {
        $AkTestUser = new AkTestUser();
        //$AkTestUser->_db->debug = true;
        $AkTestUser->transactionStart();
        for ($i=1; $i <= 5; $i++){
            $AkTestUser->create(array('user_name' => 'from transaction','country'=>100));
        }
        $AkTestUser->transactionFail();

        $this->assertTrue($AkTestUser->transactionHasFailed());

        $AkTestUser->transactionComplete();

        $this->assertFalse($AkTestUser->find('all', array('conditions'=>"country = 100")),'Transactions are not working on current database. If you are using MySQL please check that  your server supports InnoDB tables');

        $AkTestUser->transactionStart();
        for ($i=1; $i <= 5; $i++){
            $AkTestUser->create(array('user_name' => 'from transaction','country'=>$i));
        }
        $this->assertFalse($AkTestUser->transactionHasFailed());
        $AkTestUser->transactionComplete();

        $this->assertEqual(count($AkTestUser->find('all', array('conditions'=>"user_name = 'from transaction'"))), 5);

        //$AkTestUser->_db->debug = false;
    }

    function Test_of_cloneRecord()
    {
        $AkTestUser = new AkTestUser();
        $User = $AkTestUser->find('first');
        $Cloned = $User->cloneRecord();

        $user_attributes = $User->getAttributes();
        $user_attributes[$User->getPrimaryKey()] = null;

        $this->assertEqual($user_attributes, $Cloned->getAttributes());

    }


    function Test_of_find2()
    {

        $Users = new AkTestUser('first_name=>','Tim','last_name->',"O'Reilly",'user_name->','tim_oreilly');
        $Users->_create();

        $User = $Users->find('first', array('conditions' => array("last_name = :last_name", ':last_name' => "O'Reilly")));
        $this->assertTrue($User->first_name=='Tim' && $User->last_name == "O'Reilly" && $User->user_name == 'tim_oreilly');

        $this->assertFalse($Users->find('first', array('conditions' => array("last_name = :last_name", ':last_name' => "' OR 1=1 AND first_name='Tim"))));

        $params = array('last_name'=>"O'Reilly");
        $User = $Users->find('first', array('conditions' => $params));
        $this->assertTrue($User->first_name=='Tim' && $User->last_name == "O'Reilly" && $User->user_name == 'tim_oreilly');

        //Trying sql inyection on values
        $params = array('last_name'=>"' OR 1=1 AND first_name='Tim");
        $this->assertFalse($Users->find('first', array('conditions' => $params)));

        //Trying sql inyection on keys
        $params = array("last_name ='Tim' OR last_name"=>"Not available name");
        $User = $Users->find('first', array('conditions' => $params));
        $this->assertFalse($User->first_name=='Tim' && $User->last_name == "O'Reilly" && $User->user_name == 'tim_oreilly');

        $User = $Users->find("first_name = ?",'Tim');
        $this->assertTrue($User[0]->first_name=='Tim' && $User[0]->last_name == "O'Reilly" && $User[0]->user_name == 'tim_oreilly');


        $User = $Users->find('first',"first_name = ?",'Tim');
        $this->assertTrue($User->first_name=='Tim' && $User->last_name == "O'Reilly" && $User->user_name == 'tim_oreilly');

        $FoundUsers = $Users->find('all',"first_name = ? OR first_name LIKE ?",'Tim','Al%',array('order'=>'last_name ASC'));
        $this->assertTrue($FoundUsers[0]->first_name=='Tim');
        $this->assertTrue($FoundUsers[1]->first_name=='Alicia');


        $FoundUsers = $Users->find('all',"first_name = ? OR first_name LIKE ?",'Tim','Al%',array('order'=>'last_name ASC'));
        $this->assertTrue($FoundUsers[0]->first_name=='Tim');
        $this->assertTrue($FoundUsers[1]->first_name=='Alicia');

        $this->assertFalse($Users->find("last_name = ?","' OR 1=1 AND first_name='Tim"));

    }

    function Test_of_binary_data_on_database()
    {
        $long_string = file_get_contents(AK_LIB_DIR.DS.'AkActiveRecord.php');

        $_tmp_file = fopen(AK_LIB_DIR.DS.'AkActiveRecord.php', "rb");
        $binary_data = fread($_tmp_file, fileSize(AK_LIB_DIR.DS.'AkActiveRecord.php'));

        $i = 1;

        $details = array(
        'varchar_field'=>"$i string ",
        'longtext_field'=>$long_string,
        'text_field'=>"$i text",
        'logblob_field'=>$binary_data,
        'date_field'=>"2005/05/$i",
        'datetime_field'=>"2005/05/$i",
        'tinyint_field'=>$i,
        'integer_field'=>$i,
        'smallint_field'=>$i,
        'bigint_field'=>$i,
        'double_field'=>"$i.$i",
        'numeric_field'=>$i,
        'bytea_field'=>$binary_data,
        'timestamp_field'=>"2005/05/$i $i:$i:$i",
        'boolean_field'=>!($i%2),
        'int2_field'=>"$i",
        'int4_field'=>$i,
        'int8_field'=>$i,
        'foat_field'=>"$i.$i",
        'varchar4000_field'=>"$i text",
        'clob_field'=>"$i text",
        'nvarchar2000_field'=>"$i text",
        'blob_field'=> $binary_data,
        'nvarchar_field'=>"$i",
        'decimal1_field'=>"$i",
        'decimal3_field'=>$i,
        'decimal5_field'=>$i,
        'decimal10_field'=>"$i",
        'decimal20_field'=>$i,
        'decimal_field'=>$i,
        );

        $AkTestField = new AkTestField($details);

        $this->assertEqual($long_string, $binary_data);

        $this->assertTrue($AkTestField->save());

        $AkTestField = new AkTestField($AkTestField->getId());

        $this->assertEqual($AkTestField->longtext_field, $long_string);
        $this->assertEqual($AkTestField->bytea_field, $binary_data);
        $this->assertEqual($AkTestField->blob_field, $binary_data);
        $this->assertEqual($AkTestField->logblob_field, $binary_data);


        //Now we add some more records for next tests

        foreach (range(2,10) as $i)
        {
            $details = array(
            'varchar_field'=>"$i string",
            'text_field'=>"$i text",
            'date_field'=>"2005/05/$i",
            'datetime_field'=>"2005/05/$i",
            'tinyint_field'=>$i,
            'integer_field'=>$i,
            'smallint_field'=>$i,
            'bigint_field'=>$i,
            'double_field'=>"$i.$i",
            'numeric_field'=>$i,
            'timestamp_field'=>"2005/05/$i $i:$i:$i",
            'boolean_field'=>!($i%2),
            'int2_field'=>"$i",
            'int4_field'=>$i,
            'int8_field'=>$i,
            'foat_field'=>"$i.$i",
            'varchar4000_field'=>"$i text",
            'clob_field'=>"$i text",
            'nvarchar2000_field'=>"$i text",
            'nvarchar_field'=>"$i",
            //'decimal1_field'=>"$i",
            'decimal3_field'=>$i,
            'decimal5_field'=>$i,
            'decimal10_field'=>"$i",
            'decimal20_field'=>$i,
            'decimal_field'=>$i,
            );

            $AkTestField = new AkTestField($details);
            $this->assertTrue($AkTestField->save());
        }

    }

    function Test_of_findBy()
    {
        $Users = new AkTestUser();

        $User = $Users->findBy('first',"first_name AND last_name",'Tim', "O'Reilly");
        $this->assertTrue($User->first_name=='Tim' && $User->last_name == "O'Reilly" && $User->user_name == 'tim_oreilly');


        $User_arr = $Users->findBy("first_name AND last_name",'Tim', "O'Reilly");
        $this->assertTrue($User_arr[0]->first_name=='Tim' && $User_arr[0]->last_name == "O'Reilly" && $User_arr[0]->user_name == 'tim_oreilly');

        $User_arr = $Users->findBy('all', "first_name AND last_name",'Tim', "O'Reilly");
        $this->assertTrue($User_arr[0]->first_name=='Tim' && $User_arr[0]->last_name == "O'Reilly" && $User_arr[0]->user_name == 'tim_oreilly');

        $FoundUsers = $Users->findBy("first_name OR first_name:begins",'Tim','Al',array('order'=>'last_name ASC'));
        $this->assertTrue($FoundUsers[0]->first_name=='Tim');
        $this->assertTrue($FoundUsers[1]->first_name=='Alicia');

        $this->assertErrorPattern('/Argument list did not match expected set/',$Users->findBy("username",'tim_oreilly'));
        $this->assertErrorPattern('/Argument list did not match expected set/',$Users->findBy("user_name AND password",'tim_oreilly'));

        //$Users->findBy("user_name AND password",'tim_oreilly','1234');

        $AkTestFields = new AkTestField();

        $this->assertTrue($AkTestField = $AkTestFields->findBy('numeric_field:< AND boolean_field',4,'yes'));
        $this->assertEqual($AkTestField[0]->varchar_field, '2 string');
        $this->assertEqual(count($AkTestField), 1);

        $this->assertTrue($AkTestField = $AkTestFields->findBy('varchar_field:begins',2));
        $this->assertEqual($AkTestField[0]->varchar_field, '2 string');
        $this->assertEqual(count($AkTestField), 1);

        $this->assertTrue($AkTestField = $AkTestFields->findBy('(varchar_field:begins OR int8_field OR timestamp_field:<) AND tinyint_field:>=', 2,3,'2005/05/04 23:00:00',2));
        $this->assertEqual($AkTestField[0]->varchar_field, '2 string');
        $this->assertEqual($AkTestField[1]->varchar_field, '3 string');
        $this->assertEqual($AkTestField[2]->varchar_field, '4 string');
        $this->assertEqual(count($AkTestField), 3);

        $this->assertTrue($AkTestField = $AkTestFields->findBy('(varchar_field:begins OR int8_field OR timestamp_field:<) AND tinyint_field:>=', 2,3,'2005/05/04 23:00:00',2,
        array('order'=>'numeric_field DESC')));
        $this->assertEqual($AkTestField[0]->varchar_field, '4 string');
        $this->assertEqual($AkTestField[1]->varchar_field, '3 string');
        $this->assertEqual($AkTestField[2]->varchar_field, '2 string');
        $this->assertEqual(count($AkTestField), 3);
    }

    function Test_of_findAllBy()
    {
        $Users = new AkTestUser();

        $User_arr = $Users->findAllBy("first_name AND last_name",'Tim', "O'Reilly");
        $this->assertTrue($User_arr[0]->first_name=='Tim' && $User_arr[0]->last_name == "O'Reilly" && $User_arr[0]->user_name == 'tim_oreilly');

        $User_arr = $Users->findAllBy("first_name AND last_name",'Tim', "O'Reilly");
        $this->assertTrue($User_arr[0]->first_name=='Tim' && $User_arr[0]->last_name == "O'Reilly" && $User_arr[0]->user_name == 'tim_oreilly');

        $FoundUsers = $Users->findAllBy("first_name OR first_name:begins",'Tim','Al',array('order'=>'last_name ASC'));
        $this->assertTrue($FoundUsers[0]->first_name=='Tim');
        $this->assertTrue($FoundUsers[1]->first_name=='Alicia');

        $this->assertErrorPattern('/Argument list did not match expected set/',$Users->findAllBy("username",'tim_oreilly'));
        $this->assertErrorPattern('/Argument list did not match expected set/',$Users->findAllBy("user_name AND password",'tim_oreilly'));

        $AkTestFields = new AkTestField();

        $this->assertTrue($AkTestField = $AkTestFields->findAllBy('numeric_field:< AND boolean_field',4,'yes'));
        $this->assertEqual($AkTestField[0]->varchar_field, '2 string');
        $this->assertEqual(count($AkTestField), 1);

        $this->assertTrue($AkTestField = $AkTestFields->findAllBy('varchar_field:begins',2));
        $this->assertEqual($AkTestField[0]->varchar_field, '2 string');
        $this->assertEqual(count($AkTestField), 1);

        $this->assertTrue($AkTestField = $AkTestFields->findAllBy('(varchar_field:begins OR int8_field OR timestamp_field:<) AND tinyint_field:>=', 2,3,'2005/05/04 23:00:00',2));
        $this->assertEqual($AkTestField[0]->varchar_field, '2 string');
        $this->assertEqual($AkTestField[1]->varchar_field, '3 string');
        $this->assertEqual($AkTestField[2]->varchar_field, '4 string');
        $this->assertEqual(count($AkTestField), 3);

        $this->assertTrue($AkTestField = $AkTestFields->findAllBy('(varchar_field:begins OR int8_field OR timestamp_field:<) AND tinyint_field:>=', 2,3,'2005/05/04 23:00:00',2,
        array('order'=>'numeric_field DESC')));
        $this->assertEqual($AkTestField[0]->varchar_field, '4 string');
        $this->assertEqual($AkTestField[1]->varchar_field, '3 string');
        $this->assertEqual($AkTestField[2]->varchar_field, '2 string');
        $this->assertEqual(count($AkTestField), 3);
    }

    function Test_of_findFirstBy()
    {
        $Users = new AkTestUser();

        $User = $Users->findFirstBy("first_name AND last_name",'Tim', "O'Reilly");
        $this->assertTrue($User->first_name=='Tim' && $User->last_name == "O'Reilly" && $User->user_name == 'tim_oreilly');

        $User_arr = $Users->findFirstBy("first_name AND last_name",'Tim', "O'Reilly");
        $this->assertTrue($User->first_name=='Tim' && $User->last_name == "O'Reilly" && $User->user_name == 'tim_oreilly');

        $FoundUser = $Users->findFirstBy("first_name OR first_name:begins",'Tim','Al',array('order'=>'last_name ASC'));
        $this->assertTrue($FoundUser->first_name=='Tim');

        $this->assertErrorPattern('/Argument list did not match expected set/',$Users->findFirstBy("username",'tim_oreilly'));
        $this->assertErrorPattern('/Argument list did not match expected set/',$Users->findFirstBy("user_name AND password",'tim_oreilly'));

        $AkTestFields = new AkTestField();

        $this->assertTrue($AkTestField = $AkTestFields->findFirstBy('numeric_field:< AND boolean_field',4,'yes'));
        $this->assertEqual($AkTestField->varchar_field, '2 string');

        $this->assertTrue($AkTestField = $AkTestFields->findFirstBy('varchar_field:begins',2));
        $this->assertEqual($AkTestField->varchar_field, '2 string');

        $this->assertTrue($AkTestField = $AkTestFields->findFirstBy('(varchar_field:begins OR int8_field OR timestamp_field:<) AND tinyint_field:>=', 2,3,'2005/05/04 23:00:00',2));
        $this->assertEqual($AkTestField->varchar_field, '2 string');

        $this->assertTrue($AkTestField = $AkTestFields->findFirstBy('(varchar_field:begins OR int8_field OR timestamp_field:<) AND tinyint_field:>=', 2,3,'2005/05/04 23:00:00',2,
        array('order'=>'numeric_field DESC')));
        $this->assertEqual($AkTestField->varchar_field, '4 string');
    }

    function Test_of_findLastBy()
    {
        $Users = new AkTestUser();

        $this->assertErrorPattern('/Argument list did not match expected set/',$Users->findLastBy("username",'tim_oreilly'));
        $this->assertErrorPattern('/Argument list did not match expected set/',$Users->findLastBy("user_name AND password",'tim_oreilly'));

        $AkTestFields = new AkTestField();

        $this->assertTrue($AkTestField = $AkTestFields->findLastBy('(varchar_field:begins OR int8_field OR timestamp_field:<) AND tinyint_field:>=', 2,3,'2005/05/04 23:00:00',2));
        $this->assertEqual($AkTestField->varchar_field, '4 string');

    }

    function Test_of_getInheritanceColumn()
    {
        $AkTestUser = new AkTestUser();

        $this->assertFalse($AkTestUser->getInheritanceColumn());

        // Adding false column
        $AkTestUser->_columns['type'] = array('name' => 'type', 'type' => 'string');

        $this->assertEqual($AkTestUser->getInheritanceColumn(), 'type');

        $AkTestUser->setInheritanceColumn('first_name');

        $this->assertEqual($AkTestUser->getInheritanceColumn(), 'first_name');

    }



    function Test_of_setInheritanceColumn()
    {
        $AkTestUser = new AkTestUser();

        $this->assertTrue($AkTestUser->setInheritanceColumn('first_name'));
        $this->assertErrorPattern('/instead/',$AkTestUser->setInheritanceColumn('country'));
        $this->assertErrorPattern('/available/',$AkTestUser->setInheritanceColumn('type'));
    }


    function Test_of_typeCondition()
    {
        $AkTestMember = new AkTestMember();
        $AkTestMember->setInheritanceColumn('role');

        $this->assertEqual($AkTestMember->typeCondition(),"( ak_test_members.role = 'Ak test member' ) ");
    }

    function Test_of_addConditions()
    {
        $AkTestUser = new AkTestUser();
        $sql = 'SELECT * FROM ak_test_users';
        $copy = $sql;
        $conditions = "last_name = 'Ferrer' AND country = 25";
        $AkTestUser->addConditions($sql,$conditions);
        $this->assertEqual($sql,$copy.' WHERE '.$conditions);

        $AkTestMember = new AkTestMember();
        $sql = 'SELECT * FROM ak_test_members, ak_test_users';
        $copy = $sql;
        $conditions = "ak_test_users.last_name = 'Ferrer' AND ak_test_users.country = 25";
        $AkTestMember->setInheritanceColumn('role');
        $AkTestMember->addConditions($sql, $conditions);
        $this->assertEqual($sql,$copy." WHERE ( ak_test_members.role = 'Ak test member' )  AND (".$conditions.")");
    }

    function Test_of_resetColumnInformation()
    {
        $AkTestUser = new AkTestUser();
        $AkTestUser->getColumns(); // Loads settings
        $AkTestUser->resetColumnInformation();
        $this->assertTrue(empty($AkTestUser->_columnNames) && empty($AkTestUser->_columns) && empty($AkTestUser->_columnsSettings) && empty($AkTestUser->_contentColumns));
    }



    function Test_of_freeze_and_isFrozen()
    {
        $AkTestMember = new AkTestMember();
        $this->assertFalse($AkTestMember->isFrozen());
        $AkTestMember->freeze();
        $this->assertTrue($AkTestMember->isFrozen());
    }


    function Test_of_count()
    {
        $AkTestUser = new AkTestUser();
        $this->assertEqual($AkTestUser->count(), count($AkTestUser->find()));

        $this->assertEqual($AkTestUser->count("first_name = 'Tim'"), count($AkTestUser->findAll("first_name = 'Tim'")));
    }

    function Test_of_countBySql()
    {
        $AkTestUser = new AkTestUser();
        $this->assertEqual($AkTestUser->countBySql("SELECT COUNT(*) FROM ak_test_users"), count($AkTestUser->find()));

        $this->assertEqual($AkTestUser->countBySql("SELECT COUNT(*) FROM ak_test_users WHERE first_name = 'Tim'"), count($AkTestUser->findAll("first_name = 'Tim'")));
    }

    // Test_of_getConditions(){}

    // Test_of_constructFinderSql(){}
    // Test_of_findWithAssociations(){}

    // Test_of_establishConnection(){}
    // Test_of_getConnection(){}

    // Test_of_init(){}
    // Test_of_initCrud(){}


    // Test_of_objectCache(){}
    // Test_of_removeAttributesProtectedFromMassAssignment(){}
    // Test_of_resetColumnInformation(){}

    // Test_of_t(){}



}

require_once('_AkActiveRecord_1.php');
require_once('_AkActiveRecord_2.php');
ak_test('test_AkActiveRecord_3',true);

?>
