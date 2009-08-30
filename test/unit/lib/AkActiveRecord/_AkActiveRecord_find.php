<?php

defined('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION') ? null : define('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION', false);
defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);

require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

class AkActiveRecord_find_TestCase extends AkUnitTest
{

    /**
     * @var ActiveRecord
     */
    public $Hybrid;

    public function setUp()
    {
        $this->installAndIncludeModels(array('Hybrid'=>'id,name'));
        Mock::generate('AkDbAdapter');
        $Db = new MockAkDbAdapter();
        $Db->setReturnValue('select',array());
        $this->Db =& $Db;
        $this->Hybrid->setConnection($Db);
    }

    public function test_find_all()
    {
        $this->Db->expectAt(0,'select',array('SELECT * FROM hybrids','selecting'));
        $this->Hybrid->find('all');
    }

    public function test_add_group_by_clause()
    {
        $this->Db->expectAt(0,'select',array('SELECT * FROM hybrids GROUP BY id','selecting'));
        $this->Hybrid->find('all',array('group'=>'id'));
    }

}

ak_test('AkActiveRecord_find_TestCase',true);


?>