<?php

defined('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION') ? null : define('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION', false);
defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);

require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

class AkDbAdapter_schema_TestCase extends  AkUnitTest
{

    function test_should_return_available_tables_for_mysql()
    {
        $db =& AkDbAdapter::getInstance();
        if ($db->type() !== 'mysql') return;
        $old_adodb_style = $db->connection->MetaTables();
        $new_implementation = $db->availableTables();
        $this->assertEqual($old_adodb_style,$new_implementation);                    
    }
    
    function test_should_return_available_tables_for_postgre()
    {
        $db =& AkDbAdapter::getInstance();
        if ($db->type() !== 'postgre') return;
        $old = $db->connection->MetaTables();
        $new = $db->availableTables();
        sort($new);
        $this->assertEqual($old,$new);
    }
    
    function test_should_return_column_details()
    {
        $this->installAndIncludeModels(array(
            'AkTestUser'=>'id,user_name string(32),email string(150), visits int default 1, taken bool, created_at, updated_at, expires_on'
        ));
        $db =& AkDbAdapter::getInstance();
        $old_adodb_style = $db->connection->MetaColumns('ak_test_users');
        $new_implementation = $db->getColumnDetails('ak_test_users');
        $this->assertEqual($old_adodb_style,$new_implementation);
    }
    
    function _test_column_details_should_serve_activerecord_with_a_processed_array()
    {
        $this->installAndIncludeModels(array(
            'AkTestUser'=>'id,user_name string(32),email string(150), visits int default 1, taken bool, created_at, updated_at, expires_on'
        ));
        $db =& AkDbAdapter::getInstance();
        $processed_return = $db->getColumnDetails('ak_test_users');
        $expected_return = $this->AkTestUser->getColumns();
        $this->assertEqual($processed_return,$expected_return);
    }
    
    function test_should_rename_columns_for_mysql()
    {
        $db =& AkDbAdapter::getInstance();
        if ($db->type() !== 'mysql') return;
        
        $this->installAndIncludeModels(array(
            'RenameColumn'=>"id,namen string(55),postcunt int not null default 0,help string default 'none'"
        ));
        $table_name = 'rename_columns';
        $this->_mysql_rename($db, $table_name,'namen','name');
        $this->_mysql_rename($db, $table_name,'help','nohelp');
        $this->_mysql_rename($db, $table_name,'postcunt','postcount');
        
        $this->assertError($db->renameColumn($table_name,'not_found','not_here'));
    }
    
    function _mysql_rename($db, $table_name,$old_name,$new_name)
    {
        $old = $db->select("SHOW COLUMNS FROM $table_name LIKE '$old_name'");
        
        $db->renameColumn($table_name,$old_name,$new_name);
        
        $this->assertFalse($db->select("SHOW COLUMNS FROM $table_name LIKE '$old_name'"));
        $new = $db->select("SHOW COLUMNS FROM $table_name LIKE '$new_name'");
        unset($old[0],$old['Field'],$new[0],$new['Field']);
        $this->assertEqual($old,$new);
        
    }
    
    function test_should_rename_columns_for_postgre()
    {
        $db =& AkDbAdapter::getInstance();
        if ($db->type() !== 'postgre') return;
        
        $this->installAndIncludeModels(array(
            'RenameColumn'=>"id,namen string(55),postcunt int not null default 0,help string default 'none'"
        ));
        $table_name = 'rename_columns';
        $db->renameColumn($table_name,'namen','name');
    }

}

ak_test('AkDbAdapter_schema_TestCase',true);

?>
