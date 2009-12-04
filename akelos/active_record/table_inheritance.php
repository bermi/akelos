<?php

/**
* == Single table inheritance ==
*
* Active Record allows inheritance by storing the name of the class in a column that by default is called "type" (can be changed
* by overwriting <tt>AkActiveRecord->_inheritanceColumn</tt>). This means that an inheritance looking like this:
*
* <code>
*   class Company extends ActiveRecord{}
*   class Firm extends Company{}
*   class Client extends Company{}
*   class PriorityClient extends Client{}
* </code>
*
* When you do $Firm->create('name =>', "akelos"), this record will be saved in the companies table with type = "Firm". You can then
* fetch this row again using $Company->find('first', "name = '37signals'") and it will return a Firm object.
*
* If you don't have a type column defined in your table, single-table inheritance won't be triggered. In that case, it'll work just
* like normal subclasses with no special magic for differentiating between them or reloading the right type with find.
*
* Note, all the attributes for all the cases are kept in the same table. Read more:
* http://www.martinfowler.com/eaaCatalog/singleTableInheritance.html
*/
class AkActiveRecordTableInheritance
{
    protected $_ActiveRecord;

    /**
     * Gets the column name for use with single table inheritance. Can be overridden in subclasses.
    */
    public function getInheritanceColumn()
    {
        return empty($this->_ActiveRecord->_inheritanceColumn) ? ($this->_ActiveRecord->hasColumn('type') ? 'type' : false ) : $this->_ActiveRecord->_inheritanceColumn;
    }

    /**
     * Defines the column name for use with single table inheritance. Can be overridden in subclasses.
     */
    public function setInheritanceColumn($column_name)
    {
        if(!$this->_ActiveRecord->hasColumn($column_name)){
            trigger_error(Ak::t('Could not set "%column_name" as the inheritance column as this column is not available on the database.',array('%column_name'=>$column_name)).' '.Ak::getFileAndNumberTextForError(1), E_USER_NOTICE);
            return false;
        }elseif($this->_ActiveRecord->getColumnType($column_name) != 'string'){
            trigger_error(Ak::t('Could not set %column_name as the inheritance column as this column type is "%column_type" instead of "string".',array('%column_name'=>$column_name,'%column_type'=>$this->_ActiveRecord->getColumnType($column_name))).' '.Ak::getFileAndNumberTextForError(1), E_USER_NOTICE);
            return false;
        }else{
            $this->_ActiveRecord->_inheritanceColumn = $column_name;
            return true;
        }
    }

    public function getSubclasses()
    {
        $current_class = get_class($this->_ActiveRecord);
        $subclasses = array();
        $classes = get_declared_classes();

        while ($class = array_shift($classes)) {
            $parent_class = get_parent_class($class);
            if($parent_class == $current_class || in_array($parent_class, $subclasses)){
                $subclasses[] = $class;
            }elseif(!empty($parent_class)){
                $classes[] = $parent_class;
            }
        }
        $subclasses = array_unique(array_map(array($this->_ActiveRecord,'_getModelName'),$subclasses));
        return $subclasses;
    }


    public function typeCondition($table_alias = null)
    {
        $inheritance_column = $this->getInheritanceColumn();
        $type_condition = array();
        $table_name = $this->_ActiveRecord->getTableName();
        $available_types = array_merge(array($this->_ActiveRecord->getModelName()), $this->getSubclasses());
        foreach ($available_types as $subclass){
            $type_condition[] = ' '.($table_alias != null ? $table_alias : $table_name).'.'.$inheritance_column.' = \''.AkInflector::humanize(AkInflector::underscore($subclass)).'\' ';
        }
        return empty($type_condition) ? '' : '('.join('OR',$type_condition).') ';
    }

    public function setExtendedBy(&$ActiveRecord)
    {
        $this->_ActiveRecord = $ActiveRecord;
    }
}
