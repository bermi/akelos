<?php

defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);
require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

class test_AkActiveRecord extends  AkUnitTest 
{

    function test_AkActiveRecord()
    {
        $this->installAndIncludeModels(array(
            'AkTestUser'=>'id I AUTO KEY, user_name C(32), first_name C(200), last_name C(200), email C(150), country I, password C(32), created_at T, updated_at T, expires_on T',
            'AkTestMember'=>'ak_test_user_id I, role C(25)',
            'AkTestComment'=>'id I AUTO KEY, ak_test_user_id I, private_comment L, birth_date T',
            'AkTestField'=>'id I AUTO KEY,varchar_field C(255),longtext_field XL,text_field X,logblob_field B,date_field D, 
                    datetime_field T,tinyint_field I2,integer_field I,smallint_field I2,bigint_field I8,double_field F,
                    numeric_field N(10.5),bytea_field B,timestamp_field T,
                    boolean_field L,int2_field I2,int4_field I4,int8_field I8,foat_field F,varchar4000_field X, 
                    clob_field XL,nvarchar2000_field X2,blob_field B,nvarchar_field C2(255),
                    decimal1_field L,'.//*decimal3_field I1,
                    'decimal5_field I2,decimal10_field I4,decimal20_field I8,decimal_field N(10.5),
                    created_at T,updated_at T,expires_on T'));
    }

    function Test_of_getArrayFromAkString()
    {
        $User = new AkTestUser();
        $expected = array('name', 'last_name','options','date');
        $this->assertEqual($User->getArrayFromAkString('name,last_name,options,date'),$expected);
        $this->assertEqual($User->getArrayFromAkString(' name AND last_name ; options | date '),$expected);
        $this->assertEqual($User->getArrayFromAkString(' name and last_name + options , date '),$expected);
    }
    /**/
    function Test_of_parseAkelosArgs()
    {
        $User = new AkTestUser();
        
        $expected = array('name'=>'Bermi', 'last_name'=>'Ferrer','options'=>array('admin'=>true,'expire'=>'never'),'date'=>'1978-06-16');
        $akelos_args = array('name->','Bermi', 'last_name->','Ferrer','options'=>array('admin'=>true,'expire'=>'never'),'date->','1978-06-16');
        $User->parseAkelosArgs($akelos_args);
        $this->assertEqual($akelos_args,$expected);
        $User->parseAkelosArgs($expected);
        $this->assertEqual($akelos_args,$expected);
    }
    /**/
    function Test_of_isConnected()
    {
        $User = new AkTestUser();
        $User->setConnection();
        $this->assertTrue($User->isConnected());
    }
    
    function Test_of_new_object_instantation()
    {
        $AkTestUser = new AkTestUser();

        $this->assertEqual($AkTestUser->getModelName(), 'AkTestUser');
        $this->assertEqual($AkTestUser->getTableName(), 'ak_test_users');
        $this->assertErrorPattern('/ak_test_user/',$AkTestUser->setTableName('ak_test_user'));

        //$this->_createNewTestingModel('AkTestUnavailableDatabase');
        //$AkTestUnavailableDatabase = new AkTestUnavailableDatabase();
        //$this->assertEqual($AkTestUnavailableDatabase->getModelName(), 'AkTestUnavailableDatabase');
        //ak_define('AK_ACTIVE_RECORD_VALIDATE_TABLE_NAMES', true);
        //$this->assertErrorPattern('/Ooops! Could not fetch details for the table ak_test_unavailable_database./',$AkTestUnavailableDatabase->getTableName());
    }


    // Test_of_setConnection(){} //This will not be tested due its simplicity

    function Test_of_descendsFromActiveRecord()
    {
        $User = new AkTestUser();
        $TestField = new AkTestField();
        
        $this->assertTrue($User->descendsFromActiveRecord($TestField));
        
        $Object = new AkObject();
        $this->assertFalse($User->descendsFromActiveRecord($Object));
    }
    
    function Test_of_getModelName()
    {
        $AkTestUser = new AkTestUser();
        $this->assertEqual($AkTestUser->getModelName(), 'AkTestUser');

        $AkTestField = new AkTestField();
        $this->assertEqual($AkTestField->getModelName(), 'AkTestField');
    }
    
    
    function Test_of_set_and_getParentModelName()
    {
        $AkTestMember = new AkTestMember();
        $this->assertEqual($AkTestMember->getParentModelName(), 'AkTestUser');
        
        $AkTestUser = new AkTestUser();
        $this->assertErrorPattern('/YourParentModelName/',$AkTestUser->getParentModelName());
        
        $AkTestUser = new AkTestUser();
        $AkTestUser->setParentModelName('FakeClass');
        $this->assertEqual($AkTestUser->getParentModelName(), 'FakeClass');

    }

    // More db type inspection on Test_of_db_inspection method of this test
    function Test_of_getAkelosDataType()
    {
        $AkTestField = new AkTestField();

        $adodb_column_object = new AkObject();
        $adodb_column_object->name = 'decimal_field';

        $adodb_column_object->type = 'BLOB';
        $this->assertEqual('binary',$AkTestField->getAkelosDataType($adodb_column_object));

        $adodb_column_object->type = 'integer';
        $adodb_column_object->auto_increment = 1;
        $this->assertEqual('serial',$AkTestField->getAkelosDataType($adodb_column_object));

        $AkTestField->decimalFieldDataType = 'string';
        $this->assertEqual('string',$AkTestField->getAkelosDataType($adodb_column_object));
    }

    // This test is implemented in Test_of_db_inspection
    // function Test_of_setColumnSettings(){
    // }

    // More of this testing in Test_of_db_inspection
    function Test_of_loadColumnsSettings()
    {
        $AkTestField = new AkTestField();
        $AkTestField->loadColumnsSettings();
        // Testing database settings cache on session (this might be changed in a future
        AkDbSchemaCache::doRefresh(false);
        $this->assertEqual($AkTestField->_columnsSettings,AkDbSchemaCache::getModelColumnSettings('AkTestField'));
        AkDbSchemaCache::doRefresh(true);
    }

    function Test_of_initiateColumnsToNull()
    {
        $AkTestField = new AkTestField();
        $AkTestField->loadColumnsSettings();

        $columns = array('id','varchar_field','longtext_field','text_field','logblob_field','date_field','datetime_field','tinyint_field','integer_field','smallint_field',
        'bigint_field','double_field','numeric_field','bytea_field','timestamp_field','boolean_field','int2_field','int4_field',
        'int8_field','foat_field','varchar4000_field','clob_field','nvarchar2000_field','blob_field','nvarchar_field','decimal1_field',//'decimal3_field',
        'decimal5_field','decimal10_field','decimal20_field','decimal_field','created_at','updated_at','expires_on'
        );

        foreach ($columns as $column){
            $this->assertFalse(isset($AkTestField->$column));
        }

        $AkTestField->initiateColumnsToNull();

        foreach ($columns as $column){
            $this->assertTrue(is_null($AkTestField->$column));
        }
    }


    function Test_of_getColumnSettings()
    {
        $AkTestField = new AkTestField();

        $columns = array('id','varchar_field','longtext_field','text_field','logblob_field','date_field','datetime_field','tinyint_field','integer_field','smallint_field',
        'bigint_field','double_field','numeric_field','bytea_field','timestamp_field','boolean_field','int2_field','int4_field',
        'int8_field','foat_field','varchar4000_field','clob_field','nvarchar2000_field','blob_field','nvarchar_field','decimal1_field',//'decimal3_field',
        'decimal5_field','decimal10_field','decimal20_field','decimal_field','created_at','updated_at','expires_on'
        );

        $got = $AkTestField->getColumnSettings();

        $this->assertTrue($got['id']['type'] == 'serial' && $got['id']['primaryKey'] == true);
        foreach ($columns as $column){
            $this->assertTrue(isset($got[$column]) && $got[$column]['name'] == $column && !empty($got[$column]['type']));
        }
    }


    function Test_of_get_and_get_PrimaryKey()
    {
        $AkTestField = new AkTestField();
        $this->assertEqual($AkTestField->getPrimaryKey(), 'id');

        $AkTestField->setPrimaryKey('text_field');
        $this->assertEqual($AkTestField->getPrimaryKey(), 'text_field');

        $this->assertError($AkTestField->setPrimaryKey('unavailable_field'),'unavailable_field');
    }

    function Test_of_get_and_get_TableName()
    {
        $AkTestField = new AkTestField();
        $this->assertEqual($AkTestField->getTableName(), 'ak_test_fields');

        $this->assertError($AkTestField->setTableName('table_not_available_on_database'),'AK_ACTIVE_CONTROLLER_VALIDATE_TABLE_NAMES');

        $this->assertTrue($AkTestField->setTableName('ak_test_users'));
        $this->assertEqual($AkTestField->getTableName(), 'ak_test_users');
    }

    function Test_of_hasColumn()
    {
        $AkTestUser = new AkTestUser();
        $this->assertTrue($AkTestUser->hasColumn('first_name'));
        $this->assertFalse($AkTestUser->hasColumn('not_valid'));
        $AkTestUser->addCombinedAttributeConfiguration('name', "%s %s", 'first_name', 'last_name');
        $this->assertFalse($AkTestUser->hasColumn('name'));
    }

    function Test_of_getColumnNames()
    {
        $AkTestField = new AkTestField();
        $expected = array ( 'id' => 'Id', 'varchar_field' => 'Varchar field', 'longtext_field' => 'Longtext field', 'text_field' => 'Text field', 'logblob_field' => 'Logblob field', 'date_field' => 'Date field', 'datetime_field' => 'Datetime field', 'tinyint_field' => 'Tinyint field', 'integer_field' => 'Integer field', 'smallint_field' => 'Smallint field', 'bigint_field' => 'Bigint field', 'double_field' => 'Double field', 'numeric_field' => 'Numeric field', 'bytea_field' => 'Bytea field', 'timestamp_field' => 'Timestamp field', 'boolean_field' => 'Boolean field', 'int2_field' => 'Int2 field', 'int4_field' => 'Int4 field', 'int8_field' => 'Int8 field', 'foat_field' => 'Foat field', 'varchar4000_field' => 'Varchar4000 field', 'clob_field' => 'Clob field', 'nvarchar2000_field' => 'Nvarchar2000 field', 'blob_field' => 'Blob field', 'nvarchar_field' => 'Nvarchar field', 'decimal1_field' => 'Decimal1 field', /*'decimal3_field' => 'Decimal3 field',*/ 'decimal5_field' => 'Decimal5 field', 'decimal10_field' => 'Decimal10 field', 'decimal20_field' => 'Decimal20 field', 'decimal_field' => 'Decimal field', 'created_at' => 'Created at', 'updated_at' => 'Updated at', 'expires_on' => 'Expires on' );
        $this->assertEqual($AkTestField->getColumnNames(), $expected);
    }

    // This is tested in db inspection tests (Test_of_db_inspection)
    // function Test_of_getColumns(){}


    function Test_of_db_inspection()
    {
        $AkTestUser = new AkTestUser();

        $expected = array(
        'id' => array ('name' => 'id', 'type' => 'serial', 'primaryKey' => true),
        'user_name' => array ( 'name' => 'user_name', 'type' => 'string'),
        'first_name' => array ( 'name' => 'first_name', 'type' => 'string'),
        'last_name' => array ( 'name' => 'last_name', 'type' => 'string'),
        'email' => array ( 'name' => 'email', 'type' => 'string'),
        'country' => array ( 'name' => 'country', 'type' => 'integer'),
        'password' => array ( 'name' => 'password', 'type' => 'string'),
        'created_at' => array ( 'name' => 'created_at', 'type' => 'datetime' ),
        'updated_at' => array ( 'name' => 'updated_at', 'type' => 'datetime' ),
        'expires_on' => array ( 'name' => 'expires_on', 'type' => 'date' ) );

        $got = array();
        $tmp_got = $AkTestUser->getColumns();
        foreach ($tmp_got as $k=>$v){
            $got[$k]['name'] = $v['name'];
            $got[$k]['type'] = $v['type'];
            if(!empty($v['primaryKey'])){
                $got[$k]['primaryKey'] = $v['primaryKey'];
            }
        }

        $this->assertEqual($got, $expected);

        //---------------------

        $AkTestField = new AkTestField();
        $AkTestField->getColumns();
        //$AkTestField->debug();

        $expected = array(
        'id'=>'serial',
        'varchar_field'=>'string',
        'longtext_field'=>'text',
        'text_field'=>'text',
        'logblob_field'=>'binary',
        'date_field'=>'date',
        'datetime_field'=>'datetime',
        'tinyint_field'=>'integer',
        'integer_field'=>'integer',
        'smallint_field'=>'integer',
        'bigint_field'=>'integer',
        'double_field'=>'float',
        'numeric_field'=>'decimal',
        'bytea_field'=>'binary',
        'timestamp_field'=>'datetime',
        'boolean_field'=>'boolean',
        'int2_field'=>'integer',
        'int4_field'=>'integer',
        'int8_field'=>'integer',
        'foat_field'=>'float',
        'varchar4000_field'=>'text',
        'clob_field'=>'text',
        'nvarchar2000_field'=>'text',
        'blob_field'=>'binary',
        'nvarchar_field'=>'string',
        'decimal1_field'=>'boolean',
        //'decimal3_field'=>'integer',
        'decimal5_field'=>'integer',
        'decimal10_field'=>'integer',
        'decimal20_field'=>'integer',
        'decimal_field'=>'decimal',
        'created_at'=>'datetime',
        'updated_at'=>'datetime',
        'expires_on'=>'date'
        );


        $got = array();
        foreach ($AkTestField->_columnsSettings as $k=>$v){
            $got[$k] = $v['type'];
            if($expected[$k] != $got[$k]){
                //Ak::trace("$k => ".$expected[$k].' ::: '.$got[$k],__LINE__,__FILE__);
            }
        }
        $this->assertEqual($got, $expected);
        //$AkTestField->debug();
    }

    /// @todo implement test cases
    
    // Test_of___construct(){}
    // Test_of___destruct(){}
    // Test_of__addMethod(){}
    // Test_of__constructAssociatedModels(){}
    // Test_of__getIncludedModelNames(){}
    // Test_of__link(){}
    // Test_of__linkAssociations(){}
    // Test_of__update(){}
    // Test_of__validateAssociatedModel(){}



    /////// COMBINED ATTRIBUTES TESTS
    
    function Test_of_addCombinedAttributeConfiguration()
    {
        $User = new AkTestUser();
        $User->addCombinedAttributeConfiguration('name', "%s %s", 'first_name', 'last_name');
        $User->addCombinedAttributeConfiguration('reversed_name', "%s, %s", 'last_name', 'first_name');
        // Checking that composeCombinedAttributes is called when new rules are added
        $this->assertTrue(empty($User->name));
        $this->assertTrue(empty($User->reversed_name));

        $expected = array('name' => array('%s %s','first_name','last_name'),'reversed_name' => array('%s, %s','last_name','first_name'));
        $this->assertEqual($User->_combinedAttributes, $expected);
    }

    function Test_of_composeCombinedAttribute()
    {
        $User = new AkTestUser();
        $User->addCombinedAttributeConfiguration('name', "%s %s", 'first_name', 'last_name');
        $User->addCombinedAttributeConfiguration('reversed_name', "%s, %s", 'last_name', 'first_name');
        $User->addCombinedAttributeConfiguration('reversed_name', array("%s, %s","%[^,], %s"), 'last_name', 'first_name');
        $User->addCombinedAttributeConfiguration('email_link', array("callBackFunctionCompose","callBackFunctionDecompose"), 'email', 'name');
        $User->addCombinedAttributeConfiguration('email_header_from', "<%s>%s", 'email', 'name');

        $User->first_name = 'Bermi';
        $User->last_name = 'Ferrer';

        $User->composeCombinedAttribute('name');
        $User->composeCombinedAttribute('reversed_name');

        $this->assertEqual($User->name,'Bermi Ferrer');
        $this->assertEqual($User->reversed_name,'Ferrer, Bermi');

        $User->setAttribute('last_name', 'Ferrer');
        $User->first_name = 'Bermi';
        $this->assertEqual($User->getAttribute('name'), 'Bermi Ferrer');

        $User->first_name = 'Hilario';
        $User->set('last_name', 'Hervas');
        $this->assertEqual($User->get('reversed_name'), 'Hervas, Hilario');
        
        $User = new AkTestUser();
        $User->addCombinedAttributeConfiguration(array('name', "%s %s", 'first_name', 'last_name')); // This is how combined attributes are added when they are set trhough a model variable
        $User->first_name = 'Bermi';
        $User->last_name = 'Ferrer';

        $User->composeCombinedAttribute('name');
        $this->assertEqual($User->name,'Bermi Ferrer');

    }

    function Test_of_decomposeCombinedAttribute()
    {

        $User = new AkTestUser();
        $User->addCombinedAttributeConfiguration('name', "%s %s", 'first_name', 'last_name');
        $User->addCombinedAttributeConfiguration('reversed_name', "%[^,], %s", 'last_name', 'first_name');
        $User->addCombinedAttributeConfiguration('email_link', array("callBackFunctionCompose","callBackFunctionDecompose"), 'email', 'name');
        $User->addCombinedAttributeConfiguration('email_header_from', "<%s>%s", 'email', 'name');

        $User->setAttribute('name', 'Bermi Ferrer');
        $this->assertEqual($User->getAttribute('first_name'), 'Bermi');
        $this->assertEqual($User->last_name, 'Ferrer');

        $User->set('reversed_name', 'Hervas, Hilario');
        $this->assertEqual($User->first_name, 'Hilario');
        $this->assertEqual($User->last_name, 'Hervas');

        $User->email_link = "<a href='mailto:nospam@example.com'>Bermi Ferrer</a>";
        $User->decomposeCombinedAttribute('email_link');
        $this->assertEqual($User->email, 'nospam@example.com');
        $this->assertEqual($User->name, 'Bermi Ferrer');

        $this->assertEqual($User->get('email_header_from'), '<nospam@example.com>Bermi Ferrer');
    }

    function Test_of_decomposeCombinedAttributes()
    {

        $User = new AkTestUser();

        $User->addCombinedAttributeConfiguration('name', "%s %s", 'first_name', 'last_name');
        $User->addCombinedAttributeConfiguration('reversed_name', "%[^,], %s", 'last_name', 'first_name');
        $User->addCombinedAttributeConfiguration('email_link', array("callBackFunctionCompose","callBackFunctionDecompose"), 'email', 'name');
        $User->addCombinedAttributeConfiguration('email_header_from', "<%s>%s", 'email', 'name');

        $User->name = 'Bermi Ferrer';

        $User->decomposeCombinedAttributes();
        $this->assertEqual($User->getAttribute('first_name'), 'Bermi');
        $this->assertEqual($User->last_name, 'Ferrer');

        $User->email_link = "<a href='mailto:nospam@example.com'>Hilario Hervas</a>";
        $User->decomposeCombinedAttributes();
        $this->assertEqual($User->first_name, 'Hilario');
        $this->assertEqual($User->last_name, 'Hervas');
        $this->assertEqual($User->name, 'Hilario Hervas');
        $this->assertEqual($User->email, 'nospam@example.com');


        $User->name = 'Wont Precede';
        $User->email_link = "<a href='mailto:nospam-again@example.com'>Must Precede</a>";
        $User->decomposeCombinedAttributes();
        $this->assertEqual($User->getAttribute('name'), 'Must Precede');
        $this->assertEqual($User->getAttribute('email'), 'nospam-again@example.com');
    }


    function Test_of_getAttribute()
    {
        $User = new AkTestUser();

        $User->first_name = 'Bermi';
        $User->password = 'pass';
        $this->assertEqual($User->get('first_name'), 'Bermi');
        $this->assertEqual($User->get('_columns'), null);
        $this->assertEqual($User->get('password'),'*********');

        $User->set('password', 'bermi');
        $this->assertEqual($User->getAttributeBeforeTypeCast('password'),'c6dd746a20f85fecb18591f29508d42d');

    }

    function Test_of_getAvailableAttributes()
    {
        $User = new AkTestUser();
        $User->addCombinedAttributeConfiguration('name', "%s %s", 'first_name', 'last_name');
        $tmp_got = $User->getAvailableAttributes();
        $expected = array('name' => 'name', 'type' => 'string', 'path' => '%s %s','uses'=>array('first_name','last_name'));
        $got = $tmp_got['name'];
        $this->assertEqual($got, $expected);

        $expected = array('name' => 'id', 'type' => 'serial', 'primaryKey' => 1);
        $got = array('name' => $tmp_got['id']['name'], 'type' => $tmp_got['id']['type'], 'primaryKey' => $tmp_got['id']['primaryKey']);
        $this->assertEqual($got, $expected);

        $expected = array('name' => 'created_at', 'type' => 'datetime');
        $got = array('name' => $tmp_got['created_at']['name'], 'type' => $tmp_got['created_at']['type']);
        $this->assertEqual($got, $expected);
    }


    function Test_of_getAttributes()
    {
        $User = new AkTestUser();

        $User->addCombinedAttributeConfiguration('name', "%s %s", 'first_name', 'last_name');

        $User->name = "Bermi Ferrer";
        $User->email = "bermi@example.com";
        $User->set('password','nada');
        $User->decomposeCombinedAttributes();
        $this->assertEqual($User->getAttributes(),array ( 'id' => NULL, 'user_name' => NULL, 'first_name' => 'Bermi', 'last_name' => 'Ferrer', 'email' => 'bermi@example.com', 'country' => NULL, 'password' => '*********', 'created_at' => NULL, 'updated_at' => NULL, 'expires_on' => NULL, 'name' => 'Bermi Ferrer' ));

    }

    function Test_of_getAttributeNames()
    {
        $User = new AkTestUser();

        $User->addCombinedAttributeConfiguration('name', "%s %s", 'first_name', 'last_name');
        $this->assertEqual($User->getAttributeNames(),array ( 'country' => 'Country', 'created_at' => 'Created at', 'email' => 'Email', 'expires_on' => 'Expires on', 'first_name' => 'First name', 'id' => 'Id', 'last_name' => 'Last name', 'name' => 'Name', 'password' => 'Password', 'updated_at' => 'Updated at', 'user_name' => 'User name' ));

    }

    function Test_of_getAttributesBeforeTypeCast()
    {
        $User = new AkTestUser();
        $User->set('password', 'bermi');
        $this->assertEqual($User->getAttributesBeforeTypeCast(), array('password' => 'c6dd746a20f85fecb18591f29508d42d'));
        $this->assertEqual($User->get('password'), '*********');
    }


    function Test_of_getOnlyAvailableAttributes()
    {
        $User = new AkTestUser();
        $User->addCombinedAttributeConfiguration('name', "%s %s", 'first_name', 'last_name');
        $attributes = array('name'=>'Bermi Ferrer', 'email' => 'bermi@example.com', 'invalid_field'=>'value');
        $attributes = $User->getOnlyAvailableAttributes($attributes);
        $this->assertEqual($attributes,array('name'=>'Bermi Ferrer', 'email' => 'bermi@example.com'));
    }
    
    function Test_of_getColumnsForAttributes()
    {
        $User = new AkTestUser();
        $User->addCombinedAttributeConfiguration('name', "%s %s", 'first_name', 'last_name');
        $attributes = array('name'=>'Bermi Ferrer', 'email' => 'bermi@example.com', 'invalid_field'=>'value');
        $attributes = $User->getColumnsForAttributes($attributes);
        $this->assertEqual($attributes,array('email' => 'bermi@example.com'));
    }


    function Test_of_hasAttribute()
    {
        $User = new AkTestUser();
        $User->addCombinedAttributeConfiguration('name', "%s %s", 'first_name', 'last_name');
        $this->assertTrue($User->hasAttribute('name'));
        $this->assertTrue($User->hasAttribute('first_name'));
        $this->assertTrue($User->hasAttribute('id'));
        $this->assertTrue($User->hasAttribute('expires_on'));
        $this->assertFalse($User->hasAttribute('this_is_not_a_column_name'));
        $this->assertFalse($User->hasAttribute('_columns'));
    }

    function Test_of_isAttributePresent()
    {
        $User = new AkTestUser();
        $User->addCombinedAttributeConfiguration('name', "%s %s", 'first_name', 'last_name');

        $this->assertFalse($User->isAttributePresent('name'));

        $User->set('name','Bermi Ferrer');
        $this->assertTrue($User->isAttributePresent('name'));
        $this->assertTrue($User->isAttributePresent('first_name'));

        $User->set('last_name',null);
        $this->assertFalse($User->isAttributePresent('last_name'));

        $User->set('first_name','');
        $this->assertFalse($User->isAttributePresent('first_name'));
    }

    function Test_of_getAvailableAttributesQuoted()
    {
        $User = new AkTestUser();

        $User->addCombinedAttributeConfiguration('name', "%s %s", 'first_name', 'last_name');

        $fields = array('name'=>"Tim O'Reilly",'user_name'=>"a'''''a",'id'=>555,'expires_on'=>'2089-06-16');
        $User->set($fields);
        $expected = array('id' => "id=555",
        'user_name' => "user_name='a\'\'\'\'\'a'",
        'first_name' => "first_name='Tim'",
        'last_name' => "last_name='O\'Reilly'",
        'email' => "email=null",
        'country' => "country=null",
        'password' => "password='*********'",
        'created_at' => "created_at=null",
        'updated_at' => "updated_at=null",
        'expires_on' => "expires_on='2089-06-16'"
        );

        $got = $User->getAvailableAttributesQuoted();
        $this->assertTrue($expected['last_name'] == $got['last_name'] || str_replace("\\","'",$expected['last_name']) == $got['last_name']);
        $this->assertTrue($expected['user_name'] == $got['user_name'] || str_replace("\\","'",$expected['user_name']) == $got['user_name']);
        unset($expected['user_name'], $got['user_name'], $expected['last_name'], $got['last_name']);
        $this->assertEqual($expected, $got);
    }

    function Test_of_getSerializedAttributes_and_setSerializeAttribute()
    {
        $User = new AkTestUser();
        $User->addCombinedAttributeConfiguration('name', "%s %s", 'first_name', 'last_name');
        $User->setSerializeAttribute('user_name');
        $User->setSerializeAttribute('name');
        $User->setSerializeAttribute('not_valid');
        $User->setSerializeAttribute('email','MimeEmail');
        $expected = array('user_name' => null, 'email' => 'MimeEmail');
        $this->assertEqual($expected, $User->getSerializedAttributes());

    }

    function Test_of_setAccessibleAttributes()
    {
        $User = new AkTestUser();
        $User->addCombinedAttributeConfiguration('name', "%s %s", 'first_name', 'last_name');
        $User->setAccessibleAttributes('first_name','last_name','name','country','email');
        $expected = array('first_name', 'last_name', 'name', 'country', 'email');
        $this->assertEqual($expected, $User->_accessibleAttributes);

    }

    function Test_of_setProtectedAttributes()
    {
        $User = new AkTestUser();
        $User->addCombinedAttributeConfiguration('name', "%s %s", 'first_name', 'last_name');
        $User->setProtectedAttributes('first_name','last_name','name','country','email');
        $expected = array('first_name', 'last_name', 'name', 'country', 'email');
        $this->assertEqual($expected, $User->_protectedAttributes);
    }

    function Test_of_setAttribute()
    {
        $User = new AkTestUser();
        $User->setAttribute('first_name', 'Bermi');
        $User->setAttribute('password', 'pass');
        $this->assertEqual($User->first_name, 'Bermi');
        $this->assertFalse($User->setAttribute('_columns',array()));
        $this->assertFalse(empty($User->_columns));
        $this->assertEqual($User->get('password'),'*********');
        $User->set('password', 'bermi');
        $this->assertEqual($User->getAttributeBeforeTypeCast('password'),'c6dd746a20f85fecb18591f29508d42d');
    }

    function Test_of_setAttributes()
    {
        $User = new AkTestUser();
        $attributes = array(
        'first_name'=> 'Bermi', 'password'=> 'bermi', '_columns'=>array(), '_test_private_var'=>true
        );
        $User->setAttributes($attributes);
        $this->assertFalse(empty($User->_columns));
        $this->assertEqual($User->first_name, 'Bermi');
        $this->assertEqual($User->get('password'),'*********');
        $this->assertEqual($User->getAttributeBeforeTypeCast('password'),'c6dd746a20f85fecb18591f29508d42d');

        $User->setAttributes($attributes,true);
        $this->assertFalse(!empty($User->_test_private_var));
    }

    function Test_of_toggleAttribute()
    {
        $AkTestField = new AkTestField();
        $AkTestField->set('boolean_field', true);
        $this->assertTrue($AkTestField->boolean_field);
        $this->assertTrue($AkTestField->get('boolean_field'));
        $AkTestField->toggleAttribute('boolean_field');
        $this->assertTrue(!$AkTestField->boolean_field);
        $this->assertTrue(!$AkTestField->get('boolean_field'));
        $AkTestField->toggleAttribute('boolean_field');
        $this->assertTrue($AkTestField->boolean_field);
        $this->assertTrue($AkTestField->get('boolean_field'));
    }

    function Test_of_get_and_set_DisplayField()
    {
        $AkTestField = new AkTestField();
        $this->assertEqual($AkTestField->getDisplayField(), 'id');
        $AkTestField->setDisplayField('text_field');
        $this->assertEqual($AkTestField->getDisplayField(), 'text_field');

        $AkTestUser = new AkTestUser();
        $this->assertEqual($AkTestUser->getDisplayField(), 'id');

        $AkTestUser->addCombinedAttributeConfiguration('name', "%s %s", 'first_name', 'last_name');
        $this->assertEqual($AkTestUser->getDisplayField(), 'name');

        $AkTestField->setDisplayField('invalid_field');
        $this->assertEqual($AkTestUser->getDisplayField(), 'name');
    }

    function Test_of_get_and_get_Id()
    {
        $AkTestField = new AkTestField();
        $this->assertEqual($AkTestField->getId(), null);

        $AkTestField->setId(123);
        $this->assertEqual($AkTestField->getId(), 123);

        $AkTestField->incrementAttribute($AkTestField->getPrimaryKey());
        $this->assertEqual($AkTestField->getId(), 124);
    }
    /**/


}

ak_test('test_AkActiveRecord',true);

?>
