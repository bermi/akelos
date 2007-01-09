<?php

defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);
require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

require_once(AK_LIB_DIR.DS.'AkActiveRecord.php');

if(!defined('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION')){
    define('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION', false);
}


class test_AkActiveRecord_actAsTree extends  UnitTestCase
{
    var $_testing_models_to_delete = array();
    var $_testing_model_databases_to_delete = array();

    function test_AkActiveRecord_actAsTree()
    {
        parent::UnitTestCase();
        $this->_createNewTestingModelDatabase('AkTestCategory');
        $this->_createNewTestingModel('AkTestCategory');
        $this->_createNewTestingModel('AkDependentTestCategory');         
    }

    function setUp()
    {
        $this->_resetTable();
    }

    function tearDown()
    {
        unset($_SESSION['__activeRecordColumnsSettingsCache']);
    }

    function _createNewTestingModel($test_model_name)
    {

        static $shutdown_called;
        switch ($test_model_name) {

            case 'AkTestCategory':
            $model_source =
            '<?php
    class AkTestCategory extends AkActiveRecord 
    {
        var $act_as = "tree";
    } 
?>';
            break;
            
                        case 'AkDependentTestCategory':
            $model_source =
            '<?php
    class AkDependentTestCategory extends AkActiveRecord 
    {
        var $act_as = array("tree" => array("dependent" => true));
        var $table_name = "ak_test_categories";
    } 
?>';
            break;

            default:
            $model_source = '<?php class '.$test_model_name.' extends AkActiveRecord { } ?>';
            break;
        }

        $file_name = AkInflector::toModelFilename($test_model_name);

        if(!Ak::file_put_contents($file_name,$model_source)){
            die('Ooops!, in order to perform this test, you must set your app/model permissions so this can script can create and delete files into/from it');
        }
        if(!in_array($file_name, get_included_files()) && !class_exists($test_model_name)){
            include($file_name);
        }else {
            return false;
        }
        $this->_testing_models_to_delete[] = $file_name;
        if(!isset($shutdown_called)){
            $shutdown_called = true;
            register_shutdown_function(array(&$this,'_deleteTestingModels'));
        }
        return true;
    }

    function _deleteTestingModels()
    {
        foreach ($this->_testing_models_to_delete as $file){
            Ak::file_delete($file);
        }
    }




    function _createNewTestingModelDatabase($test_model_name)
    {
        static $shutdown_called;
        // Create a data dictionary object, using this connection
        $db =& AK::db();
        //$db->debug = true;
        $table_name = AkInflector::tableize($test_model_name);
        if(in_array($table_name, (array)$db->MetaTables())){
            return false;
        }
        switch ($table_name) {
            case 'ak_test_categories':
            $table =
            array(
            'table_name' => 'ak_test_categories',
            'fields' =>
            'id I AUTO KEY,
            parent_id I(11),
            description C(250),
            department C(25)',
            'index_fileds' => 'id',
            'table_options' => array('mysql' => 'TYPE=InnoDB', 'REPLACE')
            );

            break;
            default:
            return false;
            break;
        }

        $dict = NewDataDictionary($db);
        $sqlarray = $dict->CreateTableSQL($table['table_name'], $table['fields'], $table['table_options']);
        $dict->ExecuteSQLArray($sqlarray);
        if(isset($table['index_fileds'])){
            $sqlarray = $dict->CreateIndexSQL('idx_'.$table['table_name'], $table['table_name'], $table['index_fileds']);
            $dict->ExecuteSQLArray($sqlarray);
        }

        $db->CreateSequence('seq_'.$table['table_name']);

        $this->_testing_model_databases_to_delete[] = $table_name;
        if(!isset($shutdown_called)){
            $shutdown_called = true;
            register_shutdown_function(array(&$this,'_deleteTestingModelDatabases'));
        }
        //$db->debug = false;
        return true;
    }

    function _deleteTestingModelDatabases()
    {
        $db =& AK::db();
        foreach ($this->_testing_model_databases_to_delete as $table_name){
            $db->Execute('DROP TABLE '.$table_name);
            $db->DropSequence('seq_'.$table_name);
        }
    }

    function Test_of_actsAsTree_instatiation()
    {
        $Categories =& new AkTestCategory();
        $this->assertEqual($Categories->actsLike(), 'active record,tree');

        $this->assertEqual($Categories->tree->_parent_column_name,'parent_id');

        $Categories =& new AkTestCategory();

        $this->assertErrorPattern('/columns are required/',$Categories->actsAs('tree', array('parent_column'=>'not_available')));

        $this->assertEqual($Categories->actsLike(), 'active record');

    }

    function Test_of_Test_of_init()
    {
        $Categories =& new AkTestCategory();
        $Categories->tree->init(array('scope'=>array('category_id = ? AND completed = 0',$Categories->getId()),'custom_attribute'=>'This is not allowed here'));

        $this->assertEqual($Categories->tree->getScopeCondition(), array ( 0 => 'category_id = ? AND completed = 0', 1 => null));
        $this->assertTrue(empty($Categories->tree->custom_attribute));
    }

    
    function Test_of__ensureIsActiveRecordInstance()
    {
        $Categories =& new AkTestCategory();
        $Object =& new AkObject();
        $this->assertErrorPattern('/is not an active record/',$Categories->tree->_ensureIsActiveRecordInstance(&$Object));
    }

    function Test_of_getType()
    {
        $Categories =& new AkTestCategory();
        $this->assertEqual($Categories->tree->getType(), 'tree');
    }


    function Test_of_getScopeCondition_and_setScopeCondition()
    {
        $Categories =& new AkTestCategory();
        $this->assertEqual($Categories->tree->getScopeCondition(), (substr($Categories->_db->databaseType,0,4) == 'post') ? 'true' : '1');
        $Categories->tree->setScopeCondition('true');
        $this->assertEqual($Categories->tree->getScopeCondition(), 'true');
    }

    function Test_of_getters_and_setters()
    {
        $Categories =& new AkTestCategory();

        $Categories->tree->setParentColumnName('column_name');
        $this->assertEqual($Categories->tree->getParentColumnName(), 'column_name');
        
        $Categories->tree->setDependent(true);
        $this->assertTrue($Categories->tree->getDependent());
        $Categories->tree->setDependent(false);
        $this->assertFalse($Categories->tree->getDependent());
    }

    function Test_of_hasChildren_and_hasParent()
    {        
        $CategoryA =& new AkTestCategory();
        $CategoryA->description = "Cat A";
        
        $CategoryAa =& new AkTestCategory();
        $CategoryAa->description = "Cat Aa";
        
        $this->assertFalse($CategoryA->tree->hasChildren());
        $this->assertFalse($CategoryA->tree->hasParent());
        $this->assertFalse($CategoryAa->tree->hasChildren());
        $this->assertFalse($CategoryAa->tree->hasParent());
        
        $CategoryA->tree->addChild($CategoryAa);
        
        $this->assertTrue($CategoryA->tree->hasChildren());
        $this->assertFalse($CategoryA->tree->hasParent());
        $this->assertFalse($CategoryAa->tree->hasChildren());
        $this->assertTrue($CategoryAa->tree->hasParent());
    }


    function Test_of_addChild_and_children()
    {        
        $CategoryA =& new AkTestCategory();
        $CategoryA->description = "Cat A";
        
        $CategoryAa =& new AkTestCategory();
        $CategoryAa->description = "Cat Aa";
        
        $CategoryAb =& new AkTestCategory();
        $CategoryAb->description = "Cat Ab";
        
        $CategoryA->tree->addChild($CategoryAa);
        $CategoryA->tree->addChild($CategoryAb);
        
        $children = $CategoryA->tree->getChildren();
        $this->assertEqual($CategoryAa->getId(), $children[0]->getId());
        $this->assertEqual($CategoryAb->getId(), $children[1]->getId());
        
        $this->assertErrorPattern('/Cannot add myself as a child to myself/', $CategoryA->tree->addChild($CategoryA));
    }

    function Test_of_childrenCount()
    {
        $CategoryA =& new AkTestCategory();
        $CategoryA->description = "Cat A";
        
        $CategoryB =& new AkTestCategory();
        $CategoryB->description = "Cat B";
        
        $CategoryAa =& new AkTestCategory();
        $CategoryAa->description = "Cat Aa";
        
        $CategoryAb =& new AkTestCategory();
        $CategoryAb->description = "Cat Ab";

        $CategoryA->tree->addChild($CategoryAa);
        $CategoryA->tree->addChild($CategoryAb);
        
        $this->assertEqual(2, $CategoryA->tree->childrenCount());
        $this->assertEqual(0, $CategoryB->tree->childrenCount());
        $this->assertEqual(0, $CategoryAa->tree->childrenCount());
        $this->assertEqual(0, $CategoryAb->tree->childrenCount());        
    }
    
    function Test_of_parent()
    {
        $CategoryA =& new AkTestCategory();
        $CategoryA->description = "Cat A";
        
        $CategoryAa =& new AkTestCategory();
        $CategoryAa->description = "Cat Aa";
        
        $CategoryAb =& new AkTestCategory();
        $CategoryAb->description = "Cat Ab";

        $CategoryA->tree->addChild($CategoryAa);
        $CategoryA->tree->addChild($CategoryAb);
        
        $catAaParent = $CategoryAa->tree->getParent();
        $catAbParent = $CategoryAb->tree->getParent();
        $this->assertEqual($CategoryA->getId(), $catAaParent->getId());
        $this->assertEqual($CategoryA->getId(), $catAbParent->getId());
    }

    function Test_of_beforeDestroy()
    {
        $CategoryA =& new AkDependentTestCategory();
        $CategoryA->description = "Cat A";
        
        $CategoryB =& new AkTestCategory();
        $CategoryB->description = "Cat B";
        
        $CategoryAa =& new AkDependentTestCategory();
        $CategoryAa->description = "Cat Aa";
        
        $CategoryBa =& new AkTestCategory();
        $CategoryBa->description = "Cat Ba";

        $CategoryA->tree->addChild($CategoryAa);
        $CategoryB->tree->addChild($CategoryBa);
        
        $CategoryA->destroy();
        $this->assertFalse($CategoryAa->reload());
        
        $CategoryB->destroy();
        $this->assertTrue($CategoryBa->reload());
        $this->assertFalse($CategoryBa->tree->hasParent());
    }


    function _resetTable()
    {
        $this->_deleteTestingModelDatabases();
        $this->_createNewTestingModelDatabase('AkTestCategory');
    }
    
}


Ak::test('test_AkActiveRecord_actAsTree',true);

?>