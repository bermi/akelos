<?php

require_once(dirname(__FILE__).'/../config.php');

class DocumentFinders_TestCase extends ActiveDocumentUnitTest
{
    public function setup() {
        $this->db = new AkOdbAdapter();
        $this->db->connect(array('type' => 'mongo_db', 'database' => 'akelos_testing'));
        $this->WebPage = new WebPage();
        $this->WebPage->setAdapter($this->db);
    }

    public function tearDown() {
        $this->db->dropDatabase();
        $this->db->disconnect();
    }

    public function test_should_get_collection() {
        $this->assertEqual($this->WebPage->getCollectionName(), 'web_pages');
        $this->assertEqual($this->WebPage->getTableName(),      'web_pages');
    }

    public function test_should_find_by_attributes() {
        $attributes = array('title' => 'Akelos.org', 'author' => 'Bermi');
        $Akelos = $this->WebPage->create($attributes);
        
        $attributes = array('title' => 'BermiLabs', 'author' => 'Bermi');
        $BermiLabs = $this->WebPage->create($attributes);
        
        $this->assertTrue($FoundAkelos = $this->WebPage->findFirst(array('conditions'=>array('title'=>'Akelos.org'))));
        $this->assertEqual($FoundAkelos->getId(), $Akelos->getId());
        
        $this->assertTrue($FoundBermiLabs = $this->WebPage->findFirst(array('conditions'=>array('title'=>'BermiLabs', 'author' => 'Bermi'))));
        $this->assertEqual($FoundBermiLabs->getId(), $BermiLabs->getId());

        $this->assertTrue($Pages = $this->WebPage->find(array('conditions'=>array('author'=>'Bermi'))));
        $this->assertEqual($Pages[0]->getId(), $Akelos->getId());
        $this->assertEqual($Pages[1]->getId(), $BermiLabs->getId());        
    }
    
    public function test_should_find_by_id_string() {
        $attributes = array('title' => 'UserMinds.com');
        $UserMinds = $this->WebPage->create($attributes);
        
        $FoundUserMinds = new WebPage((string)$UserMinds->getId());
        
        $this->assertFalse($FoundUserMinds->isNewRecord());
        $this->assertEqual($FoundUserMinds->getId(), $UserMinds->getId());
       
        $FoundUserMinds = $UserMinds->find((string)$UserMinds->getId());
        
        $this->assertFalse($FoundUserMinds->isNewRecord());
        $this->assertEqual($FoundUserMinds->getId(), $UserMinds->getId());
        
        
    }
}

ak_test_case('DocumentFinders_TestCase');
