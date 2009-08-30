<?php

defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);
require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

class test_PostgreSQL_datatype_migration extends AkUnitTest
{
    public $WeHaveAPostgreSqlEnvironment;

    public function test_init()
    {
        $this->db =& Ak::db();
        $this->WeHaveAPostgreSqlEnvironment = $this->db->type() === 'postgre';
    }

    public function test_integers_and_booleans_should_not_be_numerics()
    {
        if (!$this->WeHaveAPostgreSqlEnvironment) return;
        $this->installAndIncludeModels(array(
        'TestPage'=> "id,
                          parent_id integer,
                          is_public boolean"
                          ));

                          $from_datadict = $this->db->getColumnDetails('test_pages');
                          $this->assertEqual($from_datadict['PARENT_ID']->type, 'int4');
                          $this->assertEqual($from_datadict['IS_PUBLIC']->type, 'bool');
    }

    public function test_should_return_simplified_akelos_datatypes()
    {
        if (!$this->WeHaveAPostgreSqlEnvironment) return;
        $this->installAndIncludeModels(array('TestPage'=>"id,parent_id integer,is_public boolean"));
        $from_datadict = $this->TestPage->getColumnSettings();
        $this->assertEqual($from_datadict['parent_id']['type'], 'integer');
        $this->assertEqual($from_datadict['is_public']['type'], 'boolean');
    }


    public function test_should_migrate_integers()
    {
        if (!$this->WeHaveAPostgreSqlEnvironment) return;
        // mimic OLD boolean and integer behavior!
        $this->installAndIncludeModels(array('TestPage'=>"id,parent_id n(11.0),is_public n(1)"));
        $from_datadict = $this->db->getColumnDetails('test_pages');
        $this->assertEqual($from_datadict['PARENT_ID']->type, 'numeric');
        $this->assertEqual($from_datadict['PARENT_ID']->max_length, 11);
        $this->assertEqual($from_datadict['PARENT_ID']->scale, 0);
        $this->assertEqual($from_datadict['IS_PUBLIC']->type, 'numeric');
        $this->assertEqual($from_datadict['IS_PUBLIC']->max_length, 1);
        $this->assertEqual($from_datadict['IS_PUBLIC']->scale, 0);

        // we insert some data, not using ActiveRecord
        $this->db->execute('INSERT INTO test_pages (is_public) VALUES (1)');
        $this->db->execute('INSERT INTO test_pages (is_public) VALUES (0)');
        $this->db->execute('INSERT INTO test_pages (parent_id) VALUES (1)');        // we want is_public = NULL

        $data = $this->db->select('SELECT * FROM test_pages');
        $expected = array(
        array('id'=>1,'parent_id'=>null,'is_public'=>1),
        array('id'=>2,'parent_id'=>null,'is_public'=>0),
        array('id'=>3,'parent_id'=>1,'is_public'=>null)
        );
        $this->assertEqual($data,$expected);

        // now we migrate
        $installer = new AkInstaller();

        $installer->transactionStart();
        // if the following fails, you're on Postgre 7 and you have to do it just like we'll do it with the boolean-field
        // except you can use the CAST-function:
        // UPDATE test_pages SET parent_id_temp = CAST(parent_id AS integer)
        $installer->execute('ALTER TABLE test_pages ALTER COLUMN parent_id TYPE integer');

        $installer->addColumn('test_pages','is_public_temp boolean');
        $installer->execute(
        'UPDATE test_pages
             SET is_public_temp =
                     CASE is_public
                       WHEN 0 THEN false
                       WHEN 1 THEN true
                       ELSE NULL
                     END');
        $installer->removeColumn('test_pages','is_public');
        $installer->renameColumn('test_pages','is_public_temp','is_public');
        $installer->transactionComplete();

        // let's see what we got
        $from_datadict = $this->db->getColumnDetails('test_pages');
        $this->assertEqual($from_datadict['PARENT_ID']->type, 'int4');
        $this->assertEqual($from_datadict['IS_PUBLIC']->type, 'bool');

        $data = $this->db->select('SELECT * FROM test_pages');
        $expected = array(
        array('id'=>1,'parent_id'=>null,'is_public'=>'t'),
        array('id'=>2,'parent_id'=>null,'is_public'=>'f'),
        array('id'=>3,'parent_id'=>1,'is_public'=>null)
        );
        $this->assertEqual($data,$expected);

        // ok, we're done
        $installer->dropTable('test_pages');
    }

}

ak_test('test_PostgreSQL_datatype_migration',true);
?>