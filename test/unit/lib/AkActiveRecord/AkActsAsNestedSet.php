<?php


defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);
require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

require_once(AK_LIB_DIR.DS.'AkActiveRecord.php');

if(!defined('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION')){
    define('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION', false);
}


class test_AkActiveRecord_actsAsNestedSet extends  AkUnitTest
{
    /**/
    var $_testing_models_to_delete = array();
    var $_testing_model_databases_to_delete = array();

    function test_AkActiveRecord_actsAsNestedSet()
    {
        parent::UnitTestCase();
        $this->_createNewTestingModelDatabase('AkTestNestedCategory');
        $this->_createNewTestingModel('AkTestNestedCategory');
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


    /**/

    // New tests for Better Nested Set implementation

    function getLocation($Location)
    {
        if(is_array($Location)){
            return array_values($this->Location->collect($Location,'id','name'));
        }else{
            return $Location->get('name');
        }
    }

    function test_include_locations()
    {
        $this->installAndIncludeModels(array('Location'));
        $this->Location =& new Location();
    }


    function test_getRoot()
    {
        $this->Europe =& $this->Location->create('name->','Europe');

        $this->assertEqual('Europe',$this->getLocation($this->Location->nested_set->getRoot()));
        $this->assertTrue($this->Europe->nested_set->isRoot());


        $this->Spain =& $this->Location->create('name->','Spain');

        $this->Europe->nested_set->addChild($this->Spain);

        $this->assertFalse($this->Spain->nested_set->isRoot());

        $this->assertEqual('Europe',$this->getLocation($this->Spain->nested_set->getRoot()));
    }

    function test_getRoots()
    {
        $this->Oceania =& $this->Location->create('name->','Oceania');
        $Roots = $this->Oceania->nested_set->getRoots();

        $this->assertEqual('Europe',$Roots[0]->name);
        $this->assertEqual('Oceania',$Roots[1]->name);

        $this->Australia =& $this->Location->create('name->','Australia');
        $this->Oceania->nested_set->addChild($this->Australia);

        $Roots = $this->Oceania->nested_set->getRoots();

        $this->assertEqual('Europe',$Roots[0]->name);
        $this->assertEqual('Oceania',$Roots[1]->name);
    }


    function test_getAncestors()
    {
        $this->Valencia =& $this->Location->create('name->','Valencia');
        $this->Spain->nested_set->addChild($this->Valencia);

        $this->Carlet =& $this->Location->create('name->','Carlet');
        $this->Valencia->nested_set->addChild($this->Carlet);


        $this->assertEqual(array('Europe','Spain','Valencia'), $this->getLocation($this->Carlet->nested_set->getAncestors()));
        $this->assertEqual(array('Europe'), $this->getLocation($this->Spain->nested_set->getAncestors()));
    }


    function test_getSelfAndAncestors()
    {
        $this->assertEqual(array('Europe','Spain','Valencia','Carlet'), array_values($this->Location->collect($this->Carlet->nested_set->getSelfAndAncestors(),'id','name')));

        $this->assertEqual(array('Europe','Spain'), array_values($this->Location->collect($this->Spain->nested_set->getSelfAndAncestors(),'id','name')));
    }


    function test_getSiblings()
    {
        $this->Gandia =& $this->Location->create('name->','Gandia');
        $this->Alcudia =& $this->Location->create('name->','Alcudia');
        $this->Daimus =& $this->Location->create('name->','Daimus');

        $this->Valencia->nested_set->addChild($this->Gandia);
        $this->Valencia->nested_set->addChild($this->Alcudia);
        $this->Valencia->nested_set->addChild($this->Daimus);

        $this->assertEqual(array('Gandia','Alcudia','Daimus'), array_values($this->Location->collect($this->Carlet->nested_set->getSiblings(),'id','name')));

        $this->Barcelona =& $this->Location->create('name->','Barcelona');
        $this->Spain->nested_set->addChild($this->Barcelona);

        $this->assertEqual(array('Valencia'), array_values($this->Location->collect($this->Barcelona->nested_set->getSiblings(),'id','name')));

    }


    function test_getSelfAndSiblings()
    {
        $this->assertEqual(array('Carlet','Gandia','Alcudia','Daimus'), $this->getLocation($this->Carlet->nested_set->getSelfAndSiblings()));

        $this->assertEqual(array('Carlet','Gandia','Alcudia','Daimus'), $this->getLocation($this->Alcudia->nested_set->getSelfAndSiblings()));

        $this->assertEqual(array('Valencia','Barcelona'),$this->getLocation($this->Barcelona->nested_set->getSelfAndSiblings()));


    }

    function test_getLevel()
    {
        $this->assertIdentical(0,$this->Europe->nested_set->getLevel());
        $this->assertIdentical(0,$this->Oceania->nested_set->getLevel());
        $this->assertIdentical(1,$this->Spain->nested_set->getLevel());
        $this->assertIdentical(2,$this->Barcelona->nested_set->getLevel());
        $this->assertIdentical(3,$this->Carlet->nested_set->getLevel());
    }

    function test_countChildren()
    {
        $this->Europe->reload();
        $this->Oceania->reload();
        $this->Spain->reload();
        $this->Barcelona->reload();
        $this->Valencia->reload();

        $this->assertIdentical(7,$this->Europe->nested_set->countChildren());
        $this->assertIdentical(1,$this->Oceania->nested_set->countChildren());
        $this->assertIdentical(6,$this->Spain->nested_set->countChildren());
        $this->assertIdentical(0,$this->Barcelona->nested_set->countChildren());
        $this->assertIdentical(4,$this->Valencia->nested_set->countChildren());
    }

    function test_getAllChildren()
    {
        $this->assertEqual(array('Carlet','Gandia','Alcudia','Daimus'), $this->getLocation($this->Valencia->nested_set->getAllChildren()));
        $this->assertEqual(array('Valencia','Carlet','Gandia','Alcudia','Daimus','Barcelona'), $this->getLocation($this->Spain->nested_set->getAllChildren()));
        $this->assertEqual(array('Spain','Valencia','Carlet','Gandia','Alcudia','Daimus','Barcelona'), $this->getLocation($this->Europe->nested_set->getAllChildren()));

    }

    function test_getAllChildren_excuding_some()
    {
        $this->assertEqual(array('Spain','Barcelona'), $this->getLocation($this->Europe->nested_set->getAllChildren($this->Valencia)));
        $this->assertEqual(array('Spain','Barcelona'), $this->getLocation($this->Europe->nested_set->getAllChildren($this->Valencia->id)));
        $this->assertEqual(array('Spain','Barcelona'), $this->getLocation($this->Europe->nested_set->getAllChildren(array($this->Valencia))));
        $this->assertEqual(array('Spain','Barcelona'), $this->getLocation($this->Europe->nested_set->getAllChildren(array($this->Valencia->id))));
        $this->assertEqual(array('Alcudia','Daimus'), $this->getLocation($this->Valencia->nested_set->getAllChildren($this->Carlet,$this->Gandia)));
        $this->assertEqual(array('Alcudia','Daimus'), $this->getLocation($this->Valencia->nested_set->getAllChildren($this->Carlet->id,$this->Gandia->id)));
        $this->assertEqual(array('Alcudia','Daimus'), $this->getLocation($this->Valencia->nested_set->getAllChildren(array($this->Carlet,$this->Gandia))));
        $this->assertEqual(array('Alcudia','Daimus'), $this->getLocation($this->Valencia->nested_set->getAllChildren(array($this->Carlet->id,$this->Gandia->id))));
        $this->assertEqual(array('Alcudia','Daimus'), $this->getLocation($this->Valencia->nested_set->getAllChildren(array($this->Carlet->id,$this->Gandia))));
    }


    function test_getFullSet()
    {
        $this->assertEqual(array('Europe','Spain','Barcelona'), $this->getLocation($this->Europe->nested_set->getFullSet($this->Valencia)));
        $this->assertEqual(array('Valencia','Carlet','Gandia','Alcudia','Daimus'), $this->getLocation($this->Valencia->nested_set->getFullSet()));
    }

    function test_moveToLeftOf()
    {
        $this->Alcudia->nested_set->moveToLeftOf($this->Gandia);
        $this->assertEqual(array('Carlet','Alcudia','Gandia','Daimus'), $this->getLocation($this->Valencia->nested_set->getAllChildren()));

        $this->Carlet->reload();
        $this->Spain->reload();
        $this->Valencia->reload();

        $this->Carlet->nested_set->moveToLeftOf($this->Spain->id);
        $this->assertEqual(array('Alcudia','Gandia','Daimus'), $this->getLocation($this->Valencia->nested_set->getAllChildren()));
        $this->assertEqual(array('Carlet'), $this->getLocation($this->Spain->nested_set->getSiblings()));
    }


    function test_moveToRightOf()
    {
        $this->Alcudia->reload();
        $this->Gandia->reload();
        $this->Valencia->reload();

        $this->Alcudia->nested_set->moveToRightOf($this->Gandia);
        $this->assertEqual(array('Gandia','Alcudia','Daimus'), $this->getLocation($this->Valencia->nested_set->getAllChildren()));

        $this->Carlet->reload();
        $this->Alcudia->reload();

        $this->Carlet->nested_set->moveToRightOf($this->Alcudia->id);

        $this->Valencia->reload();
        $this->Spain->reload();

        $this->assertEqual(array('Gandia','Alcudia','Carlet','Daimus'), $this->getLocation($this->Valencia->nested_set->getAllChildren()));
        $this->assertEqual(array('Spain'), $this->getLocation($this->Spain->nested_set->getSelfAndSiblings()));
    }


    function test_moveToChildOf()
    {
        $this->Oceania->reload();
        $this->Spain->nested_set->moveToChildOf($this->Oceania);
        $this->Spain->reload();
        $this->assertEqual(array('Australia'), $this->getLocation($this->Spain->nested_set->getSiblings()));

        $this->Europe->reload();
        $this->Spain->nested_set->moveToChildOf($this->Europe);
        $this->Spain->reload();
        $this->assertEqual(array('Spain'), $this->getLocation($this->Spain->nested_set->getSelfAndSiblings()));

        $this->Europe->reload();
        $this->Oceania->reload();
        $this->World =& $this->Location->create('name->','World');
        $this->Oceania->nested_set->moveToChildOf($this->World);
        $this->World->reload();
        $this->Europe->nested_set->moveToChildOf($this->World);
        $this->Europe->reload();
        $this->Oceania->reload();
        $this->assertEqual('World',$this->getLocation($this->Europe->nested_set->getRoot()));
        $this->assertEqual('World',$this->getLocation($this->Oceania->nested_set->getRoot()));
        $this->assertEqual(array('Europe','Oceania'), $this->getLocation($this->Europe->nested_set->getSelfAndSiblings()));

    }

    function test_of_countChildren()
    {
        $this->Spain->reload();
        $this->Oceania->reload();
        $this->World->reload();
        $this->assertEqual(6, $this->Spain->nested_set->countChildren());
        $this->assertEqual(1, $this->Oceania->nested_set->countChildren());
        $this->assertEqual(10, $this->World->nested_set->countChildren());
    }


    function test_of_getParent()
    {
        $this->assertEqual('World',$this->getLocation($this->Europe->nested_set->getParent()));
        $this->assertEqual('Europe',$this->getLocation($this->Spain->nested_set->getParent()));
        $this->assertEqual(false,$this->World->nested_set->getParent());
    }



    function test_of_getParents()
    {
        $this->Valencia->reload();
        $this->assertEqual(array('World','Europe','Spain'),$this->getLocation($this->Valencia->nested_set->getParents()));
        $this->assertEqual(false,$this->World->nested_set->getParents());
    }


    function Test_of_isChild()
    {
        $this->assertTrue($this->Carlet->nested_set->isChild());
        $this->assertFalse($this->World->nested_set->isChild());
        $this->assertTrue($this->Valencia->nested_set->isChild());
    }


    function test_deletions_with_children()
    {
        $this->assertEqual(6, $this->Spain->nested_set->countChildren());
        $this->Valencia->destroy();
        $this->Spain->reload();
        $this->assertEqual(1, $this->Spain->nested_set->countChildren());
    }

    function test_deletions_without_children()
    {
        $this->Barcelona->reload();
        $this->Barcelona->destroy();
        $this->Spain->reload();
        $this->assertEqual(0, $this->Spain->nested_set->countChildren());
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
    /**/
}


ak_test('test_AkActiveRecord_actsAsNestedSet',true);

?>
