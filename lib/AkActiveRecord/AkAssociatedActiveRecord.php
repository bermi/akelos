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

require_once(AK_LIB_DIR.DS.'AkObject.php');

/*
Adds the following methods for retrieval and query of a single associated object. association is replaced with the symbol passed as the first argument, so has_one :manager would add among others manager.nil?.

* association(force_reload = false) - returns the associated object. Nil is returned if none is found.
* association=(associate) - assigns the associate object, extracts the primary key, sets it as the foreign key, and saves the associate object.
* association.nil? - returns true if there is no associated object.
* build_association(attributes = {}) - returns a new object of the associated type that has been instantiated with attributes and linked to this object through a foreign key but has not yet been saved. Note: This ONLY works if an association already exists. It will NOT work if the association is nil.
* create_association(attributes = {}) - returns a new object of the associated type that has been instantiated with attributes and linked to this object through a foreign key and that has already been saved (if it passed the validation).

Example: An Account class declares has_one :beneficiary, which will add:

* Account#beneficiary (similar to Beneficiary.find(:first, :conditions => "account_id = #{id}"))
* Account#beneficiary=(beneficiary) (similar to beneficiary.account_id = account.id; beneficiary.save)
* Account#beneficiary.nil?
* Account#build_beneficiary (similar to Beneficiary.new("account_id" => id))
* Account#create_beneficiary (similar to b = Beneficiary.new("account_id" => id); b.save; b)
*/


class AkAssociatedActiveRecord extends AkObject
{
    var $__activeRecordObject = false;
    var $_AssociationHandler;
    var $_associationId = false;
    // Holds different association IDs related to this model
    var $_associationIds = array();
    var $_associations = array();

    function _loadAssociationHandler($association_type)
    {
        if(empty($this->$association_type) && in_array($association_type, array('hasOne','belongsTo','hasMany','hasAndBelongsToMany'))){
            $association_handler_class_name = 'Ak'.ucfirst($association_type);
            require_once(AK_LIB_DIR.DS.'AkActiveRecord'.DS.$association_handler_class_name.'.php');
            $this->$association_type =& new $association_handler_class_name($this);
        }
        return !empty($this->$association_type);
    }

    function setAssociationHandler(&$AssociationHandler, $association_id)
    {
        $this->_AssociationHandler =& $AssociationHandler;
    }

    function loadAssociations()
    {
        $association_aliases = array(
        'hasOne' => array('hasOne','has_one'),
        'belongsTo' => array('belongsTo','belongs_to'),
        'hasMany' => array('hasMany','has_many'),
        'hasAndBelongsToMany' => array('hasAndBelongsToMany', 'habtm', 'has_and_belongs_to_many'),
        );

        foreach ($association_aliases as $association_type=>$aliases){
            $association_details = false;
            foreach ($aliases as $alias){
                if(empty($association_details) && !empty($this->$alias)){
                    $association_details = $this->$alias;
                }
                unset($this->$alias);
            }
            if(!empty($association_details) && $this->_loadAssociationHandler($association_type)){
                $this->$association_type->initializeAssociated($association_details);
                $this->_associations[$association_type] =& $this->$association_type;
            }
        }
    }

    /**
     * Gets an array of associated object of selected association type.
     */
    function &getAssociated($association_type)
    {
        $result = array();
        if(!empty($this->$association_type) && in_array($association_type, array('hasOne','belongsTo','hasMany','hasAndBelongsToMany'))){
            $result =& $this->$association_type->getModels();
        }
        return $result;
    }

    function getId()
    {
        return false;
    }


    function &assign(&$Associated)
    {
        $result = $GLOBALS['false'];
        if(is_object($this->_AssociationHandler)){
            $result =& $this->_AssociationHandler->assign($this->getAssociationId(), $Associated);
        }
        return $result;
    }

    /**
     * Returns a new object of the associated type that has been instantiated with attributes 
     * and linked to this object through a foreign key but has not yet been saved.
     */
    function &build($attributes = array(), $replace_existing = true)
    {
        $result = $GLOBALS['false'];
        if(!empty($this->_AssociationHandler)){
            $result =& $this->_AssociationHandler->build($this->getAssociationId(), $attributes, $replace_existing);
        }
        return $result;
    }


    function &create($attributes = array(), $replace_existing = true)
    {
        $result = $GLOBALS['false'];
        if(!empty($this->_AssociationHandler)){
            $result =& $this->_AssociationHandler->create($this->getAssociationId(), $attributes, $replace_existing);
        }
        return $result;
    }

    function &replace(&$NewAssociated, $dont_save = false)
    {
        $result = $GLOBALS['false'];
        if(!empty($this->_AssociationHandler)){
            $result =& $this->_AssociationHandler->replace($this->getAssociationId(), $NewAssociated, $dont_save = false);
        }
        return $result;
    }

    function &find()
    {
        $result = $GLOBALS['false'];
        if(!empty($this->_AssociationHandler)){
            $result =& $this->_AssociationHandler->findAssociated($this->getAssociationId());
        }
        return $result;
    }

    function &load()
    {
        $result = $GLOBALS['false'];
        if(!empty($this->_AssociationHandler)){
            $result =& $this->_AssociationHandler->loadAssociated($this->getAssociationId());
        }
        return $result;
    }

    function constructSql()
    {
        return !empty($this->_AssociationHandler) ? $this->_AssociationHandler->constructSql($this->getAssociationId()) : false;
    }

    function constructSqlForInclusion()
    {
        return !empty($this->_AssociationHandler) ? $this->_AssociationHandler->constructSqlForInclusion($this->getAssociationId()) : false;
    }

    function getAssociatedFinderSqlOptions($options = array())
    {
        return !empty($this->_AssociationHandler) ? $this->_AssociationHandler->getAssociatedFinderSqlOptions($this->getAssociationId(), $options) : false;
    }

    function getAssociationOption($option)
    {
        return !empty($this->_AssociationHandler) ? $this->_AssociationHandler->getOption($this->getAssociationId(), $option) : false;
    }

    function setAssociationOption($option, $value)
    {
        return !empty($this->_AssociationHandler) ? $this->_AssociationHandler->setOption($this->getAssociationId(), $option, $value) : false;
    }

    function getAssociationId()
    {
        if(empty($this->_associationId)){
            trigger_error(Ak::t('You are trying to access a non associated Object property. '.
            'This error might have been caused by asigning directly an object '.
            'to the association instead of using the "assign()" method'),E_USER_WARNING);
        }
        return $this->_associationId;
    }

    function getAssociatedIds()
    {
        return array_keys($this->_associationIds);
    }

    function getAssociatedHandlerName($association_id)
    {
        return empty($this->_associationIds[$association_id]) ? false : $this->_associationIds[$association_id];
    }

    function getAssociatedType()
    {
        return !empty($this->_AssociationHandler) ? $this->_AssociationHandler->getType() : false;
    }

    function getAssociationType()
    {
        return $this->getAssociatedType();
    }

    function getType()
    {
        return $this->getAssociatedType();
    }


    function hasAssociations()
    {
        return !empty($this->_associations) && count($this->_associations) > 0;
    }

    function &findWithAssociations($options, $limit = null, $offset = null)
    {
        $result = $GLOBALS['false'];

        $options['include'] = is_array($options['include']) ? $options['include'] : array($options['include']);
        $options['order'] = empty($options['order']) ? '' : $options['order'];
        $options['conditions'] = empty($options['conditions']) ? '' : $this->_addTableAliasesToAssociatedSql('__owner', $options['conditions']);

        $included_associations = array();
        $included_association_options = array();
        foreach ($options['include'] as $k=>$v){
            if(is_numeric($k)){
                $included_associations[] = $v;
            }else {
                $included_associations[] = $k;
                $included_association_options[$k] = $v;
            }
        }

        $avaliable_associated_options = array('order'=>array(), 'conditions'=>array(), 'joins'=>array(), 'selection'=>array());

        foreach ($included_associations as $association_id){
            $association_options = empty($included_association_options[$association_id]) ? array() : $included_association_options[$association_id];

            $handler_name = $this->getCollectionHandlerName($association_id);
            $handler_name = empty($handler_name) ? $association_id : (in_array($handler_name, $included_associations) ? $association_id : $handler_name);
            $associated_options = $this->$handler_name->getAssociatedFinderSqlOptions($association_options);

            foreach (array_keys($avaliable_associated_options) as $associated_option){
                if(!empty($associated_options[$associated_option])){
                    $avaliable_associated_options[$associated_option][] = $associated_options[$associated_option];
                }
            }
        }

        foreach ($avaliable_associated_options as $option=>$values){
            if(!empty($values)){
                $separator = $option == 'joins' ? ' ' : (in_array($option, array('selection','order')) ? ', ': ' AND ');
                $values = array_map('trim', $values);
                $options[$option] = empty($options[$option]) ?
                join($separator, $values) :
                trim($options[$option]).$separator.join($separator, $values);
            }
        }
        
        $sql = trim($this->constructFinderSqlWithAssociations($options));
        $sql = substr($sql, -5) == 'AND =' ? substr($sql, 0,-5) : $sql;

        if(!empty($options['bind']) && is_array($options['bind']) && strstr($sql,'?')){
            $sql = array_merge(array($sql),$options['bind']);
        }

        $result =& $this->_findBySqlWithAssociations($sql, $limit, $offset, $options['include']);

        return $result;
    }


    function getCollectionHandlerName($association_id)
    {
        if(isset($this->$association_id) && is_object($this->$association_id) && method_exists($this->$association_id,'getAssociatedFinderSqlOptions')){
            return false;
        }
        $collection_handler_name = AkInflector::singularize($association_id);
        if(isset($this->$collection_handler_name) &&
        is_object($this->$collection_handler_name)  &&
        in_array($this->$collection_handler_name->getType(),array('hasMany','hasAndBelongsToMany'))){
            return $collection_handler_name;
        }else{
            return false;
        }
    }


    function constructFinderSqlWithAssociations($options, $columns = array())
    {
        $selection = '';
        foreach (array_keys($this->getColumns()) as $column_name){
            $selection .= '__owner.'.$column_name.' AS __owner_'.$column_name.', ';
        }

        $sql  = 'SELECT '.trim($selection.@$options['selection'], ', ').' '.
        'FROM '.$this->getTableName().' AS __owner '.
        (!empty($options['joins']) ? $options['joins'].' ' : '');

        empty($options['conditions']) ? null : $this->addConditions($sql, $options['conditions']);

        // Create an alias for order
        if(empty($options['order']) && !empty($options['sort'])){
            $options['order'] = $options['sort'];
        }
        $sql  .= !empty($options['order']) ? ' ORDER BY  '.$options['order'] : '';
        return $sql;
    }


    function &_findBySqlWithAssociations($sql, $limit = null, $offset = null, $included_associations = array())
    {
        if(is_array($sql)){
            $sql_query = array_shift($sql);
            $bindings = is_array($sql) && count($sql) > 0 ? $sql : array($sql);
            $sql = $sql_query;
        }
        $this->setConnection();
        $objects = array();
        if(is_integer($limit)){
            if(is_integer($offset)){
                $results = !empty($bindings) ?
                $this->_db->SelectLimit($sql, $limit, $offset, $bindings) :
                $this->_db->SelectLimit($sql, $limit, $offset);
            }else {
                $results = !empty($bindings) ?
                $this->_db->SelectLimit($sql, $limit, -1, $bindings) :
                $this->_db->SelectLimit($sql, $limit);
            }
        }else{
            $results = !empty($bindings) ?
            $this->_db->Execute($sql, $bindings) :
            $this->_db->Execute($sql);
        }

        if(!$results && AK_DEBUG){
            trigger_error($this->_db->ErrorMsg(), E_USER_NOTICE);
        }else{
            $objects = array();
            $i = 0;
            $associated_ids = $this->getAssociatedIds();
            $ids = array();
            while ($record = $results->FetchRow()) {
                $this_item_attributes = array();
                $associated_items = array();
                foreach ($record as $column=>$value){
                    if(!is_numeric($column)){
                        if(substr($column,0,8) == '__owner_'){
                            $attribute_name = substr($column,8);
                            $this_item_attributes[$attribute_name] = $value;
                        }elseif(preg_match('/^_('.join('|',$associated_ids).')_(.+)/',$column, $match)){
                            $associated_items[$match[1]][$match[2]] = $value;
                        }
                    }
                }

                
                // We need to keep a pointer to unique parent elements in order to add associates to the first loaded item
                $e = null;
                $object_id = $this_item_attributes[$this->getPrimaryKey()];
                if(!isset($ids[$object_id])){
                    $ids[$object_id] = $i;
                    $objects[$i] =& $this->instantiate($this->removeUnavailableAttributes($this_item_attributes), false);;
                }else{
                    $e = $i;
                    $i = $ids[$object_id];
                }

                foreach ($associated_items as $association_id=>$attributes){
                    if(count(array_diff($attributes, array(''))) > 0){
                        if(!method_exists($objects[$i]->$association_id, 'build')){
                            $handler_name = $this->getAssociatedHandlerName($association_id);
                            $objects[$i]->$handler_name->build($attributes, false);
                        }else{
                            $objects[$i]->$association_id->build($attributes, false);
                            $objects[$i]->$association_id->_newRecord = false;
                        }

                        /**
                     * @todo FIXME This is a dirty hack for sqlite table joins which are not exclusive as they are on MySql
                     *       this makes table joins behave the same way as they do on MySql
                     */
                    }elseif (in_array($association_id, $included_associations) && $this->_getDatabaseType() == 'sqlite'){
                        return $GLOBALS['false'];
                    }
                }
                
                $i = !is_null($e) ? $e : $i+1;
            }
        }

        if(!empty($objects)){
            $result =& $objects;
        }else{
            $result =& $GLOBALS['false'];
        }

        return $result;
    }


    function _addTableAliasesToAssociatedSql($table_alias, $sql)
    {
        return preg_replace($this->getColumnsWithRegexBoundaries(),'\1'.$table_alias.'.\2',' '.$sql.' ');
    }

}


?>
