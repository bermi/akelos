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
 * @subpackage Base
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

require_once(AK_LIB_DIR.DS.'AkBaseModel.php');

/**
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


class AkAssociatedActiveRecord extends AkBaseModel
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
            require_once(AK_LIB_DIR.DS.'AkActiveRecord'.DS.'AkAssociations'.DS.$association_handler_class_name.'.php');
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
        $result = false;
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
        $result = false;
        if(!empty($this->_AssociationHandler)){
            $result =& $this->_AssociationHandler->build($this->getAssociationId(), $attributes, $replace_existing);
        }
        return $result;
    }


    function &create($attributes = array(), $replace_existing = true)
    {
        $result = false;
        if(!empty($this->_AssociationHandler)){
            $result =& $this->_AssociationHandler->create($this->getAssociationId(), $attributes, $replace_existing);
        }
        return $result;
    }

    function &replace(&$NewAssociated, $dont_save = false)
    {
        $result = false;
        if(!empty($this->_AssociationHandler)){
            $result =& $this->_AssociationHandler->replace($this->getAssociationId(), $NewAssociated, $dont_save = false);
        }
        return $result;
    }

    function &find()
    {
        $result = false;
        if(!empty($this->_AssociationHandler)){
            $result =& $this->_AssociationHandler->findAssociated($this->getAssociationId());
        }
        return $result;
    }

    function &load()
    {
        $result = false;
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
    
    /**
     * Experimental!!!!
     * 
     * Allows you to get a 2nd level association
     * 
     * Example:
     * 
     * $user->findBy('name','user',array('include'=>array('roles'=>array('include'=>'permissions'))));
     *
     * @param unknown_type $options
     * @return unknown
     */
    function &_findWithAssociationsExt($options)
    {
        $result = false;
        $options ['include'] = Ak::toArray($options ['include']);
        $options ['order'] = empty($options ['order']) ? '' : $this->_addTableAliasesToAssociatedSql('__owner', $options ['order']);
        $options ['group'] = empty($options ['group']) ? '' : $this->_addTableAliasesToAssociatedSql('__owner', $options ['group']);
        $options ['conditions'] = empty($options ['conditions']) ? '' : $this->_addTableAliasesToAssociatedSql('__owner', $options ['conditions']);
        
        $included_associations = array ();
        $included_association_options = array ();
        foreach ( $options ['include'] as $k => $v ) {
            if (is_numeric($k)) {
                $included_associations [] = $v;
                //$options['include'][$k] = AkInflector::pluralize($v);
            } else {
                $included_associations [] = $k;
                $included_association_options [$k] = $v;
            }
        }
        
        $available_associated_options = array ('order' => array (), 'conditions' => array (), 'joins' => array (), 'selection' => array () );
        
        foreach ( $included_associations as $association_id ) {
            $association_options = empty($included_association_options [$association_id]) ? array () : $included_association_options [$association_id];
            
            $handler_name = $this->getCollectionHandlerName($association_id);
            $handler_name = empty($handler_name) ? $association_id : (in_array($handler_name, $included_associations) ? $association_id : $handler_name);
            $associated_options = $this->$handler_name->getAssociatedFinderSqlOptions($association_options);

            foreach ( array_keys($available_associated_options) as $associated_option ) {
                if (! empty($associated_options [$associated_option])) {
                    $available_associated_options [$associated_option] [] = $associated_options [$associated_option];
                }
            }
            if (isset($association_options ['include'])) {
                $extended_config = array();
                $association_options ['include'] = Ak::toArray($association_options ['include']);
                if (isset($this->$handler_name) && method_exists($this->$handler_name,'getModelName')) {
                    $main_association_class_name = $this->$handler_name->getModelName();
                    Ak::import($main_association_class_name);
                    $sub_association_object = new $main_association_class_name;
                } else {
                    $sub_association_object = &$this->$handler_name->getAssociatedModelInstance();
                    $main_association_class_name = $sub_association_object->getModelName();
                }
                foreach ( $association_options ['include'] as $sub_association_id ) {
                    $sub_handler_name = $sub_association_object->getCollectionHandlerName($sub_association_id);
                    if (!$sub_handler_name) {
                        $sub_handler_name = $sub_association_id;
                    }
                    
                    $sub_associated_options = $sub_association_object->$sub_handler_name->getAssociatedFinderSqlOptions(
                                $association_options);
                                
                    $type = $sub_association_object->$sub_handler_name->getType();
                    
                    if ($type == 'hasMany' || $type ==
                             'hasAndBelongsToMany') {
                       $instance=&$sub_association_object->$sub_handler_name->getAssociatedModelInstance();
                       $pk = $instance->getPrimaryKey();
                       
                       $pluralize = true;
                       
                       $subs = Ak::toArray($options['include'][$association_id]['include']);
                       $subs = array_diff($subs,array($sub_association_id));
                       $name = AkInflector::singularize($sub_association_id)==$sub_association_id?AkInflector::pluralize($sub_association_id):$sub_association_id;
                       $subs[] = $name;
                       $options['include'][$association_id]['include'] = $subs;
                    } else if ( $type == 'belongsTo' || $type == 'hasOne') {
                        $class_name = $sub_association_object->$sub_handler_name->getAssociationOption('class_name');
                        $ob = new $class_name;
                        $pk = $ob->getPrimaryKey();
                        $pluralize = false;
                        $name = $sub_association_id;
                    } else {
                        $pk = $sub_association_object->$sub_handler_name->getPrimaryKey();
                        $pluralize = false;
                        $name = $sub_association_id;
                    }
                    $extended_config[$name] = array('parent_class_name'=>$main_association_class_name,'parent_primary_key'=>$sub_association_object->getPrimaryKey(),'parent_handler_name'=>$handler_name,'primary_key'=>$sub_association_object->getPrimaryKey(),'belongs_to'=>$association_id);
                    
                    $extended_config[$name]['n-m'] = $pluralize;
                    $extended_config[$name]['primary_key'] = $pk;
                    foreach ( array_keys(
                            $available_associated_options) as $sub_associated_option ) {
                        if (! empty($sub_associated_options [$sub_associated_option])) {

                            $newoption = str_replace(
                                    '__owner.', 
                                    '_' . ($pluralize?AkInflector::pluralize($association_id):$association_id) .
                                             '.', 
                                            $sub_associated_options [$sub_associated_option]);
                            
$available_associated_options [$sub_associated_option] []  = $newoption;
                        }
                    }
                
                }

            }
        }
        $joins = array ();
        foreach ( $available_associated_options as $option => $values ) {
            if (! empty($values)) {
                $separator = $option == 'joins' ? ' ' : (in_array($option, array ('selection', 'order' )) ? ', ' : ' AND ');
                $values = array_map('trim', $values);
                if ($option == 'joins' && ! empty($options [$option])) {
                    $newJoinParts = array ();
                    foreach ( $values as $part ) {
                        if (! stristr($options [$option], $part)) {
                            $newJoinParts [] = $part;
                        }
                    }
                    $values = $newJoinParts;
                }
                
                $options [$option] = empty($options [$option]) ? join($separator, $values) : trim($options [$option]) . $separator . join(
                        $separator, $values);
            
            }
        }
        $sql = trim($this->constructFinderSqlWithAssociations($options));
        if (! empty($options ['bind']) && is_array($options ['bind']) && strstr($sql, '?')) {
            $sql = array_merge(array ($sql ), $options ['bind']);
        }
        
        $result = & $this->_findBySqlWithAssociationsExt($sql, isset($options ['include']) ? $options ['include'] : array (), 
                empty($options ['virtual_limit']) ? false : $options ['virtual_limit'], $extended_config);
        
        return $result;
    }
    function &_findBySqlWithAssociationsExt($sql, $included_associations = array(), $virtual_limit = false, $extended_config = array())
    {
        $objects = array();
        $results = $this->_db->execute ($sql,'find with associations ext');
        if (!$results){
            return $objects;
        }
        $result =& $this->_generateObjectGraphFromResultSetExt($results,$included_associations,$virtual_limit,$extended_config);
        return $result;
    
    }
    function &_generateObjectGraphFromResultSetExt($results,$included_associations = array(), $virtual_limit = false,$internal_assocs = array())
    {
        $objects = array();
        $thirdpartyAssocs = array();

        foreach($included_associations as $assoc=>$options) {
            if (isset($options['include'])) {
                $as = Ak::toArray($options['include']);
                
                $thirdpartyAssocs = array_merge($thirdpartyAssocs,$as);
                
            }
            
        }
        
        $i = 0;
        
        $associated_ids = $this->getAssociatedIds();
        
        
        
        $number_of_associates = count($associated_ids);
        
        $_included_results = array(); // Used only in conjuntion with virtual limits for doing find('first',...include'=>...
        $object_associates_details = array();
        $un_associated_items = array();
        $ids = array();

        $tmp = array();
        $idx=0;
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
                        
                    } else {
                        preg_match('/^_('.join('|',$thirdpartyAssocs).')_(.+)$/',$column, $match);
                        
                        if (isset($match[1])) {
                        $config = isset($internal_assocs[$match[1]])?$internal_assocs[$match[1]]:false;
                        if ($config !== false) {
                        
                        $aname = $config['n-m']?AkInflector::pluralize($config['belongs_to']):$config['belongs_to'];
                        $pk  = isset($record['_'.$match[1].'_'.$config['primary_key']])?$record['_'.$match[1].'_'.$config['primary_key']]:'';
                        empty($pk) ? isset($record['_'.AkInflector::pluralize($match[1]).'_'.$config['primary_key']]) ?
                                     $pk = $record['_'.AkInflector::pluralize($match[1]).'_'.$config['primary_key']]: (isset($record['_'.AkInflector::singularize($match[1]).'_'.$config['primary_key']])?
                                     $pk = $record['_'.AkInflector::singularize($match[1]).'_'.$config['primary_key']]:''): '';
                        if (!empty($pk)) {
                            if ($config['n-m']) {
                                
                                
                                $un_associated_items[$config['belongs_to']][$associated_items[$aname][$config['parent_primary_key']]][$match[1]][$pk][$match[2]] = $value;
                        
                            } else {
                                $un_associated_items[$config['belongs_to']][$associated_items[$aname][$config['parent_primary_key']]][$match[1]][$match[2]] = $value;
                        
                            }
                        }
                        } else {
                            
                        }
                        }
                    }
                }
            }
            // We need to keep a pointer to unique parent elements in order to add associates to the first loaded item
            $e = null;
            $object_id = $this_item_attributes[$this->getPrimaryKey()];
            
            if(!empty($virtual_limit)){
                $_included_results[$object_id] = $object_id;
                //if (MY_DEBUG) var_dump($object_id);
                if(count($_included_results) > $virtual_limit * $number_of_associates){
                    continue;
                }
            }

            if(!isset($ids[$object_id])){
                $ids[$object_id] = $i;
                $attributes_for_instantation = $this->getOnlyAvailableAttributes($this_item_attributes);
                $attributes_for_instantation['load_associations'] = true;
                $objects[$i] =& $this->instantiate($attributes_for_instantation, false);
            }else{
                $e = $i;
                $i = $ids[$object_id];
            }

            foreach ($associated_items as $association_id=>$attributes){
                if(count(array_diff($attributes, array(''))) > 0){
                    $object_associates_details[$i][$association_id][md5(serialize($attributes))] = $attributes;
                }
            }

            $i = !is_null($e) ? $e : $i+1;
        }
        if(!empty($object_associates_details)){
            foreach ($object_associates_details as $i=>$object_associate_details){
                
                foreach ($object_associate_details as $association_id => $associated_attributes){
                    foreach ($associated_attributes as $attributes){
                        if(count(array_diff($attributes, array(''))) > 0){
                            if(!method_exists($objects[$i]->$association_id, 'build')){
                                $handler_name = $this->getAssociatedHandlerName($association_id);
                                $objects[$i]->$handler_name->build($attributes, false);
                                $objects[$i]->$handler_name->_newRecord = false;
                                $use_handler = $handler_name;
                            } else if (isset($objects[$i]->$association_id) && method_exists($objects[$i]->$association_id, 'build')){
                                $objects[$i]->$association_id->build($attributes, false);
                                $objects[$i]->$association_id->_newRecord = false;
                                $use_handler =$association_id;
                            } 
                            
                        }
                    }
                    
                    foreach($un_associated_items as $owned_by=>$data) {
                        foreach ($data as $owner_id => $attributes) {
                            if (!isset($attributes[0])) {
                                $attributes = array($attributes);
                            }
                            foreach ($attributes as $att) {
                                $plural = AkInflector::pluralize($owned_by);
                                $relation_name = key($att);
                               
                                $values = $att[$relation_name];

                                $base = false;
                                if (isset($objects[$i]->$plural) && is_array($objects[$i]->$plural)) {
                                    foreach($objects[$i]->$plural as $idx=>$o) {
                                        if ($o->getID()==$owner_id) {
                                            $base =&$o; 
                                            $relation_name = AkInflector::singularize($relation_name);
                                            if (isset($base->$relation_name)) {
                                                $base = &$base->$relation_name;
                                            } else {
                                                 $relation_name = AkInflector::pluralize($relation_name)!=$relation_name?AkInflector::pluralize($relation_name):AkInflector::singularize($relation_name);
                                                if (isset($base->$relation_name)) {
                                                    $base = &$base->$relation_name;
                                                }
                                            }
                                        }
                                    }
                                } else if(isset($objects[$i]->$owned_by)){
                                    if (isset($objects[$i]->$owned_by->$relation_name)) {
                                        $base = &$objects[$i]->$owned_by->$relation_name;
                                    } else {
                                        $relation_name = AkInflector::pluralize($relation_name)!=$relation_name?AkInflector::pluralize($relation_name):AkInflector::singularize($relation_name);
                                        if (isset($objects[$i]->$owned_by->$relation_name)) {
                                            $base = &$objects[$i]->$owned_by->$relation_name;
                                        }
                                    }
                                    
                                }
                                if ($base !==false && $base !== null && is_object($base)) {
                                    if (!is_object($base)) {
                                        if (MY_DEBUG) var_dump($base);
                                    }
                                    
                                    if (!is_numeric(key($values))) {
                                        $values = array($values);
                                    }
                                    foreach($values as $val) {
                                        $r=&$base->build($val,false);
                                        $r->_newRecord = false;
                                        $r->_loaded = true;
                                    }
                                }
                                
                            }
                        }
                    }
                    
                   
                }
            }
        }

        $result =& $objects;
        return $result;
    }
    function &findWithAssociations($options)
    {
        if (isset($options['group'])) {
            $result=&$this->_findWithAssociationsExt($options);
            return $result;
        }
        $orgoptions =$options;
        $result = false;
        $options['include'] = Ak::toArray($options['include']);
        $options['order'] = empty($options['order']) ? '' : $this->_addTableAliasesToAssociatedSql('__owner', $options['order']);
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
        
        $available_associated_options = array('order'=>array(), 'conditions'=>array(), 'joins'=>array(), 'selection'=>array());

        foreach ($included_associations as $association_id){
            $association_options = empty($included_association_options[$association_id]) ? array() : $included_association_options[$association_id];

            if (isset($association_options['include'])) {
                 $result=&$this->_findWithAssociationsExt($orgoptions);
                 return $result;
            }
            
            $handler_name = $this->getCollectionHandlerName($association_id);
            $handler_name = empty($handler_name) ? $association_id : (in_array($handler_name, $included_associations) ? $association_id : $handler_name);
            $associated_options = $this->$handler_name->getAssociatedFinderSqlOptions($association_options);
            foreach (array_keys($available_associated_options) as $associated_option){
                if(!empty($associated_options[$associated_option])){
                    $available_associated_options[$associated_option][] = $associated_options[$associated_option];
                }
            }
        }

        foreach ($available_associated_options as $option=>$values){
            if(!empty($values)){
                $separator = $option == 'joins' ? ' ' : (in_array($option, array('selection','order')) ? ', ': ' AND ');
                $values = array_map('trim', $values);
                $options[$option] = empty($options[$option]) ?
                join($separator, $values) :
                trim($options[$option]).$separator.join($separator, $values);
            }
        }

        $sql = trim($this->constructFinderSqlWithAssociations($options));

        if(!empty($options['bind']) && is_array($options['bind']) && strstr($sql,'?')){
            $sql = array_merge(array($sql),$options['bind']);
        }
        $result =& $this->_findBySqlWithAssociations($sql, $options['include'], empty($options['virtual_limit']) ? false : $options['virtual_limit']);

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


    /**
     * Used for generating custom selections for habtm, has_many and has_one queries
     */
    function constructFinderSqlWithAssociations($options, $include_owner_as_selection = true)
    {
        $sql = 'SELECT ';
        $selection = '';
        if($include_owner_as_selection){
            foreach (array_keys($this->getColumns()) as $column_name){
                $selection .= '__owner.'.$column_name.' AS __owner_'.$column_name.', ';
            }
            $selection .= (isset($options['selection']) ? $options['selection'].' ' : '');
            $selection = trim($selection,', ').' '; // never used by the unit tests
        }else{
            // used only by HasOne::findAssociated
            $selection .= $options['selection'].'.* ';
        }
        $sql .= $selection;
        $sql .= 'FROM '.($include_owner_as_selection ? $this->getTableName().' AS __owner ' : $options['selection'].' ');
        $sql .= (!empty($options['joins']) ? $options['joins'].' ' : '');

        empty($options['conditions']) ? null : $this->addConditions($sql, $options['conditions'], '__owner');

        // Create an alias for order
        if(empty($options['order']) && !empty($options['sort'])){
            $options['order'] = $options['sort'];
        }
        $sql  .= !empty($options['order']) ? ' ORDER BY  '.$options['order'] : '';

        $this->_db->addLimitAndOffset($sql,$options);
        return $sql;
    }


    /**
     * @todo Refactor in order to increase performance of associated inclussions
     */
    function &_findBySqlWithAssociations($sql, $included_associations = array(), $virtual_limit = false)
    {
        $objects = array();
        $results = $this->_db->execute ($sql,'find with associations');
        if (!$results){
            return $objects;
        }
        $result =& $this->_generateObjectGraphFromResultSet($results,$included_associations,$virtual_limit);
        return $result;
    }
    
    /**
     * Pass hand-made sql directly to _db->execute and generate the OG with this method.
     *
     * @param ADOResultSet $results            a result set from Db->execute
     * @param array $included_associations     just like in ->find(); $options['include']; but in fact unused
     * @param mixed $virtual_limit             int or false; unsure if this works                     
     * @return array                           ObjectGraph as an array
     */
    function &_generateObjectGraphFromResultSet($results,$included_associations = array(), $virtual_limit = false)
    {
        $objects = array();
        
        $i = 0;
        $associated_ids = $this->getAssociatedIds();
        $number_of_associates = count($associated_ids);
        $_included_results = array(); // Used only in conjuntion with virtual limits for doing find('first',...include'=>...
        $object_associates_details = array();
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

            if(!empty($virtual_limit)){
                $_included_results[$object_id] = $object_id;
                if(count($_included_results) > $virtual_limit * $number_of_associates){
                    continue;
                }
            }

            if(!isset($ids[$object_id])){
                $ids[$object_id] = $i;
                $attributes_for_instantation = $this->getOnlyAvailableAttributes($this_item_attributes);
                $attributes_for_instantation['load_associations'] = true;
                $objects[$i] =& $this->instantiate($attributes_for_instantation, false);
            }else{
                $e = $i;
                $i = $ids[$object_id];
            }

            foreach ($associated_items as $association_id=>$attributes){
                if(count(array_diff($attributes, array(''))) > 0){
                    $object_associates_details[$i][$association_id][md5(serialize($attributes))] = $attributes;
                }
            }

            $i = !is_null($e) ? $e : $i+1;
        }

        if(!empty($object_associates_details)){
            foreach ($object_associates_details as $i=>$object_associate_details){
                foreach ($object_associate_details as $association_id => $associated_attributes){
                    foreach ($associated_attributes as $attributes){
                        if(count(array_diff($attributes, array(''))) > 0){
                            if(!method_exists($objects[$i]->$association_id, 'build')){
                                $handler_name = $this->getAssociatedHandlerName($association_id);
                                $objects[$i]->$handler_name->build($attributes, false);
                            }else{
                                $objects[$i]->$association_id->build($attributes, false);
                                $objects[$i]->$association_id->_newRecord = false;
                            }
                        }
                    }
                }
            }
        }

        $result =& $objects;
        return $result;
    }


    function _addTableAliasesToAssociatedSql($table_alias, $sql)
    {
        return preg_replace($this->getColumnsWithRegexBoundaries(),'\1'.$table_alias.'.\2',' '.$sql.' ');
    }

}


?>
