<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2006, Akelos Media, S.L.  & Bermi Ferrer Martinez |
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+
// | Acts as Tree                                                         |
// +----------------------------------------------------------------------+
// | Copyright (c) 2006, Raw Ideas Pty Ltd & Niels Ganser                 |
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// | If the Akelos Framework License is changed to another one as or less |
// | restrictive as the LGPL, permission is granted to also re-license    |
// | this file.                                                           |
// +----------------------------------------------------------------------+

/**
 * @package ActiveRecord
 * @subpackage Behaviours
 * @author Niels Ganser <ng a.t depoll d.e>
 * @copyright Copyright (c) 2006, Raw Ideas Pty Ltd
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

require_once(AK_LIB_DIR.DS.'AkActiveRecord'.DS.'AkObserver.php');


/**
 * acts_as_tree
 * 
 * Makes your model acts as a tree (surprise!). Consider the following example:
 * 
 * class Category extends ActiveRecord {
 *   var $acts_as = 'tree';
 * }
 * 
 * $Category = new Category;
 * 
 * $CategoryA = $Category->create();
 * $CategoryAa = $Category->create();
 * $CategoryAa1 = $Category->create();
 * $CategoryAa2 = $Category->create();
 * $CategoryAb = $Category->create();
 * $CategoryB = $Category->create();
 * 
 * $CategoryA->tree->addChild($CategoryAa)
 * $CategoryA->tree->addChild($CategoryAb)
 * $CategoryAa->tree->addChild($CategoryAa1)
 * $CategoryAa->tree->addChild($CategoryAa2)
 * 
 * 
 * This will effectively give you:
 * 
 * Category A
 *  \_ Category Aa
 *      \_ Category Aa1
 *      \_ Category Aa2
 *  \_ Category Ab
 * Category B
 * 
 * 
 * OK. Admittedly you won't get a graph in real life. But at least the following functions:
 * 
 * $CategoryA->tree->hasChildren()		# ==> true
 * $CategoryA->tree->childrenCount()	# ==> 2
 * $CategoryA->tree->getChildren() 		# ==> array($CategoryAa, $CategoryAb)
 * // fairly expensive operation follows
 * // (yes, array(parent, array_of_children) is not nice but unfortunately PHP doesn't allow for objects as keys)
 * $CategoryA->tree->getDescendants()	# ==> array(array($CategoryAa, array($CategoryAa1, $CategoryAa2)), $CategoryAb)
 * 
 * $CategoryAa->tree->getChildren()		# ==> array($CategoryAa1, $CategoryAa2)
 * $CategoryAa->tree->getSiblings()		# ==> array($CategoryAb)
 * $CategoryAa->tree->hasParent()		# ==> true
 * $CategoryAa->tree->getParent()		# ==> $CategoryA
 * 
 * $CatagoryAa1->tree->hasChildren()	# ==> false
 * $CategoryAa1->tree->getParent()		# ==> $CategoryAa
 * // fairly expensive operation follows
 * $CategoryAa1->tree->getAncestors()	# ==> array($CategoryAa, $CategoryA)
 * // fairly expensive operation follows
 * $CategoryAa1->tree->getAncestors(1)	# ==> array($CategoryAa)
 *  
 * 
 * To make this work your model needs a parent_id column (whose name can be overriden with +parent_column+. Furthermore
 * you can set the +dependent+ option to automatically delete all children if their parent gets deleted. Otherwise they
 * will become orphants (i.e. have parent_id = NULL)
 * 
 * (Note that on adding a child it will be saved. If the parent has been unsaved until now it will also be saved.)
 */
class AkActsAsTree extends AkObserver
{



    /**
    * Configuration options are:
    *
    * * +parent_column+ - specifies the column name to use for keeping the position integer (default: parent_id)
    * * +dependent+ - set to true to automatically delete all children when its parent is deleted
    * * +scope+ - restricts what is to be considered a list. Given a symbol, it'll attach "_id" 
    *   (if that hasn't been already) and use that as the foreign key restriction. It's also possible 
    *   to give it an entire string that is interpolated if you need a tighter scope than just a foreign key.
    *   Example: <tt>actsAsTree(array('scope' => array('todo_list_id = ? AND completed = 0',$todo_list_id)));</tt>
    */

    var $parent_column = 'parent_id';
    var $scope;
    var $scope_condition;
    var $_parent_column_name = 'parent_id';
    var $_dependent = false;

    var $_ActiveRecordInstance;

    function AkActsAsTree(&$ActiveRecordInstance)
    {
        $this->_ActiveRecordInstance =& $ActiveRecordInstance;
    }

    function init($options = array())
    {
        empty($options['parent_column']) ? null : ($this->_parent_column_name = $options['parent_column']);
        empty($options['dependent']) ? null : ($this->_dependent = $options['dependent']);
        empty($options['scope']) ? null : $this->setScopeCondition($options['scope']);
        $this->parent_column = !empty($options['parent_column']) ? $options['parent_column'] : $this->parent_column;
        return $this->_ensureIsActiveRecordInstance($this->_ActiveRecordInstance);
    }


    function _ensureIsActiveRecordInstance(&$ActiveRecordInstance)
    {
        if(is_object($ActiveRecordInstance) && method_exists($ActiveRecordInstance,'actsLike')){
            $this->_ActiveRecordInstance =& $ActiveRecordInstance;
            if(!$this->_ActiveRecordInstance->hasColumn($this->_parent_column_name)){
                trigger_error(Ak::t(
                'The following columns are required in the table "%table" for the model "%model" to act as a Tree: "%columns".',array(
                '%columns'=>$this->getParentColumnName(),'%table'=>$this->_ActiveRecordInstance->getTableName(),'%model'=>$this->_ActiveRecordInstance->getModelName())),E_USER_ERROR);
                unset($this->_ActiveRecordInstance->tree);
                return false;
            }else{
                $this->observe(&$ActiveRecordInstance);
            }
        }else{
            trigger_error(Ak::t('You are trying to set an object that is not an active record.'), E_USER_ERROR);
            return false;
        }
        return true;
    }

    function reloadActiveRecordInstance(&$nodeInstance)
    {
        AK_PHP5 ? null : $nodeInstance->tree->_ensureIsActiveRecordInstance($nodeInstance);
    }

    function getType()
    {
        return 'tree';
    }
    
    function getScopeCondition()
    {
        if (!empty($this->variable_scope_condition)){
            return $this->_ActiveRecordInstance->_getVariableSqlCondition($this->variable_scope_condition);
            
        // True condition in case we don't have a scope
        }elseif(empty($this->scope_condition) && empty($this->scope)){
            $this->scope_condition = (substr($this->_ActiveRecordInstance->_db->databaseType,0,4) == 'post') ? 'true' : '1';
        }elseif (!empty($this->scope)){
            $this->setScopeCondition(join(' AND ',array_diff(array_map(array(&$this,'getScopedColumn'),(array)$this->scope),array(''))));
        }
        return  $this->scope_condition;
    }


    function setScopeCondition($scope_condition)
    {
        if(!is_array($scope_condition) && strstr($scope_condition, '?')){
            $this->variable_scope_condition = $scope_condition;
        }else{
            $this->scope_condition  = $scope_condition;
        }
    }

    function getScopedColumn($column)
    {
        if($this->_ActiveRecordInstance->hasColumn($column)){
            $value = $this->_ActiveRecordInstance->get($column);
            $condition = $this->_ActiveRecordInstance->getAttributeCondition($value);
            $value = $this->_ActiveRecordInstance->castAttributeForDatabase($column, $value);
            return $column.' '.str_replace('?', $value, $condition);
        }else{
            return $column;
        }
    }

    function getParentColumnName()
    {
        return $this->_parent_column_name;
    }

    function setParentColumnName($parent_column_name)
    {
        $this->_parent_column_name = $parent_column_name;
    }

    function getDependent()
    {
        return $this->_dependent;
    }

    function setDependent($val)
    {
        $this->_dependent = (bool)$val;
    }

    function hasChildren()
    {
        return $this->childrenCount() > 0;
    }

    function hasParent()
    {
        $parent_id = $this->_ActiveRecordInstance->{$this->getParentColumnName()};
        return !empty($parent_id);
    }

    function addChild( &$child )
    {
        $this->_ActiveRecordInstance->transactionStart();

        if ($this->_ActiveRecordInstance->isNewRecord()){
            if (!$this->_ActiveRecordInstance->save()) {
                $this->_ActiveRecordInstance->transactionFail();
                $this->_ActiveRecordInstance->transactionComplete();
                return false;
            }
        }

        if ($this->_ActiveRecordInstance->getId() == $child->getId()) {
            $this->_ActiveRecordInstance->transactionFail();
            $this->_ActiveRecordInstance->transactionComplete();
            trigger_error(Ak::t('Cannot add myself as a child to myself'), E_USER_ERROR);
            return false;
        }

        $child->{$this->getParentColumnName()} = $this->_ActiveRecordInstance->getId();
        if (!$child->save()) {
            $this->_ActiveRecordInstance->transactionFail();
            $this->_ActiveRecordInstance->transactionComplete();
            return false;
        }

        $this->_ActiveRecordInstance->transactionComplete();

        $this->reloadActiveRecordInstance($child);
        return $child;
    }

    function childrenCount()
    {
        
        return $this->_ActiveRecordInstance->isNewRecord() ? 0 : $this->_ActiveRecordInstance->count(" ".$this->getScopeCondition()." AND ".$this->getParentColumnName()." = ".$this->_ActiveRecordInstance->getId());
    }

    function getChildren()
    {
        return $this->_ActiveRecordInstance->isNewRecord() ? false : $this->_ActiveRecordInstance->findAll(" ".$this->getScopeCondition()." AND ".$this->getParentColumnName()." = ".$this->_ActiveRecordInstance->getId());
    }

    function getParent()
    {
        if (!$this->hasParent()){
            return false;
        } else {
            return $this->_ActiveRecordInstance->find('first',
            array('conditions' => ' '.$this->getScopeCondition().' AND '.$this->_ActiveRecordInstance->getPrimaryKey()." = ".$this->_ActiveRecordInstance->{$this->getParentColumnName()}));
        }
    }

    /**
     * @param	integer	$level	How deep do you want to search? everything <= 0 means infinite deep
     */
    function getAncestors($level=0)
    {
        if (!$this->hasParent()) {
            return array();
        }

        $last = $this->getParent();
        $ancestors = array($last);
        --$level;

        // we can't do end($ancestors)->hasParent() due to PHP4 compatibility
        while ($level != 0 && $last->tree->hasParent()) {
            $last = $last->tree->getParent();
            $ancestors[]= $last;
            --$level;
        }

        return $ancestors;
    }


    function getSiblings($options = array())
    {
        $default_options = array('include_self'=>false);
        $options = array_merge($default_options, $options);
        $parent_condition = (is_null($this->_ActiveRecordInstance->{$this->getParentColumnName()})) ? 'ISNULL('. $this->getParentColumnName() .")" : $this->getParentColumnName() .' = '. $this->_ActiveRecordInstance->{$this->getParentColumnName()};
        $id_condition = !empty($options['include_self']) ? '' : ' AND '. $this->_ActiveRecordInstance->getPrimaryKey() .' != '. $this->_ActiveRecordInstance->getId();
        return $this->_ActiveRecordInstance->findAll(' '. $this->getScopeCondition().
        ' AND '. $parent_condition.$id_condition);
    }
    
    function getSelfAndSiblings()
    {
        return $this->getSiblings(array('include_self'=>true));
    }

    /**
     * @param	integer	$level	How deep do you want to search? everything <= 0 means infinite deep
     */
    function getDescendants($level=0)
    {
        if (!$this->hasChildren()) {
            return array();
        }

        return $this->_recursiveGetDescendants($level, $this->getChildren());
    }
    function _recursiveGetDescendants($level, $from) {
        --$level;

        if ($level == 0) {
            return $from;
        }

        $children = array();
        foreach ($from as $item) {
            if ($item->tree->hasChildren()) {
                $children[] = array($item, $this->_recursiveGetDescendants($level, $item->tree->getChildren()));
            } else {
                $children[] = $item;
            }
        }

        return $children;
    }

    function beforeDestroy(&$object)
    {
        if(!$object->tree->hasChildren()){
            return true;
        }

        $object->transactionStart();

        if ($this->getDependent()){
            $object->deleteAll($this->getScopeCondition().' AND '.$this->getParentColumnName().' = '.$object->getId());
        }else{
            $object->updateAll(	$this->getParentColumnName() .' = NULL', $this->getScopeCondition().' AND '.$this->getParentColumnName().' = '.$object->getId() );
        }

        if($object->transactionHasFailed()){
            $object->transactionComplete();
            return false;
        }
        $object->transactionComplete();

        return true;
    }

}


?>
