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
 * @subpackage Associations
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

require_once(AK_LIB_DIR.DS.'AkActiveRecord'.DS.'AkObserver.php');

class AkAssociation extends AkObserver
{
    var $Owner;
    var $options = array();
    var $models = array();

    function AkAssociation(&$Owner)
    {
        $this->Owner =& $Owner;
        $this->observe($this->Owner);
        $this->_setAssociationAccesorAliasReferences();
    }

    function initializeAssociated($options)
    {
        $options = is_string($options) ? array_map('trim',array_diff(explode(',',$options.','), array(''))) : $options;
        if(is_array($options)){
            foreach ($options as $k=>$option){
                if(is_numeric($k)){
                    $association_id = $option;
                    $association_options = array();
                }else{
                    $association_id = $k;
                    $association_options = $option;
                }
                if($association_id = $this->setAssociationId($association_id)){
                    $this->addAssociated($association_id, $association_options);
                }
            }
        }
    }

    function setAssociationId($association_id)
    {
        $association_id = strtolower(AkInflector::underscore($association_id));
        if(isset($this->Owner->$association_id)){
            trigger_error(Ak::t('Could not load %association_id on %model_name because "%model_name->%association_id" attribute '.
            'is already defided and can\' be used as an association placeholder',
            array('%model_name'=>$this->Owner->getModelName(),'%association_id'=>$association_id)),
            E_USER_ERROR);
            return false;
        }else {
            return $association_id;
        }
    }

    function _setAssociationAccesorAliasReferences()
    {
        $underscored_alias = AkInflector::underscore($this->getType());
        if(!isset($this->Owner->$underscored_alias)){
            $this->Owner->$underscored_alias =& $this;
        }
    }

    function setOptions($association_id, $options)
    {
        $this->options[$association_id] = $options;
    }

    function getOptions($association_id)
    {
        return $this->options[$association_id];
    }

    function getOption($association_id, $option_name)
    {
        return isset($this->options[$association_id][$option_name]) ? $this->options[$association_id][$option_name] : false;
    }

    function &addModel($association_id, &$associated_model)
    {
        $this->models[$association_id] =& $associated_model;
        return $this->models[$association_id];

    }

    function &getModel($association_id)
    {
        return $this->models[$association_id];
    }

    function &getModels()
    {
        return $this->models;
    }

    function getAssociatedIds()
    {
        return array_keys($this->options);
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

    function setAssociatedId($association_id, $associated_id)
    {
        $this->Owner->_associationIds[$association_id]  = $associated_id;
        $this->associated_ids[$association_id] = $associated_id;
    }

    function &loadAssociated($association_id)
    {
        if (!$this->Owner->isNewRecord()){
            if(empty($this->Owner->$association_id->_loaded)){
                if($Associated =& $this->findAssociated($association_id)){
                    $Associated->_loaded = true;
                    $this->_build($association_id, $Associated, false);
                }
            }
        }

        return $this->Owner->$association_id;
    }

    /**
     * Class interfaces. All Association objects must implement the following methods
     */

    function addAssociated($association_id, $options = array())
    {
        trigger_error(__FUNCTION__.' must be defined into your specific association handler');
    }

    function getType()
    {
        trigger_error(__FUNCTION__.' must be defined into your specific association handler');
    }

    function getAssociatedFinderSqlOptions()
    {
        trigger_error(__FUNCTION__.' must be defined into your specific association handler');
    }

    function isOwnerAnActiveRecord()
    {
        return $this->__activeRecordObject;
    }


    function _hasTablePrefix($association_id)
    {
        return isset($this->$association_id->_associationTablePrefixes[$this->$association_id->_tableName]);
    }

    function _saveLoadedHandler($association_id, $associated)
    {
        $this->Owner->_association_handler_copies[$association_id] = $associated;
    }

    function _getLoadedHandler($association_id)
    {
        return $this->Owner->_association_handler_copies[$association_id];
    }

    /**
 	* Recurses through $owner and its superclasses until it finds the class which defines the association to the given $associatedModel 
 	*/ 
    function _findOwnerTypeForAssociation(&$AssociatedModel, $Owner) {
        if (!is_object($Owner)) {
            $Owner = new $Owner;
        }
        $owner_type = AkInflector::underscore($Owner->getType());
        if (isset($AssociatedModel->$owner_type)) {
            return $owner_type;
        } else {
            return $this->_findOwnerTypeForAssociation($AssociatedModel, get_parent_class($Owner));
        }
    }
}


?>
