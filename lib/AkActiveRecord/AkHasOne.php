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
* Adds the following methods for retrieval and query of a single associated object.
* $association is replaced with the symbol passed as the first argument, so 
* <tt>hasOne('manager')</tt> would add among others <tt>$this->manager->getAttributes()</tt>.
*
* Example: An Account class declares <tt>hasOne('beneficiary');</tt>, which will add:
* * <tt>$Account->beneficiary->load()</tt> (similar to <tt>$Beneficiary->find('first', array('conditions' => "account_id = $id"))</tt>)
* * <tt>$Account->beneficiary->assign($Beneficiary);</tt> (similar to <tt>$Beneficiary->account_id = $Account->id; $Beneficiary->save()</tt>)
* * <tt>$Account->beneficiary->build();</tt> (similar to <tt>$Beneficiary = new Beneficiary("account_id->", $Account->id)</tt>)
* * <tt>$Account->beneficiary->create();</tt> (similar to <tt>$b = new Beneficiary("account_id->", $Account->id); $b->save(); $b</tt>)
*
* The declaration can also include an options array to specialize the behavior of the association.
*
* Options are:
* * <tt>class_name</tt>  - specify the class name of the association. Use it only if that name can't be inferred
*   from the association name. So <tt>hasOne('manager')</tt> will by default be linked to the "Manager" class, but
*   if the real class name is "Person", you'll have to specify it with this option.
* * <tt>conditions</tt>  - specify the conditions that the associated object must meet in order to be included as a "WHERE"
*   sql fragment, such as "rank = 5".
* * <tt>order</tt>       - specify the order from which the associated object will be picked at the top. Specified as
*    an "ORDER BY" sql fragment, such as "last_name, first_name DESC"
* * <tt>dependent</tt>   - if set to true, the associated object is destroyed when this object is. It's also destroyed if another
*   association is assigned.
* * <tt>foreign_key</tt> - specify the foreign key used for the association. By default this is guessed to be the name
*   of this class in lower-case and "_id" suffixed. So a "Person" class that makes a hasOne association will use "person_id"
*   as the default foreign_key.
*
* Option examples:
*   var $hasOne = array(
*       'credit_card' => array('dependent' => true),
*       'last_comment' => array('class_name' => "Comment", 'order' => "posted_on"),
*       'project_manager' => array('class_name' => "Person", 'conditions' => "role = 'project_manager'")
*       );
*/
class AkHasOne extends AkAssociation
{
    var $asssociated_ids = array();

    function &addAssociated($association_id, $options = array())
    {
        $default_options = array(
        'class_name' => empty($options['class_name']) ? AkInflector::camelize($association_id) : $options['class_name'],
        'foreign_key' => empty($options['foreign_key']) ? AkInflector::singularize($this->Owner->getTableName()).'_id' : $options['foreign_key'],
        'remote'=>false,
        'instantiate'=>false,
        'conditions'=>false,
        'include_conditions_when_included'=>true,
        'order'=>false,
        'include_order_when_included'=>true,
        'dependent'=>false,
        'counter_cache'=>false
        );

        $options = array_merge($default_options, $options);

        $options['table_name'] = empty($options['table_name']) ? AkInflector::tableize($options['class_name']) : $options['table_name'];

        $this->setOptions($association_id, $options);

        $this->addModel($association_id,  new AkAssociatedActiveRecord());

        $associated =& $this->getModel($association_id);
        $this->setAssociatedId($association_id, $associated->getId());

        $associated =& $this->_build($association_id, &$associated, false);

        $this->_saveLoadedHandler($association_id, $associated);

        if($options['instantiate']){
            $associated =& $this->addModel($association_id,  new $options['class_name']($options['foreign_key'].' = '.$this->Owner->quotedId()));
        }

        return $associated;
    }


    /**
     * Assigns the associate object, extracts the primary key, sets it as the foreign key, and saves the associate object.
     */
    function &assign($association_id, &$Associated)
    {
        if(!$this->Owner->isNewRecord()){
            $Associated->set($Associated->getAssociationOption('foreign_key'), $this->Owner->getId());
            $Associated->save();
        }

        $this->_build($association_id, &$Associated);
        $this->Owner->$association_id->_loaded = true;
        return $Associated;
    }

    function getAssociatedId($association_id)
    {
        return isset($this->asssociated_ids[$association_id]) ? $this->asssociated_ids[$association_id] : false;
    }


    function getType()
    {
        return 'hasOne';
    }


    function getAssociatedFinderSqlOptions($association_id, $options = array())
    {
        $default_options = array(
        'conditions' => $this->Owner->$association_id->getAssociationOption('include_conditions_when_included'),
        'order' => $this->Owner->$association_id->getAssociationOption('include_order_when_included')
        );

        if(empty($this->Owner->$association_id->__activeRecordObject)){
            $this->build($association_id, array(), false);
        }

        $table_name = $this->Owner->$association_id->getTableName();
        $options = array_merge($default_options, $options);

        $finder_options = array();

        foreach ($options as $option=>$available) {
            if($available){
                $value = $this->Owner->$association_id->getAssociationOption($option);
                empty($value) ? null : ($finder_options[$option] = trim($this->Owner->$association_id->_addTableAliasesToAssociatedSql('_'.$association_id, $value)));
            }
        }

        $finder_options['joins'] = $this->Owner->$association_id->constructSqlForInclusion();

        $finder_options['selection'] = '';
        foreach (array_keys($this->Owner->$association_id->getColumns()) as $column_name){
            $finder_options['selection'] .= '_'.$association_id.'.'.$column_name.' AS _'.$association_id.'_'.$column_name.', ';
        }
        $finder_options['selection'] = trim($finder_options['selection'], ', ');

        return $finder_options;
    }

    function constructSqlForInclusion($association_id)
    {
        return ' LEFT OUTER JOIN '.
        $this->Owner->$association_id->getTableName().' AS _'.$association_id.
        ' ON '.
        '__owner.'.$this->Owner->getPrimaryKey().
        ' = '.
        '_'.$association_id.'.'.$this->Owner->$association_id->getAssociationOption('foreign_key').' ';
    }

    function &build($association_id, $attributes = array(), $replace_existing = true)
    {
        $class_name = $this->Owner->$association_id->getAssociationOption('class_name');
        $foreign_key = $this->Owner->$association_id->getAssociationOption('foreign_key');

        Ak::import($class_name);
        $record =& new $class_name($attributes);
        if ($replace_existing){
            $record =& $this->replace($association_id, $record, true);
        }
        if(!$this->Owner->isNewRecord()){
            $record->set($foreign_key, $this->Owner->getId());
        }

        $record =& $this->_build($association_id, &$record);

        return $record;
    }


    /**
    * Returns a new object of the associated type that has been instantiated with attributes 
    * and linked to this object through a foreign key and that has already been 
    * saved (if it passed the validation)
    */
    function &create($association_id, $attributes = array(), $replace_existing = true)
    {
        $this->build($association_id, $attributes, $replace_existing);
        $this->Owner->$association_id->save();
        $this->Owner->$association_id->_loaded = true;
        return $this->Owner->$association_id;
    }

    function &replace($association_id, &$NewAssociated, $dont_save = false)
    {
        $Associated =& $this->loadAssociated($association_id);

        if(!empty($Associated->__activeRecordObject) && !empty($NewAssociated->__activeRecordObject) && $Associated->getId() == $NewAssociated->getId()){
            return $NewAssociated;
        }

        if(!empty($Associated->__activeRecordObject)){
            if ($Associated->getAssociationOption('dependent') && !$dont_save){
                if(!$Associated->isNewRecord()){
                    $Associated->destroy();
                }
            }elseif(!$dont_save){
                $Associated->set($Associated->getAssociationOption('foreign_key'), null);
                if($Associated->isNewRecord()){
                    $Associated->save();
                }
            }
        }

        $result = false;

        if (!empty($NewAssociated->__activeRecordObject)){
            if(!$this->Owner->isNewRecord()){
                $NewAssociated->set($Associated->getAssociationOption('foreign_key'), $this->Owner->getId());
            }

            $NewAssociated =& $this->_build($association_id, &$NewAssociated);

            $NewAssociated->_loaded = true;
            if(!$NewAssociated->isNewRecord() || !$dont_save){
                if($NewAssociated->save()){
                    return $NewAssociated;
                }
            }else{
                return $NewAssociated;
            }
        }
        return $result;
    }

    function &findAssociated($association_id)
    {
        $result = false;
        if(!$this->Owner->getId()){
            return $result;
        }

        if(empty($this->Owner->$association_id->__activeRecordObject)){
            $this->build($association_id, array(), false);
        }

        $table_name = $this->Owner->$association_id->getAssociationOption('table_name');

        $result =& $this->Owner->$association_id->find(
        'first',
        array(
        'conditions' => trim($this->Owner->$association_id->_addTableAliasesToAssociatedSql($table_name, $this->constructSqlConditions($association_id))),
        'joins' => trim($this->Owner->$association_id->_addTableAliasesToAssociatedSql($table_name, $this->constructSql($association_id))),
        'order' => trim($this->Owner->$association_id->_addTableAliasesToAssociatedSql($table_name, $this->Owner->$association_id->getAssociationOption('order')))
        )
        );

        return $result;
    }

    function constructSqlConditions($association_id)
    {
        $foreign_key = $this->Owner->$association_id->getAssociationOption('foreign_key');
        $conditions = $this->Owner->$association_id->getAssociationOption('conditions');

        $foreign_key_value = $this->Owner->getId();
        if(empty($foreign_key_value)){
            return $conditions;
        }
        return (empty($conditions) ? '' : $conditions.' AND ').$foreign_key.' = '.$this->Owner->castAttributeForDatabase($foreign_key, $foreign_key_value);
    }

    function constructSql($association_id)
    {
        $foreign_key = $this->Owner->$association_id->getAssociationOption('foreign_key');
        $table_name = $this->Owner->$association_id->getAssociationOption('table_name');
        $owner_table = $this->Owner->getTableName();

        return ' LEFT OUTER JOIN '.$owner_table.' ON '.$owner_table.'.'.$this->Owner->getPrimaryKey().' = '.$table_name.'.'.$foreign_key;
    }


    /**
     * Triggers
     */
    function afterSave(&$object)
    {
        $success = true;
        $associated_ids = $object->getAssociatedIds();

        foreach ($associated_ids as $associated_id){
            if(!empty($object->$associated_id->__activeRecordObject)){

                if(strtolower($object->hasOne->getOption($associated_id, 'class_name')) == strtolower($object->$associated_id->getType())){
                    $object->hasOne->replace($associated_id, $object->$associated_id, false);
                    $object->$associated_id->set($object->hasOne->getOption($associated_id, 'foreign_key'), $object->getId());
                    $success = $object->$associated_id->save() ? $success : false;

                }elseif($object->$associated_id->getType() == 'hasOne'){
                    $attributes = array();
                    foreach ((array)$object->$associated_id as $k=>$v){
                        $k[0] != '_' ? $attributes[$k] = $v : null;
                    }
                    $attributes = array_diff($attributes, array(''));
                    if(!empty($attributes)){
                        $object->hasOne->build($associated_id, $attributes);
                    }
                }
            }
        }
        return $success;
    }


    function afterDestroy(&$object)
    {
        $success = true;
        $associated_ids = $object->getAssociatedIds();
        foreach ($associated_ids as $associated_id){
            if( isset($object->$associated_id->_associatedAs) &&
            $object->$associated_id->_associatedAs == 'hasOne' &&
            $object->$associated_id->getAssociationOption('dependent')){
                if(method_exists($object->$associated_id, 'destroy')){
                    $success = $object->$associated_id->destroy() ? $success : false;
                }
            }
        }
        return $success;
    }
}

?>