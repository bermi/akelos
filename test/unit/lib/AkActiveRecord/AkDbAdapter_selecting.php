<?php

defined('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION') ? null : define('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION', false);
defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);

require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

class AkDbAdapter_Select_TestCase extends AkUnitTest 
{
    var $db;
    function setUp()
    {
        $this->db =& new AkDbAdapter(array());  // no conection details, we're using a Mock
        Mock::generate('ADOConnection');
        $connection =& new MockADOConnection();
        Mock::generate('ADORecordSet');
        $RecordSet =& new MockADORecordSet();
        $RecordSet->setReturnValue('FetchRow',false);
        $RecordSet->setReturnValueAt(0, 'FetchRow', array('id'=>1,'name'=>'One'));
        $RecordSet->setReturnValueAt(1, 'FetchRow', array('id'=>2,'name'=>'Two'));
        $RecordSet->setReturnValueAt(2, 'FetchRow', array('id'=>3,'name'=>'Three'));
        $RecordSet->setReturnValueAt(3, 'FetchRow', array('id'=>4,'name'=>'Four'));
        $connection->setReturnValue('Execute',$RecordSet);
        $this->db->connection =& $connection;
        
    }
    
    function test_select_all()
    {
        $result = array();
        $result[] = array('id'=>1,'name'=>'One');
        $result[] = array('id'=>2,'name'=>'Two');
        $result[] = array('id'=>3,'name'=>'Three');
        $result[] = array('id'=>4,'name'=>'Four');
        $this->assertEqual($this->db->select('SELECT id,name FROM selecttests'),$result);
    }
    
    function test_selectOne()  // selects first row
    {
        $result = array('id'=>1,'name'=>'One');
        $this->assertEqual($this->db->selectOne("SELECT id,name FROM selecttests"),$result);
    }
    
    function test_selectValues() // selects first column, all rows
    {
        $result = array(1,2,3,4);
        $this->assertEqual($this->db->selectValues("SELECT id,name FROM selecttests"),$result);
        
    }
    function test_selectValue() // select first column, first row; "first Value"
    {
        $result = 1;
        $this->assertEqual($this->db->selectValue("SELECT id,name FROM selecttests"),$result);
        
    }
    
}

ak_test('AkDbAdapter_Select_TestCase',true);

?>