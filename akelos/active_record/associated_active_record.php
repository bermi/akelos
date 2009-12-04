<?php

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
    public
    $_AssociationHandler,
    $_associationId = false,
    // Holds different association IDs related to this model
    $_associationIds = array(),
    $_associations = array();

    public function _loadAssociationHandler($association_type)
    {
        if(empty($this->$association_type) && in_array($association_type, array('hasOne','belongsTo','hasMany','hasAndBelongsToMany'))){
            $association_handler_class_name = 'Ak'.ucfirst($association_type);
            $this->$association_type = new $association_handler_class_name($this);
        }
        return !empty($this->$association_type);
    }

    public function setAssociationHandler(&$AssociationHandler, $association_id)
    {
        $this->_AssociationHandler =& $AssociationHandler;
    }

    public function loadAssociations()
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
    public function &getAssociated($association_type)
    {
        $result = array();
        if(!empty($this->$association_type) && in_array($association_type, array('hasOne','belongsTo','hasMany','hasAndBelongsToMany'))){
            $result =& $this->$association_type->getModels();
        }
        return $result;
    }

    public function getId()
    {
        return false;
    }


    public function &assign(&$Associated)
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
    public function &build($attributes = array(), $replace_existing = true)
    {
        $result = false;
        if(!empty($this->_AssociationHandler)){
            $result =& $this->_AssociationHandler->build($this->getAssociationId(), $attributes, $replace_existing);
        }
        return $result;
    }


    public function &create($attributes = array(), $replace_existing = true)
    {
        $result = false;
        if(!empty($this->_AssociationHandler)){
            $result =& $this->_AssociationHandler->create($this->getAssociationId(), $attributes, $replace_existing);
        }
        return $result;
    }

    public function &replace(&$NewAssociated, $dont_save = false)
    {
        $result = false;
        if(!empty($this->_AssociationHandler)){
            $result =& $this->_AssociationHandler->replace($this->getAssociationId(), $NewAssociated, $dont_save = false);
        }
        return $result;
    }


    public function &load()
    {
        $result = false;
        $association_id = $this->getAssociationId();
        if(!empty($this->_AssociationHandler) && (empty($this->_loaded))){
            $result =& $this->_AssociationHandler->loadAssociated($association_id, true);
        } else if (!empty($this->_AssociationHandler->Owner->$association_id)) {
            $result = &$this->_AssociationHandler->Owner->$association_id;
        }
        return $result;
    }

    public function constructSql()
    {
        return !empty($this->_AssociationHandler) ? $this->_AssociationHandler->constructSql($this->getAssociationId()) : false;
    }

    public function constructSqlForInclusion()
    {
        return !empty($this->_AssociationHandler) ? $this->_AssociationHandler->constructSqlForInclusion($this->getAssociationId()) : false;
    }
    public function constructSqlForInclusionChain($handler_name,$parent_handler_name)
    {
        return !empty($this->_AssociationHandler) ? $this->_AssociationHandler->constructSqlForInclusionChain($this->getAssociationId(),$handler_name,$parent_handler_name) : false;
    }
    public function getAssociatedFinderSqlOptions($options = array())
    {
        return !empty($this->_AssociationHandler) ? $this->_AssociationHandler->getAssociatedFinderSqlOptions($this->getAssociationId(), $options) : false;
    }
    public function getAssociatedFinderSqlOptionsForInclusionChain($prefix, $parent_handler_name,$options = array(),$pluralize=false)
    {
        return !empty($this->_AssociationHandler) ? $this->_AssociationHandler->getAssociatedFinderSqlOptionsForInclusionChain($this->getAssociationId(), $prefix, $parent_handler_name, $options,$pluralize) : false;
    }
    public function getAssociationOption($option)
    {
        return !empty($this->_AssociationHandler) ? $this->_AssociationHandler->getOption($this->getAssociationId(), $option) : false;
    }

    public function setAssociationOption($option, $value)
    {
        return !empty($this->_AssociationHandler) ? $this->_AssociationHandler->setOption($this->getAssociationId(), $option, $value) : false;
    }

    public function getAssociationId()
    {
        if(empty($this->_associationId)){
            trigger_error(Ak::t('You are trying to access a non associated Object property. '.
            'This error might have been caused by asigning directly an object '.
            'to the association instead of using the "assign()" method'),E_USER_WARNING);
        }
        return $this->_associationId;
    }

    public function getAssociatedIds()
    {
        return array_keys($this->_associationIds);
    }

    public function getAssociatedHandlerName($association_id)
    {
        return empty($this->_associationIds[$association_id]) ? false : $this->_associationIds[$association_id];
    }

    public function getAssociatedType()
    {
        return !empty($this->_AssociationHandler) ? $this->_AssociationHandler->getType() : false;
    }

    public function getAssociationType()
    {
        return $this->getAssociatedType();
    }

    public function getType()
    {
        return $this->getAssociatedType();
    }

    public function hasAssociations()
    {
        return !empty($this->_associations) && count($this->_associations) > 0;
    }


    public function getCollectionHandlerName($association_id)
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

    public function addTableAliasesToAssociatedSqlWithAlias($add_alias, $alias,$sql)
    {
        return preg_replace($this->getColumnsWithRegexBoundariesAndAlias($alias),'\1'.$add_alias.'.\3',' '.$sql.' ');
    }

    public function addTableAliasesToAssociatedSql($table_alias, $sql)
    {
        return preg_replace($this->getColumnsWithRegexBoundaries(),'\1'.$table_alias.'.\2',' '.$sql.' ');
    }

}

