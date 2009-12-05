<?php

require_once(dirname(__FILE__).'/../config.php');

class DocumentAdapter_TestCase extends ActiveDocumentUnitTest
{
    public function setup()
    {
        $this->db = new AkOdbAdapter();
    }

    public function test_should_require_settings_for_connection()
    {
        $this->assertUpcomingError('You must provide a connection settings array');
        $this->db->setupAdapter();

        $this->assertUpcomingError('You must provide a connection settings array');
        $this->db->setupAdapter('mongo');
    }

    public function test_should_connect_using_config_namespace_as_parameter()
    {
        file_put_contents(AK_CONFIG_DIR.'/mongo_db.yml', AkConfig::getDir('fixtures').'/sample_config.yml');
        $this->assertTrue($this->db->setupAdapter('mongo_db'));
        unlink(AK_CONFIG_DIR.'/mongo_db.yml');
    }

    public function test_should_connect_using_custom_namespace()
    {
        file_put_contents(AK_CONFIG_DIR.'/testing_object_database.yml', AkConfig::getDir('fixtures').'/sample_config.yml');
        $this->db->settings_namespace = 'testing_object_database';
        $this->assertTrue($this->db->setupAdapter());
        unlink(AK_CONFIG_DIR.'/testing_object_database.yml');
    }

    public function test_should_get_adapter_type()
    {
        $this->assertTrue($this->db->setupAdapter(array('type' => 'mongo_db')));
        $this->assertEqual($this->db->getType(), 'mongo_db');
    }


    public function test_should_establish_connection()
    {
        $this->assertTrue($this->db->connect(array('type' => 'mongo_db')));
        $this->assertTrue($this->db->isConnected());
        $this->assertTrue($this->db->disconnect());
        $this->assertFalse($this->db->isConnected());
    }

    public function test_should_reuse_connection()
    {
        $this->assertTrue($this->db->connect(array('type' => 'mongo_db')));
        $Connection = $this->db->getConnection();

        $this->db = new AkOdbAdapter();
        $this->assertTrue($this->db->connect(array('type' => 'mongo_db')));
        $SecondConnection = $this->db->getConnection();

        $this->assertReference($Connection, $SecondConnection);

        $this->assertTrue($this->db->disconnect());
        $this->assertFalse($this->db->isConnected());
    }

    public function test_should_select_database()
    {
        $this->db->connect(array('type' => 'mongo_db', 'database' => 'akelos_testing'));
        $this->assertEqual((string)$this->db->getDatabase(), 'akelos_testing');
        $this->db->dropDatabase();
    }
}

ak_test_case('DocumentAdapter_TestCase');
