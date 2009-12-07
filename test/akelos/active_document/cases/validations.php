<?php

require_once(dirname(__FILE__).'/../config.php');

class ValidDocument extends AkActiveDocument
{
    public $validate_code = '';
    public $validate_before_create_code = '';
    public $validate_before_update_code = '';

    public function validate(){
        $result = true;
        eval($this->validate_code);
        return $result;
    }
    public function validateOnCreate(){
        $result = true;
        eval($this->validate_before_create_code);
        return $result;
    }
    public function validateOnUpdate(){
        $result = true;
        eval($this->validate_before_update_code);
        return $result;
    }
}


class DocumentValidations_TestCase extends ActiveDocumentUnitTest
{
    public function setup() {
        $this->db = new AkOdbAdapter();
        $this->db->connect(array('type' => 'mongo_db', 'database' => 'akelos_testing'));
        $this->ValidDocument = new ValidDocument();
        $this->ValidDocument->setAdapter($this->db);
    }

    public function tearDown(){
        $this->db->dropDatabase();
        $this->db->disconnect();
    }
    
    public function test_should_not_validate_if_an_error_is_added(){
        $this->ValidDocument->validate_code = '$this->addError("title", "is invalid");';
        $this->assertFalse($this->ValidDocument->save());
        $this->assertEqual($this->ValidDocument->getErrorsOn('title'), 'is invalid');
    }

    public function test_should_not_validate_if_validation_returns_false(){
        $this->ValidDocument->validate_code = '$result = false;';
        $this->assertFalse($this->ValidDocument->save());
        $this->assertFalse($this->ValidDocument->hasErrors());
    }

    public function test_should_validate_before_creation(){
        $this->ValidDocument->validate_before_create_code = '$this->addError("body", "can\'t create");';
        $this->assertFalse($this->ValidDocument->save());
        $this->assertEqual($this->ValidDocument->getErrorsOn('body'), 'can\'t create');
    }

    public function test_should_not_validate_before_creation_if_validation_returns_false(){
        $this->ValidDocument->validate_before_create_code = '$result = false;';
        $this->assertFalse($this->ValidDocument->save());
        $this->assertFalse($this->ValidDocument->hasErrors());
    }

    public function test_should_validate_before_updating(){
        $this->ValidDocument->body = 'Big';
        $this->assertTrue($this->ValidDocument->save());
        $this->ValidDocument->validate_before_update_code = '$this->addError("url", "can\'t update");';
        $this->assertFalse($this->ValidDocument->save());
        $this->assertEqual($this->ValidDocument->getErrorsOn('url'), 'can\'t update');
    }

    public function test_should_not_validate_before_updating_if_validation_returns_false(){
        $this->ValidDocument->body = 'Big';
        $this->assertTrue($this->ValidDocument->save());
        $this->ValidDocument->validate_before_update_code = '$result = false;';
        $this->assertFalse($this->ValidDocument->save());
        $this->assertFalse($this->ValidDocument->hasErrors());
    }

    public function test_validates_presence_of(){
        $D= $this->ValidDocument;

        $D->validatesPresenceOf('user_name');
        $this->assertEqual($D->getErrorsOn('user_name'), $D->getDefaultErrorMessageFor('blank'));
        $D->clearErrors();
        $D->validatesPresenceOf('user_name','is a compulsory field');
        $this->assertEqual($D->getErrorsOn('user_name'),'is a compulsory field');
        $D->clearErrors();
        $D->set('user_name','bermi');
        $this->assertFalse($D->hasErrors());
    }

    public function test_should_validate_length(){
        $D= $this->ValidDocument;

        $D->city = 'Vilanova i la Geltrí';
        $D->validatesLengthOf("city", array("maximum"=>5,'message'=>"less than %d if you don't mind"));
        $this->assertEqual($D->getErrorsOn('city'),"less than 5 if you don't mind");

        $D->clearErrors();
        $D->city = 'Carlet';
        $D->validatesLengthOf("city", array("maximum"=>10));
        $this->assertFalse($D->getErrorsOn('city'));

        $D->clearErrors();
        $D->city = '';
        $D->validatesLengthOf("city", array("maximum"=>10, 'allow_null'=>true, 'message'=> "less than %d if you don't mind"));
        $this->assertFalse($D->getErrorsOn('city'));

        $D->clearErrors();
        $D->score = 101;
        $D->validatesLengthOf("score", array("within"=>array(1, 100)));
        $this->assertEqual($D->getErrorsOn('score'),sprintf($D->getDefaultErrorMessageFor('too_long'),100));

        $D->clearErrors();
        $D->score = -5;
        $D->validatesLengthOf("score", array("within"=>array(1, 100)));
        $this->assertEqual($D->getErrorsOn('score'),sprintf($D->getDefaultErrorMessageFor('too_short'),1));

        $D->clearErrors();
        $D->score = 25;
        $D->validatesLengthOf("score", array("within"=>array(1, 100)));
        $this->assertFalse($D->getErrorsOn('score'));

        $D->clearErrors();
        $D->state = 'CA';
        $D->validatesLengthOf("state", array("in"=>array(5, 20), "too_long" => "pick a shorter name", "too_short" => "pick a longer name"));
        $this->assertEqual($D->getErrorsOn('state'),"pick a longer name");

        $D->clearErrors();
        $D->state = 'Barcelona';
        $D->validatesLengthOf("state", array("in"=>array(2, 5), "too_long" => "pick a shorter name", "too_short" => "pick a longer name"));
        $this->assertEqual($D->getErrorsOn('state'),"pick a shorter name");

        $D->clearErrors();
        $D->state = 'Valencia';
        $D->validatesLengthOf("state", array("in"=>array(5, 20), "too_long" => "pick a shorter name", "too_short" => "pick a longer name"));
        $this->assertFalse($D->getErrorsOn('state'));


        $D->clearErrors();
        $D->subscriptions = array();
        $D->validatesLengthOf("subscriptions", array("minimum"=>4, "too_short"=>"you need to select at least 4 subscriptions"));
        $this->assertEqual($D->getErrorsOn('subscriptions'),"you need to select at least 4 subscriptions");

        $D->clearErrors();
        $D->subscriptions = array('php architect');
        $D->validatesLengthOf("subscriptions", array("minimum"=>4, "too_short"=>"you need to select at least 4 subscriptions"));
        $this->assertEqual($D->getErrorsOn('subscriptions'),"you need to select at least 4 subscriptions");

        $D->clearErrors();
        $D->subscriptions = array('php architect','computer world', 'wired','slashdot');
        $D->validatesLengthOf("subscriptions", array("minimum"=>4, "too_short"=>"you need to select at least 4 subscriptions"));
        $this->assertFalse($D->getErrorsOn('subscriptions'));

        $D->clearErrors();
        $D->validatesLengthOf("country", array("is"=>2, "message"=>"must be %d characters long as specified on ISO 3166"));
        $this->assertEqual($D->getErrorsOn('country'),"must be 2 characters long as specified on ISO 3166");

        $D->clearErrors();
        $D->country = '';
        $D->validatesLengthOf("country", array("is"=>2, "message"=>"must be %d characters long as specified on ISO 3166"));
        $this->assertEqual($D->getErrorsOn('country'),"must be 2 characters long as specified on ISO 3166");

        $D->clearErrors();
        $D->country = 2;
        $D->validatesLengthOf("country", array("is"=>2, "message"=>"must be %d characters long as specified on ISO 3166"));
        $this->assertFalse($D->getErrorsOn('country'));

        $D->clearErrors();
        $D->country = 'ES';
        $D->validatesLengthOf("country", array("is"=>2, "message"=>"must be %d characters long as specified on ISO 3166"));
        $this->assertFalse($D->getErrorsOn('country'));
    }


    public function test_should_validate_inclussion_of() {
        $D= clone($this->ValidDocument);
        $D->validatesInclusionOf('gender', array('male', 'female'), "woah! what are you then!??!!");
        $this->assertEqual($D->getErrorsOn('gender'),"woah! what are you then!??!!");

        $D->clearErrors();
        $D->gender = 'm';
        $D->validatesInclusionOf('gender', array('male', 'female'), "woah! what are you then!??!!");
        $this->assertEqual($D->getErrorsOn('gender'),"woah! what are you then!??!!");

        $D->clearErrors();
        $D->gender = 'male';
        $D->validatesInclusionOf('gender', array('male', 'female'));
        $this->assertFalse($D->hasErrors());

        $D->clearErrors();
        unset($D->gender);
        $D->validatesInclusionOf('gender', array('male', 'female'),'I need to know your gender', true);
        $this->assertFalse($D->hasErrors());

        $D= clone($this->ValidDocument);
        $D->gender = '0';
        $D->validatesInclusionOf('gender', array('male', 'female'),'I need to know your gender', true);
        $this->assertTrue($D->hasErrors());

        $D= clone($this->ValidDocument);
        $D->gender = 0;
        $D->validatesInclusionOf('gender', array('male', 'female'),'I need to know your gender', true);
        $this->assertTrue($D->hasErrors());

        $D= clone($this->ValidDocument);
        $D->gender = null;
        $D->validatesInclusionOf('gender', array('male', 'female'),'I need to know your gender', true);
        $this->assertFalse($D->hasErrors());

        $D->clearErrors();
        $D->age = 17;
        $D->validatesInclusionOf('age', range(18, 120));
        $this->assertEqual($D->getErrorsOn('age'),$D->getDefaultErrorMessageFor('inclusion'));

        $D->clearErrors();
        $D->age = 121;
        $D->validatesInclusionOf('age', range(18, 120));
        $this->assertEqual($D->getErrorsOn('age'),$D->getDefaultErrorMessageFor('inclusion'));

        $D->clearErrors();
        $D->age = 18;
        $D->validatesInclusionOf('age', range(18, 120));
        $this->assertFalse($D->hasErrors());
    }



    public function test_should_validate_exclussion_of() {
        $D = clone($this->ValidDocument);
        $D->validatesExclusionOf('gender', array('too much'), "don't lie");
        $this->assertEqual($D->getErrorsOn('gender'),"don't lie");

        $D->clearErrors();
        $D->gender = 'too much';
        $D->validatesExclusionOf('gender', array('too much'), "don't lie");
        $this->assertEqual($D->getErrorsOn('gender'),"don't lie");

        $D->clearErrors();
        $D->gender = 'male';
        $D->validatesExclusionOf('gender', array('too much'), "don't lie");
        $this->assertFalse($D->hasErrors());

        $D->clearErrors();
        unset($D->gender);
        $D->validatesExclusionOf('gender', array('too much'), "don't lie", true);
        $this->assertFalse($D->hasErrors());

        $D->clearErrors();
        $D->age = 17;
        $D->validatesExclusionOf('age', range(18, 120));
        $this->assertFalse($D->hasErrors());

        $D->clearErrors();
        $D->age = 121;
        $D->validatesExclusionOf('age', range(18, 120));
        $this->assertFalse($D->hasErrors());

        $D->clearErrors();
        $D->age = 18;
        $D->validatesExclusionOf('age', range(18, 120));
        $this->assertEqual($D->getErrorsOn('age'),$D->getDefaultErrorMessageFor('exclusion'));
    }

    public function test_should_validate_numericality_of() {
        $D = clone($this->ValidDocument);

        $D->validatesNumericalityOf('age');
        $this->assertEqual($D->getErrorsOn('age'),$D->getDefaultErrorMessageFor('not_a_number'));

        $D->clearErrors();
        $D->age = 'text';
        $D->validatesNumericalityOf('age');
        $this->assertEqual($D->getErrorsOn('age'),$D->getDefaultErrorMessageFor('not_a_number'));

        $D->clearErrors();
        $D->age = 15.98;
        $D->validatesNumericalityOf('age');
        $this->assertFalse($D->hasErrors());

        $D->clearErrors();
        $D->age = 15.98;
        $D->validatesNumericalityOf('age','not_valid', true);
        $this->assertEqual($D->getErrorsOn('age'),'not_valid');

        $D->clearErrors();
        $D->age = 18;
        $D->validatesNumericalityOf('age');
        $this->assertFalse($D->hasErrors());

        $D->clearErrors();
        $D->age = '18';
        $D->validatesNumericalityOf('age','not_valid',true);
        $this->assertFalse($D->hasErrors());

        $D->clearErrors();
        unset($D->age);
        $D->validatesNumericalityOf('age', 'not_valid',false, true);
        $this->assertFalse($D->hasErrors());

        $D->clearErrors();
        $D->age = null;
        $D->validatesNumericalityOf('age', 'not_valid',false, false);
        $this->assertTrue($D->hasErrors());
    }


    public function test_should_validate_format() {
        $D = $this->ValidDocument;;
        $D->validatesFormatOf('email', AK_EMAIL_REGULAR_EXPRESSION);
        $this->assertEqual($D->getErrorsOn('email'),$D->getDefaultErrorMessageFor('invalid'));

        $D->clearErrors();
        $D->email = 'bermi [at] example.com';
        $D->validatesFormatOf('email', AK_EMAIL_REGULAR_EXPRESSION);
        $this->assertEqual($D->getErrorsOn('email'),$D->getDefaultErrorMessageFor('invalid'));

        $D->clearErrors();
        $D->email = 'bermi@example.com';
        $D->validatesFormatOf('email', AK_EMAIL_REGULAR_EXPRESSION);
        $D->email = 'bermi@example.co.uk';
        $D->validatesFormatOf('email', AK_EMAIL_REGULAR_EXPRESSION);
        $D->email = 'bermi_ferrer@exam-ple.co.uk';
        $D->validatesFormatOf('email', AK_EMAIL_REGULAR_EXPRESSION);
        $this->assertFalse($D->hasErrors());


        $D->clearErrors();
        $D->email = 'bermi [at] example.com';
        $D->validatesFormatOf('email', AK_EMAIL_REGULAR_EXPRESSION);
        $this->assertEqual($D->getErrorsOn('email'),$D->getDefaultErrorMessageFor('invalid'));

        $D->clearErrors();
        $D->email = 'bermi@example.com';
        $D->validatesFormatOf('email', AK_EMAIL_REGULAR_EXPRESSION);
        $D->email = 'bermi@example.co.uk';
        $D->validatesFormatOf('email', AK_EMAIL_REGULAR_EXPRESSION);
        $D->email = 'bermi_ferrer@exam-ple.co.uk';
        $D->validatesFormatOf('email', AK_EMAIL_REGULAR_EXPRESSION);
        $this->assertFalse($D->hasErrors());


        $D->clearErrors();
        $D->first_name = '';
        $D->validatesFormatOf('first_name', AK_NOT_EMPTY_REGULAR_EXPRESSION,"can not be empty");
        $this->assertEqual($D->getErrorsOn('first_name'),"can not be empty");

        $D->clearErrors();
        $D->first_name = 'Bermi';
        $D->validatesFormatOf('first_name', AK_NOT_EMPTY_REGULAR_EXPRESSION,"can not be empty");
        $this->assertFalse($D->hasErrors());


        $D->clearErrors();
        $D->number = 12.56;
        $D->validatesFormatOf('number', AK_NUMBER_REGULAR_EXPRESSION);
        $this->assertEqual($D->getErrorsOn('number'),$D->getDefaultErrorMessageFor('invalid'));

        $D->clearErrors();
        $D->number = 1256;
        $D->validatesFormatOf('number', AK_NUMBER_REGULAR_EXPRESSION);
        $this->assertFalse($D->hasErrors());

        $D->clearErrors();
        $D->phone = 'blah';
        $D->validatesFormatOf('phone', AK_PHONE_REGULAR_EXPRESSION);
        $this->assertEqual($D->getErrorsOn('phone'),$D->getDefaultErrorMessageFor('invalid'));

        $D->clearErrors();
        $D->phone = '+34 96 299 3000';
        $D->validatesFormatOf('phone', AK_PHONE_REGULAR_EXPRESSION);
        $this->assertFalse($D->hasErrors());


        $D->clearErrors();
        $D->date = 'Monday';
        $D->validatesFormatOf('date', AK_DATE_REGULAR_EXPRESSION);
        $this->assertEqual($D->getErrorsOn('date'),$D->getDefaultErrorMessageFor('invalid'));

        $D->clearErrors();
        $D->date = '1978/06/16';
        $D->validatesFormatOf('date', AK_DATE_REGULAR_EXPRESSION);
        $this->assertFalse($D->hasErrors());


        $D->clearErrors();
        $D->ip = '257.0.0.1';
        $D->validatesFormatOf('ip', AK_IP4_REGULAR_EXPRESSION);
        $this->assertEqual($D->getErrorsOn('ip'),$D->getDefaultErrorMessageFor('invalid'));

        $D->clearErrors();
        $D->ip = '255.0.0.1';
        $D->validatesFormatOf('ip', AK_IP4_REGULAR_EXPRESSION);
        $this->assertFalse($D->hasErrors());


        $D->clearErrors();
        $D->post_code = 'a';
        $D->validatesFormatOf('post_code', AK_POST_CODE_REGULAR_EXPRESSION);
        $this->assertEqual($D->getErrorsOn('post_code'),$D->getDefaultErrorMessageFor('invalid'));

        $D->clearErrors();
        $D->post_code = 'san francisco';
        $D->validatesFormatOf('post_code', AK_POST_CODE_REGULAR_EXPRESSION);
        $this->assertEqual($D->getErrorsOn('post_code'),$D->getDefaultErrorMessageFor('invalid'));

        $D->clearErrors();
        $D->post_code = 'NSW 8376';
        $D->validatesFormatOf('post_code', AK_POST_CODE_REGULAR_EXPRESSION);
        $this->assertFalse($D->hasErrors());

        $D->clearErrors();
        $D->post_code = 'NSW 1008';
        $D->validatesFormatOf('post_code', AK_POST_CODE_REGULAR_EXPRESSION);
        $D->post_code = 46240;
        $D->validatesFormatOf('post_code', AK_POST_CODE_REGULAR_EXPRESSION);
        $this->assertFalse($D->hasErrors());

    }


    public function test_should_validate_acceptance_of() {
        $D = clone($this->ValidDocument);
        $D->validatesAcceptanceOf('tos');
        $this->assertEqual($D->getErrorsOn('tos'),$D->getDefaultErrorMessageFor('accepted'));

        $D = clone($this->ValidDocument);
        $D->validatesAcceptanceOf('tos','You need to type down "I accept this terms and conditions"',"I accept this terms and conditions");
        $this->assertEqual($D->getErrorsOn('tos'),'You need to type down "I accept this terms and conditions"');
        $D->clearErrors();
        $D->set('tos',"I accept this terms and conditions");
        $this->assertFalse($D->hasErrors());
    }


    public function test_should_validate_confirmation_of() {
        $D = clone($this->ValidDocument);
        $D->validatesConfirmationOf('user_name');
        $this->assertFalse($D->hasErrors());

        $D->set('user_name', 'bermi');

        $this->assertEqual($D->getErrorsOn('user_name'),'');

        $D->user_name_confirmation = '';
        $D->validatesConfirmationOf('user_name');
        $this->assertEqual($D->getErrorsOn('user_name'),$D->getDefaultErrorMessageFor('confirmation'));

        $D = clone($this->ValidDocument);
        $D->set('user_name', 'Bermi');
        $D->user_name_confirmation = 'bermi';
        $D->validatesConfirmationOf('user_name');
        $this->assertEqual($D->getErrorsOn('user_name'),$D->getDefaultErrorMessageFor('confirmation'));

        $D = clone($this->ValidDocument);
        $D->setAttributes(array('password'=>'abc','password_confirmation'=>'ake'));
        $D->validatesConfirmationOf('password');
        $this->assertEqual($D->getErrorsOn('password'), $D->getDefaultErrorMessageFor('confirmation'));
    }

    public function test_should_validate_uniqueness_of_attribute() {
        $D = clone($this->ValidDocument);
        $D->setAttributes(array('user_name' =>'bermi','first_name' =>'Bermi','last_name' =>'Ferrer','country' =>'ES','tos' => 1));
        $this->assertTrue($D->save());

        $D = clone($this->ValidDocument);
        $D->setAttributes(array('user_name' =>'bermi','first_name' =>'Bermi','last_name' =>'Ferrer'));
        $D->validatesUniquenessOf("user_name");
        $this->assertTrue($D->hasErrors());

        $D = $D->findFirst(array('user_name' => 'bermi'));
        $this->assertFalse($D->isNewRecord());
        $this->assertEqual($D->user_name,'bermi');
        $D->validatesUniquenessOf("user_name");
        $this->assertFalse($D->hasErrors());

        $D = $D->findFirst(array('user_name' => 'bermi'));
        $D->validatesUniquenessOf("user_name",array('scope'=>'country'));
        $this->assertFalse($D->hasErrors());

        $D = clone($this->ValidDocument);
        $D->setAttributes(array('user_name' =>'bermi','first_name' =>'Bermi','last_name' =>'Ferrer','country' =>'US'));
        $D->validatesUniquenessOf("user_name",array('scope'=>'country'));
        $this->assertFalse($D->hasErrors());

        $D = clone($this->ValidDocument);
        $D->setAttributes(array('user_name' =>'bermi','first_name' =>'Bermi','last_name' =>'Ferrer','country' =>'ES'));
        $D->validatesUniquenessOf("user_name",array('scope'=>'country'));
        $this->assertTrue($D->hasErrors());

    }

}

ak_test_case('DocumentValidations_TestCase');
