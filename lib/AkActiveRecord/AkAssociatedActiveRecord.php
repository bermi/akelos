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
        $association_id = $this->getAssociationId();
        if(!empty($this->_AssociationHandler) && (empty($this->_loaded))){
            $result =& $this->_AssociationHandler->loadAssociated($association_id);
        } else if (!empty($this->_AssociationHandler->Owner->$association_id)) {
            $result = &$this->_AssociationHandler->Owner->$association_id;
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
    function constructSqlForInclusionChain($handler_name,$parent_handler_name)
    {
        return !empty($this->_AssociationHandler) ? $this->_AssociationHandler->constructSqlForInclusionChain($this->getAssociationId(),$handler_name,$parent_handler_name) : false;
    }
    function getAssociatedFinderSqlOptions($options = array())
    {
        return !empty($this->_AssociationHandler) ? $this->_AssociationHandler->getAssociatedFinderSqlOptions($this->getAssociationId(), $options) : false;
    }
    function getAssociatedFinderSqlOptionsForInclusionChain($prefix, $parent_handler_name,$options = array(),$pluralize=false)
    {
        return !empty($this->_AssociationHandler) ? $this->_AssociationHandler->getAssociatedFinderSqlOptionsForInclusionChain($this->getAssociationId(), $prefix, $parent_handler_name, $options,$pluralize) : false;
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
     * New features:
     *
     * $options['returns'] can be default, array, simulated
     *
     *     default    - default behaviour, instantiating ActiveRecord Objects
     *     array      - returning the result as a big array
     *     simulated  - returning the result as AkActiveRecordMock Objects
     *
     * @param array $options
     * @return array of ActiveRecord Objects
     */
    function &findWithAssociations($options)
    {
        $result = false;
        $options ['include'] = Ak::toArray($options ['include']);

        $load_acts = isset($options['load_acts'])?$options['load_acts']:true;

        $config = array('__owner'=>array('class'=>$this->getType(),'pk'=>$this->getPrimaryKey()));

        $returns = isset($options['returns'])?$options['returns']:'default';
        if ($returns == 'simulated' && !AK_PHP5) {
            trigger_error(Ak::t('In PHP4 you cannot use the return type "simulated" on finders. Return type reset to "default"'),E_USER_WARNING);
            $returns = 'default';
        }
        $simulation_class = isset($options['simulation_class']) && class_exists($options['simulation_class'])?$options['simulation_class']:'AkActiveRecordMock';
        if (!in_array($returns,array('default','array','simulated'))) {
            $this->log('option "returns" must be one of default,array,simulated');
            $returns = 'default';
        }
        $included_associations = array ();
        $included_association_options = array ();
        foreach ( $options ['include'] as $k => $v ) {
            if (is_numeric($k)) {
                $included_associations [] = $v;
            } else {
                $included_associations [] = $k;
                $included_association_options [$k] = $v;
            }
        }
        unset($options['include']);
        $parent_pk = $this->getPrimaryKey();
        $available_associated_options = array ('bind'=> array (),'order' => array (), 'conditions' => array (), 'joins' => array (), 'selection' => array () );
        $replacements = array();
        foreach ( $included_associations as $association_id ) {
            $association_options = empty($included_association_options [$association_id]) ? array () : $included_association_options [$association_id];

            $handler_name = $this->getCollectionHandlerName($association_id);
            $handler_name = empty($handler_name) ? $association_id : (in_array($handler_name, $included_associations) ? $association_id : $handler_name);
            $type =$this->$handler_name->getType();
            $multi = false;

            if (in_array($type,array('hasMany','hasAndBelongsToMany'))) {
                $multi = true;
                $instance = $this->$handler_name->getAssociatedModelInstance();
                $class = $instance->getType();
                $pk_name = $instance->getPrimaryKey();
                $table_name =$instance->getTableName();
            } else {
                $class = $this->$handler_name->getAssociationOption('class_name');
                if(!class_exists($class)) {
                    Ak::import($class);
                }

                if(is_string($class)) {
                    $instance = new $class;
                    $pk_name = $instance->getPrimaryKey();
                    $table_name =$instance->getTableName();
                } else {

                    continue;
                }
            }
            $config['__owner'][$handler_name] = array('class'=>$class,'association_id'=>$association_id,'pk'=>$pk_name);
            $associated_options = $this->$handler_name->getAssociatedFinderSqlOptionsForInclusionChain('owner[@'.$parent_pk.']','__owner',$association_options,$multi);

            $options ['order'] = empty($options ['order']) ? '' : $this->_addTableAliasesToAssociatedSql('__owner', $options ['order']);

            $options ['group'] = empty($options ['group']) ? '' : $this->_addTableAliasesToAssociatedSql('__owner', $options ['group']);

            $options ['conditions'] = empty($options ['conditions']) ? '' : $this->_addTableAliasesToAssociatedSql('__owner', $options ['conditions']);


            foreach(array_keys($associated_options) as $option) {
                if(isset($associated_options[$option]) && is_string($associated_options[$option]))$associated_options[$option]=trim($associated_options[$option]);
                if(!empty($associated_options[$option])) {
                    $available_associated_options[$option][]=$associated_options[$option];
                }
            }
            $replacements['/ ('.$this->getTableName().')\./']=' __owner.';
            $replacements['/^_('.$association_id.')\./']='__owner__'.$handler_name.'.';
            $replacements['/ _('.$association_id.')\./'] = ' __owner__'.$handler_name.'.';
            $replacements['/^_('.$table_name.')\./']='__owner__'.$handler_name.'.';
            $replacements['/ _('.$table_name.')\./'] = ' __owner__'.$handler_name.'.';
            $replacements['/^('.$table_name.')\./']='__owner__'.$handler_name.'.';
            $replacements['/ ('.$table_name.')\./'] = ' __owner__'.$handler_name.'.';


            $this->_prepareIncludes('owner[@'.$parent_pk.']',$multi,$this,$available_associated_options,$handler_name,$handler_name,$association_id,$options,$association_options,$replacements, $config['__owner']);

        }
        //$this->log('Config:'.var_export($config,true));
        $replace_regex = array_keys($replacements);
        $replace_value = array_values($replacements);
        if(isset($options['order'])) $options['order'] = preg_replace($replace_regex,$replace_value,$options['order']);
        if(isset($options['conditions'])) $options['conditions'] = preg_replace($replace_regex,$replace_value,$options['conditions']);
        if(isset($options['group']))$options['group'] = preg_replace($replace_regex,$replace_value,$options['group']);

        foreach ( $available_associated_options as $option => $values ) {
            if($option == 'order' || $option=='conditions' || $option == 'group') {
                foreach($values as $idx=>$value) {
                    $available_associated_options[$option][$idx] = preg_replace($replace_regex,$replace_value,$value);
                }
            }

            if (! empty($values) && $option!='include') {
                $separator = $option == 'joins' ? ' ' : (in_array($option, array ('selection', 'order' )) ? ', ' : ' AND ');
                $values = array_map('trim', $values);

                if ($option == 'joins' && ! empty($options [$option])) {
                    $newJoinParts = array ();
                    foreach ( $values as $part ) {

                        if (! stristr($options [$option], $part) && !empty($part)) {
                            $newJoinParts [] = $part;
                        }
                    }
                    $values = $newJoinParts;
                }
                if($option!='include' && $option!='bind') {
                    $options [$option] = empty($options [$option]) ? join($separator, $values) : trim($options [$option]) . $separator . join(
                    $separator, $values);
                } else if ($option=='bind') {
                    $options [$option] = array_merge($options [$option],$values);
                }

            }
        }

        $sql = trim($this->constructFinderSqlWithAssociations($options));

        $sql = preg_replace('/,\s*,/',' , ',$sql);

        if (isset($options['wrap'])) {
            $addLimit='';
            if(preg_match('/LIMIT ([\d]+){1}(,){0,1}(\s*)([\d]+){0,1}/i',$sql,$matches) && strstr($options['wrap'],'{limit}')) {
                $sql = str_replace($matches[0],'',$sql);
                $addLimit = $matches[0];
            }
            $sql = str_replace('{query}',$sql,$options['wrap']);
            //if(!empty($addLimit)) {
            $sql = str_replace('{limit}',$addLimit,$sql);
            //}
        }

        if (! empty($options ['bind']) && is_array($options ['bind']) && strstr($sql, '?')) {
            $sql = array_merge(array ($sql ), $options ['bind']);
        }

        $result = & $this->_findBySqlWithAssociations($sql, empty($options ['virtual_limit']) ? false : $options ['virtual_limit'], $load_acts, $returns,$simulation_class, $config);
        if (empty($result)) {
            $result = false;
        }
        return $result;
    }


    function _prepareIncludes($prefix,$parent_is_plural, &$parent,&$available_associated_options,$handler_name,$parent_association_id,$association_id,&$options,&$association_options, &$replacements, &$config)
    {
        if (isset($association_options['include'])) {
            $association_options['include'] = Ak::toArray($association_options['include']);
            if (isset($parent->$handler_name) && method_exists($parent->$handler_name,'getModelName')) {
                $main_association_class_name = $parent->$handler_name->getModelName();
                Ak::import($main_association_class_name);
                $sub_association_object = new $main_association_class_name;
            } else if (isset($parent->$handler_name) && method_exists($parent->$handler_name,'getAssociatedModelInstance')){
                $sub_association_object = &$parent->$handler_name->getAssociatedModelInstance();
            } else {
                $sub_association_object = &$parent;
            }

        } else {
            /**
             * No included associations
             */
            return;
        }

        foreach ( $association_options ['include'] as $idx=>$sub_association_id ) {
            if (!is_numeric($idx) && is_array($sub_association_id)) {
                $sub_options = $sub_association_id;

                $sub_association_id = $idx;
            } else {
                $sub_options = array();
            }

            $sub_handler_name = $sub_association_object->getCollectionHandlerName($sub_association_id);

            if (!$sub_handler_name) {
                $sub_handler_name = $sub_association_id;
            }

            $type = $sub_association_object->$sub_handler_name->getType();

            if ($type == 'hasMany' || $type ==
            'hasAndBelongsToMany') {
                $instance=&$sub_association_object->$sub_handler_name->getAssociatedModelInstance();
                $class_name = $instance->getType();
                $table_name = $instance->getTableName();
                $pk = $instance->getPrimaryKey();
                $pluralize = true;
            } else if ( $type == 'belongsTo' || $type == 'hasOne') {
                $class_name = $sub_association_object->$sub_handler_name->getAssociationOption('class_name');
                if(!class_exists($class_name)) {
                    Ak::import($class_name);
                }
                $instance = new $class_name;
                $table_name = $instance->getTableName();

                $pk = $instance->getPrimaryKey();
                $pluralize = false;
            } else {
                $pk = $sub_association_object->$sub_handler_name->getPrimaryKey();
                $instance = &$sub_association_object;
                $class_name =$instance->getType();
                $pluralize = false;
                $table_name = $instance->getTableName();
            }
            $config[$handler_name][$sub_handler_name] = array('association_id'=>$sub_association_id,'class'=>$class_name,'pk'=>$pk);
            $sub_associated_options = $sub_association_object->$sub_handler_name->getAssociatedFinderSqlOptionsForInclusionChain($prefix.'['.$handler_name.']'.($parent_is_plural?'[@'.$pk.']':''),'__owner__'.$parent_association_id,
            $sub_options, $pluralize);

            /**
                     * Adding replacements for base options like order,conditions,group.
                     * The table-aliases of the included associations will be replaced
                     * with their respective __owner_$handler_name.$column_name representative.
                     */
            $replacements['/([,\s])_('.$sub_association_id.')\./']='\\1__owner__'.$parent_association_id.'__'.$sub_handler_name.'.';
            $replacements['/([,\s])('.$sub_association_id.')\./']='\\1__owner__'.$parent_association_id.'__'.$sub_handler_name.'.';
            $replacements['/([,\s])_('.$table_name.')\./']='\\1__owner__'.$parent_association_id.'__'.$sub_handler_name.'.';
            $replacements['/([,\s])('.$table_name.')\./']='\\1__owner__'.$parent_association_id.'__'.$sub_handler_name.'.';
            $replacements['/([,\s])_('.$sub_handler_name.')\./']='\\1__owner__'.$parent_association_id.'__'.$sub_handler_name.'.';
            $replacements['/([,\s])('.$sub_handler_name.')\./']='\\1__owner__'.$parent_association_id.'__'.$sub_handler_name.'.';


            foreach ( array_keys(
            $available_associated_options) as $sub_associated_option ) {

                $newoption=isset($sub_associated_options [$sub_associated_option])?$sub_associated_options [$sub_associated_option]:'';
                if ($sub_associated_option!='bind' && $sub_associated_option!='include') {
                    $newoption=trim($newoption);
                    if(!empty($newoption)) {
                        $available_associated_options [$sub_associated_option] []  = $newoption;
                    }
                } else {
                    $available_associated_options [$sub_associated_option] = array_merge($available_associated_options [$sub_associated_option],Ak::toArray($newoption));
                }

            }
            if (!empty($sub_options)) {
                $this->_prepareIncludes($prefix.'['.$handler_name.']'.($parent_is_plural?'[@'.$pk.']':''),$pluralize,$instance,$available_associated_options,$sub_handler_name,$parent_association_id.'__'.$sub_handler_name,$sub_association_id,$options['include'][$association_id],$association_options['include'][$idx],$replacements,$config[$handler_name]);
            }
        }
    }

    function constructCalculationSqlWithAssociations($sql, $options = array())
    {
        $calculation_function = isset($options['calculation']) && isset($options['calculation']['function'])?$options['calculation']['function']:'count';
        $calculation_column = isset($options['calculation']) && isset($options['calculation']['column'])?$options['calculation']['column']:'*';
        $calculation_alias = isset($options['calculation']) && isset($options['calculation']['alias'])?$options['calculation']['alias']:'count_all';

        $selection = $calculation_function.'( '.$calculation_column.' ) AS '.$calculation_alias.' ';

        $sql = preg_replace('/SELECT (.*?) FROM/i','SELECT '.$selection. ' FROM', $sql);
        $groupBy = 'GROUP BY __owner.id';
        if (preg_match('/GROUP BY (.*?)($|ORDER)/i',$sql,$matches)) {
            $sql = str_replace($matches[1],'__owner.id',$sql);
        }
        return $sql;
    }

    function &_calculateBySqlWithAssociations($sql)
    {
        $objects = array();
        $results = $this->_db->execute ($sql,'find with associations');
        if (!$results){
            return $objects;
        }
        return $results;
    }

    function &_findBySqlWithAssociations($sql, $virtual_limit = false, $load_acts = true, $returns = 'default', $simulation_class = 'AkActiveRecordMock', $config = array())
    {
        $objects = array();
        $results = $this->_db->execute ($sql,'find with associations ext');
        if (!$results){
            return $objects;
        }

        $result =& $this->_generateObjectGraphFromResultSet($results,$virtual_limit, $load_acts, $returns, $simulation_class, $config);
        return $result;

    }
    /**
     * Generates objects from special sql:
     * SELECT id as owner[id]...
     *
     *
     *
     * @param ADOResultSet $results            a result set from Db->execute
     * @param array $included_associations     just like in ->find(); $options['include']; but in fact unused
     * @param mixed $virtual_limit             int or false; unsure if this works
     * @return array                           ObjectGraph as an array
     */
    function &_generateObjectGraphFromResultSet($results, $virtual_limit = false, $load_acts=true, $returns = 'default',$simulation_class='AkActiveRecordMock', $config = array())
    {
        $return = array();
        $owner = array();
        $keys = array();
        while ($record = $results->FetchRow()) {
            /**
             * implement limits here, config should have limits per association
             * need offset as well
             */
            foreach($record as $key=>$value) {

                if (strstr($key,'@')) {
                    $true=true;
                    while($true) {
                        if (!isset($keys[$key])) {
                            $pos=@strrpos($key,'@');
                            $length = @strpos(']',$key,$pos);
                            $pk = @substr($key,$pos+1,$length+2);
                            $kpos=@strpos(']',$key,$pos);
                            $base = @substr($key,0,$kpos+$pos-1);
                            $replace = $base.'[@'.$pk.']';
                            $subkey = $replace.'['.$pk.']';
                            $keys[$key] = array('pos'=>$pos,'length'=>$length,'pk'=>$pk,'kpos'=>$kpos,'subkey'=>$subkey,'replace'=>$replace,'base'=>$base);
                        } else {
                            $subkey = $keys[$key]['subkey'];
                            $pos=$keys[$key]['pos'];
                            $kpos=$keys[$key]['kpos'];
                            $pk=$keys[$key]['pk'];
                            $replace=$keys[$key]['replace'];
                            $base=$keys[$key]['base'];
                        }
                        if (isset($record[$subkey])) {
                            $id = $record[$subkey];
                        } else {
                            $id = 0;
                        }
                        $key = str_replace($replace,$base.'['.$id.']',$key);

                        if(!strstr($key,'@')) {
                            $true=false;
                        }
                    }
                }

                $this->_addToOwner($owner,str_replace('owner[','[',$key),$value, $returns, $config['__owner']);

            }
            unset($record);
        }
        if ($returns == 'default') {
            unset($keys);
            if (!empty($owner)) {
                $available_attributes = $this->getAvailableAttributes();
                $available_attributes = array_keys($available_attributes);
                foreach($owner as $id=>$data) {

                    if (!isset($diff)) {
                        $diff = @array_diff(array_keys($data),$available_attributes);
                        $nondiff = array();
                        foreach($diff as $d) {
                            $nondiff[$d] = null;
                        }
                    }
                    $available =  array_merge($data,$nondiff);

                    $available['load_associations'] = false;
                    $available['load_acts'] = $load_acts;

                    $obj=&$this->instantiate($available,false,false);

                    foreach(array_values($diff) as $rel) {
                        $this->_setAssociations($rel,$data[$rel],$obj, $load_acts);
                    }

                    $obj->afterInstantiate();
                    $obj->notifyObservers('afterInstantiate');
                    $return[]=&$obj;
                }
            } else {
                $return = false;
            }
        } else if ($returns == 'array') {

            $this->_reindexArray($owner);
            $return = $owner;
        } else if ($returns == 'simulated') {
            include_once AK_LIB_DIR.DS.'AkActiveRecord'.DS.'AkActiveRecordMock.php';
            $false = false;
            $return = &$this->_generateStdClasses($simulation_class,$owner, $this->getType(), $false, $false, $config);
        }
        return $return;
    }
    function _reindexArray(&$array)
    {
        if (is_numeric(key($array))) {
            $array = array_values($array);
        }
        foreach($array as $key => $value) {
            if (is_array($value)) {
                $this->_reindexArray($array[$key]);
            }
        }
    }
    function _generateStdClasses($simulation_class,$owner, $class, $handler_name, &$parent, $config = array(), $config_key = '__owner')
    {
        /**echo "<pre>";
        var_dump($config_key);
        var_dump($config);
        echo "</pre>";*/

        $return = array();
        $singularize=false;
        $pk = isset($config[$config_key]['pk'])?$config[$config_key]['pk']:'id';
        if (!is_numeric(key($owner))) {
            $singularize =true;

            $key = isset($owner[$pk])?$owner[$pk]:0;
            $owner = array($key=>$owner);
        }
        if(is_array($owner))
        foreach($owner as $id=>$data) {
            require_once AK_LIB_DIR.DS.'AkActiveRecord'.DS.'AkActiveRecordMock.php';
            $id = isset($data[$pk])?$data[$pk]:$id;
            $obj = &new $simulation_class($id,$class, $handler_name, $parent);
            if(is_array($data))
            foreach($data as $key => $value) {
                if ($key{0}=='_') continue;
                if ( is_scalar($value)) {
                    $obj->$key = $value;
                } else if (is_array($value)) {
                    //$assoc = $key;
                    $assoc = isset($config[$config_key][$key]['association_id'])?$config[$config_key][$key]['association_id']:false;
                    //if(is_numeric(key($value))) {
                    //    $key = AkInflector::pluralize($key);
                    //}
                    /** if ($handler_name!==false) {
                        $config_key = '__owner';
                    } else {
                        $config_key = $handler_name;
                    }*/
                    if ($assoc) {
                        $obj->$assoc = &$this->_generateStdClasses($simulation_class,$value, @$config[$config_key][$key]['class'], $key,$obj, @$config[$config_key], $key);
                        //$this->log('setting assoc:'.$assoc);
                        $obj->_addAssociation($assoc, $key);
                    } else {
                        /**$assoc = $key;
                        $obj->$assoc = &$this->_generateStdClasses($simulation_class,$value, @$config[$config_key][$key]['class'], $key,$obj, @$config[$config_key], $key);
                        $this->log('setting assoc with key:'.$assoc);
                        $obj->_addAssociation($assoc, $key);*/
                        //die('fuck');
                        /**var_dump($key);
                        var_dump($value);
                        var_dump($config_key);
                        var_dump($config);*/
                        //var_dump(func_get_args());
                    }
                }
            }
            $return[]=&$obj;
        }
        return $singularize?$return[0]:$return;
    }


    function org_addToOwner(&$owner, $key, $value) {

        if(preg_match_all('/\[(.*?)\]/',$key,$matches)) {
            $count = count($matches[1]);
            $last = &$owner;
            for($idx=0;$idx<$count;$idx++) {

                if (!isset($last[$matches[1][$idx]])) {
                    $last[$matches[1][$idx]] = array();
                }
                $last = &$last[$matches[1][$idx]];

            }
            $last = $value;
        }
    }
    function _addToOwner(&$owner, $key, $value, $returns, $config) {

        if(preg_match_all('/\[(.*?)\]/',$key,$matches)) {
            $count = count($matches[1]);
            $last = &$owner;
            for($idx=0;$idx<$count;$idx++) {
                $key = $matches[1][$idx];
                $association = $key;

                if (isset($config[$key]) && $returns == 'array') {
                    $config = $config[$key];
                    $association = $config['association_id'];
                    //$this->log('using association:'.$association);
                }
                if (!isset($last[$association])) {
                    $last[$association] = array();
                }
                $last = &$last[$association];

            }
            $last = $value;
        }
        //$this->log('owner:'.var_export($owner,true));
    }

    function _setAssociations($assoc_name, $val, &$parent, $load_acts = true) {
        static $instances = array();
        static $instance_attributes = array();
        if ($assoc_name{0}=='_') return;
        if (method_exists($parent,'getAssociationOption')) {
            $class=$parent->getType();

            $instance = new $class;

            if (isset($instance->$assoc_name) && method_exists($instance->$assoc_name,'getAssociationOption')) {
                $class = $instance->$assoc_name->getAssociationOption('class_name');
                if (!isset($instances[$class])) {
                    $instance = new $class;
                    $instances[$class] = &$instance;
                } else {
                    $instance = &$instances[$class];
                }
            } else if (isset($parent->$assoc_name) && method_exists($parent->$assoc_name,'getAssociatedModelInstance')){
                if (!isset($instances[$parent->getType().'-'.$assoc_name])) {
                    $instance = $parent->$assoc_name->getAssociatedModelInstance();
                    $instances[$parent->getType().'-'.$assoc_name] = &$instance;
                } else {
                    $instance=&$instances[$parent->getType().'-'.$assoc_name];
                }
            } else if (isset($parent->$assoc_name) && method_exists($parent->$assoc_name,'getType') && !in_array($parent->$assoc_name->getType(),array('belongsTo','hasOne','hasOne','hasMany','hasAndBelongsToMany'))) {

                $instance = $parent->$assoc_name;
            } else if (isset($instance->$assoc_name)) {
                if (!isset($instances[$instance->getType().'-'.$assoc_name])) {
                    $instance = $instance->$assoc_name->getAssociatedModelInstance();
                    $instances[$instance->getType().'-'.$assoc_name] = &$instance;
                } else {
                    $instance=&$instances[$instance->getType().'-'.$assoc_name];
                }
            } else {
                $this->log('Cannot find association:'.$assoc_name.' on '.$parent->getType());
                return;
            }

        } else {
            if (!$parent->$assoc_name) {
                $this->log($parent->getType().'->'.$assoc_name.' does not have assoc');
                return;
            }
            if (!isset($instances[$parent->getType().'-'.$assoc_name])) {
                $instance = $parent->$assoc_name->getAssociatedModelInstance();
                $instances[$parent->getType().'-'.$assoc_name]=&$instance;
            } else {
                $instance=&$instances[$parent->getType().'-'.$assoc_name];
            }
        }

        if (is_numeric(key($val))) {
            $owner =$val;
        } else {
            $owner = array($val);
        }
        if (!isset($instance_attributes[$instance->getType()])) {
            $available_attributes = $instance->getAvailableAttributes();
            $available_attributes = array_keys($available_attributes);
            $instance_attributes[$instance->getType()] = $available_attributes;
        } else {
            $available_attributes=$instance_attributes[$instance->getType()];
        }

        foreach($owner as $data) {

            if (!isset($diff)) {
                $diff = @array_diff(@array_keys($data),$available_attributes);
                $nondiff = array();
                if(is_array($diff)) {
                    foreach(array_keys($diff) as $d) {
                        $nondiff[$d] = null;
                    }
                }
            }
            $available = @array_merge($data,$nondiff);

            if(empty($available[$instance->getPrimaryKey()])) {
                $parent->$assoc_name->_loaded=true;
                //return;
                continue;
            }
            $available['load_associations'] = false;
            $available['load_acts'] = $load_acts;

            $obj=&$parent->$assoc_name->build($available,false);

            $obj->_newRecord = false;
            $parent->$assoc_name->_loaded=true;
            $obj->_loaded=true;
            if(is_array($diff)) {
                foreach(array_values($diff) as $rel) {
                    $this->_setAssociations($rel,$data[$rel],$obj);
                }
            }

        }
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
        } else if (isset($this->_associationIds[$association_id])) {
            return $this->_associationIds[$association_id];
        } else{
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
        $parent_pk = $this->getPrimaryKey();
        $parenthesis = $this->_db->type()=='mysql'?"'":'"';
        if($include_owner_as_selection){
            foreach (array_keys($this->getColumns()) as $column_name){
                $selection .= '__owner.'.$column_name.' AS '.$parenthesis.'owner[@'.$parent_pk.']['.$column_name.']'.$parenthesis.', ';
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

        empty($options['conditions']) ? null : $this->addConditions($sql, $options['conditions'], $include_owner_as_selection?'__owner':null);

        // Create an alias for order
        if(empty($options['order']) && !empty($options['sort'])){
            $options['order'] = $options['sort'];
        }
        $sql  .= !empty($options['group']) ? ' GROUP BY  '.$options['group'] : '';
        $sql  .= !empty($options['order']) ? ' ORDER BY  '.$options['order'] : '';

        $this->_db->addLimitAndOffset($sql,$options);
        return $sql;
    }



    function _addTableAliasesToAssociatedSqlWithAlias($add_alias, $alias,$sql)
    {
        return preg_replace($this->getColumnsWithRegexBoundariesAndAlias($alias),'\1'.$add_alias.'.\3',' '.$sql.' ');
    }

    function _addTableAliasesToAssociatedSql($table_alias, $sql)
    {
        return preg_replace($this->getColumnsWithRegexBoundaries(),'\1'.$table_alias.'.\2',' '.$sql.' ');
    }

}
if (!function_exists('str_ireplace')) {
    function str_ireplace($needle, $str, $haystack) {

        if (!is_array($needle)) {
            $needle = array($needle);
        }
        if (!is_array($str)) {
            $str = array($str);
        }
        foreach($needle as $i=>$n) {
            $n = preg_quote($n, '/');
            $haystack=preg_replace("/$n/i", $str[$i], $haystack);
        }
        return $haystack;
    }
}

?>
