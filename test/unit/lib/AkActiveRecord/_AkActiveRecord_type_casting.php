<?php

defined('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION') ? null : define('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION', false);
defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);

require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

class test_AkActiveRecord_type_casting extends  AkUnitTest
{
    function test_start()
    {
        $this->installAndIncludeModels(array('Tag','Post'));
    }

    // Ticket #21
    function test_should_store_zero_strings_as_intergers()
    {
        $Tag = new Tag(array('name'=>'Ticket #21'));
        $this->assertTrue($Tag->save());
        $this->assertEqual($Tag->get('score'), 100);

        $Tag->setAttributes(array('score' => '0'));
        $this->assertTrue($Tag->save());

        $Tag =& $Tag->find($Tag->id);
        $this->assertIdentical($Tag->get('score'), 0);

    }

    // Ticket #36
    function test_should_update_dates_correctly()
    {
        $params = array(
        'title' => 'Hello',
        'body' => 'Hello world!',
        'posted_on(1i)' => '2005',
        'posted_on(2i)' => '6',
        'posted_on(3i)' => '16');
        $Post =& new Post();
        $Post->setAttributes($params);
        $this->assertTrue($Post->save());
        $Post->reload();
        $this->assertEqual($Post->get('posted_on'), '2005-06-16');

    }

    // Ticket #76
    function test_should_update_datetime_correctly()
    {
        $params = array(
        'title' => 'Expiring salutation',
        'body' => 'Expiring Hello world!',
        'expires_at(1i)' => '2007', 
        'expires_at(2i)' => '10', 
        'expires_at(3i)' => '15', 
        'expires_at(4i)' => '17', 
        'expires_at(5i)' => '30'
        );
        $Post =& new Post();
        $Post->setAttributes($params);
        $this->assertTrue($Post->save());
        $Post->reload();
        $this->assertEqual($Post->get('expires_at'), '2007-10-15 17:30:00');
    }
    
    function test_should_handle_empty_date_as_null()
    {
        $this->installAndIncludeModels(array('Post'));

        $params = array('title'=>'An empty date is a null date','posted_on(1i)'=>'','posted_on(2i)'=>'','posted_on(3i)'=>'');
        $MyPost =& $this->Post->create($params);
        
        $MyPost->reload();
        $this->assertNull($MyPost->posted_on);
    }
    
    function test_cast_date_parameters()
    {
        $params = array('posted_on(1i)'=>'','posted_on(2i)'=>'','posted_on(3i)'=>'');
        $this->Post->_castDateParametersFromDateHelper_($params);
        $this->assertEqual('',$params['posted_on']);

        $params = array('posted_on(1i)'=>'2008','posted_on(2i)'=>'10','posted_on(3i)'=>'');
        $this->Post->_castDateParametersFromDateHelper_($params);
        $this->assertEqual('2008-10',$params['posted_on']);
        
        $this->assertEqual('2008',$this->Post->{"posted_on(1i)"});
        $this->assertEqual('10',$this->Post->{"posted_on(2i)"});
        $this->assertEqual('',$this->Post->{"posted_on(3i)"});
    }
    
    function test_should_serialize_attributes()
    {
        $User = new User(array('preferences'=>array("background" => "black", "display" => 'large')));
        $User->save();
        $User = $User->find($User->getId());
        $this->assertEqual($User->get('preferences'), array("background" => "black", "display" => 'large'));
    }
}

ak_test('test_AkActiveRecord_type_casting',true);

?>
