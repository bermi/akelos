<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2006, Akelos Media, S.L.  & Bermi Ferrer Martinez |
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
* @package ActiveRecord
* @subpackage Behaviours
* @author Bermi Ferrer <bermi a.t akelos c.om>
* @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
* @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
*/

require_once(AK_LIB_DIR.DS.'AkActiveRecord'.DS.'AkObserver.php');

/**
* This act provides the capabilities for sorting and reordering a number of objects in list.
* The class that has this specified needs to have a "position" column defined as an integer on
* the mapped database table.
* 
* Todo list example:
* <code>
*   class TodoList extends AkActiveRecord
*   {
*       var $has_many = array('todo_items', array('order' => "position"));
*   }
* 
*   class TodoItem extends AkActiveRecord
*   {
*       var $belongs_to = 'todo_list';
*       var $acts_as = array('list' => array('scope' => 'todo_list')); 
*   }
*     
*   $TodoList =& new TodoList();
*
*   $TodoList->list->moveToBottom();
* </code>
*/
class AkActsAsList extends AkObserver
{
    var $column = 'position';
    var $scope = '';
    var $scope_condition;
    /**
* Configuration options are:
* 
* * +column+ - specifies the column name to use for keeping the position integer (default: position)
* * +scope+ - restricts what is to be considered a list. Given a symbol, it'll attach "_id" 
*   (if that hasn't been already) and use that as the foreign key restriction. It's also possible 
*   to give it an entire string that is interpolated if you need a tighter scope than just a foreign key.
*   Example: 
* 
* class TodoTask extends ActiveRecord
* {
*   var $acts_as = array('list'=> array('scope'=> array('todo_list_id','completed = 0')));
*   var $belongs_to = 'todo_list';
* }
*/
    var $_ActiveRecordInstance;
    function AkActsAsList(&$ActiveRecordInstance)
    {
        $this->_ActiveRecordInstance =& $ActiveRecordInstance;
    }

    function init($options = array())
    {
        $this->column = !empty($options['column']) ? $options['column'] : $this->column;
        $this->scope = !empty($options['scope']) ? $options['scope'] : $this->scope;
        return $this->_ensureIsActiveRecordInstance($this->_ActiveRecordInstance);
    }

    function _ensureIsActiveRecordInstance(&$ActiveRecordInstance)
    {
        if(is_object($ActiveRecordInstance) && method_exists($ActiveRecordInstance,'actsLike')){
            $this->_ActiveRecordInstance =& $ActiveRecordInstance;
            if(!$this->_ActiveRecordInstance->hasColumn($this->column)){
                trigger_error(Ak::t('Could not find the column "%column" into the table "%table". This column is needed in order to make "%model" act as a list.',array('%column'=>$this->column,'%table'=>$this->_ActiveRecordInstance->getTableName(),'%model'=>$this->_ActiveRecordInstance->getModelName())),E_USER_ERROR);
                unset($this->_ActiveRecordInstance->list);
                return false;
            }else {
                $this->observe(&$ActiveRecordInstance);
            }
        }else{
            trigger_error(Ak::t('You are trying to set an object that is not an active record.'), E_USER_ERROR);
            return false;
        }
        return true;
    }

    function reloadActiveRecordInstance(&$listObject)
    {
        AK_PHP5 ? null : $listObject->list->setActiveRecordInstance(&$listObject);
    }

    function getType()
    {
        return 'list';
    }

    function beforeDestroy(&$object)
    {
        $object->list->_ActiveRecordInstance->reload();
        return true;
    }

    function afterSave(&$object)
    {
        $object->list->_ActiveRecordInstance->reload();
        return true;
    }

    function afterDestroy(&$object)
    {
        return $object->list->removeFromList();
    }

    function beforeCreate(&$object)
    {
        $object->list->_addToBottom();
        return true;
    }

    /**
    * All the methods available to a record that has had <tt>acts_as list</tt> specified. Each method works
    * by assuming the object to be the item in the list, so <tt>$Chapter->list->moveLower()</tt> would move that chapter
    * lower in the list of all chapters. Likewise, <tt>$Chapter->list->isFirst()</tt> would return true if that chapter is
    * the first in the list of all chapters.
    */


    function insertAt($position = 1)
    {
        return $this->insertAtPosition($position);
    }

    function moveLower()
    {
        $this->_ActiveRecordInstance->transactionStart();
        if($LowerItem = $this->getLowerItem()){
            if($LowerItem->list->decrementPosition() && $this->incrementPosition()){
                $this->_ActiveRecordInstance->transactionComplete();
                return true;
            }else{
                $this->_ActiveRecordInstance->transactionFail();
            }
        }
        $this->_ActiveRecordInstance->transactionComplete();
        return false;
    }

    function moveHigher()
    {
        $this->_ActiveRecordInstance->transactionStart();
        if($HigherItem = $this->getHigherItem()){
            if($HigherItem->list->incrementPosition() && $this->decrementPosition()){
                $this->_ActiveRecordInstance->transactionComplete();
                return true;
            }else{
                $this->_ActiveRecordInstance->transactionFail();
            }
        }
        $this->_ActiveRecordInstance->transactionComplete();
        return false;
    }


    function moveToBottom()
    {
        if($this->isInList()){
            $this->_ActiveRecordInstance->transactionStart();
            if($this->decrementPositionsOnLowerItems() && $this->assumeBottomPosition()){
                $this->_ActiveRecordInstance->transactionComplete();
                return true;
            }else{
                $this->_ActiveRecordInstance->transactionFail();
            }
            $this->_ActiveRecordInstance->transactionComplete();
        }
        return false;
    }

    /**
    * This has the effect of moving all the lower items up one.
    */
    function decrementPositionsOnLowerItems()
    {
        if($this->isInList()){
            $this->_ActiveRecordInstance->updateAll("{$this->column} = ({$this->column} - 1)", $this->getScopeCondition()." AND {$this->column} > ".$this->_ActiveRecordInstance->getAttribute($this->column));
            return true;
        }
        return false;
    }

    function assumeBottomPosition()
    {
        return $this->_ActiveRecordInstance->updateAttribute($this->column, $this->getBottomPosition($this->_ActiveRecordInstance->getId()) + 1);
    }

    function getBottomPosition($except = null)
    {
        return ($item = $this->getBottomItem($except)) ? $item->getAttribute($this->column) : 0;
    }

    /**
    * Returns an instance of the item that's on the very bottom of the list. Returns false if there's none
    */
    function getBottomItem($except = null)
    {
        $conditions = $this->getScopeCondition();

        if(isset($except)){
            $conditions .= " AND id != $except";
        }
        return $this->_ActiveRecordInstance->find('first', array('conditions' => $conditions, 'order' => "{$this->column} DESC"));
    }


    function isInList()
    {
        return !empty($this->_ActiveRecordInstance->{$this->column});
    }


    function moveToTop()
    {
        if($this->isInList()){
            $this->_ActiveRecordInstance->transactionStart();
            if($this->incrementPositionsOnHigherItems() && $this->assumeTopPosition()){
                $this->_ActiveRecordInstance->transactionComplete();
                return true;
            }else{
                $this->_ActiveRecordInstance->transactionFail();
            }
            $this->_ActiveRecordInstance->transactionComplete();
        }
        return false;
    }

    /**
    * This has the effect of moving all the higher items down one.
    */
    function incrementPositionsOnHigherItems()
    {
        if($this->isInList()){
            $this->_ActiveRecordInstance->updateAll("{$this->column} = ({$this->column} + 1)", $this->getScopeCondition()." AND {$this->column} < ".$this->_ActiveRecordInstance->getAttribute($this->column));
            return true;
        }
        return false;
    }

    function assumeTopPosition()
    {
        return $this->_ActiveRecordInstance->updateAttribute($this->column, 1);
    }


    function removeFromList()
    {
        if($this->isInList()){
            if($this->decrementPositionsOnLowerItems()){
                $this->_ActiveRecordInstance->{$this->column} = null;
                return true;
            }
        }
        return false;
    }


    function incrementPosition()
    {
        if($this->isInList()){
            return $this->_ActiveRecordInstance->updateAttribute($this->column, $this->_ActiveRecordInstance->getAttribute($this->column) + 1);
        }
        return false;
    }

    function decrementPosition()
    {
        if($this->isInList()){
            return $this->_ActiveRecordInstance->updateAttribute($this->column, $this->_ActiveRecordInstance->getAttribute($this->column) - 1);
        }
        return false;
    }

    function isFirst()
    {
        if($this->isInList()){
            return $this->_ActiveRecordInstance->getAttribute($this->column) == 1;
        }
        return false;
    }

    function isLast()
    {
        if($this->isInList()){
            return $this->_ActiveRecordInstance->getAttribute($this->column) == $this->getBottomPosition();
        }
        return false;
    }

    function getHigherItem()
    {
        if($this->isInList()){
            return $this->_ActiveRecordInstance->find('first', array('conditions' => $this->getScopeCondition()." AND {$this->column} = ".($this->_ActiveRecordInstance->getAttribute($this->column) - 1)));
        }
        return false;
    }

    function getLowerItem()
    {
        if($this->isInList()){
            return $this->_ActiveRecordInstance->find('first', array('conditions' => $this->getScopeCondition()." AND {$this->column} = ".($this->_ActiveRecordInstance->getAttribute($this->column) + 1)));
        }
        return false;
    }


    function addToListTop()
    {
        $this->incrementPositionsOnAllItems();
    }

    function _addToBottom()
    {
        $this->_ActiveRecordInstance->{$this->column} = $this->getBottomPosition() + 1;
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


    /**
    * This has the effect of moving all the higher items up one.
    */
    function decrementPositionsOnHigherItems($position)
    {
        return $this->_ActiveRecordInstance->updateAll("{$this->column} = ({$this->column} - 1)", $this->getScopeCondition()." AND {$this->column} <= $position");
    }

    /**
    * This has the effect of moving all the lower items down one.
    */
    function incrementPositionsOnLowerItems($position)
    {
        return $this->_ActiveRecordInstance->updateAll("{$this->column} = ({$this->column} + 1)", $this->getScopeCondition()." AND {$this->column} >= $position");
    }

    function incrementPositionsOnAllItems()
    {
        return $this->_ActiveRecordInstance->updateAll("{$this->column} = ({$this->column} + 1)",  $this->getScopeCondition());
    }



    /**
    * This function saves the object using save() before inserting it into the list
    */
    function insertAtPosition($position)
    {
        $this->_ActiveRecordInstance->transactionStart();
        if($this->_ActiveRecordInstance->isNewRecord()){
            $this->_ActiveRecordInstance->save();
        }
        $this->removeFromList();
        $this->incrementPositionsOnLowerItems($position);

        $this->_ActiveRecordInstance->updateAttribute($this->column, $position);
        if($this->_ActiveRecordInstance->transactionHasFailed()){
            $this->_ActiveRecordInstance->transactionComplete();
            return false;
        }

        $this->_ActiveRecordInstance->transactionComplete();
        return true;
    }
}

?>