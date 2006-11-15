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
require_once(AK_LIB_DIR.DS.'AkActiveRecord'.DS.'AkAssociation.php');

/**
 * Adds the following methods for retrieval and query of collections of associated objects. 
 * collection is replaced with the singular form of current association, 
 * so var $has_many = 'clients' would hold an array of objects on $this->clients
 * and a collection handling interface instance on $this->client (singular form)
 * 
 * * collection->load($force_reload = false) - returns an array of all the associated objects. An empty array is returned if none are found.
 * * collection->add($object, ?) - adds one or more objects to the collection by setting their foreign keys to the collection's primary key. 
 * (collection->push and $collection->concat are aliases to this method).
 * * collection->delete($object, ?) - removes one or more objects from the collection by setting their foreign keys to NULL. This will also destroy the objects if they?re declared as belongs_to and dependent on this model.
 * * collection->set($objects) - replaces the collections content by deleting and adding objects as appropriate.
 * * collection->setByIds($ids) - replace the collection by the objects identified by the primary keys in ids
 * * collection->clear() - removes every object from the collection. This destroys the associated objects if they are 'dependent', deletes them directly from the database if they are 'dependent' => 'delete_all', and sets their foreign keys to NULL otherwise.
 * * collection->isEmpty() - returns true if there are no associated objects.
 * * collection->getSize() - returns the number of associated objects.
 * * collection->find() - finds an associated object according to the same rules as ActiveRecord->find.
 * * collection->count() - returns the number of elements associated.  (collection->size() is an alias to this method)
 * * collection->build($attributes = array()) - returns a new object of the collection type that has been instantiated with attributes and linked to this object through a foreign key but has not yet been saved. *Note:* This only works if an associated object already exists, not if it?s null
 * * collection->create($attributes = array()) - returns a new object of the collection type that has been instantiated with attributes and linked to this object through a foreign key and that has already been saved (if it passed the validation). *Note:* This only works if an associated object already exists, not if it?s null
 *
 * Example: A Firm class declares has_many clients, which will add:
 *
 *  * Firm->client->load() (similar to $Clients->find('all', array('conditions' => 'firm_id = '.$id)) )
 *  * Firm->client->add()
 *  * Firm->client->delete()
 *  * Firm->client->assign()
 *  * Firm->client->assignByIds()
 *  * Firm->client->clear()
 *  * Firm->client->isEmpty() (similar to count($Firm->clients) == 0)
 *  * Firm->client->getSize() (similar to Client.count "firm_id = #{id}")
 *  * Firm->client->find() (similar to $Client->find($id, array('conditions' => 'firm_id = '.$id)) )
 *  * Firm->client->build() (similar to new Client(array('firm_id' => $id)) )
 *  * Firm->client->create() (similar to $c = new Client(array('firm_id' => $id)); $c->save(); return $c )
 *
 * The declaration can also include an options array to specialize the behavior of the association.
 * 
 * Options are:
 * 
 *  * 'class_name' - specify the class name of the association. Use it only if that name can't be inferred from the association name. So "$has_many = 'products'" will by default be linked to the Product class, but if the real class name is SpecialProduct, you?ll have to specify it with this option.
 *  * 'conditions' - specify the conditions that the associated objects must meet in order to be included as a "WHERE" sql fragment, such as "price > 5 AND name LIKE ?B%?".
 *  * 'order' - specify the order in which the associated objects are returned as a "ORDER BY" sql fragment, such as "last_name, first_name DESC"
 *  * 'group' - specify the attribute by which the associated objects are returned as a "GROUP BY" sql fragment, such as "category"
 *  * 'foreign_key' - specify the foreign key used for the association. By default this is guessed to be the name of this class in lower-case and "_id" suffixed. So a Person class that makes a has_many association will use "person_id" as the default foreign_key.
 *  * 'dependent' - if set to 'destroy' all the associated objects are destroyed alongside this object by calling their destroy method. If set to 'delete_all' all associated objects are deleted without calling their destroy method. If set to 'nullify' all associated objects? foreign keys are set to NULL without calling their save callbacks.
 *  * 'finder_sql' - specify a complete SQL statement to fetch the association. This is a good way to go for complex associations that depend on multiple tables. Note: When this option is used, findInCollection is not added.
 *  * 'counter_sql' - specify a complete SQL statement to fetch the size of the association. If +'finder_sql'+ is specified but +'counter_sql'+, +'counter_sql'+ will be generated by replacing SELECT ? FROM with SELECT COUNT(*) FROM.
 *  * 'include' - specify second-order associations that should be eager loaded when the collection is loaded.
 *  * 'group' An attribute name by which the result should be grouped. Uses the GROUP BY SQL-clause.
 *  * 'limit' An integer determining the limit on the number of rows that should be returned.
 *  * 'offset' An integer determining the offset from where the rows should be fetched. So at 5, it would skip the first 4 rows.
 *  * 'select' By default, this is * as in SELECT * FROM, but can be changed if you for example want to do a join, but not include the joined columns.
 *
 * Option examples:
 *
 * $has_many = array(
 *                  'comments'  => array('order' => 'posted_on', 'include' => 'author', 'dependent' => 'nullify'),
 *                  'people'    => array('conditions' => 'deleted = 0', 'order' => 'name'),
 *                  'tracks'    => array('order' => 'position', 'dependent' => 'destroy'),
 *                  'members'   => array('class_name' => 'Person', 'conditions' => 'role = "merber"'));
 */
class AkHasMany extends AkAssociation
{
    var $asssociated_ids = array();
    var $association_id;

    function &addAssociated($association_id, $options = array())
    {

        $default_options = array(
        'class_name' => empty($options['class_name']) ? AkInflector::modulize($association_id) : $options['class_name'],
        'conditions' => false,
        'order' => false,
        'include_conditions_when_included' => true,
        'include_order_when_included' => true,
        'group' => false,
        'foreign_key' => false,
        'dependent' => 'nullify',
        'finder_sql' => false,
        'counter_sql' => false,
        'include' => false,
        'instantiate' => false,
        'group' => false,
        'limit' => false,
        'offset' => false,
        'handler_name' => strtolower(AkInflector::underscore(AkInflector::singularize($association_id))),
        'select' => false
        );

        $options = array_merge($default_options, $options);

        $options['foreign_key'] = empty($options['foreign_key']) ? AkInflector::underscore($this->Owner->getModelName()).'_id' : $options['foreign_key'];

        $Collection =& $this->_setCollectionHandler($association_id, $options['handler_name']);
        $Collection->setOptions($association_id, $options);


        $this->addModel($association_id,  $Collection);

        if($options['instantiate']){
            $associated =& $Collection->load();
        }

        $this->setAssociatedId($association_id, $options['handler_name']);
        $Collection->association_id = $association_id;

        return $Collection;
    }

    function getType()
    {
        return 'hasMany';
    }

    function &_setCollectionHandler($association_id, $handler_name)
    {
        if(isset($this->Owner->$association_id)){
            if(!is_array($this->Owner->$association_id)){
                trigger_error(Ak::t('%model_name::%association_id is not a collection array on current %association_id hasMany association',array('%model_name'=>$this->Owner->getModelName(), '%association_id'=>$association_id)), E_USER_NOTICE);
            }
            $associated =& $this->Owner->$association_id;
        }else{
            $associated = array();
            $this->Owner->$association_id =& $associated;
        }

        if(isset($this->Owner->$handler_name)){
            trigger_error(Ak::t('Could not load %association_id on %model_name because "%model_name->%handler_name" attribute '.
            'is already defided and can\' be used as an association placeholder',
            array('%model_name'=>$this->Owner->getModelName(),'%association_id'=>$association_id, '%handler_name'=>$handler_name)),
            E_USER_ERROR);
            return false;
        }else{
            $this->Owner->$handler_name =& new AkHasMany($this->Owner);
        }
        return $this->Owner->$handler_name;
    }


    function &load($force_reload = false)
    {
        $options = $this->getOptions($this->association_id);
        if($force_reload || empty($this->Owner->{$options['handler_name']}->_loaded)){
            if(!$this->Owner->isNewRecord()){
                $this->constructSql();
                $options = $this->getOptions($this->association_id);
                $Associated =& $this->getAssociatedModelInstance();
                $finder_options = array('conditions'=>$options['finder_sql']);
                if(!empty($options['order'])){
                    $finder_options['order'] = $options['order'];
                }
                if($FoundAssociates = $Associated->find('all',$finder_options)){
                    array_map(array(&$this,'_setAssociatedMemberId'),$FoundAssociates);
                    $this->Owner->{$this->association_id} =& $FoundAssociates;
                }
            }
            if(empty($this->Owner->{$this->association_id})){
                $this->Owner->{$this->association_id} = array();
            }

            $this->Owner->{$options['handler_name']}->_loaded = true;
        }
        return $this->Owner->{$this->association_id};
    }


    /**
     * add($object), add(array($object, $object2)) - adds one or more objects to the collection by setting 
     * their foreign keys to the collection?s primary key. Items are saved automatically when parent has been saved.
     */
    function add(&$Associated)
    {
        if(is_array($Associated)){
            $succes = true;
            $succes = $this->Owner->notifyObservers('beforeAdd') ? $succes : false;
            foreach (array_keys($Associated) as $k){
                if($succes && !$this->_hasAssociatedMember($Associated[$k])){
                    $this->Owner->{$this->association_id}[] =& $Associated[$k];
                    $this->_setAssociatedMemberId($Associated[$k]);
                    if($this->_relateAssociatedWithOwner($Associated[$k])){
                        $succes = $Associated[$k]->save() ? $succes : false;
                    }
                }
            }
            $succes = $this->Owner->notifyObservers('afterAdd') ? $succes : false;
            return $succes;
        }else{
            $associates = array();
            $associates[] =& $Associated;
            return $this->add($associates);
        }
    }

    function push(&$record)
    {
        return $this->add($record);
    }

    function concat(&$record)
    {
        return $this->add($record);
    }

    /**
    * Remove all records from this association
    */
    function deleteAll($Skip = null)
    {
        $this->load();
        return $this->delete($this->Owner->{$this->association_id}, $Skip);
    }

    function reset()
    {
        $options = $this->getOptions($this->association_id);
        $this->Owner->{$options['handler_name']}->_loaded = false;
    }

    function set(&$objects)
    {
        $this->deleteAll($objects);
        $this->add($objects);
    }

    function setIds()
    {
        $ids = func_get_args();
        $ids = is_array($ids[0]) ? $ids[0] : $ids;
        $AssociatedModel =& $this->getAssociatedModelInstance();
        if(!empty($ids)){
            $NewAssociates =& $AssociatedModel->find($ids);
            $this->set($NewAssociates);
        }
    }

    function setByIds()
    {
        $ids = func_get_args();
        call_user_func_array(array($this,'setIds'), $ids);
    }

    function addId($id)
    {
        $AssociatedModel =& $this->getAssociatedModelInstance();
        if($NewAssociated = $AssociatedModel->find($id)){
            return $this->add($NewAssociated);
        }
        return false;
    }


    function delete(&$Associated, $Skip = null)
    {
        $success = true;
        if(!is_array($Associated)){
            $associated_elements = array();
            $associated_elements[] =& $Associated;
            return $this->delete($associated_elements, $Skip);
        }else{
            $options = $this->getOptions($this->association_id);

            $ids_to_skip = array();
            $Skip = empty($Skip) ? null : (is_array($Skip) ? $Skip : array($Skip));
            if(!empty($Skip)){
                foreach (array_keys($Skip) as $k){
                    $ids_to_skip[] = $Skip[$k]->getId();
                }
            }

            $ids_to_nullify = array();
            $ids_to_delete = array();
            $items_to_remove_from_collection = array();
            $AssociatedModel =& $this->getAssociatedModelInstance();

            $owner_type = $this->_findOwnerTypeForAssociation($AssociatedModel, $this->Owner);
            $allow_dependency = isset($AssociatedModel->$owner_type->_associatedAs) && $AssociatedModel->$owner_type->_associatedAs == 'belongsTo' && $AssociatedModel->$owner_type->getAssociationOption('dependent') ;

            foreach (array_keys($Associated) as $k){
                $items_to_remove_from_collection[] = $Associated[$k]->getId();
                if(!in_array($Associated[$k]->getId() , $ids_to_skip)){
                    switch ($options['dependent']) {
                        case 'destroy':
                        $success = $allow_dependency && $Associated[$k]->destroy() ? $success : false;
                        break;
                        case 'delete_all':
                        if($allow_dependency){
                            $ids_to_delete[] = $Associated[$k]->getId();
                        }
                        break;
                        case 'nullify':
                        $ids_to_nullify[] = $Associated[$k]->quotedId();
                        default:
                        break;
                    }
                }
            }

            if(!empty($ids_to_nullify)){
                $success = $AssociatedModel->updateAll(
                ' '.$options['foreign_key'].' = NULL ',
                ' '.$options['foreign_key'].' = '.$this->Owner->quotedId().' AND '.$AssociatedModel->getPrimaryKey().' IN ('.join(', ',$ids_to_nullify).')'
                ) ? $success : false;
            }elseif(!empty($ids_to_delete)){
                $success = $AssociatedModel->delete($ids_to_delete) ? $success : false;
            }

            //$items_to_remove_from_collection

            $this->removeFromCollection($items_to_remove_from_collection);
            //$this->removeFromCollection($Associated);
        }

        return $success;
    }



    /**
    * Remove records from the collection. Use delete() in order to trigger database dependencies
    */
    function removeFromCollection(&$records)
    {
        if(!is_array($records)){
            $records_array = array();
            $records_array[] =& $records;
            $this->delete($records_array);
        }else{
            $this->Owner->notifyObservers('beforeRemove');
            foreach (array_keys($records) as $k){
                if(isset($records[$k]->__activeRecordObject)){
                    $record_id = $records[$k]->getId();
                }else{
                    $record_id = $records[$k];
                }

                foreach (array_keys($this->Owner->{$this->association_id}) as $kk){
                    if(
                    (
                    !empty($this->Owner->{$this->association_id}[$kk]->__hasManyMemberId) &&
                    !empty($records[$k]->__hasManyMemberId) &&
                    $records[$k]->__hasManyMemberId == $this->Owner->{$this->association_id}[$kk]->__hasManyMemberId
                    ) || (
                    !empty($this->Owner->{$this->association_id}[$kk]->__activeRecordObject) &&
                    $record_id == $this->Owner->{$this->association_id}[$kk]->getId()
                    )
                    ){
                        unset($this->Owner->{$this->association_id}[$kk]);
                    }
                }
                $this->_unsetAssociatedMemberId($records[$k]);
            }
            $this->Owner->notifyObservers('afterRemove');
        }
        $this->Owner->{$this->association_id} = array_diff($this->Owner->{$this->association_id},array(null));
    }




    function _setAssociatedMemberId(&$Member)
    {
        if(empty($Member->__hasManyMemberId)) {
            $Member->__hasManyMemberId = Ak::randomString();
        }
        $object_id = $Member->getId();
        if(!empty($object_id)){
            $this->asssociated_ids[$object_id] = $Member->__hasManyMemberId;
        }
    }

    function _unsetAssociatedMemberId(&$Member)
    {
        $id = $this->_getAssociatedMemberId($Member);
        unset($this->asssociated_ids[$id]);
        unset($Member->__hasManyMemberId);
    }

    function _getAssociatedMemberId(&$Member)
    {
        if(!empty($Member->__hasManyMemberId)) {
            return array_search($Member->__hasManyMemberId, $this->asssociated_ids);
        }
        return false;
    }

    function _hasAssociatedMember(&$Member)
    {
        return !empty($Member->__hasManyMemberId);
    }


    function _relateAssociatedWithOwner(&$Associated)
    {
        if(!$this->Owner->isNewRecord()){
            $foreign_key = $this->getOption($this->association_id, 'foreign_key');
            $Associated->set($foreign_key, $this->Owner->getId());
            return true;
        }
        return false;
    }

    function &_build($association_id, &$AssociatedObject, $reference_associated = true)
    {
        if($reference_associated){
            $this->Owner->$association_id =& $AssociatedObject;
        }else{
            $this->Owner->$association_id = $AssociatedObject;
        }
        $this->Owner->$association_id->_AssociationHandler =& $this;
        $this->Owner->$association_id->_associatedAs = $this->getType();
        $this->Owner->$association_id->_associationId = $association_id;
        $this->Owner->_associations[$association_id] =& $this->Owner->$association_id;
        return $this->Owner->$association_id;
    }




    function constructSql()
    {
        $options = $this->getOptions($this->association_id);
        $Associated =& $this->getAssociatedModelInstance();
        $owner_id = $this->Owner->quotedId();
        if(empty($options['finder_sql'])){
            $options['finder_sql'] = ' '.$Associated->getTableName().'.'.$options['foreign_key'].' = '.(empty($owner_id) ? 'null' : $owner_id).' ';
            $options['finder_sql'] .= !empty($options['conditions']) ? ' AND '.$options['conditions'].' ' : '';
        }
        if(empty($options['counter_sql']) && !empty($options['finder_sql'])){
            $options['counter_sql'] = $options['finder_sql'];
        }elseif(empty($options['counter_sql'])){
            $options['counter_sql'] = ' '.$Associated->getTableName().'.'.$options['foreign_key'].' = '.(empty($owner_id) ? 'null' : $owner_id).' ';
            $options['counter_sql'] .= !empty($options['conditions']) ? ' AND '.$options['conditions'].' ' : '';
        }

        if(!empty($options['counter_sql']) && strtoupper(substr($options['counter_sql'],0,6)) != 'SELECT'){
            $options['counter_sql'] = 'SELECT COUNT(*) FROM '.$Associated->getTableName().' WHERE '.$options['counter_sql'];
        }

        $this->setOptions($this->association_id, $options);
    }


    /**
     * Enter description here...
     *
     * @return unknown
     */
    function count()
    {
        $count = 0;
        $options = $this->getOptions($this->association_id);
        if(empty($this->Owner->{$options['handler_name']}->_loaded) && !$this->Owner->isNewRecord()){
            $this->constructSql();
            $options = $this->getOptions($this->association_id);
            $Associated =& $this->getAssociatedModelInstance();

            if($this->_hasCachedCounter()){
                $count = $Associated->getAttribute($this->_getCachedCounterAttributeName());
            }elseif(!empty($options['counter_sql'])){
                $count = $Associated->countBySql($options['counter_sql']);
            }else{
                $count = (strtoupper(substr($options['finder_sql'],0,6)) != 'SELECT') ?
                $Associated->count($options['foreign_key'].'='.$this->Owner->quotedId()) :
                $Associated->countBySql($options['finder_sql']);
            }
        }else{
            $count = count($this->Owner->{$this->association_id});
        }

        if($count == 0){
            $this->Owner->{$this->association_id} = array();
            $this->Owner->{$options['handler_name']}->_loaded = true;
        }

        return $count;
    }

    function size()
    {
        return $this->count();
    }


    function &build($attributes = array(), $set_as_new_record = true)
    {
        $options = $this->getOptions($this->association_id);
        $record =& new $options['class_name']($attributes);
        $record->_newRecord = $set_as_new_record;
        $this->Owner->{$this->association_id}[] =& $record;
        $this->_setAssociatedMemberId($record);
        $this->_relateAssociatedWithOwner($record);
        return $record;
    }

    function &create($attributes = array())
    {
        $record =& $this->build($attributes);
        if(!$this->Owner->isNewRecord()){
            $record->save();
        }
        return $record;
    }


    function getAssociatedFinderSqlOptions($association_id, $options = array())
    {
        $options = $this->getOptions($this->association_id);
        $Associated =& $this->getAssociatedModelInstance();
        $table_name = $Associated->getTableName();
        $owner_id = $this->Owner->quotedId();

        $finder_options = array();

        foreach ($options as $option=>$value) {
            if(!empty($value)){
                $finder_options[$option] = trim($Associated->_addTableAliasesToAssociatedSql('_'.$this->association_id, $value));
            }
        }

        $finder_options['joins'] = $this->constructSqlForInclusion();
        $finder_options['selection'] = '';

        foreach (array_keys($Associated->getColumns()) as $column_name){
            $finder_options['selection'] .= '_'.$this->association_id.'.'.$column_name.' AS _'.$this->association_id.'_'.$column_name.', ';
        }

        $finder_options['selection'] = trim($finder_options['selection'], ', ');

        /**
         * @todo Refactorize me. This is too confusing
         */
        $finder_options['conditions'] =
        // If owner is not available we build the searcher without an specific id
        (empty($owner_id) ? '' :

        // We have an Id so we add it to the conditions
        ' '.$Associated->_addTableAliasesToAssociatedSql('_'.$this->association_id, $options['foreign_key']).' = '.$owner_id.' '.
        // After adding the Id we need to add AND in case we have a previous contidion available
        (!empty($options['conditions']) ? ' AND ' : ' ')).

        // We add previous conditions
        (!empty($options['conditions']) ?
        $Associated->_addTableAliasesToAssociatedSql('_'.$this->association_id, $options['conditions']).' ' : '');

        return $finder_options;
    }

    function constructSqlForInclusion()
    {
        $Associated =& $this->getAssociatedModelInstance();
        $options = $this->getOptions($this->association_id);
        return ' LEFT OUTER JOIN '.
        $Associated->getTableName().' AS _'.$this->association_id.
        ' ON '.
        '__owner.'.$this->Owner->getPrimaryKey().
        ' = '.
        '_'.$this->association_id.'.'.$options['foreign_key'].' ';
    }


    function _hasCachedCounter()
    {
        $Associated =& $this->getAssociatedModelInstance();
        return $Associated->isAttributePresent($this->_getCachedCounterAttributeName());
    }

    function _getCachedCounterAttributeName()
    {
        return $this->association_id.'_count';
    }


    function &getAssociatedModelInstance()
    {
        static $ModelInstance;
        if(empty($ModelInstance)){
            $class_name = $this->getOption($this->association_id, 'class_name');
            $ModelInstance =& new $class_name();
        }
        return $ModelInstance;
    }


    function &find()
    {
        $result =& $GLOBALS['false'];
        if(!$this->Owner->isNewRecord()){
            $this->constructSql();
            $has_many_options = $this->getOptions($this->association_id);
            $Associated =& $this->getAssociatedModelInstance();

            $args = func_get_args();
            $num_args = func_num_args();

            if(!empty($args[$num_args-1]) && is_array($args[$num_args-1])){
                $options_in_args = true;
                $options = $args[$num_args-1];
            }else{
                $options_in_args = false;
                $options = array();
            }

            if (empty($options['conditions'])) {
                $options['conditions'] = @$has_many_options['finder_sql'];
            } elseif(!empty($has_many_options['finder_sql']) && is_array($options['conditions']) && !strstr($options['conditions'][0], $has_many_options['finder_sql'])) {
                $options['conditions'][0] .= ' AND '. $has_many_options['finder_sql'];
            } elseif (!empty($has_many_options['finder_sql']) && !strstr($options['conditions'], $has_many_options['finder_sql'])) {
                $options['conditions'] .= ' AND '. $has_many_options['finder_sql'];
            }


            $options['order'] = empty($options['order']) ? @$has_many_options['order'] : $options['order'];

            if($options_in_args){
                $args[$num_args-1] = $options;
            }else{
                $args = empty($args) ? array('all') : $args;
                array_push($args, $options);
            }

            $result =& Ak::call_user_func_array(array(&$Associated,'find'), $args);
        }

        return $result;
    }


    function isEmpty()
    {
        return $this->count() === 0;
    }

    function getSize()
    {
        return $this->count();
    }

    function clear()
    {
        return $this->deleteAll();
    }

    /**
    * Triggers
    */
    function afterCreate(&$object)
    {
        return $this->_afterCallback($object);
    }

    function afterUpdate(&$object)
    {
        return $this->_afterCallback($object);
    }


    function beforeDestroy(&$object)
    {
        $success = true;

        foreach ((array)$object->_associationIds as $k => $v){
            if(isset($object->$k) && is_array($object->$k) && isset($object->$v) && method_exists($object->$v, 'getType') && $object->$v->getType() == 'hasMany'){

                $ids_to_delete = array();
                $ids_to_nullify = array();
                $items_to_remove_from_collection = array();

                $object->$v->load();
                foreach(array_keys($object->$k) as $key){

                    $items_to_remove_from_collection[] =& $object->{$k}[$key];

                    switch ($object->$v->options[$k]['dependent']) {

                        case 'destroy':
                        $success = $object->{$k}[$key]->destroy() ? $success : false;
                        break;

                        case 'delete_all':
                        $ids_to_delete[] = $object->{$k}[$key]->getId();
                        break;

                        case 'nullify':
                        $ids_to_nullify[] = $object->{$k}[$key]->quotedId();
                        break;

                        default:
                        break;
                    }
                }

                if(!empty($ids_to_nullify)){
                    $success = $object->{$k}[$key]->updateAll(
                    ' '.$object->$v->options[$k]['foreign_key'].' = NULL ',
                    ' '.$object->$v->options[$k]['foreign_key'].' = '.$object->quotedId().' AND '.$object->{$k}[$key]->getPrimaryKey().' IN ('.join(', ',$ids_to_nullify).')'
                    ) ? $success : false;
                }elseif(!empty($ids_to_delete)){
                    $success = $object->{$k}[$key]->delete($ids_to_delete) ? $success : false;
                }
                $object->$v->removeFromCollection($items_to_remove_from_collection);
            }
        }

        return $success;
    }

    function _afterCallback(&$object)
    {

        $success = true;

        $object_id = $object->getId();
        foreach (array_keys($object->hasMany->models) as $association_id){
            $CollectionHandler =& $object->hasMany->models[$association_id];
            $foreign_key = $CollectionHandler->getOption($association_id, 'foreign_key');
            $class_name = strtolower($CollectionHandler->getOption($association_id, 'class_name'));
            if(!empty($object->$association_id) && is_array($object->$association_id)){
                foreach (array_keys($object->$association_id) as $k){
                    if(!empty($object->{$association_id}[$k]) && strtolower(get_class($object->{$association_id}[$k])) == strtolower($class_name)){
                        $AssociatedItem =& $object->{$association_id}[$k];
                        $AssociatedItem->set($foreign_key, $object_id);
                        $success = !$AssociatedItem->save() ? false : $success;
                    }
                }
            }
        }
        return $success;
    }

}


?>
