<?php

defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);
require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

require_once(AK_LIB_DIR.DS.'AkActiveRecord.php');
require_once(AK_LIB_DIR.DS.'AkActiveRecord'.DS.'AkObserver.php');


class test_AkActiveRecord_validators extends  AkUnitTest
{
    var $_testing_models_to_delete = array();
    var $_testing_model_databases_to_delete = array();

    function test_AkActiveRecord_validators()
    {
        parent::UnitTestCase();
        $this->_createNewTestingModelDatabase('AkTestPerson');
        $this->_createNewTestingModel('AkTestPerson');

        $this->installAndIncludeModels(array('Picture', 'Landlord'));
    }

    function setUp()
    {
    }

    function tearDown()
    {
        unset($_SESSION['__activeRecordColumnsSettingsCache']);
    }


    function _createNewTestingModel($test_model_name)
    {

        static $shutdown_called;
        switch ($test_model_name) {

            case 'AkTestPerson':
            $model_source =
            '<?php
    class AkTestPerson extends AkActiveRecord 
    { 
        function validate()
        {
            $this->validatesPresenceOf("first_name");            
        }
        
        function validateOnCreate()
        {
            $this->validatesAcceptanceOf("tos");
        }
        
        function validateOnUpdate()
        {
            $this->validatesPresenceOf("email");
        }
    
    } 
?>';
            break;

            default:
            $model_source = '<?php class '.$test_model_name.' extends AkActiveRecord { } ?>';
            break;
        }

        $file_name = AkInflector::toModelFilename($test_model_name);

        if(!Ak::file_put_contents($file_name,$model_source)){
            die('Ooops!, in order to perform this test, you must set your app/model permissions so this can script can create and delete files into/from it');
        }
        if(!in_array($file_name, get_included_files()) && !class_exists($test_model_name)){
            include($file_name);
        }else {
            return false;
        }
        $this->_testing_models_to_delete[] = $file_name;
        if(!isset($shutdown_called)){
            $shutdown_called = true;
            register_shutdown_function(array(&$this,'_deleteTestingModels'));
        }
        return true;
    }

    function _deleteTestingModels()
    {
        foreach ($this->_testing_models_to_delete as $file){
            Ak::file_delete($file);
        }
    }




    function _createNewTestingModelDatabase($test_model_name)
    {
        static $shutdown_called;
        // Create a data dictionary object, using this connection
        $db =& AK::db();
        //$db->debug = true;
        $table_name = AkInflector::tableize($test_model_name);
        if(in_array($table_name, (array)$db->MetaTables())){
            return false;
        }
        switch ($table_name) {
            case 'ak_test_people':
            $table =
            array(
            'table_name' => 'ak_test_people',
            'fields' => 'id I AUTO KEY,
            user_name C(32), 
            first_name C(200), 
            last_name C(200), 
            phone_number I(18), 
            city C(40), 
            state C(40), 
            email C(150), 
            country C(2), 
            sex C(1), 
            birth T, 
            age I(3), 
            password C(32), 
            tos L(1), 
            score I(3), 
            comments X, 
            created_at T, 
            updated_at T, 
            expires T',
            'index_fileds' => 'id',
            'table_options' => array('mysql' => 'TYPE=InnoDB', 'REPLACE')
            );
            break;
            default:
            return false;
            break;
        }

        $dict = NewDataDictionary($db);
        $sqlarray = $dict->CreateTableSQL($table['table_name'], $table['fields'], $table['table_options']);
        $dict->ExecuteSQLArray($sqlarray);
        if(isset($table['index_fileds'])){
            $sqlarray = $dict->CreateIndexSQL('idx_'.$table['table_name'], $table['table_name'], $table['index_fileds']);
            $dict->ExecuteSQLArray($sqlarray);
        }

        $db->CreateSequence('seq_'.$table['table_name']);

        $this->_testing_model_databases_to_delete[] = $table_name;
        if(!isset($shutdown_called)){
            $shutdown_called = true;
            register_shutdown_function(array(&$this,'_deleteTestingModelDatabases'));
        }
        //$db->debug = false;
        return true;
    }

    function _deleteTestingModelDatabases()
    {
        $db =& AK::db();
        foreach ($this->_testing_model_databases_to_delete as $table_name){
            $db->Execute('DROP TABLE '.$table_name);
            $db->DropSequence('seq_'.$table_name);
        }
    }


    function Test_of_isBlank()
    {
        $Person = new AkTestPerson();
        $this->assertTrue($Person->isBlank());
        $this->assertTrue($Person->isBlank(''));
        $this->assertTrue($Person->isBlank(' '));
        $this->assertTrue($Person->isBlank("\n\r"));
        $this->assertTrue($Person->isBlank("\t"));
        $this->assertFalse($Person->isBlank(0));
    }

    function Test_of_addError()
    {
        $Person = new AkTestPerson();
        $Person->addError('user_name');
        $this->assertTrue(count($Person->_errors['user_name']) == 1);
        $Person->addError('user_name','has an error');
        $this->assertTrue(count($Person->_errors['user_name']) == 2);
        $Person->addError('password');
        $this->assertTrue(count($Person->_errors['password']) == 1);
        $this->assertTrue(count($Person->_errors) == 2);
        $this->assertTrue(count($Person->_errors['user_name']) == 2);
    }

    function Test_of_clearErrors()
    {
        $Person = new AkTestPerson();
        $Person->addError('user_name');
        $Person->addError('user_name','has an error');
        $Person->addError('password');
        $Person->clearErrors();
        $this->assertTrue(count($Person->_errors) == 0);
    }

    function Test_of_hasErrors()
    {
        $Person = new AkTestPerson();
        $this->assertFalse($Person->hasErrors());
        $Person->addError('user_name');
        $this->assertTrue($Person->hasErrors());
    }

    function Test_of_getErrorsOn()
    {
        $Person = new AkTestPerson();
        $this->assertFalse($Person->getErrorsOn('user_name'));
        $Person->addError('user_name');
        $this->assertEqual($Person->getErrorsOn('user_name'),$Person->_defaultErrorMessages['invalid']);
        $Person->addError('user_name','not nice');
        $this->assertEqual($Person->getErrorsOn('user_name'),array($Person->_defaultErrorMessages['invalid'],'not nice'));
    }

    function Test_of_countErrors()
    {
        $Person = new AkTestPerson();
        $this->assertEqual($Person->countErrors(), 0);
        $Person->addError('user_name');
        $this->assertEqual($Person->countErrors(), 1);
        $Person->addError('user_name','has an error');
        $this->assertEqual($Person->countErrors(), 2);
        $Person->addError('password');
        $this->assertEqual($Person->countErrors(), 3);
    }

    function Test_of_isInvalid()
    {
        $Person = new AkTestPerson();
        $this->assertFalse($Person->isInvalid('user_name'));
        $Person->addError('user_name');
        $this->assertTrue($Person->isInvalid('user_name'));
    }

    function Test_of_getErrors()
    {
        $Person = new AkTestPerson();
        $this->assertTrue(is_array($Person->getErrors()));
        $this->assertEqual(count($Person->getErrors()), 0);

        $Person->addError('user_name');
        $expected = array('user_name'=>array($Person->_defaultErrorMessages['invalid']));
        $this->assertEqual($Person->getErrors(), $expected);

        $Person->addError('password','is not a valid password');
        $expected = array_merge($expected,array('password'=>array('is not a valid password')));
        $this->assertEqual($Person->getErrors(), $expected);

        $expected = array_merge($expected, array('password'=>array_merge($expected['password'],array('too short'))));
        $Person->addError('password','too short');
        $this->assertEqual($Person->getErrors(), $expected);
    }

    function Test_of_getFullErrorMessages()
    {
        $Person = new AkTestPerson();
        $this->assertTrue(is_array($Person->getFullErrorMessages()));
        $this->assertEqual(count($Person->getFullErrorMessages()), 0);

        $Person->addError('user_name');
        $expected = array('user_name'=>array('User name '.$Person->_defaultErrorMessages['invalid']));
        $this->assertEqual($Person->getFullErrorMessages(), $expected);

        $Person->addError('password','is not a valid password');
        $expected = array_merge($expected,array('password'=>array('Password is not a valid password')));
        $this->assertEqual($Person->getFullErrorMessages(), $expected);

        $expected = array_merge($expected, array('password'=>array_merge($expected['password'],array('Password too short'))));
        $Person->addError('password','too short');
        $this->assertEqual($Person->getFullErrorMessages(), $expected);
    }


    function Test_of_addErrorOnEmpty()
    {
        $Person = new AkTestPerson();
        $Person->addErrorOnEmpty('user_name');
        $expected = array('user_name'=>array($Person->_defaultErrorMessages['empty']));
        $this->assertEqual($Person->getErrors(), $expected);

        $Person->set('first_name','Bermi');
        $Person->addErrorOnEmpty('first_name');
        $this->assertEqual($Person->getErrors(), $expected);
    }

    function Test_of_addErrorOnBlank()
    {
        $Person = new AkTestPerson();
        $Person->addErrorOnBlank('user_name');
        $expected = array('user_name'=>array($Person->_defaultErrorMessages['blank']));
        $this->assertEqual($Person->getErrors(), $expected);

        $Person->set('first_name','Bermi');
        $Person->addErrorOnBlank('first_name');
        $this->assertEqual($Person->getErrors(), $expected);
    }

    function Test_of_addErrorOnBoundaryBreaking()
    {
        $Person = new AkTestPerson();
        $Person->set('age',17);
        $Person->addErrorOnBoundaryBreaking('age',18,65,'too old','too young');
        $expected = array('age'=>array('too young'));
        $this->assertEqual($Person->getErrors(), $expected);

        $Person = new AkTestPerson();
        $Person->set('age',66);
        $Person->addErrorOnBoundaryBreaking('age',18,65,'too old','too young');
        $expected = array('age'=>array('too old'));
        $this->assertEqual($Person->getErrors(), $expected);

        $Person = new AkTestPerson();
        $Person->set('age',65);
        $Person->addErrorOnBoundaryBreaking('age',18,65,'too old','too young');
        $Person->set('age',18);
        $Person->addErrorOnBoundaryBreaking('age',18,65,'too old','too young');

        $this->assertEqual($Person->getErrors(), array());
    }


    function Test_of_addErrorOnBoundryBreaking()
    {
        //this is an alias of addErrorOnBoundaryBreaking
    }

    function Test_of_addErrorToBase()
    {
        $Person = new AkTestPerson();
        $Person->addErrorToBase('Nothing has changed');
        $expected = array('AkTestPerson'=>array('Nothing has changed'));
        $this->assertEqual($Person->getErrors(), $expected);
        $expected = array('AkTestPerson'=>array('Nothing has changed','Nothing has changed at all'));
        $Person->addErrorToBase('Nothing has changed at all');
        $this->assertEqual($Person->getErrors(), $expected);
    }

    function Test_of_getBaseErrors()
    {
        $Person = new AkTestPerson();
        $this->assertEqual($Person->getBaseErrors(), array());
        $Person->addErrorToBase('Nothing has changed');
        $expected = array('Nothing has changed');
        $this->assertEqual($Person->getBaseErrors(), $expected);
        $expected = array('Nothing has changed','Nothing has changed at all');
        $Person->addErrorToBase('Nothing has changed at all');
        $this->assertEqual($Person->getBaseErrors(), $expected);
    }



    function Test_of_errorsToString()
    {
        $Person = new AkTestPerson();
        $Person->addErrorOnBlank('user_name');
        $Person->addErrorOnBlank('first_name');
        $this->assertTrue(strstr($Person->errorsToString(), "User name can't be blank"));
        $this->assertTrue(strstr($Person->errorsToString(), "First name can't be blank"));
    }


    function Test_of_validatesConfirmationOf()
    {
        $Person = new AkTestPerson();
        $Person->validatesConfirmationOf('user_name');
        $this->assertFalse($Person->hasErrors());

        $Person->set('user_name', 'bermi');

        $this->assertEqual($Person->getErrorsOn('user_name'),'');

        $Person->user_name_confirmation = '';
        $Person->validatesConfirmationOf('user_name');
        $this->assertEqual($Person->getErrorsOn('user_name'),$Person->_defaultErrorMessages['confirmation']);

        $Person = new AkTestPerson();
        $Person->set('user_name', 'Bermi');
        $Person->user_name_confirmation = 'bermi';
        $Person->validatesConfirmationOf('user_name');
        $this->assertEqual($Person->getErrorsOn('user_name'),$Person->_defaultErrorMessages['confirmation']);

        $Person = new AkTestPerson();
        $Person->setAttributes(array('password'=>'abc','password_confirmation'=>'ake'));
        $Person->validatesConfirmationOf('password');
        $this->assertEqual($Person->getErrorsOn('password'), $Person->_defaultErrorMessages['confirmation']);
    }


    function Test_of_validatesAcceptanceOf()
    {
        $Person = new AkTestPerson();
        $Person->validatesAcceptanceOf('tos');
        $this->assertEqual($Person->getErrorsOn('tos'),$Person->_defaultErrorMessages['accepted']);

        $Person = new AkTestPerson();
        $Person->validatesAcceptanceOf('tos','You need to type down "I accept this terms and conditions"',"I accept this terms and conditions");
        $this->assertEqual($Person->getErrorsOn('tos'),'You need to type down "I accept this terms and conditions"');
        $Person->clearErrors();
        $Person->set('tos',"I accept this terms and conditions");
        $this->assertFalse($Person->hasErrors());
    }


    function Test_of_validatesPresenceOf()
    {
        $Person = new AkTestPerson();
        $Person->validatesPresenceOf('user_name');
        $this->assertEqual($Person->getErrorsOn('user_name'),$Person->_defaultErrorMessages['blank']);

        $Person = new AkTestPerson();
        $Person->validatesPresenceOf('user_name','is a compulsory field');
        $this->assertEqual($Person->getErrorsOn('user_name'),'is a compulsory field');
        $Person->clearErrors();
        $Person->set('user_name','bermi');
        $this->assertFalse($Person->hasErrors());
    }



    function Test_of_validatesLengthOf()
    {
        $Person = new AkTestPerson();

        $Person->city = 'Vilanova i la Geltrí';
        $Person->validatesLengthOf("city", array("maximum"=>5,'message'=>"less than %d if you don't mind"));
        $this->assertEqual($Person->getErrorsOn('city'),"less than 5 if you don't mind");

        $Person->clearErrors();
        $Person->city = 'Carlet';
        $Person->validatesLengthOf("city", array("maximum"=>10));
        $this->assertFalse($Person->getErrorsOn('city'));

        $Person->clearErrors();
        $Person->city = '';
        $Person->validatesLengthOf("city", array("maximum"=>10, 'allow_null'=>true, 'message'=> "less than %d if you don't mind"));
        $this->assertFalse($Person->getErrorsOn('city'));

        $Person->clearErrors();
        $Person->score = 101;
        $Person->validatesLengthOf("score", array("within"=>array(1, 100)));
        $this->assertEqual($Person->getErrorsOn('score'),sprintf($Person->_defaultErrorMessages['too_long'],100));

        $Person->clearErrors();
        $Person->score = -5;
        $Person->validatesLengthOf("score", array("within"=>array(1, 100)));
        $this->assertEqual($Person->getErrorsOn('score'),sprintf($Person->_defaultErrorMessages['too_short'],1));

        $Person->clearErrors();
        $Person->score = 25;
        $Person->validatesLengthOf("score", array("within"=>array(1, 100)));
        $this->assertFalse($Person->getErrorsOn('score'));

        $Person->clearErrors();
        $Person->state = 'CA';
        $Person->validatesLengthOf("state", array("in"=>array(5, 20), "too_long" => "pick a shorter name", "too_short" => "pick a longer name"));
        $this->assertEqual($Person->getErrorsOn('state'),"pick a longer name");

        $Person->clearErrors();
        $Person->state = 'Barcelona';
        $Person->validatesLengthOf("state", array("in"=>array(2, 5), "too_long" => "pick a shorter name", "too_short" => "pick a longer name"));
        $this->assertEqual($Person->getErrorsOn('state'),"pick a shorter name");

        $Person->clearErrors();
        $Person->state = 'Valencia';
        $Person->validatesLengthOf("state", array("in"=>array(5, 20), "too_long" => "pick a shorter name", "too_short" => "pick a longer name"));
        $this->assertFalse($Person->getErrorsOn('state'));


        $Person->clearErrors();
        $Person->subscriptions = array();
        $Person->validatesLengthOf("subscriptions", array("minimum"=>4, "too_short"=>"you need to select at least 4 subscriptions"));
        $this->assertEqual($Person->getErrorsOn('subscriptions'),"you need to select at least 4 subscriptions");

        $Person->clearErrors();
        $Person->subscriptions = array('php architect');
        $Person->validatesLengthOf("subscriptions", array("minimum"=>4, "too_short"=>"you need to select at least 4 subscriptions"));
        $this->assertEqual($Person->getErrorsOn('subscriptions'),"you need to select at least 4 subscriptions");

        $Person->clearErrors();
        $Person->subscriptions = array('php architect','computer world', 'wired','slashdot');
        $Person->validatesLengthOf("subscriptions", array("minimum"=>4, "too_short"=>"you need to select at least 4 subscriptions"));
        $this->assertFalse($Person->getErrorsOn('subscriptions'));

        $Person->clearErrors();
        $Person->validatesLengthOf("country", array("is"=>2, "message"=>"must be %d characters long as specified on ISO 3166"));
        $this->assertEqual($Person->getErrorsOn('country'),"must be 2 characters long as specified on ISO 3166");

        $Person->clearErrors();
        $Person->country = '';
        $Person->validatesLengthOf("country", array("is"=>2, "message"=>"must be %d characters long as specified on ISO 3166"));
        $this->assertEqual($Person->getErrorsOn('country'),"must be 2 characters long as specified on ISO 3166");

        $Person->clearErrors();
        $Person->country = 2;
        $Person->validatesLengthOf("country", array("is"=>2, "message"=>"must be %d characters long as specified on ISO 3166"));
        $this->assertFalse($Person->getErrorsOn('country'));

        $Person->clearErrors();
        $Person->country = 'ES';
        $Person->validatesLengthOf("country", array("is"=>2, "message"=>"must be %d characters long as specified on ISO 3166"));
        $this->assertFalse($Person->getErrorsOn('country'));

    }

    function Test_of_validatesSizeOf()
    {
        //alias for validatesLengthOf
    }

    function Test_of_validatesUniquenessOf()
    {
        $Person = new AkTestPerson('user_name->','bermi','first_name->','Bermi','last_name->','Ferrer','country->','ES','tos->',1);
        $this->assertTrue($Person->save());

        $Person = new AkTestPerson('user_name->','bermi','first_name->','Bermi','last_name->','Ferrer');
        $Person->validatesUniquenessOf("user_name");
        $this->assertTrue($Person->hasErrors());

        $Person = $Person->findFirst(array('username = ?','bermi'));
        $Person->validatesUniquenessOf("user_name");
        $this->assertFalse($Person->hasErrors());


        $Person = $Person->findFirst(array('username = ?','bermi'));
        $Person->validatesUniquenessOf("user_name",array('scope'=>'country'));
        $this->assertFalse($Person->hasErrors());

        $Person = new AkTestPerson('user_name->','bermi','first_name->','Bermi','last_name->','Ferrer','country->','US');
        $Person->validatesUniquenessOf("user_name",array('scope'=>'country'));
        $this->assertFalse($Person->hasErrors());

        $Person = new AkTestPerson('user_name->','bermi','first_name->','Bermi','last_name->','Ferrer','country->','ES');
        $Person->validatesUniquenessOf("user_name",array('scope'=>'country'));
        $this->assertTrue($Person->hasErrors());

    }

    function Test_of_validatesUniquenessOfUsingMultipleScopes()
    {
        $Person = new AkTestPerson('user_name->','admin','first_name->','Sam','last_name->','','country->','ES','tos->',1);
        $this->assertTrue($Person->save());

        $Person = new AkTestPerson('user_name->','admin','first_name->','Sam','last_name->','','country->','FR','tos->',1);
        $Person->validatesUniquenessOf("user_name",array('scope'=>'first_name'));
        $this->assertTrue($Person->hasErrors());

        $Person = new AkTestPerson('user_name->','admin','first_name->','Sam','last_name->','','country->','FR','tos->',1);
        $Person->validatesUniquenessOf("user_name",array('scope'=>array('first_name','country')));
        $this->assertFalse($Person->hasErrors());

    }

    function Test_of_validatesUniquenessOfConditionally()
    {
        $Person = new AkTestPerson('user_name->','james','first_name->','James','last_name->','','country->','ES','tos->',1);
        $this->assertTrue($Person->save());

        $Person = new AkTestPerson('user_name->','james','first_name->','James','last_name->','','country->','ES','tos->',1);
        $Person->validatesUniquenessOf("user_name");
        $this->assertTrue($Person->hasErrors());

        $Person = new AkTestPerson('user_name->','james','first_name->','James','last_name->','','country->','ES','tos->',1);
        $Person->force_validation = false;
        $Person->validatesUniquenessOf("user_name", array('if'=>'$this->force_validation'));
        $this->assertFalse($Person->hasErrors());

    }



    function Test_of_validatesFormatOf()
    {
        $Person = new AkTestPerson();
        $Person->validatesFormatOf('email', AK_EMAIL_REGULAR_EXPRESSION);
        $this->assertEqual($Person->getErrorsOn('email'),$Person->_defaultErrorMessages['invalid']);

        $Person->clearErrors();
        $Person->email = 'bermi [at] example.com';
        $Person->validatesFormatOf('email', AK_EMAIL_REGULAR_EXPRESSION);
        $this->assertEqual($Person->getErrorsOn('email'),$Person->_defaultErrorMessages['invalid']);

        $Person->clearErrors();
        $Person->email = 'bermi@example.com';
        $Person->validatesFormatOf('email', AK_EMAIL_REGULAR_EXPRESSION);
        $Person->email = 'bermi@example.co.uk';
        $Person->validatesFormatOf('email', AK_EMAIL_REGULAR_EXPRESSION);
        $Person->email = 'bermi_ferrer@exam-ple.co.uk';
        $Person->validatesFormatOf('email', AK_EMAIL_REGULAR_EXPRESSION);
        $this->assertFalse($Person->hasErrors());


        $Person->clearErrors();
        $Person->email = 'bermi [at] example.com';
        $Person->validatesFormatOf('email', AK_EMAIL_REGULAR_EXPRESSION);
        $this->assertEqual($Person->getErrorsOn('email'),$Person->_defaultErrorMessages['invalid']);

        $Person->clearErrors();
        $Person->email = 'bermi@example.com';
        $Person->validatesFormatOf('email', AK_EMAIL_REGULAR_EXPRESSION);
        $Person->email = 'bermi@example.co.uk';
        $Person->validatesFormatOf('email', AK_EMAIL_REGULAR_EXPRESSION);
        $Person->email = 'bermi_ferrer@exam-ple.co.uk';
        $Person->validatesFormatOf('email', AK_EMAIL_REGULAR_EXPRESSION);
        $this->assertFalse($Person->hasErrors());


        $Person->clearErrors();
        $Person->first_name = '';
        $Person->validatesFormatOf('first_name', AK_NOT_EMPTY_REGULAR_EXPRESSION,"can not be empty");
        $this->assertEqual($Person->getErrorsOn('first_name'),"can not be empty");

        $Person->clearErrors();
        $Person->first_name = 'Bermi';
        $Person->validatesFormatOf('first_name', AK_NOT_EMPTY_REGULAR_EXPRESSION,"can not be empty");
        $this->assertFalse($Person->hasErrors());


        $Person->clearErrors();
        $Person->number = 12.56;
        $Person->validatesFormatOf('number', AK_NUMBER_REGULAR_EXPRESSION);
        $this->assertEqual($Person->getErrorsOn('number'),$Person->_defaultErrorMessages['invalid']);

        $Person->clearErrors();
        $Person->number = 1256;
        $Person->validatesFormatOf('number', AK_NUMBER_REGULAR_EXPRESSION);
        $this->assertFalse($Person->hasErrors());

        $Person->clearErrors();
        $Person->phone = 'blah';
        $Person->validatesFormatOf('phone', AK_PHONE_REGULAR_EXPRESSION);
        $this->assertEqual($Person->getErrorsOn('phone'),$Person->_defaultErrorMessages['invalid']);

        $Person->clearErrors();
        $Person->phone = '+34 96 299 3000';
        $Person->validatesFormatOf('phone', AK_PHONE_REGULAR_EXPRESSION);
        $this->assertFalse($Person->hasErrors());


        $Person->clearErrors();
        $Person->date = 'Monday';
        $Person->validatesFormatOf('date', AK_DATE_REGULAR_EXPRESSION);
        $this->assertEqual($Person->getErrorsOn('date'),$Person->_defaultErrorMessages['invalid']);

        $Person->clearErrors();
        $Person->date = '1978/06/16';
        $Person->validatesFormatOf('date', AK_DATE_REGULAR_EXPRESSION);
        $this->assertFalse($Person->hasErrors());


        $Person->clearErrors();
        $Person->ip = '257.0.0.1';
        $Person->validatesFormatOf('ip', AK_IP4_REGULAR_EXPRESSION);
        $this->assertEqual($Person->getErrorsOn('ip'),$Person->_defaultErrorMessages['invalid']);

        $Person->clearErrors();
        $Person->ip = '255.0.0.1';
        $Person->validatesFormatOf('ip', AK_IP4_REGULAR_EXPRESSION);
        $this->assertFalse($Person->hasErrors());


        $Person->clearErrors();
        $Person->post_code = 'a';
        $Person->validatesFormatOf('post_code', AK_POST_CODE_REGULAR_EXPRESSION);
        $this->assertEqual($Person->getErrorsOn('post_code'),$Person->_defaultErrorMessages['invalid']);

        $Person->clearErrors();
        $Person->post_code = 'san francisco';
        $Person->validatesFormatOf('post_code', AK_POST_CODE_REGULAR_EXPRESSION);
        $this->assertEqual($Person->getErrorsOn('post_code'),$Person->_defaultErrorMessages['invalid']);

        $Person->clearErrors();
        $Person->post_code = 'NSW 8376';
        $Person->validatesFormatOf('post_code', AK_POST_CODE_REGULAR_EXPRESSION);
        $this->assertFalse($Person->hasErrors());

        $Person->clearErrors();
        $Person->post_code = 'NSW 1008';
        $Person->validatesFormatOf('post_code', AK_POST_CODE_REGULAR_EXPRESSION);
        $Person->post_code = 46240;
        $Person->validatesFormatOf('post_code', AK_POST_CODE_REGULAR_EXPRESSION);
        $this->assertFalse($Person->hasErrors());

    }

    function Test_of_validatesInclusionOf()
    {
        $Person = new AkTestPerson();
        $Person->validatesInclusionOf('gender', array('male', 'female'), "woah! what are you then!??!!");
        $this->assertEqual($Person->getErrorsOn('gender'),"woah! what are you then!??!!");

        $Person->clearErrors();
        $Person->gender = 'm';
        $Person->validatesInclusionOf('gender', array('male', 'female'), "woah! what are you then!??!!");
        $this->assertEqual($Person->getErrorsOn('gender'),"woah! what are you then!??!!");

        $Person->clearErrors();
        $Person->gender = 'male';
        $Person->validatesInclusionOf('gender', array('male', 'female'));
        $this->assertFalse($Person->hasErrors());

        $Person->clearErrors();
        unset($Person->gender);
        $Person->validatesInclusionOf('gender', array('male', 'female'),'I need to know your gender',true);
        $this->assertFalse($Person->hasErrors());

        $Person->clearErrors();
        $Person->age = 17;
        $Person->validatesInclusionOf('age', range(18, 120));
        $this->assertEqual($Person->getErrorsOn('age'),$Person->_defaultErrorMessages['inclusion']);

        $Person->clearErrors();
        $Person->age = 121;
        $Person->validatesInclusionOf('age', range(18, 120));
        $this->assertEqual($Person->getErrorsOn('age'),$Person->_defaultErrorMessages['inclusion']);

        $Person->clearErrors();
        $Person->age = 18;
        $Person->validatesInclusionOf('age', range(18, 120));
        $this->assertFalse($Person->hasErrors());
    }


    function Test_of_validatesExclusionOf()
    {
        $Person = new AkTestPerson();
        $Person->validatesExclusionOf('gender', array('too much'), "don't lie");
        $this->assertEqual($Person->getErrorsOn('gender'),"don't lie");

        $Person->clearErrors();
        $Person->gender = 'too much';
        $Person->validatesExclusionOf('gender', array('too much'), "don't lie");
        $this->assertEqual($Person->getErrorsOn('gender'),"don't lie");

        $Person->clearErrors();
        $Person->gender = 'male';
        $Person->validatesExclusionOf('gender', array('too much'), "don't lie");
        $this->assertFalse($Person->hasErrors());

        $Person->clearErrors();
        unset($Person->gender);
        $Person->validatesExclusionOf('gender', array('too much'), "don't lie", true);
        $this->assertFalse($Person->hasErrors());

        $Person->clearErrors();
        $Person->age = 17;
        $Person->validatesExclusionOf('age', range(18, 120));
        $this->assertFalse($Person->hasErrors());

        $Person->clearErrors();
        $Person->age = 121;
        $Person->validatesExclusionOf('age', range(18, 120));
        $this->assertFalse($Person->hasErrors());

        $Person->clearErrors();
        $Person->age = 18;
        $Person->validatesExclusionOf('age', range(18, 120));
        $this->assertEqual($Person->getErrorsOn('age'),$Person->_defaultErrorMessages['exclusion']);
    }

    function Test_of_validatesNumericalityOf()
    {
        $Person = new AkTestPerson();

        $Person->validatesNumericalityOf('age');
        $this->assertEqual($Person->getErrorsOn('age'),$Person->_defaultErrorMessages['not_a_number']);

        $Person->clearErrors();
        $Person->age = 'text';
        $Person->validatesNumericalityOf('age');
        $this->assertEqual($Person->getErrorsOn('age'),$Person->_defaultErrorMessages['not_a_number']);

        $Person->clearErrors();
        $Person->age = 15.98;
        $Person->validatesNumericalityOf('age');
        $this->assertFalse($Person->hasErrors());

        $Person->clearErrors();
        $Person->age = 15.98;
        $Person->validatesNumericalityOf('age','not_valid', true);
        $this->assertEqual($Person->getErrorsOn('age'),'not_valid');

        $Person->clearErrors();
        $Person->age = 18;
        $Person->validatesNumericalityOf('age');
        $this->assertFalse($Person->hasErrors());

        $Person->clearErrors();
        unset($Person->age);
        $Person->validatesNumericalityOf('age', 'not_valid',false, true);
        $this->assertFalse($Person->hasErrors());

    }

    function Test_of_validateOnCreate()
    {
        $Person = new AkTestPerson('user_name->','hilario','first_name->','Hilario','last_name->','Hervás','country->','ES','tos->',1);
        $Person->validateOnCreate();
        $this->assertFalse($Person->hasErrors());

        $Person = new AkTestPerson('user_name->','hilario','first_name->','Hilario','last_name->','Hervás','country->','ES');
        $Person->validateOnCreate();
        $this->assertEqual($Person->getErrorsOn('tos'),$Person->_defaultErrorMessages['accepted']);
        $this->assertFalse($Person->save());
    }

    function Test_of_validateOnUpdate()
    {
        $Person = new AkTestPerson('email->','email@example.com');
        $Person->validateOnUpdate();
        $this->assertFalse($Person->hasErrors());

        $Person = new AkTestPerson('user_name->','hilario','first_name->','Hilario','last_name->','Hervás','country->','ES');
        $Person->validateOnUpdate();
        $this->assertEqual($Person->getErrorsOn('email'),$Person->_defaultErrorMessages['blank']);
    }


    function Test_of_validate()
    {
        $Person = new AkTestPerson('first_name->','Alicia');
        $Person->validate();
        $this->assertFalse($Person->hasErrors());

        $Person = new AkTestPerson('last_name->','Sadurní','country->','ES');
        $Person->validate();
        $this->assertEqual($Person->getErrorsOn('first_name'),$Person->_defaultErrorMessages['blank']);
    }

    function Test_of_isValid()
    {
        $Person = new AkTestPerson('country->','ES');
        $this->assertFalse($Person->isValid());
        $this->assertEqual($Person->getErrors(), array('first_name' => array("can't be blank"),'tos' =>array("must be accepted")));

        $Person->clearErrors();
        $Person = $Person->findFirst(array('username = ?','bermi'));
        $Person->set('tos',0);
        $this->assertFalse($Person->isValid());
        $this->assertEqual($Person->getErrors(), array('email' => array("can't be blank")));
    }


    function Test_of_validatesAssociated()
    {
        $Picture =& new Picture('title->','Carlet');
        
        $Landlord =& new Landlord();
        $Landlord->test_validators = array('validatesPresenceOf'=>array('name'));
        $Picture->landlord->assign($Landlord);

        $Picture->validatesAssociated('landlord');
        
        $this->assertEqual($Picture->getErrorsOn('landlord'),$Picture->_defaultErrorMessages['invalid']);
    }



}

ak_test('test_AkActiveRecord_validators',true);


?>
