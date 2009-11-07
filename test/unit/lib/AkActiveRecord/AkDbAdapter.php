<?php

defined('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION') ? null : define('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION', false);
defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);

require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

class AkDbAdapter_TestCase extends  AkUnitTest
{

    public function test_should_generate_sequence_ids()
    {
        $db =& AkDbAdapter::getInstance(array('type'=>'sqlite'),false);
    }

    public function test_should_report_errors()
    {
        $db =& AkDbAdapter::getInstance();
        $db->debug();
        $db->execute('selct wrong sql statement');
        $this->assertError();
        //$db->debug(false);
    }

    public function test_execute_should_handle_bindings()
    {
        $db = new AkDbAdapter(array());  // no conection details, we're using a Mock
        Mock::generate('ADOConnection');
        $connection = new MockADOConnection();
        $connection->setReturnValue('Execute',true);
        $connection->expectAt(0,'Execute',array('SELECT * FROM articles WHERE id=1'));
        $connection->expectAt(1,'Execute',array('SELECT * FROM articles WHERE id=?',array(1)));
        $db->connection =& $connection;
        $db->execute('SELECT * FROM articles WHERE id=1');
        $db->execute(array('SELECT * FROM articles WHERE id=?',1));
    }

    public function test_should_add_limit_and_offset_mysql_style()
    {
        $mysql_db =& AkDbAdapter::getInstance(array('type'=>'mysql'),false);
        $sql = 'SELECT * FROM articles';
        $mysql_db->addLimitAndOffset($sql,array('limit'=>2,'offset'=>10));
        $this->assertEqual('SELECT * FROM articles LIMIT 10, 2',$sql);

        $sql = 'SELECT * FROM articles';
        $mysql_db->addLimitAndOffset($sql,array('offset'=>10));
        $this->assertEqual('SELECT * FROM articles',$sql);

        $sql = 'SELECT * FROM articles';
        $mysql_db->addLimitAndOffset($sql,array('limit'=>10));
        $this->assertEqual('SELECT * FROM articles LIMIT 10',$sql);
    }

    public function test_should_add_limit_and_offset_common_style()
    {
        $mysql_db =& AkDbAdapter::getInstance(array('type'=>'postgre'),false);
        $sql = 'SELECT * FROM articles';
        $mysql_db->addLimitAndOffset($sql,array('limit'=>2,'offset'=>10));
        $this->assertEqual('SELECT * FROM articles LIMIT 2 OFFSET 10',$sql);

        $sql = 'SELECT * FROM articles';
        $mysql_db->addLimitAndOffset($sql,array('offset'=>10));
        $this->assertEqual('SELECT * FROM articles',$sql);

        $sql = 'SELECT * FROM articles';
        $mysql_db->addLimitAndOffset($sql,array('limit'=>10));
        $this->assertEqual('SELECT * FROM articles LIMIT 10',$sql);
    }

    public function test_should_quote_strings_for_mysql()
    {
        $db =& AkDbAdapter::getInstance();
        if ($db->type() != 'mysql') return;

        $this->assertEqual("'Hello'",$db->quote_string('Hello'));
        $this->assertEqual("'Hel\\\"lo'",$db->quote_string('Hel"lo'));
        $this->assertEqual("'Hel\'\'lo'",$db->quote_string("Hel''lo"));
        $this->assertEqual("'Hel\\\lo'",$db->quote_string("Hel\lo"));
        $this->assertEqual("'Hel\\\lo'",$db->quote_string("Hel\\lo"));
    }

    public function test_should_quote_strings_for_postgre()
    {
        $db =& AkDbAdapter::getInstance();
        if ($db->type() != 'postgre') return;

        $this->assertEqual("'Hello'",$db->quote_string('Hello'));
        $this->assertEqual("'Hel\"lo'",$db->quote_string('Hel"lo'));
        $this->assertEqual("'Hel''''lo'",$db->quote_string("Hel''lo"));
        $this->assertEqual("'Hel''lo'",$db->quote_string("Hel'lo"));
        $this->assertEqual("'Hel\\\lo'",$db->quote_string("Hel\lo"));
        $this->assertEqual("'Hel\\\lo'",$db->quote_string("Hel\\lo"));
    }

    public function test_should_quote_strings_for_sqlite()
    {
        $db =& AkDbAdapter::getInstance();
        if ($db->type() != 'sqlite') return;

        $this->assertEqual("'Hello'",$db->quote_string('Hello'));
        $this->assertEqual("'Hel\"lo'",$db->quote_string('Hel"lo'));
        $this->assertEqual("'Hel''''lo'",$db->quote_string("Hel''lo"));
        $this->assertEqual("'Hel''lo'",$db->quote_string("Hel'lo"));
        $this->assertEqual("'Hel\lo'",$db->quote_string("Hel\lo"));
        $this->assertEqual("'Hel\lo'",$db->quote_string("Hel\\lo"));
    }

    public function _test_investigate_DBTimeStamp()
    {
        $db =& AkDbAdapter::getInstance();

        var_dump($db->DBTimeStamp('2007.11.17'));
        var_dump($db->DBTimeStamp('2007-11-17'));
        var_dump($db->DBTimeStamp('2007-11-17 17:40:23'));
        var_dump($db->DBTimeStamp('2007-11-17 8:40:23'));
        var_dump($db->DBTimeStamp('17-11-2007'));
        var_dump($db->DBTimeStamp(time()));
    }
}

ak_test('AkDbAdapter_TestCase',true);

?>
