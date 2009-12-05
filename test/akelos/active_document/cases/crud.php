<?php

require_once(dirname(__FILE__).'/../config.php');

class DocumentCrud_TestCase extends ActiveDocumentUnitTest
{
    public function setup()
    {
        $this->db = new AkOdbAdapter();
        $this->db->connect(array('type' => 'mongo_db', 'database' => 'akelos_testing'));
        $this->WebPage = new WebPage();
        $this->WebPage->setAdapter($this->db);
    }

    public function tearDown()
    {
        $this->db->dropDatabase();
        $this->db->disconnect();
    }

    public function test_should_get_collection()
    {
        $this->assertEqual($this->WebPage->getCollectionName(), 'web_pages');
        $this->assertEqual($this->WebPage->getTableName(),      'web_pages');
    }

    public function test_should_create_document()
    {
        $Akelos = $this->WebPage->create(array(
        'title' => 'Akelos.org',
        'body'  =>  'Akelos is a web application framework...',
        'keywords' => array('one', 'two')
        ));
        $this->assertEqual($Akelos->title, 'Akelos.org');
        $this->assertEqual($Akelos->title, 'Akelos.org');
    }

}

ak_test_case('DocumentCrud_TestCase');
