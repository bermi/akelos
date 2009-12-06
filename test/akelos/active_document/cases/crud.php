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
        $attributes = array(
        'title' => 'Akelos.org',
        'body'  =>  'Akelos PHP framework...',
        'keywords' => array('one', 'two')
        );
        $Akelos = $this->WebPage->create($attributes);
        $this->assertFalse($Akelos->isNewRecord());
        $this->assertEqual($Akelos->title, 'Akelos.org');
        $this->assertEqual(Ak::pick('title,body,keywords', $Akelos->getAttributes()), $attributes);
        $this->assertNotNull($Akelos->getId());
    }


    public function test_should_not_duplicate_documents()
    {
        $attributes = array(
        'title' => 'Doc 1',
        );
        $Akelos             = $this->WebPage->create($attributes);
        $AkelosDuplicated   = $this->WebPage->create($attributes);

        $attributes['body'] = 'Akelos PHP framework...';
        $AkelosDuplicated   = $this->WebPage->create($attributes);
        $this->assertNotEqual($AkelosDuplicated->getId(), $Akelos->getId());
    }


    public function test_should_set_and_get_attributes()
    {
        $this->WebPage->title = 'Akelos.org';
        $this->WebPage->body  =  'Akelos PHP framework...';
        $this->WebPage->keywords = array('one', 'two');
        $this->assertNull($this->WebPage->getId());
        $this->assertTrue($this->WebPage->isNewRecord());
        $this->WebPage->save();
        $this->assertFalse($this->WebPage->isNewRecord());
        $this->assertEqual($this->WebPage->title, 'Akelos.org');
        $this->assertEqual(Ak::pick('body', $this->WebPage->getAttributes()), array('body' => 'Akelos PHP framework...'));
        $this->assertEqual($this->WebPage->getAttribute('body'), 'Akelos PHP framework...');
        $this->assertNotNull($this->WebPage->getId());
    }


    public function test_should_update_records()
    {
        $this->WebPage->body  =  'Akelos PHP framework...';
        $this->WebPage->save();
        $id = $this->WebPage->getId();
        $this->assertEqual($this->WebPage->body, 'Akelos PHP framework...');
        $this->WebPage->body  =  'Akelos';
        $this->WebPage->save();
        $this->assertEqual($this->WebPage->get('body'), 'Akelos');
        $this->assertEqual($id, $this->WebPage->getId());
    }

    public function test_should_record_timestamps()
    {
        $this->WebPage->body  =  'Akelos PHP framework...';
        $this->WebPage->save();
        $created_at = Ak::getDate();
        $this->assertEqual($this->WebPage->created_at, $created_at);
        sleep(1);
        $this->WebPage->save();
        $this->assertEqual($this->WebPage->created_at, $created_at);
        $this->assertEqual($this->WebPage->updated_at, Ak::getDate());
    }

    public function test_should_instantiate_record_by_primary_key()
    {
        $this->WebPage->body  =  'Akelos PHP framework...';
        $this->WebPage->save();
        $WebPage = new WebPage($this->WebPage->getId());
        $this->assertFalse($WebPage->isNewRecord());
        $this->assertEqual($WebPage->body, $this->WebPage->body);
        $this->assertEqual($WebPage->getId(), $this->WebPage->getId());
    }

}

ak_test_case('DocumentCrud_TestCase');
