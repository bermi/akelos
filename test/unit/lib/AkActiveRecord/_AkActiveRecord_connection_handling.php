<?php

defined('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION') ? null : define('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION', false);
defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);

require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

class AkActiveRecord_connection_handling_TestCase extends  AkUnitTest
{

    public function test_should_establish_a_connection()
    {
        $this->installAndIncludeModels(array('DummyModel'=>'id'));

        $Model = $this->DummyModel;
        $default_connection =& AkDbAdapter::getInstance();
        $available_tables_on_default = $default_connection->availableTables();
        unset ($Model->_db);

        $this->assertReference($Model->establishConnection(),$default_connection);
        $development_connection = $Model->establishConnection('development');

        $available_tables_on_development = $development_connection->availableTables();
        $this->assertFalse($development_connection===$default_connection);

        $this->assertFalse($Model->establishConnection('not_specified_profile'));
        $this->assertError("Could not find the database profile 'not_specified_profile' in config/database.yml.");

        $check_default_connection =& AkDbAdapter::getInstance();
        $this->assertReference($default_connection,$check_default_connection);
        $this->assertReference($default_connection->connection,$check_default_connection->connection);

        //because we dont get two different connections at the same time on PHP if user and password is identical
        //thus: !$this->assertEqual($available_tables_on_default,$check_default_connection->availableTables());
        //we have to:
        $check_default_connection->connect();
        //now we get:
        $this->assertEqual($available_tables_on_default,$check_default_connection->availableTables());
        //BUT again: !!
        //$this->assertNotEqual($available_tables_on_development,$development_connection->availableTables());
        //$this->assertEqual($available_tables_on_default,$development_connection->availableTables());

    }

    public function test_should_establish_multiple_connections()
    {
        $db_settings = Ak::convert('yaml', 'array', AK_CONFIG_DIR.DS.'database.yml');
        $db_settings['sqlite_databases'] = array(
        'database_file' => AK_TMP_DIR.DS.'testing_sqlite_database.sqlite',
        'type' => 'sqlite'
        );
        file_put_contents(AK_CONFIG_DIR.DS.'database.yml', Ak::convert('array', 'yaml', $db_settings));


        $this->installAndIncludeModels(array('TestOtherConnection'));

        Ak::import('test_other_connection');
        $OtherConnection = new TestOtherConnection(array('name'=>'Delia'));
        $this->assertTrue($OtherConnection->save());


        $this->installAndIncludeModels(array('DummyModel'=>'id,name'));
        $Dummy = new DummyModel();

        $this->assertNotEqual($Dummy->getConnection(), $OtherConnection->getConnection());

        unset($db_settings['sqlite_databases']);
        file_put_contents(AK_CONFIG_DIR.DS.'database.yml', Ak::convert('array', 'yaml', $db_settings));
    }

}

ak_test('AkActiveRecord_connection_handling_TestCase',true);

?>
