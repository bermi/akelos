<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2006, Akelos Media, S.L.  & Bermi Ferrer Martinez |
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
 * @package AkelosFramework
 * @subpackage AkActiveRecord
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

require_once(AK_LIB_DIR.DS.'AkActiveRecord'.DS.'AkObserver.php');

class AkActsAsNestedSet extends AkObserver
{

    /**
    * This acts provides Nested Set functionality.  Nested Set is similiar to Tree, but with
    * the added feature that you can select the children and all of it's descendants with
    * a single query.  A good use case for this is a threaded post system, where you want
    * to display every reply to a comment without multiple selects.
    *
    * A google search for "Nested Set" should point you in the direction to explain the
    * data base theory.  I figured a bunch of this from
    * http://threebit.net/tutorials/nestedset/tutorial1.html
    *
    * Instead of picturing a leaf node structure with child pointing back to their parent,
    * the best way to imagine how this works is to think of the parent entity surrounding all
    * of it's children, and it's parent surrounding it, etc.  Assuming that they are lined up
    * horizontally, we store the left and right boundaries in the database.
    *
    * Imagine:
    *   root
    *     |_ Child 1
    *       |_ Child 1.1
    *       |_ Child 1.2
    *     |_ Child 2
    *       |_ Child 2.1
    *       |_ Child 2.2
    *
    * If my circles in circles description didn't make sense, check out this sweet
    * ASCII art:
    *
    *     ___________________________________________________________________
    *    |  Root                                                             |
    *    |    ____________________________    ____________________________   |
    *    |   |  Child 1                  |   |  Child 2                  |   |
    *    |   |   __________   _________  |   |   __________   _________  |   |
    *    |   |  |  C 1.1  |  |  C 1.2 |  |   |  |  C 2.1  |  |  C 2.2 |  |   |
    *    1   2  3_________4  5________6  7   8  9_________10 11_______12 13  14
    *    |   |___________________________|   |___________________________|   |
    *    |___________________________________________________________________| 
    *
    * The numbers represent the left and right boundaries.  The table them might
    * look like this:
    *    ID | PARENT | LEFT | RIGHT | DATA
    *     1 |      0 |    1 |    14 | root
    *     2 |      1 |    2 |     7 | Child 1
    *     3 |      2 |    3 |     4 | Child 1.1
    *     4 |      2 |    5 |     6 | Child 1.2
    *     5 |      1 |    8 |    13 | Child 2
    *     6 |      5 |    9 |    10 | Child 2.1
    *     7 |      5 |   11 |    12 | Child 2.2
    *
    * So, to get all children of an entry, you
    *     SELECT * WHERE CHILD.LEFT IS BETWEEN PARENT.LEFT AND PARENT.RIGHT
    *
    * To get the count, it's (LEFT - RIGHT + 1)/2, etc.
    *
    * To get the direct parent, it falls back to using the PARENT_ID field.   
    *
    * There are instance methods for all of these.
    *
    * The structure is good if you need to group things together; the downside is that
    * keeping data integrity is a pain, and both adding and removing and entry
    * require a full table write.        
    *
    * This sets up a beforeDestroy() trigger to prune the tree correctly if one of it's
    * elements gets deleted.
    *
    */

    /**
    * Configuration options are:
    *
    * * +parent_column+ - specifies the column name to use for keeping the position integer (default: parent_id)
    * * +left_column+ - column name for left boundary data, default "lft"
    * * +right_column+ - column name for right boundary data, default "rgt"
    * * +scope+ - restricts what is to be considered a list. Given a symbol, it'll attach "_id" 
    *   (if that hasn't been already) and use that as the foreign key restriction. It's also possible 
    *   to give it an entire string that is interpolated if you need a tighter scope than just a foreign key.
    *   Example: <tt>actsAsList(array('scope' => array('todo_list_id = ? AND completed = 0',$todo_list_id)));</tt>
    */

    var $_scope_condition;
    var $_left_column_name = 'lft';
    var $_right_column_name = 'rgt';
    var $_parent_column_name = 'parent_id';

    var $_ActiveRecordInstance;

    function AkActsAsNestedSet(&$ActiveRecordInstance)
    {
        $this->_ActiveRecordInstance =& $ActiveRecordInstance;
    }

    function init($options = array())
    {
        empty($options['parent_column']) ? null : ($this->_parent_column_name = $options['parent_column']);
        empty($options['left_column']) ? null : ($this->_left_column_name = $options['left_column']);
        empty($options['right_column']) ? null : ($this->_right_column_name = $options['right_column']);
        empty($options['scope']) ? null : $this->setScopeCondition($options['scope']);
        return $this->_ensureIsActiveRecordInstance($this->_ActiveRecordInstance);
    }


    function _ensureIsActiveRecordInstance(&$ActiveRecordInstance)
    {
        if(is_object($ActiveRecordInstance) && method_exists($ActiveRecordInstance,'actsLike')){
            $this->_ActiveRecordInstance =& $ActiveRecordInstance;
            if(!$this->_ActiveRecordInstance->hasColumn($this->_parent_column_name) || !$this->_ActiveRecordInstance->hasColumn($this->_left_column_name) || !$this->_ActiveRecordInstance->hasColumn($this->_right_column_name)){
                trigger_error(Ak::t(
                'The following columns are required in the table "%table" for the model "%model" to act as a Nested Set: "%columns".',array(
                '%columns'=>$this->getParentColumnName().', '.$this->getLeftColumnName().', '.$this->getRightColumnName(),'%table'=>$this->_ActiveRecordInstance->getTableName(),'%model'=>$this->_ActiveRecordInstance->getModelName())),E_USER_ERROR);
                unset($this->_ActiveRecordInstance->nested_set);
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
        AK_PHP5 ? null : $nodeInstance->nested_set->_ensureIsActiveRecordInstance($nodeInstance);
    }

    function getType()
    {
        return 'nested set';
    }

    function getScopeCondition()
    {
        // An allways true condition in case no scope has been specified
        if(empty($this->scope_condition) && empty($this->scope)){
            $this->scope_condition = (substr($this->_ActiveRecordInstance->_db->databaseType,0,4) == 'post') ? 'true' : '1';
        }elseif (!empty($this->scope)){
            $this->setScopeCondition(join(' AND ',array_map(array(&$this,'getScopedColumn'),(array)$this->scope)));
        }
        return  $this->scope_condition;
    }


    function setScopeCondition($scope_condition)
    {
        $this->scope_condition  = $scope_condition;
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

    function getLeftColumnName()
    {
        return $this->_left_column_name;
    }
    function setLeftColumnName($left_column_name)
    {
        $this->_left_column_name = $left_column_name;
    }

    function getRightColumnName()
    {
        return $this->_right_column_name;
    }
    function setRightColumnName($right_column_name)
    {
        $this->_right_column_name = $right_column_name;
    }


    function getParentColumnName()
    {
        return $this->_parent_column_name;
    }
    function setParentColumnName($parent_column_name)
    {
        $this->_parent_column_name = $parent_column_name;
    }

    /**
    * Returns true is this is a root node.
    */
    function isRoot()
    {
        $parent_id = $this->_ActiveRecordInstance->get($this->getParentColumnName());
        $left_id = $this->_ActiveRecordInstance->get($this->getLeftColumnName());
        $right_id = $this->_ActiveRecordInstance->get($this->getRightColumnName());
        return (empty($parent_id) && $left_id == 1 && ($right_id > $left_id)) || (!empty($parent_id) && $parent_id == $this->_ActiveRecordInstance->getId());
    }
     
    /**
    * Returns true is this is a child node
    */
    function isChild()
    {
        $parent_id = $this->_ActiveRecordInstance->{$this->getParentColumnName()};
        return !empty($parent_id) &&
        !$this->isRoot() &&
        ($this->_ActiveRecordInstance->{$this->getLeftColumnName()} > $parent_id) &&
        ($this->_ActiveRecordInstance->{$this->getRightColumnName()} > $this->_ActiveRecordInstance->{$this->getLeftColumnName()});
    }

    /**
    * Returns true if we have no idea what this is
    */
    function isUnknown()
    {
        return !$this->isRoot() && !$this->isChild();
    }

    /**
    * Added a child to this object in the tree.  If this object hasn't been initialized,
    * it gets set up as a root node.  Otherwise, this method will update all of the
    * other elements in the tree and shift them to the right. Keeping everything
    * balanaced. 
    */
    function addChild( &$child )
    {
        $this->_ActiveRecordInstance->reload();
        $child->reload();

        if ($child->nested_set->isRoot()){
            trigger_error(Ak::t("Adding sub-tree isn't currently supported"),E_USER_ERROR);
        }elseif ($child->nested_set->isChild()){
            trigger_error(Ak::t("Moving nodes isn't currently supported"),E_USER_ERROR);
        }else{
            if ( empty($this->_ActiveRecordInstance->{$this->getLeftColumnName()}) || empty($this->_ActiveRecordInstance->{$this->getRightColumnName()}) ){
                $this->_ActiveRecordInstance->transactionStart();

                if(!$child->save()){
                    $this->_ActiveRecordInstance->transactionFail();
                    $this->_ActiveRecordInstance->transactionComplete();
                    return false;
                }
                $node_id = $child->getId();
                $child->{$this->getParentColumnName()} = $node_id;
                $child->{$this->getLeftColumnName()} = $node_id+1;
                $child->{$this->getRightColumnName()} = $node_id+2;
                $root_node = $child->save();
                $this->reloadActiveRecordInstance($root_node);
                $this->_ActiveRecordInstance->transactionComplete();
                return $root_node;
            }else{
                // OK, we need to add and shift everything else to the right
                $child->{$this->getParentColumnName()} = $this->_ActiveRecordInstance->getId();
                $right_bound = $this->_ActiveRecordInstance->{$this->getRightColumnName()};
                $child->{$this->getLeftColumnName()} = $right_bound;
                $child->{$this->getRightColumnName()} = $right_bound + 1;
                $this->_ActiveRecordInstance->{$this->getRightColumnName()} += 2;
                $this->_ActiveRecordInstance->transactionStart();

                $this->_ActiveRecordInstance->updateAll($this->getLeftColumnName()." = (".$this->getLeftColumnName()." + 2)",  $this->getScopeCondition()." AND ".$this->getLeftColumnName()." >= $right_bound" );
                $this->_ActiveRecordInstance->updateAll($this->getRightColumnName()." = (".$this->getRightColumnName()." + 2)",  $this->getScopeCondition()." AND ".$this->getRightColumnName()." >= $right_bound");
                $this->_ActiveRecordInstance->save();
                $child->save();
                $this->reloadActiveRecordInstance($child);
                $this->_ActiveRecordInstance->transactionComplete();
                return $child;
            }
        }
    }



    /**
    * Returns the number of all nested children of this object.
    */
    function childrenCount()
    {
        return ($this->_ActiveRecordInstance->{$this->getRightColumnName()} - $this->_ActiveRecordInstance->{$this->getLeftColumnName()} - 1)/2;
    }

    /**
    * Returns an array of $this->_ActiveRecordInstance and all of it's nested children
    */
    function fullSet()
    {
        return $this->isUnknown() ? false : $this->_ActiveRecordInstance->find('all', array('conditions' => " ".$this->getScopeCondition()." AND (".$this->getLeftColumnName()." BETWEEN ".$this->_ActiveRecordInstance->{$this->getLeftColumnName()}." AND ".$this->_ActiveRecordInstance->{$this->getRightColumnName()}.")" ));
    }

    /**
    * Returns an array of all of it's children and nested children
    */
    function allChildren()
    {
        return $this->isUnknown() ? false : $this->_ActiveRecordInstance->find('all', array('conditions' => " ".$this->getScopeCondition()." AND (".$this->getLeftColumnName()." > ".$this->_ActiveRecordInstance->{$this->getLeftColumnName()}.") AND (".$this->getRightColumnName()." < ".$this->_ActiveRecordInstance->{$this->getRightColumnName()}.")" ));
    }

    /**
    * Returns an array of only this entries immediate children
    */
    function directChildren()
    {
        return $this->isUnknown() ? false : $this->_ActiveRecordInstance->find('all', array('conditions' => " ".$this->getScopeCondition()." AND ".$this->getParentColumnName()." = ".$this->_ActiveRecordInstance->getId()));
    }

    /**
    * Returns the parent Object
    */
    function getParent()
    {
        return !$this->isChild() ? false : $this->_ActiveRecordInstance->find(
        'first', array('conditions' => " ".$this->getScopeCondition()." AND ".$this->_ActiveRecordInstance->getPrimaryKey()." = ".$this->_ActiveRecordInstance->{$this->getParentColumnName()})
        );
    }

    /**
    * Returns an array of parent Objects this is usefull to make breadcrum like stuctures
    */
    function getParents()
    {
        return !$this->isChild() ? false : $this->_ActiveRecordInstance->find('all',
        array('conditions'=> " ".$this->getScopeCondition()." AND ".$this->getParentColumnName()." <= ".$this->_ActiveRecordInstance->{$this->getParentColumnName()}.
        " AND ".$this->getLeftColumnName()." < ".$this->_ActiveRecordInstance->{$this->getLeftColumnName()}.
        " AND ".$this->getRightColumnName()." > ".$this->_ActiveRecordInstance->{$this->getRightColumnName()}, 'order'=>$this->getParentColumnName().' ASC'));
    }


    /**
    * Prunes a branch off of the tree, shifting all of the elements on the right
    * back to the left so the counts still work.
    */
    function beforeDestroy(&$object)
    {
        if((empty($object->{$this->getRightColumnName()}) || empty($object->{$this->getLeftColumnName()})) || $object->nested_set->isUnknown()){
            return true;
        }
        $dif = $object->{$this->getRightColumnName()} - $object->{$this->getLeftColumnName()} + 1;

        $object->transactionStart();
        $object->deleteAll($this->getScopeCondition().
        " AND ".$this->getLeftColumnName()." > ".$object->{$this->getLeftColumnName()}.
        " AND ".$this->getRightColumnName()." < ".$object->{$this->getRightColumnName()});

        $object->updateAll($this->getLeftColumnName()." = (".$this->getLeftColumnName()." - $dif)",
        $this->getScopeCondition()." AND ".$this->getLeftColumnName()." >= ".$object->{$this->getRightColumnName()} );

        $object->updateAll($this->getRightColumnName()." = (".$this->getRightColumnName()." - $dif )",
        $this->getScopeCondition()." AND ".$this->getRightColumnName()." >= ".$object->{$this->getRightColumnName()});

        if($object->transactionHasFailed()){
            $object->transactionComplete();
            return false;
        }
        $object->transactionComplete();

        return true;
    }

    /**
     * on creation, set automatically lft and rgt to the end of the tree
     */
     function beforeCreate()
     {
         
        $left = $this->_ActiveRecordInstance->get($this->getLeftColumnName());
        $right = $this->_ActiveRecordInstance->get($this->getRightColumnName());

        $maxright = $this->_ActiveRecordInstance->maximum($right, array('conditions'=>$this->getScopeCondition()));
        $maxright = empty($maxright) ? 0 : $maxright;
        
        $this->_ActiveRecordInstance->set($left, $maxright+1);
        $this->_ActiveRecordInstance->set($right, $maxright+2);
        
        return true;
     }
     
    /**
     * Returns the single root
     */
    function getRoot()
    {
        return $this->_ActiveRecordInstance->find('first', array('conditions' => " ".$this->getScopeCondition()." AND ".$this->getParentColumnName()." IS NULL "));
    }

    /**
     * Returns roots when multiple roots (or virtual root, which is the same)
     */
    function getRoots()
    {
        return $this->_ActiveRecordInstance->find('all', array('conditions' => " ".$this->getScopeCondition()." AND ".$this->getParentColumnName()." IS NULL ",'order' => $this->getLeftColumnName()));
    }


    /**
     * Returns an array of all parents 
     */
    function getAncestors()
    {
        return $this->_ActiveRecordInstance->find('all', array('conditions' => ' '.$this->getScopeCondition().' AND '.
        $this->getLeftColumnName().' < '.$this->_ActiveRecordInstance->get($this->getLeftColumnName()).' AND '.
        $this->getRightColumnName().' > '.$this->_ActiveRecordInstance->get($this->getRightColumnName())
        ,'order' => $this->getLeftColumnName()));
    }

    /**
     * Returns the array of all parents and self
     */
    function getSelfAndAncestors()
    {
        if($result = $this->getAncestors()){
            array_push($result, $this->_ActiveRecordInstance);
        }else{
            $result = array($this->_ActiveRecordInstance);
        }
        return $result;
    }


    /**
     * Returns the array of all children of the parent, except self
     */
    function getSiblings()
    {
        return $this->_ActiveRecordInstance->find('all', array('conditions' => ' '.$this->getScopeCondition().' AND '.
        $this->getParentColumnName().' = '.$this->_ActiveRecordInstance->get($this->getParentColumnName()).' AND '.
        $this->_ActiveRecordInstance->getPrimaryKey().' <> '.$this->_ActiveRecordInstance->getId()
        ,'order' => $this->getLeftColumnName()));
    }

    /**
     * Returns the array of all children of the parent, included self
     */
    function getSelfAndSiblings()
    {
        $parent_id = $this->_ActiveRecordInstance->get($this->getParentColumnName());
        if(!empty($parent_id) && $result = $this->getSiblings()){
            array_push($result, $this->_ActiveRecordInstance);
        }else{
            $result = array($this->_ActiveRecordInstance);
        }
        return $result;
    }


    /**
     * Returns the level of this object in the tree 
     * root level is 0
     */
    function getLevel()
    {
        $parent_id = $this->_ActiveRecordInstance->get($this->getParentColumnName());
        if(empty($parent_id)){
            return 0;
        }
        return $this->_ActiveRecordInstance->count(' '.$this->getScopeCondition().' AND '.
        $this->getLeftColumnName().' < '.$this->_ActiveRecordInstance->get($this->getLeftColumnName()).' AND '.
        $this->getRightColumnName().' > '.$this->_ActiveRecordInstance->get($this->getRightColumnName()));
    }


    /**
    * Returns the number of all nested children of this object.
    */
    function getChildrenCount()
    {
        return ($this->_ActiveRecordInstance->{$this->getRightColumnName()} - $this->_ActiveRecordInstance->{$this->getLeftColumnName()} - 1)/2;
    }


    /**
     * Returns a set of only this entry's immediate children
     */
    function getChildren()
    {
        return $this->_ActiveRecordInstance->find('all', array('conditions' => ' '.$this->getScopeCondition().' AND '.
        $this->getParentColumnName().' = '.$this->_ActiveRecordInstance->getId()
        ,'order' => $this->getLeftColumnName()));
    }

    /**
     * Returns a set of all of its children and nested children
     */
    function getAllChildren($exclude = null)
    {
        $excluded_ids = array();
        if(!empty($exclude)){
            $exclude = is_string($exclude) ? Ak::toArray($exclude) : (is_array($exclude) ? $exclude : array($exclude));
            $parent_class_name = get_class($this->_ActiveRecordInstance);
            foreach (array_keys($exclude) as $k){
                $Item =& $exclude[$k];
                if(is_a($Item,$parent_class_name)){
                    $ItemToExclude =& $Item;
                }else{
                    $ItemToExclude =& $this->_ActiveRecordInstance->find($Item);
                }
                if($ItemSet =& $ItemToExclude->nested_set->getFullSet()){
                    foreach (array_keys($ItemSet) as $l){
                        $excluded_ids[] = $ItemSet[$l]->getId();
                    }
                }
            }
            $excluded_ids = array_unique(array_diff($excluded_ids,array('')));
        }
        return $this->_ActiveRecordInstance->find('all', array('conditions' => ' '.$this->getScopeCondition().' AND '.
        (empty($excluded_ids) ? '' : ' id NOT IN ('.join(',',$excluded_ids).') AND ').
        $this->getLeftColumnName().' > '.$this->_ActiveRecordInstance->get($this->getLeftColumnName()).' AND '.
        $this->getRightColumnName().' < '.$this->_ActiveRecordInstance->get($this->getRightColumnName())
        ,'order' => $this->getLeftColumnName()));
    }

    /**
     * Returns a set of itself and all of its nested children
     */
    function getFullSet($exclude = null)
    {
        if($this->_ActiveRecordInstance->isNewRecord() || $this->_ActiveRecordInstance->get($this->getRightColumnName()) - $this->_ActiveRecordInstance->get($this->getLeftColumnName()) == 1 ){
            $result = array($this->_ActiveRecordInstance);
        }else{
        (array)$result = $this->getAllChildren($exclude);
        array_unshift($result, $this->_ActiveRecordInstance);
        }
        return $result;
    }


    /**
     * Move the node to the left of another node
     */
    function moveToLeftOf($node)
    {
        $this->moveTo($node, 'left');
    }

    /**
     * Move the node to the left of another node
     */
    function moveToRightOf($node)
    {
        $this->moveTo($node, 'right');
    }

    /**
     * Move the node to the child of another node
     */
    function moveToChildOf($node)
    {
        $this->moveTo($node, 'child');
    }

    function moveTo(&$target, $position)
    {
        if($this->_ActiveRecordInstance->isNewRecord()){
            trigger_error(Ak::t('You cannot move a new node'), E_USER_ERROR);
        }
        $current_left = $this->_ActiveRecordInstance->get($this->getLeftColumnName());
        $current_right = $this->_ActiveRecordInstance->get($this->getRightColumnName());
        // $extent is the width of the tree self and children
        $extent = $current_right - $current_left + 1;

        // load object if node is not an object
        if (is_int($target)){
            $target =& $this->_ActiveRecordInstance->find($target);
        }
        if(!$target || !is_a($target, get_class($this->_ActiveRecordInstance))){
            trigger_error(Ak::t('Invalid target'), E_USER_NOTICE);
            return false;
        }

        $target_left = $target->get($this->getLeftColumnName());
        $target_right = $target->get($this->getRightColumnName());


        // detect impossible move
        if ((($current_left <= $target_left) && ($target_left <= $current_right)) || (($current_left <= $target_right) && ($target_right <= $current_right))){
            trigger_error(Ak::t('Impossible move, target node cannot be inside moved tree.'), E_USER_ERROR);
        }

        // compute new left/right for self
        if ($position == 'child'){
            if ($target_left < $current_left){
                $new_left  = $target_left + 1;
                $new_right = $target_left + $extent;
            }else{
                $new_left  = $target_left - $extent + 1;
                $new_right = $target_left;
            }
        }elseif($position == 'left'){
            if ($target_left < $current_left){
                $new_left  = $target_left;
                $new_right = $target_left + $extent - 1;
            }else{
                $new_left  = $target_left - $extent;
                $new_right = $target_left - 1;
            }
        }elseif($position == 'right'){
            if ($target_right < $current_right){
                $new_left  = $target_right + 1;
                $new_right = $target_right + $extent;
            }else{
                $new_left  = $target_right - $extent + 1;
                $new_right = $target_right;
            }
        }else{
            trigger_error(Ak::t("Position should be either left or right ('%position' received).",array('%position'=>$position)), E_USER_ERROR);
        }

        // boundaries of update action
        $left_boundary = min($current_left, $new_left);
        $right_boundary = max($current_right, $new_right);

        // Shift value to move self to new $position
        $shift = $new_left - $current_left;

        // Shift value to move nodes inside boundaries but not under self_and_children
        $updown = ($shift > 0) ? -$extent : $extent;

        // change null to NULL for new parent
        if($position == 'child'){
            $new_parent = $target->getId();
        }else{
            $target_parent = $target->get($this->getParentColumnName());
            $new_parent = empty($target_parent) ? 'NULL' : $target_parent;
        }

        $this->_ActiveRecordInstance->updateAll(

        $this->getLeftColumnName().' = CASE '.
        'WHEN '.$this->getLeftColumnName().' BETWEEN '.$current_left.' AND '.$current_right.' '.
        'THEN '.$this->getLeftColumnName().' + '.$shift.' '.
        'WHEN '.$this->getLeftColumnName().' BETWEEN '.$left_boundary.' AND '.$right_boundary.' '.
        'THEN '.$this->getLeftColumnName().' + '.$updown.' '.
        'ELSE '.$this->getLeftColumnName().' END, '.

        $this->getRightColumnName().' = CASE '.
        'WHEN '.$this->getRightColumnName().' BETWEEN '.$current_left.' AND '.$current_right.' '.
        'THEN '.$this->getRightColumnName().' + '.$shift.' '.
        'WHEN '.$this->getRightColumnName().' BETWEEN '.$left_boundary.' AND '.$right_boundary.' '.
        'THEN '.$this->getRightColumnName().' + '.$updown.' '.
        'ELSE '.$this->getRightColumnName().' END, '.

        $this->getParentColumnName().' = CASE '.
        'WHEN '.$this->_ActiveRecordInstance->getPrimaryKey().' = '.$this->_ActiveRecordInstance->getId().' '.
        'THEN '.$new_parent.' '.
        'ELSE '.$this->getParentColumnName().' END',

        $this->getScopeCondition() );
        $this->_ActiveRecordInstance->reload();

        return true;
    }

}


?>