<?php

defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);
require_once(dirname(__FILE__).'/../../fixtures/config/config.php');

require_once(AK_LIB_DIR.DS.'AkInstaller.php');

class Test_of_AkInstaller extends  AkUnitTest
{
    /**/

    public function setup()
    {
        $this->Installer = new AkInstaller();
    }

    public function __tearDown()
    {
        global $ADODB_FETCH_MODE;
        $ADODB_FETCH_MODE = $this->_original_ADODB_FETCH_MODE;
    }

    public function Test_setup_expected_returns()
    {
        $db_type = $this->Installer->db->type();

        switch ($db_type) {
            case 'postgre':
                $this->expected_for_creating_table = array(
                'ID' => array('type'=>'int4','not_null'=>1,'default_value'=>"nextval('test_pages_id_seq'::regclass)",'primary_key' => 1),
                'TITLE' => array('max_length'=>255,'has_default'=>null,'type'=>'varchar'),
                'BODY' => array('max_length'=>-1,'has_default'=>null,'type'=>'text'),
                'PARENT_ID' => array('max_length'=>4,'has_default'=>1,'default_value'=>0,'not_null'=>1,'type'=>'int4'),
                'CREATED_AT' => array('type'=>'timestamp', 'max_length'=> 8,'has_default'=>null),
                'UPDATED_AT' => array('type'=>'timestamp', 'max_length'=> 8,'has_default'=>null),
                );

                $this->expected_for_default_types = array(
                'ID' => array('type'=>'int4','not_null'=>1, 'default_value' => "nextval('test_defaults_id_seq'::regclass)",'primary_key'=>1),
                'NAME' => array('type'=>'varchar', 'max_length'=>255, 'has_default'=>null),
                'SCREEN_NAME' => array('type'=>'varchar', 'max_length'=>255, 'has_default'=>null),
                'DESCRIPTION' => array('max_length'=>-1,'has_default'=>null),
                'EN_URL' => array('max_length'=>255,'has_default'=>null),
                'ES_URL' => array('max_length'=>255,'has_default'=>null),
                'OWNER_ID' => array('type'=>'int4', 'max_length'=>4),
                'MODIFIED_AT' => array('type'=>'timestamp'),
                'CREATED_ON' => array('type'=>'date'),
                'IS_FEATURED' => array('max_length'=>1,'type'=>'bool'),
                'POSITION' => array('max_length'=>4),
                'LOCK_VERSION' => array('type'=>'int4', 'max_length'=>4,'default_value' => 1),
                'EDIT_COUNT' => array('type'=>'int4', 'max_length'=>4,'default_value' => 0),
                );


                break;

            case 'sqlite':
                $this->expected_for_creating_table = array(
                'ID' => array('type'=>'INTEGER', 'max_length'=>-1,'not_null'=>1,'auto_increment'=>1),
                'TITLE' => array('max_length'=>255,'not_null'=>null),
                'BODY' => array('max_length'=>-1,'not_null'=>null,'type'=>'TEXT'),
                'PARENT_ID' => array('has_default'=>1,'default_value'=>0,'not_null'=>1),
                'CREATED_AT' => array('type'=>'DATETIME'),
                );
                $this->expected_for_default_types = array(
                'ID' => array('type'=>'INTEGER', 'max_length'=>-1,'not_null'=>1,'auto_increment'=>1,'primary_key'=>1),
                'NAME' => array('max_length'=>255,'not_null'=>null),
                'SCREEN_NAME' => array('max_length'=>255,'not_null'=>null),
                'DESCRIPTION' => array('max_length'=>-1,'not_null'=>null),
                'EN_URL' => array('max_length'=>255,'not_null'=>null),
                'ES_URL' => array('max_length'=>255,'not_null'=>null),
                'OWNER_ID' => array('type'=>'INTEGER', 'max_length'=>-1),
                'MODIFIED_AT' => array('type'=>'DATETIME'),
                'CREATED_ON' => array('type'=>'DATE'),
                'IS_FEATURED' => array('max_length'=>-1,'type'=>'BOOLEAN'),
                'POSITION' => array('max_length'=>-1),
                'LOCK_VERSION' => array('type'=>'INTEGER', 'max_length'=>-1, 'default_value' => 1),
                'EDIT_COUNT' => array('type'=>'INTEGER', 'max_length'=>-1,'default_value' => 0),
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
                'LOCK_VERSION' => array('type'=>'int', 'max_length'=>11,'default_value' => 1),
                'EDIT_COUNT' => array('type'=>'int', 'max_length'=>11,'default_value' => 0),
                );
                break;
        }
    }


    public function Test_for_creating_table()
    {
        $this->Installer->createTable('test_pages', "
            id integer not null auto_increment primary_key,
            title string(255),
            body text,
            parent_id integer not null default '0',
            created_at datetime,
            updated_at datetime
        ");

        $from_datadict = $this->Installer->db->getColumnDetails('test_pages');

        foreach ($this->expected_for_creating_table as $column=>$details){
            foreach ($details as $index=>$value) {
                $this->assertEqual($this->expected_for_creating_table[$column][$index], $from_datadict[$column]->$index);
            }
        }

        $this->Installer->createTable('test_categories', "
            id integer not null auto_increment primary_key,
            name string(255),
            description text,
            parent_id integer not null default '0'
        ", array('timestamp'=>false));


        $this->Installer->createTable('test_categories_pages', "
            category_id integer not null,
            page_id integer not null
        ", array('timestamp'=>false));

        $this->Installer->createTable('test_nice_urls', "
            id integer not null auto_increment primary_key,
            url string(255),
            context_id integer not null,
            context string(255) not null default 'page'
        ", array('timestamp'=>false));


        $this->Installer->dropTables('test_pages','test_categories');
        $this->Installer->dropTables('test_categories_pages,test_nice_urls');
    }


    public function Test_of_createTable()
    {
        $this->Installer = new AkInstaller();

        $this->assertFalse($this->Installer->tableExists('test_1'));

        $this->Installer->createTable('test_1','id int auto key,body string(32) index,author string(32)');
        $this->assertTrue($this->Installer->tableExists('test_1'));

        $avail_indices = $this->Installer->db->getIndexes('test_1');
        $this->assertTrue(isset($avail_indices['idx_test_1_body']));
        $this->assertFalse($avail_indices['idx_test_1_body']['unique']);
        $this->assertTrue($avail_indices['idx_test_1_body']['columns'][0]=='body');

        $this->Installer->dropTable('test_1');

        $this->Installer->createTable('test_1','id int auto key,body string(32) index');
        $this->assertTrue($this->Installer->tableExists('test_1'));

        $avail_indices = $this->Installer->db->getIndexes('test_1');
        $this->assertTrue(isset($avail_indices['idx_test_1_body']));
        $this->assertFalse($avail_indices['idx_test_1_body']['unique']);
        $this->assertTrue($avail_indices['idx_test_1_body']['columns'][0]=='body');

        $this->Installer->dropTable('test_1');
        $this->assertFalse($this->Installer->tableExists('test_1'));

        $this->Installer->createTable('test_1','id int auto key,body string(32) unique');
        $this->assertTrue($this->Installer->tableExists('test_1'));

        $avail_indices = $this->Installer->db->getIndexes('test_1');
        $this->assertTrue(isset($avail_indices['idx_test_1_body UNIQUE']));
        $this->assertTrue($avail_indices['idx_test_1_body UNIQUE']['unique']);
        $this->assertTrue($avail_indices['idx_test_1_body UNIQUE']['columns'][0]=='body');

        $this->Installer->dropTable('test_1');
        $this->assertFalse($this->Installer->tableExists('test_1'));

        $this->Installer->createTable('test_1','id int auto key,body string(32) full_text',array('mysql'=>'Type=MyISAM'));
        $this->assertTrue($this->Installer->tableExists('test_1'));
        $this->Installer->dropTable('test_1');

    }

    public function Test_of_add_and_remove_Indices()
    {
        $this->Installer = new AkInstaller();

        $this->Installer->createTable('test_1','id int auto key,free string(32),beer string(23)');

        $avail_indices = $this->Installer->db->getIndexes('test_1');

        $this->assertFalse($this->_hasIndexes($avail_indices));

        $this->Installer->addIndex('test_1','beer, free UNIQUE');
        $avail_indices = $this->Installer->db->getIndexes('test_1');
        $this->assertTrue($this->_hasIndexes($avail_indices));
        $this->assertTrue($this->_hasIndexes($avail_indices, 'idx_test_1_beer, free UNIQUE'));
        $this->assertTrue($avail_indices['idx_test_1_beer, free UNIQUE']['unique']);

        $this->Installer->removeIndex('test_1','beer, free UNIQUE');
        $avail_indices = $this->Installer->db->getIndexes('test_1');
        $this->assertFalse(isset($avail_indices['idx_test_1_beer, free UNIQUE']));

        $this->Installer->addIndex('test_1','beer, free UNIQUE','freebeer');
        $avail_indices = $this->Installer->db->getIndexes('test_1');
        $this->assertTrue(isset($avail_indices['freebeer']));
        $this->assertTrue($avail_indices['freebeer']['unique']);
        $this->assertTrue($avail_indices['freebeer']['columns'][0]=='beer');
        $this->assertTrue($avail_indices['freebeer']['columns'][1]=='free');

        $this->Installer->dropIndex('test_1','freebeer');
        $avail_indices = $this->Installer->db->getIndexes('test_1');
        $this->assertFalse($this->_hasIndexes($avail_indices));

        $this->Installer->dropTable('test_1');

    }

    public function Test_of_default_types()
    {
        $this->Installer = new AkInstaller();
        $this->Installer->createTable('test_defaults','id,name,screen_name string,description,*url,owner_id,modified_at,created_on,is_featured,position,lock_version,edit_count');
        $from_datadict = $this->Installer->db->getColumnDetails('test_defaults');

        foreach ($this->expected_for_default_types as $column=>$details){
            foreach ($details as $index=>$value) {
                $this->assertEqual($this->expected_for_default_types[$column][$index], $from_datadict[$column]->$index, "Column '$column', attribute $index = '{$from_datadict[$column]->$index}' instead of expected '{$this->expected_for_default_types[$column][$index]}'");
            }
        }
    }


    public function _hasIndexes($meta_details, $search = false)
    {
        $result = false;
        if(!empty($meta_details) && is_array($meta_details)){
            foreach ($meta_details as $k=>$meta_detail){
                if(empty($search) && strstr($k,'idx')){
                    $result = true;
                }elseif (strstr($k,$search)){
                    $result = true;
                }
            }
        }
        return $result;
    }


    public function test_should_create_default_values_correctly()
    {
        $this->installAndIncludeModels(array('Thumbnail'));
        $Thumbnail = new Thumbnail();
        $this->assertEqual($Thumbnail->get('owner'), 'Picture');
    }

    // see AkDbAdapter_schema for the tests; this is only a wrapper
    public function test_should_rename_columns()
    {
        $this->Installer->renameColumn('test_defaults','screen_name','real_name');
        if ($this->Installer->db->type()=='sqlite') {
            $this->assertError();
        }
        $this->Installer->dropTable('test_defaults');
    }

}

ak_test('Test_of_AkInstaller', true);

?>