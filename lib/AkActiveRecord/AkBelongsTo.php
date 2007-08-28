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

require_once(AK_LIB_DIR.DS.'AkActiveRecord'.DS.'AkAssociation.php');

/**
* Adds the following methods for retrieval and query for a single associated object that this object holds an id to.
* * <tt>belongsTo->assign($association_id, $Associate);</tt> - assigns the associate object, extracts the primary key, and sets it as the foreign key.
* * <tt>belongsTo->build($association_id, $attributes = array())</tt> - returns a new object of the associated type that has been instantiated
*   with +attributes+ and linked to this object through a foreign key but has not yet been saved.
* * <tt>belongsTo->create($association_id, $attributes = array())</tt> - returns a new object of the associated type that has been instantiated
*   with +attributes+ and linked to this object through a foreign key and that has already been saved (if it passed the validation).
*
* Example: A Post class declares <tt>belongsTo('author')</tt>, which will add:
* * <tt>$Post->author->load()</tt> (similar to <tt>$Author->find($author_id)</tt>)
* * <tt>$Post->author->assign($Author)</tt> (similar to <tt>$Post->author_id = $Author->getId();</tt>)
* * <tt>$Post->author->build($Author);</tt> (similar to <tt>$Post->author = new Author();</tt>)
* * <tt>$Post->author->create($Author);</tt> (similar to <tt>$Post->author = new Author(); $Post->author->save();</tt>)
*  The declaration can also include an options hash to specialize the behavior of the association.
*  
*  Options are:
*  * <tt>class_name</tt>  - specify the class name of the association. Use it only if that name can't be inferred
*    from the association name. So <tt>belongsTo('author')</tt> will by default be linked to the 'Author' class, but
*    if the real class name is 'Person', you'll have to specify it with this option.
*  * <tt>conditions</tt>  - specify the conditions that the associated object must meet in order to be included as a "WHERE"
*    sql fragment, such as "authorized = 1".
*  * <tt>order</tt>       - specify the order from which the associated object will be picked at the top. Specified as
*    an "ORDER BY" sql fragment, such as "last_name, first_name DESC"
*  * <tt>primary_key_name</tt> - specify the foreign key used for the association. By default this is guessed to be the name
*    of the associated class in lower-case and "_id" suffixed. So a 'Person' class that makes a belongsTo association to a
*    'Boss' class will use "boss_id" as the default primary_key_name.
*  * <tt>counter_cache</tt> - caches the number of belonging objects on the associate class through use of increment_counter 
*    and decrement_counter. The counter cache is incremented when an object of this class is created and decremented when it's
*    destroyed. This requires that a column named "#{table_name}_count" (such as comments_count for a belonging Comment class)
*    is used on the associate class (such as a Post class).
* 
*  Option examples:
*    belongsTo('firm', array('primary_key_name' => 'client_of'));
*    belongsTo('author', array('class_name' => 'Person', 'primary_key_name' => 'author_id'));
*    belongsTo('valid_coupon', array('class_name' => 'Coupon', 'primary_key_name' => 'coupon_id', 'conditions' => "'discounts' > 'payments_count'"));
*/
class AkBelongsTo extends AkAssociation
{
    var $asssociated_ids = array();

    function &addAssociated($association_id, $options = array())
    {

        $default_options = array(
        'class_name' => empty($options['class_name']) ? AkInflector::camelize($association_id) : $options['class_name'],
        'primary_key_name',
        'remote',
        'conditions',
        'order',
        //'dependent',
        'instantiate'=>false,
        'counter_cache' => false
        );

        $options = array_merge($default_options, $options);

        //$options['table_name'] = empty($options['table_name']) ? AkInflector::tableize($options['class_name']) : $options['table_name'];
        $options['primary_key_name'] = empty($options['primary_key_name']) ? AkInflector::underscore($options['class_name']).'_id' : $options['primary_key_name'];
        if($options['counter_cache']){
            $options['counter_cache_column'] = !isset($options['counter_cache_column']) ? AkInflector::underscore($options['class_name']).'_counter' : $options['counter_cache_column'];
        }

        $this->setOptions($association_id, $options);

        $associated = $this->addModel($association_id,  new AkAssociatedActiveRecord());

        $this->setAssociatedId($association_id, $associated->getId());

        $this->_build($association_id, $associated);

        $this->_saveLoadedHandler($association_id, $associated);

        if($options['instantiate']){
            $associated =& $this->assign($association_id,  new $options['class_name']($this->Owner->get($options['primary_key_name'])));
        }

        return $associated;
    }


    function getType()
    {
        return 'belongsTo';
    }


    function &findAssociated($association_id)
    {
        $result = false;
        $primary_key_name = $this->Owner->$association_id->getAssociationOption('primary_key_name');
        $primary_key_name_value = $this->Owner->get($primary_key_name);
        if(!$primary_key_name_value){
            return $result;
        }
        if(empty($this->Owner->$association_id->__activeRecordObject)){
            $this->build($association_id, array(), false);
        }

        $result =& $this->Owner->$association_id->find($primary_key_name_value);
        
        return $result;
    }

    function &assign($association_id, &$Associated)
    {
        $primary_key_name = $this->Owner->$association_id->getAssociationOption('primary_key_name');
        if($Associated->save()){
            $this->Owner->set($primary_key_name, $Associated->getId());
        }
        $Associated =& $this->_build($association_id, &$Associated);
        return $Associated;
    }

    function &build($association_id, $attributes = array(), $replace = true)
    {
        $class_name = $this->Owner->$association_id->getAssociationOption('class_name');
        Ak::import($class_name);
        $record =& new $class_name($attributes);
        $record =& $this->Owner->$association_id->replace($record);
        return $record;
    }

    /**
    * Returns a new object of the associated type that has been instantiated with attributes 
    * and linked to this object through a foreign key and that has already been saved (if it passed the validation)
    */
    function &create($association_id, $attributes = array())
    {
        $class_name = $this->Owner->$association_id->getAssociationOption('class_name');
        $record =& new $class_name($attributes);
        $record->save();
        $this->replace($association_id, $record, true);
        return $this->Owner->$association_id;
    }
    
    
    function &load($association_id)
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

    function &replace($association_id, &$NewAssociated)
    {
        $counter_cache_name = $this->Owner->belongsTo->getOption($association_id, 'counter_cache_column');
        if(empty($NewAssociated)){
            $primary_key = $this->Owner->belongsTo->getOption($association_id, 'primary_key_name');
            if($counter_cache_name && isset($this->Owner->$association_id->$counter_cache_name) && !$this->Owner->isNewRecord()){
                $this->Owner->$association_id->decrementCounter($counter_cache_name, $this->Owner->get($primary_key));
            }
            $this->Owner->$association_id =& $this->_getLoadedHandler($association_id);
            $this->Owner->set($primary_key, null);
        }else{
            $primary_key = $this->Owner->belongsTo->getOption($association_id, 'primary_key_name');
            if($counter_cache_name && !$this->Owner->isNewRecord()){
                $this->Owner->$association_id->incrementCounter($counter_cache_name, $NewAssociated->getId());
                $previous_id = $this->Owner->get($primary_key);
                if($previous_id){
                    $this->Owner->$association_id->decrementCounter($counter_cache_name, $previous_id);
                }
            }
            if(!$NewAssociated->isNewRecord()){
                $this->Owner->set($primary_key, $NewAssociated->getId());
            }
            $this->updated[$association_id] = true;

            $this->updated[$association_id] = true;
            $this->loaded[$association_id] = true;
        }
        $this->_build($association_id, $NewAssociated);
        return $NewAssociated;
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
        '__owner.'.$this->Owner->$association_id->getAssociationOption('primary_key_name').
        ' = '.
        '_'.$association_id.'.'.$this->Owner->$association_id->getPrimaryKey().' ';
    }


    /**
     * Triggers
     */

    function beforeSave(&$object)
    {
        $association_ids = $object->getAssociatedIds();
        foreach ($association_ids as $association_id){
            if( !empty($object->$association_id->__activeRecordObject) &&
            strtolower($object->belongsTo->getOption($association_id, 'class_name')) == strtolower($object->$association_id->getType())){
                $primary_key_name = $this->Owner->belongsTo->getOption($association_id, 'primary_key_name');
                if($object->$association_id->isNewRecord() && !$object->$association_id->hasAttributesDefined()){
                    $object->$association_id->save(true);
                }
                $primary_key_name_value = $object->$association_id->getId();
                if(!empty($primary_key_name_value)){
                    $object->set($primary_key_name, $primary_key_name_value);
                }
            }
        }
        return true;
    }

    function beforeDestroy(&$object)
    {
        $association_ids = $object->getAssociatedIds();
        foreach ($association_ids as $association_id){
            if(!empty($object->$association_id) && is_object($object->$association_id) && method_exists($object->$association_id,'getType') && 
            strtolower($object->belongsTo->getOption($association_id, 'class_name')) == strtolower($object->$association_id->getType())){
                $primary_key_name = $this->Owner->$association_id->getAssociationOption('primary_key_name');
                if($this->Owner->$association_id->getAssociationOption('counter_cache')){
                    $object->$association_id->decrementCounter(AkInflector::pluralize($association_id).'_count', $object->get($primary_key_name));
                }
            }
        }
        return true;
    }
}


?>