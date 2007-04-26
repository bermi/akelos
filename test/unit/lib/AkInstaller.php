<?php

defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);
require_once(dirname(__FILE__).'/../../fixtures/config/config.php');

require_once(AK_LIB_DIR.DS.'AkInstaller.php');

class Test_of_AkInstaller extends  UnitTestCase
{
    /**/
    //$is_mysql = (strpos($this->Installer->db->databaseType,'mysql')!==false) ? true : false;

    function setup()
    {
        global $ADODB_FETCH_MODE;
        $this->_original_ADODB_FETCH_MODE = $ADODB_FETCH_MODE;
        $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
        $this->Installer = new AkInstaller();
    }
    
    function tearDown()
    {
        global $ADODB_FETCH_MODE;
        $ADODB_FETCH_MODE = $this->_original_ADODB_FETCH_MODE;
    }

    function Test_setup_expected_returns()
    {
        $db_type = (strpos($this->Installer->db->databaseType,'mysql')!==false) ? 'mysql' : 'sqlite'; // Need to test it on pgsql
        switch ($db_type) {
            case 'sqlite':
            $this->expected_for_creating_table = array(
            'ID' => array('type'=>'DECIMAL', 'max_length'=>10,'not_null'=>1,'auto_increment'=>1),
            'TITLE' => array('max_length'=>255,'not_null'=>null),
            'BODY' => array('max_length'=>-1,'not_null'=>null,'type'=>'TEXT'),
            'PARENT_ID' => array('has_default'=>1,'default_value'=>0,'not_null'=>1),
            'CREATED_AT' => array('type'=>'DATE'),
            );
            $this->expected_for_default_types = array(
            'ID' => array('type'=>'DECIMAL', 'max_length'=>10,'not_null'=>1,'auto_increment'=>1,'primary_key'=>1),
            'NAME' => array('max_length'=>255,'not_null'=>null),
            'SCREEN_NAME' => array('max_length'=>255,'not_null'=>null),
            'DESCRIPTION' => array('max_length'=>-1,'not_null'=>null),
            'EN_URL' => array('max_length'=>255,'not_null'=>null),
            'ES_URL' => array('max_length'=>255,'not_null'=>null),
            'OWNER_ID' => array('type'=>'DECIMAL', 'max_length'=>10),
            'MODIFIED_AT' => array('type'=>'DATE'),
            'CREATED_ON' => array('type'=>'DATE'),
            'IS_FEATURED' => array('max_length'=>3,'type'=>'DECIMAL'),
            'POSITION' => array('max_length'=>10),
            );

            break;

            default:
            $this->expected_for_creating_table = array(
            'ID' => array('type'=>'int', 'max_length'=>11,'not_null'=>1,'auto_increment'=>1),
            'TITLE' => array('max_length'=>255,'not_null'=>null),
            'BODY' => array('max_length'=>-1,'not_null'=>null),
            'PARENT_ID' => array('has_default'=>1,'default_value'=>0,'not_null'=>1),
            'CREATED_AT' => array('type'=>'datetime'),
            );
            $this->expected_for_default_types = array(
            'ID' => array('type'=>'int', 'max_length'=>11,'not_null'=>1,'auto_increment'=>1,'primary_key'=>1),
            'NAME' => array('max_length'=>255,'not_null'=>null),
            'SCREEN_NAME' => array('max_length'=>255,'not_null'=>null),
            'DESCRIPTION' => array('max_length'=>-1,'not_null'=>null),
            'EN_URL' => array('max_length'=>255,'not_null'=>null),
            'ES_URL' => array('max_length'=>255,'not_null'=>null),
            'OWNER_ID' => array('type'=>'int', 'max_length'=>11),
            'MODIFIED_AT' => array('type'=>'datetime'),
            'CREATED_ON' => array('type'=>'date'),
            'IS_FEATURED' => array('max_length'=>1),
            'POSITION' => array('max_length'=>11),
            );
            break;
        }
    }


    function Test_for_creating_table()
    {
        $this->Installer->createTable('test_pages', "
            id integer(11) not null auto_increment primary_key,
            title string(255),
            body text,
            parent_id integer(11) not null default '0',
            created_at datetime default '0000-00-00 00:00:00',
            updated_at datetime default '0000-00-00 00:00:00'
        ");

        $expected = array(
        'ID' => array('type'=>'int', 'max_length'=>11,'not_null'=>1,'auto_increment'=>1),
        'TITLE' => array('max_length'=>255,'not_null'=>null),
        'BODY' => array('max_length'=>-1,'not_null'=>null),
        'PARENT_ID' => array('has_default'=>1,'default_value'=>0,'not_null'=>1),
        'CREATED_AT' => array('type'=>'datetime'),
        );
        $from_datadict = $this->Installer->db->MetaColumns('test_pages');
        foreach ($this->expected_for_creating_table as $column=>$details){
            foreach ($details as $index=>$value) {
                $this->assertEqual($this->expected_for_creating_table[$column][$index], $from_datadict[$column]->$index);
            }
        }
        $this->Installer->createTable('test_categories', "
            id integer not null auto_increment primary_key,
            name string(255),
            description text,
            parent_id integer(11) not null default '0'
        ");


        $this->Installer->createTable('test_categories_pages', "
            category_id integer(11) not null,
            page_id integer(11) not null
        ");

        $this->Installer->createTable('test_nice_urls', "
            id integer not null auto_increment primary_key,
            url string(255),
            context_id integer(11) not null,
            context string(255) not null default 'page'
        ");

        $this->Installer->dropTables('test_pages','test_categories');
        $this->Installer->dropTables('test_categories_pages,test_nice_urls');
    }

    function Test_of_createTable()
    {
        $this->Installer = new AkInstaller();

        $this->assertFalse($this->Installer->tableExists('test_1'));

        $this->Installer->createTable('test_1','id int auto key,body string(32) index,author string(32)');
        $this->assertTrue($this->Installer->tableExists('test_1'));

        $avail_indices = $this->Installer->db->MetaIndexes('test_1');
        $this->assertTrue(isset($avail_indices['idx_test_1_body']));
        $this->assertFalse($avail_indices['idx_test_1_body']['unique']);
        $this->assertTrue($avail_indices['idx_test_1_body']['columns'][0]=='body');

        $this->Installer->dropTable('test_1');

        $this->Installer->createTable('test_1','id int auto key,body string(32) index');
        $this->assertTrue($this->Installer->tableExists('test_1'));

        $avail_indices = $this->Installer->db->MetaIndexes('test_1');
        $this->assertTrue(isset($avail_indices['idx_test_1_body']));
        $this->assertFalse($avail_indices['idx_test_1_body']['unique']);
        $this->assertTrue($avail_indices['idx_test_1_body']['columns'][0]=='body');

        $this->Installer->dropTable('test_1');
        $this->assertFalse($this->Installer->tableExists('test_1'));

        $this->Installer->createTable('test_1','id int auto key,body string(32) unique');
        $this->assertTrue($this->Installer->tableExists('test_1'));

        $avail_indices = $this->Installer->db->MetaIndexes('test_1');
        $this->assertTrue(isset($avail_indices['idx_test_1_body UNIQUE']));
        $this->assertTrue($avail_indices['idx_test_1_body UNIQUE']['unique']);
        $this->assertTrue($avail_indices['idx_test_1_body UNIQUE']['columns'][0]=='body');

        $this->Installer->dropTable('test_1');
        $this->assertFalse($this->Installer->tableExists('test_1'));

        $this->Installer->createTable('test_1','id int auto key,body string(32) full_text',array('mysql'=>'Type=MyISAM'));
        $this->assertTrue($this->Installer->tableExists('test_1'));
        $this->Installer->dropTable('test_1');

    }

    function Test_of_add_and_remove_Indices()
    {
        $this->Installer = new AkInstaller();

        $this->Installer->createTable('test_1','id int auto key,free string(32),beer string(23)');

        $avail_indices = $this->Installer->db->MetaIndexes('test_1');
        $this->assertTrue(empty($avail_indices));

        $this->Installer->addIndex('test_1','beer, free UNIQUE');
        $avail_indices = $this->Installer->db->MetaIndexes('test_1');
        $this->assertFalse(empty($avail_indices));
        $this->assertTrue(isset($avail_indices['idx_test_1_beer, free UNIQUE']));
        $this->assertTrue($avail_indices['idx_test_1_beer, free UNIQUE']['unique']);

        $this->Installer->removeIndex('test_1','beer, free UNIQUE');
        $avail_indices = $this->Installer->db->MetaIndexes('test_1');
        $this->assertFalse(isset($avail_indices['idx_test_1_beer, free UNIQUE']));

        $this->Installer->addIndex('test_1','beer, free UNIQUE','freebeer');
        $avail_indices = $this->Installer->db->MetaIndexes('test_1');
        $this->assertTrue(isset($avail_indices['freebeer']));
        $this->assertTrue($avail_indices['freebeer']['unique']);
        $this->assertTrue($avail_indices['freebeer']['columns'][0]=='beer');
        $this->assertTrue($avail_indices['freebeer']['columns'][1]=='free');

        $this->Installer->dropIndex('test_1','freebeer');
        $avail_indices = $this->Installer->db->MetaIndexes('test_1');
        $this->assertTrue(empty($avail_indices));

        $this->Installer->dropTable('test_1');

    }


    /**/

    function Test_of_default_types()
    {
        $this->Installer = new AkInstaller();
        $this->Installer->createTable('test_defaults','id,name,screen_name string,description,*url,owner_id,modified_at,created_on,is_featured,position');
        $from_datadict = $this->Installer->db->MetaColumns('test_defaults');
        foreach ($this->expected_for_default_types as $column=>$details){
            foreach ($details as $index=>$value) {
                $this->assertEqual($this->expected_for_default_types[$column][$index], $from_datadict[$column]->$index);
            }
        }

        $this->Installer->dropTable('test_defaults');
    }


}

ak_test('Test_of_AkInstaller', true);

?>