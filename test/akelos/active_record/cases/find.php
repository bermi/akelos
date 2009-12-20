<?php

require_once(dirname(__FILE__).'/../config.php');

class Find_TestCase extends ActiveRecordUnitTest
{
    public $Hybrid;

    public function test_init() {
        $this->installAndIncludeModels(array('Hybrid'=>'id,name'));
        Mock::generate('AkDbAdapter');
        $Db = new MockAkDbAdapter();
        $Db->setReturnValue('select',array());
        $this->Db = $Db;
        $this->Hybrid->setConnection($Db);
        $this->Hybrid->find();
    }

    public function test_find_all() {
        $this->Db->expectAt(0,'select',array('SELECT * FROM hybrids','selecting'));
        $this->Hybrid->find('all');
    }

    public function test_add_group_by_clause() {
        $this->Db->expectAt(0,'select',array('SELECT * FROM hybrids GROUP BY id','selecting'));
        $this->Hybrid->find('all',array('group'=>'id'));
    }

}

ak_test_case('Find_TestCase');

