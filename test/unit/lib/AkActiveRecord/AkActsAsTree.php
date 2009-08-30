<?php

defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);
require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

if(!defined('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION')){
    define('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION', false);
}

class test_AkActiveRecord_actAsTree extends  AkUnitTest
{

    public function test_start()
    {
        $this->installAndIncludeModels(array(
        'Category'=>'id, parent_id, description, department string(25)'
        ));
        Ak::import('DependentCategory');
    }

    public function Test_of_actsAsTree_instatiation()
    {
        $Categories = new Category();
        $this->assertEqual($Categories->actsLike(), 'active record,tree');

        $this->assertEqual($Categories->tree->_parent_column_name,'parent_id');

        $Categories = new Category();

        $this->assertErrorPattern('/columns are required/',$Categories->actsAs('tree', array('parent_column'=>'not_available')));

        $this->assertEqual($Categories->actsLike(), 'active record');

    }

    public function Test_of_Test_of_init()
    {
        $Categories = new Category();
        $Categories->tree->init(array('scope'=> 'category_id = ? AND completed = 0','custom_attribute'=>'This is not allowed here'));

        $this->assertEqual($Categories->tree->getScopeCondition(), 'category_id = null AND completed = 0');
        $this->assertTrue(empty($Categories->tree->custom_attribute));
    }


    public function Test_of__ensureIsActiveRecordInstance()
    {
        $Categories = new Category();
        $Object = new AkObject();
        $this->assertErrorPattern('/is not an active record/',$Categories->tree->_ensureIsActiveRecordInstance($Object));
    }

    public function Test_of_getType()
    {
        $Categories = new Category();
        $this->assertEqual($Categories->tree->getType(), 'tree');
    }


    public function Test_of_getScopeCondition_and_setScopeCondition()
    {
        $Categories = new Category();
        $this->assertEqual($Categories->tree->getScopeCondition(), ($Categories->_db->type() == 'postgre') ? 'true' : '1');
        $Categories->tree->setScopeCondition('true');
        $this->assertEqual($Categories->tree->getScopeCondition(), 'true');
    }

    public function Test_of_getters_and_setters()
    {
        $Categories = new Category();

        $Categories->tree->setParentColumnName('column_name');
        $this->assertEqual($Categories->tree->getParentColumnName(), 'column_name');

        $Categories->tree->setDependent(true);
        $this->assertTrue($Categories->tree->getDependent());
        $Categories->tree->setDependent(false);
        $this->assertFalse($Categories->tree->getDependent());
    }

    public function Test_of_hasChildren_and_hasParent()
    {
        $CategoryA = new Category();
        $CategoryA->description = "Cat A";

        $CategoryAa = new Category();
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


    public function Test_of_addChild_and_children()
    {
        $CategoryA = new Category();
        $CategoryA->description = "Cat A";

        $CategoryAa = new Category();
        $CategoryAa->description = "Cat Aa";

        $CategoryAb = new Category();
        $CategoryAb->description = "Cat Ab";

        $CategoryA->tree->addChild($CategoryAa);
        $CategoryA->tree->addChild($CategoryAb);

        $children = $CategoryA->tree->getChildren();
        $this->assertEqual($CategoryAa->getId(), $children[0]->getId());
        $this->assertEqual($CategoryAb->getId(), $children[1]->getId());

        $this->assertErrorPattern('/Cannot add myself as a child to myself/', $CategoryA->tree->addChild($CategoryA));
    }

    public function Test_of_childrenCount()
    {
        $CategoryA = new Category();
        $CategoryA->description = "Cat A";

        $CategoryB = new Category();
        $CategoryB->description = "Cat B";

        $CategoryAa = new Category();
        $CategoryAa->description = "Cat Aa";

        $CategoryAb = new Category();
        $CategoryAb->description = "Cat Ab";

        $CategoryA->tree->addChild($CategoryAa);
        $CategoryA->tree->addChild($CategoryAb);

        $this->assertEqual(2, $CategoryA->tree->childrenCount());
        $this->assertEqual(0, $CategoryB->tree->childrenCount());
        $this->assertEqual(0, $CategoryAa->tree->childrenCount());
        $this->assertEqual(0, $CategoryAb->tree->childrenCount());
    }

    public function Test_of_parent()
    {
        $CategoryA = new Category();
        $CategoryA->description = "Cat A";

        $CategoryAa = new Category();
        $CategoryAa->description = "Cat Aa";

        $CategoryAb = new Category();
        $CategoryAb->description = "Cat Ab";

        $CategoryA->tree->addChild($CategoryAa);
        $CategoryA->tree->addChild($CategoryAb);

        $catAaParent = $CategoryAa->tree->getParent();
        $catAbParent = $CategoryAb->tree->getParent();
        $this->assertEqual($CategoryA->getId(), $catAaParent->getId());
        $this->assertEqual($CategoryA->getId(), $catAbParent->getId());
    }

    public function Test_of_beforeDestroy()
    {
        $CategoryA = new DependentCategory();
        $CategoryA->description = "Cat A";

        $CategoryB = new Category();
        $CategoryB->description = "Cat B";

        $CategoryAa = new DependentCategory();
        $CategoryAa->description = "Cat Aa";

        $CategoryBa = new Category();
        $CategoryBa->description = "Cat Ba";

        $CategoryA->tree->addChild($CategoryAa);
        $CategoryB->tree->addChild($CategoryBa);

        $CategoryA->destroy();
        $this->assertFalse($CategoryAa->reload());

        $CategoryB->destroy();
        $this->assertTrue($CategoryBa->reload());
        $this->assertFalse($CategoryBa->tree->hasParent());
    }

}

ak_test('test_AkActiveRecord_actAsTree',true);

?>