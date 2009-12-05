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
        $this->db->connect();

        $this->assertUpcomingError('You must provide a connection settings array');
        $this->db->connect('mongo');
    }

    public function test_should_connect_using_config_namespace_as_parameter()
    {
        file_put_contents(AK_CONFIG_DIR.'/mongo_db.yml', AkConfig::getDir('fixtures').'/sample_config.yml');
        $this->assertTrue($this->db->connect('mongo_db'));
        unlink(AK_CONFIG_DIR.'/mongo_db.yml');
    }

    public function test_should_connect_using_custom_namespace()
    {
        file_put_contents(AK_CONFIG_DIR.'/testing_object_database.yml', AkConfig::getDir('fixtures').'/sample_config.yml');
        $this->db->settings_namespace = 'testing_object_database';
        $this->assertTrue($this->db->connect());
        unlink(AK_CONFIG_DIR.'/testing_object_database.yml');
    }



}

ak_test_case('DocumentAdapter_TestCase');
