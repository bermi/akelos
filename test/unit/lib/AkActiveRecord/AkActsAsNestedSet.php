<?php


defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);
require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

require_once(AK_LIB_DIR.DS.'AkActiveRecord.php');

if(!defined('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION')){
    define('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION', false);
}


class test_AkActiveRecord_actsAsNestedSet extends  UnitTestCase
{
    var $_testing_models_to_delete = array();
    var $_testing_model_databases_to_delete = array();

    function test_AkActiveRecord_actsAsNestedSet()
    {
        parent::UnitTestCase();
        $this->_createNewTestingModelDatabase('AkTestNestedCategory');
        $this->_createNewTestingModel('AkTestNestedCategory');        
    }

    function setUp()
    {
    }

    function tearDown()
    {
        unset($_SESSION['__activeRecordColumnsSettingsCache']);
    }

    function _createNewTestingModel($test_model_name)
    {

        static $shutdown_called;
        switch ($test_model_name) {

            case 'AkTestNestedCategory':
            $model_source =
            '<?php
    class AkTestNestedCategory extends AkActiveRecord 
    {
        var $act_as = "nested_set";
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
            case 'ak_test_nested_categories':
            $table =
            array(
            'table_name' => 'ak_test_nested_categories',
            'fields' =>
            'id I AUTO KEY,
            lft I(11),
            rgt I(11),
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

    function Test_of_actsAsNestedSet_instatiation()
    {
        $Categories =& new AkTestNestedCategory();
        $this->assertEqual($Categories->actsLike(), 'active record,nested set');

        $this->assertEqual($Categories->nested_set->_parent_column_name,'parent_id');
        $this->assertEqual($Categories->nested_set->_left_column_name,'lft');
        $this->assertEqual($Categories->nested_set->_right_column_name,'rgt');

        $Categories =& new AkTestNestedCategory();

        $this->assertErrorPattern('/columns are required/',$Categories->actsAs('nested_set', array('parent_column'=>'not_available')));

        $this->assertEqual($Categories->actsLike(), 'active record');

    }

    function Test_of_Test_of_init()
    {
        $Categories =& new AkTestNestedCategory();
        $Categories->nested_set->init(array('scope'=>array('category_id = ? AND completed = 0',$Categories->getId()),'custom_attribute'=>'This is not allowed here'));

        $this->assertEqual($Categories->nested_set->getScopeCondition(), array ( 0 => 'category_id = ? AND completed = 0', 1 => null));
        $this->assertTrue(empty($Categories->nested_set->custom_attribute));
    }

    
    function Test_of__ensureIsActiveRecordInstance()
    {
        $Categories =& new AkTestNestedCategory();
        $Object =& new AkObject();
        $this->assertErrorPattern('/is not an active record/',$Categories->nested_set->_ensureIsActiveRecordInstance(&$Object));
    }

    function Test_of_getType()
    {
        $Categories =& new AkTestNestedCategory();
        $this->assertEqual($Categories->nested_set->getType(), 'nested set');
    }


    function Test_of_getScopeCondition_and_setScopeCondition()
    {
        $Categories =& new AkTestNestedCategory();
        $this->assertEqual($Categories->nested_set->getScopeCondition(), (substr($Categories->_db->databaseType,0,4) == 'post') ? 'true' : '1');
        $Categories->nested_set->setScopeCondition('true');
        $this->assertEqual($Categories->nested_set->getScopeCondition(), 'true');
    }

    function Test_of_getters_and_setters()
    {
        $Categories =& new AkTestNestedCategory();

        $Categories->nested_set->setLeftColumnName('column_name');
        $this->assertEqual($Categories->nested_set->getLeftColumnName(), 'column_name');

        $Categories->nested_set->setRightColumnName('column_name');
        $this->assertEqual($Categories->nested_set->getRightColumnName(), 'column_name');

        $Categories->nested_set->setParentColumnName('column_name');
        $this->assertEqual($Categories->nested_set->getParentColumnName(), 'column_name');
    }

    function Test_of_isRoot()
    {        
        $Categories =& new AkTestNestedCategory();
        $this->assertFalse($Categories->nested_set->isRoot());

        $Categories->description = "Root node";
        $Categories->save();

        $Categories->nested_set->addChild($Categories);

        $this->assertTrue($Categories->nested_set->isRoot());

        $this->assertEqual($Categories->lft, 2);
        $this->assertEqual($Categories->rgt, 3);
        $this->assertEqual($Categories->parent_id, 1);

        $Category_1 = $Categories->create(array('description'=>'Category 1'));

        $Categories->nested_set->addChild($Category_1);

        $this->assertFalse($Category_1->nested_set->isRoot());

        $Categories =& new AkTestNestedCategory();
        $Categories->nested_set->setScopeCondition(" department = 'sales' ");

        $Categories->department = 'sales';
        $Categories->description = 'Sales root node';

        $Categories->save();

        $Categories->nested_set->addChild($Categories);

        $this->assertTrue($Categories->nested_set->isRoot());

        $this->assertEqual($Categories->lft, $Categories->getId()+1);
        $this->assertEqual($Categories->rgt, $Categories->getId()+2);
        $this->assertEqual($Categories->parent_id, $Categories->getId());

        $SalesCategory_1_1 = $Categories->create(array('description'=>'Sales Category 1', 'department' => 'sales'));

        $Categories->nested_set->addChild($SalesCategory_1_1);

        $this->assertFalse($SalesCategory_1_1->nested_set->isRoot());
    }

    function Test_of_isChild()
    {        
        $Categories =& new AkTestNestedCategory();
        $this->assertFalse($Categories->nested_set->isChild());

        $Categories =& new AkTestNestedCategory(1);

        $this->assertFalse($Categories->nested_set->isChild());

        $Category_1 =& new AkTestNestedCategory(2);

        $this->assertTrue($Category_1->nested_set->isChild());
        
        $Categories =& new AkTestNestedCategory(3);

        $this->assertFalse($Categories->nested_set->isChild());

        $SalesCategory_1_1 =& new AkTestNestedCategory(4);

        $this->assertTrue($SalesCategory_1_1->nested_set->isChild());

    }

    function Test_of_isUnknown()
    {
        $Categories =& new AkTestNestedCategory();
        $this->assertTrue($Categories->nested_set->isUnknown());

        $Categories =& new AkTestNestedCategory(1);

        $this->assertFalse($Categories->nested_set->isUnknown());

        $Category_1 =& new AkTestNestedCategory(2);

        $this->assertFalse($Category_1->nested_set->isUnknown());

    }

    function Test_of_addChild()
    {        
        $Categories =& new AkTestNestedCategory();
        
        $Categories->destroy(3);
        $RootNode =& new AkTestNestedCategory(1);
        

        $Category_2 = $RootNode->nested_set->addChild($Categories->create(array('description'=>'Category 2')));
        $Category_3 = $RootNode->nested_set->addChild($Categories->create(array('description'=>'Category 3')));
        $Category_4 = $RootNode->nested_set->addChild($Categories->create(array('description'=>'Category 4')));

        $this->assertFalse($Category_2->nested_set->isUnknown());
        $this->assertFalse($Category_3->nested_set->isUnknown());
        $this->assertFalse($Category_4->nested_set->isUnknown());
        
        $this->assertTrue($Category_2->lft == 5 && $Category_2->rgt == 6 && $Category_2->parent_id == 1);
        $this->assertTrue($Category_3->lft == 7 && $Category_3->rgt == 8 && $Category_2->parent_id == 1);
        $this->assertTrue($Category_4->lft == 9 && $Category_4->rgt == 10 && $Category_2->parent_id == 1);
    
        $this->assertErrorPattern('/supported/', $Category_3->nested_set->addChild($Category_2));
    }

    function Test_of_childrenCount()
    {        
        $Categories =& new AkTestNestedCategory();
        
        $Category_1 =& new AkTestNestedCategory(2);

        for ($i=1; $i <= 10; $i++){
            $var_name = 'Category_1_'.$i;
            $$var_name = $Categories->create(array('description'=>'Category 1.'.$i));
            $Category_1->nested_set->addChild($$var_name);
            $$var_name->reload();
        }


        $RootNode =& new AkTestNestedCategory(1);
        
        $this->assertEqual($Category_1->nested_set->childrenCount(), 10);
        $this->assertEqual($RootNode->nested_set->childrenCount(), 14);
        $this->assertEqual($Category_1_1->nested_set->childrenCount(), 0);
        $this->assertEqual($Category_1_5->nested_set->childrenCount(), 0);
        $this->assertEqual($Category_1_10->nested_set->childrenCount(), 0);
        
    }

    function Test_of_fullSet()
    {
        $Categories =& new AkTestNestedCategory();
        $this->assertFalse($Categories->nested_set->fullSet());

        $Categories = $Categories->find('first',array('conditions'=>array('description = ?','Category 1.5')));

        $ChildNodes = $Categories->nested_set->fullSet();
        
        $this->assertEqual(Ak::size($ChildNodes)-1,  $Categories->nested_set->childrenCount());

        $Categories =& new AkTestNestedCategory(1);
        $ChildNodes = $Categories->nested_set->fullSet();
        
        $this->assertEqual(Ak::size($ChildNodes)-1,  $Categories->nested_set->childrenCount());

        $Categories->nested_set->setScopeCondition(" department = 'sales' ");
        $this->assertFalse($Categories->nested_set->fullSet());

    }

    function Test_of_allChildren()
    {
        $Categories =& new AkTestNestedCategory();
        $this->assertFalse($Categories->nested_set->allChildren());

        $Categories = $Categories->find('first',array('conditions'=>array('description = ?','Category 1.5')));
        $ChildNodes = $Categories->nested_set->allChildren();
        $this->assertEqual(Ak::size($ChildNodes),  $Categories->nested_set->childrenCount());

        $Categories =& new AkTestNestedCategory(1);
        $ChildNodes = $Categories->nested_set->allChildren();
        $this->assertEqual(Ak::size($ChildNodes),  $Categories->nested_set->childrenCount());

        $Categories->nested_set->setScopeCondition(" department = 'sales' ");
        $this->assertFalse($Categories->nested_set->allChildren());
    }

    function Test_of_directChildren()
    {
        $Categories =& new AkTestNestedCategory();
        $this->assertFalse($Categories->nested_set->directChildren());

        $Categories = $Categories->find('first',array('conditions'=>array('description = ?','Category 1.5')));
        $ChildNodes = $Categories->nested_set->directChildren();
        $this->assertEqual(Ak::size($ChildNodes),  $Categories->nested_set->childrenCount());

        $Categories =& new AkTestNestedCategory(1); 
        $ChildNodes = $Categories->nested_set->directChildren();
        $this->assertEqual(Ak::size($ChildNodes),  5);

        $Categories = $Categories->find('first',array('conditions'=>array('description = ?','Category 1')));
        $ChildNodes = $Categories->nested_set->directChildren();
        $this->assertEqual(Ak::size($ChildNodes),  10);

        $Categories->nested_set->setScopeCondition(" department = 'sales' ");
        $this->assertFalse($Categories->nested_set->allChildren());

    }

    function Test_of_getParent()
    {
        $Categories =& new AkTestNestedCategory();
        $this->assertFalse($Categories->nested_set->getParent());

        $Categories =& new AkTestNestedCategory(1);
        $this->assertFalse($Categories->nested_set->getParent());

        $Categories = $Categories->find('first',array('conditions'=>array('description = ?','Category 1')));
        $Root = $Categories->nested_set->getParent();
        $this->assertTrue($Root->nested_set->isRoot());

        $Categories = $Categories->find('first',array('conditions'=>array('description = ?','Category 1.5')));
        $Child = $Categories->nested_set->getParent();
        $this->assertTrue($Child->nested_set->isChild());

        $this->assertEqual($Child->id, 2);
    }

    function Test_of_getParents()
    {
        $Categories =& new AkTestNestedCategory();
        $this->assertFalse($Categories->nested_set->getParents());

        $Categories =& new AkTestNestedCategory(1);
        $this->assertFalse($Categories->nested_set->getParents());

        $Categories =& new AkTestNestedCategory(2);
        $Root = $Categories->nested_set->getParents();
        $this->assertTrue($Root[0]->nested_set->isRoot());
        
        $Categories = $Categories->find('first',array('conditions'=>array('description = ?','Category 1.5')));
        $Root = $Categories->nested_set->getParents();

        $this->assertTrue(($Root[1]->nested_set->isRoot() && $Root[0]->nested_set->isChild()) || ($Root[0]->nested_set->isRoot() && $Root[1]->nested_set->isChild()));
        
    }

    function Test_of_beforeDestroy()
    {
        
        $Categories =& new AkTestNestedCategory(1);
        $this->assertEqual($Categories->NestedSet->childrenCount(), 14);

        $Categories->destroy(15);
        $Categories->reload();
        $this->assertEqual($Categories->NestedSet->childrenCount(), 13);
        
        $Categories->destroy(2);
        $Categories->reload();
        $this->assertEqual($Categories->NestedSet->childrenCount(), 3);
        
        $Categories->destroy(1);
        
        if($Categories->reload()){
            $this->assertEqual($Categories->NestedSet->childrenCount(), 0);
        }

    }
  /**/  
    function _resetTable()
    {
        $this->_deleteTestingModelDatabases();
        $this->_createNewTestingModelDatabase('AkTestNestedCategory');
    }
    
    function _getNestedSetList($Categories = null, $breadcrumb = false)
    {
        if(!isset($Categories)){
            $Categories = new AkTestNestedCategory();
            $Categories = $Categories->find('all',array('conditions'=>$Categories->nested_set->getScopeCondition(),'order'=>' lft ASC '));
        }
        $list = array();
        foreach ($Categories as $Category){
            $bread_crumb = '';
            if($Parents = $Category->nested_set->getParents()){
                foreach ($Parents as $Parent){
                    $bread_crumb .= $Parent->description.' > ';
                }
            }
            if($breadcrumb){
                $list[] = $bread_crumb."(".$Category->id.")".$Category->description;//
            }else{
                $list[$Category->parent_id][$Category->id] = $Category->lft.' &lt;- '.$Category->description.' -&gt;'.$Category->rgt;// getAttributes();
            }   
        }
        return $list;
    }
}


Ak::test('test_AkActiveRecord_actsAsNestedSet',true);

?>
