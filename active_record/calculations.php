<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

class AkActiveRecordCalculations extends AkActiveRecordExtenssion
{
    protected $_calculation_options = array('conditions', 'joins', 'order', 'select', 'group', 'having', 'distinct', 'limit', 'offset');

    /**
      * Count operates using three different approaches.
      *
      * * Count all: By not passing any parameters to count, it will return a count of all the rows for the model.
      * * Count by conditions or joins
      * * Count using options will find the row count matched by the options used.
      *
      * The last approach, count using options, accepts an option hash as the only parameter. The options are:
      *
      * * <tt>'conditions'</tt>: An SQL fragment like "administrator = 1" or array("user_name = ?", $username ). See conditions in the intro.
      * * <tt>'joins'</tt>: An SQL fragment for additional joins like "LEFT JOIN comments ON comments.post_id = id". (Rarely needed).
      * * <tt>'order'</tt>: An SQL fragment like "created_at DESC, name" (really only used with GROUP BY calculations).
      * * <tt>'group'</tt>: An attribute name by which the result should be grouped. Uses the GROUP BY SQL-clause.
      * * <tt>'select'</tt>: By default, this is * as in SELECT * FROM, but can be changed if you for example want to do a join.
      * * <tt>'distinct'</tt>: Set this to true to make this a distinct calculation, such as SELECT COUNT(DISTINCT posts.id) ...
      *
      * Examples for counting all:
      *   $Person->count();         // returns the total count of all people
      *
      * Examples for count by +conditions+ and +joins+ (this has been deprecated):
      *   $Person->count("age > 26");  // returns the number of people older than 26
      *   $Person->find("age > 26 AND job.salary > 60000", "LEFT JOIN jobs on jobs.person_id = ".$Person->id); // returns the total number of rows matching the conditions and joins fetched by SELECT COUNT(*).
      *
      * Examples for count with options:
      *   $Person->count('conditions' => "age > 26");
      *   $Person->count('conditions' => "age > 26 AND job.salary > 60000", 'joins' => "LEFT JOIN jobs on jobs.person_id = $Person->id"); // finds the number of rows matching the conditions and joins.
      *   $Person->count('id', 'conditions' => "age > 26"); // Performs a COUNT(id)
      *   $Person->count('all', 'conditions' => "age > 26"); // Performs a COUNT(*) ('all' is an alias for '*')
      *
      * Note: $Person->count('all') will not work because it will use 'all' as the condition.  Use $Person->count() instead.
      */
    public function count() {
        $args = func_get_args();
        list($column_name, $options) = $this->_constructCountOptionsFromLegacyArgs($args);
        return $this->calculate('count', $column_name, $options);
    }

    /**
      * Calculates average value on a given column.  The value is returned as a float.  See #calculate for examples with options.
      *
      *     $Person->average('age');
      */
    public function average($column_name, $options = array()) {
        return $this->calculate('avg', $column_name, $options);
    }

    /**
      * Calculates the minimum value on a given column.  The value is returned with the same data type of the column..  See #calculate for examples with options.
      *
      *   $Person->minimum('age');
      */
    public function minimum($column_name, $options = array()) {
        return $this->calculate('min', $column_name, $options);
    }

    /**
      * Calculates the maximum value on a given column.  The value is returned with the same data type of the column..  See #calculate for examples with options.
      *
      *   $Person->maximum('age');
      */
    public function maximum($column_name, $options = array()) {
        return $this->calculate('max', $column_name, $options);
    }

    /**
      * Calculates the sum value on a given column.  The value is returned with the same data type of the column..  See #calculate for examples with options.
      *
      *   $Person->sum('age');
      */
    public function sum($column_name, $options = array()) {
        return $this->calculate('sum', $column_name, $options);
    }

    /**
      * This calculates aggregate values in the given column:  Methods for count, sum, average, minimum, and maximum have been added as shortcuts.
      * Options such as 'conditions', 'order', 'group', 'having', and 'joins' can be passed to customize the query.
      *
      * There are two basic forms of output:
      *   * Single aggregate value: The single value is type cast to integer for COUNT, float for AVG, and the given column's type for everything else.
      *   * Grouped values: This returns an ordered hash of the values and groups them by the 'group' option.  It takes a column name.
      *
      *       $values = $Person->maximum('age', array('group' => 'last_name'));
      *       echo $values["Drake"]
      *       => 43
      *
      * Options:
      * * <tt>'conditions'</tt>: An SQL fragment like "administrator = 1" or array( "user_name = ?", username ). See conditions in the intro.
      * * <tt>'joins'</tt>: An SQL fragment for additional joins like "LEFT JOIN comments ON comments.post_id = id". (Rarely needed).
      *   The records will be returned read-only since they will have attributes that do not correspond to the table's columns.
      * * <tt>'order'</tt>: An SQL fragment like "created_at DESC, name" (really only used with GROUP BY calculations).
      * * <tt>'group'</tt>: An attribute name by which the result should be grouped. Uses the GROUP BY SQL-clause.
      * * <tt>'select'</tt>: By default, this is * as in SELECT * FROM, but can be changed if you for example want to do a join.
      * * <tt>'distinct'</tt>: Set this to true to make this a distinct calculation, such as SELECT COUNT(DISTINCT posts.id) ...
      *
      * Examples:
      *   $Person->calculate('count', 'all'); // The same as $Person->count();
      *   $Person->average('age'); // SELECT AVG(age) FROM people...
      *   $Person->minimum('age', array('conditions' => array('last_name != ?', 'Drake'))); // Selects the minimum age for everyone with a last name other than 'Drake'
      *   $Person->minimum('age', array('having' => 'min(age) > 17', 'group' => 'last'_name)); // Selects the minimum age for any family without any minors
      */
    public function calculate($operation, $column_name, $options = array()) {
        $this->_validateCalculationOptions($options);
        $column_name = empty($options['select']) ? $column_name : $options['select'];
        $column_name = $column_name == 'all' ? '*' : $column_name;
        $column      = $this->_getColumnFor($column_name);
        if (!empty($options['group'])){
            return $this->_executeGroupedCalculation($operation, $column_name, $column, $options);
        }else{
            return $this->_executeSimpleCalculation($operation, $column_name, $column, $options);
        }

        return 0;
    }

    protected function _constructCountOptionsFromLegacyArgs($args) {
        $options = array();
        $column_name = 'all';

        /*
        We need to handle
        count()
        count(options=array())
        count($column_name='all', $options=array())
        count($conditions=null, $joins=null)
        */
        if(count($args) > 2){
            trigger_error(Ak::t("Unexpected parameters passed to count(\$options=array())").AkDebug::getFileAndNumberTextForError(1), E_USER_ERROR);
        }elseif(count($args) > 0){
            if(!empty($args[0]) && is_array($args[0])){
                $options = $args[0];
            }elseif(!empty($args[1]) && is_array($args[1])){
                $column_name = array_shift($args);
                $options = array_shift($args);
            }else{
                $options = array('conditions' => $args[0]);
                if(!empty($args[1])){
                    $options = array_merge($options, array('joins' => $args[1]));
                }
            }
        }
        return array($column_name, $options);
    }


    protected function _constructCalculationSql($operation, $column_name, $options) {
        $operation = strtolower($operation);
        $aggregate_alias = $this->_getColumnAliasFor($operation, $column_name);
        $use_workaround = $operation == 'count' && !empty($options['distinct']) && $this->_ActiveRecord->getDatabaseType() == 'sqlite';

        $sql = $use_workaround ?
        "SELECT COUNT(*) AS $aggregate_alias" : // A (slower) workaround if we're using a backend, like sqlite, that doesn't support COUNT DISTINCT.
        "SELECT $operation(".(empty($options['distinct'])?'':'DISTINCT ')."$column_name) AS $aggregate_alias";


        $sql .= empty($options['group']) ? '' : ", {$options['group_field']} AS {$options['group_alias']}";
        $sql .= $use_workaround ? " FROM (SELECT DISTINCT {$column_name}" : '';
        $sql .=  " FROM ".$this->_ActiveRecord->getTableName()." ";

        $sql .=  empty($options['joins']) ? '' : " {$options['joins']} ";

        $sql = empty($options['conditions']) ? $sql : $this->_ActiveRecord->sanitizeConditions($sql, $options['conditions']);

        if (!empty($options['group'])){
            $sql .=  " GROUP BY {$options['group_field']} ";
            $sql .= empty($options['having']) ? '' : " HAVING {$options['having']} ";
        }

        $sql .= empty($options['order']) ? '' : " ORDER BY {$options['order']} ";
        $this->_ActiveRecord->_db->addLimitAndOffset($sql, $options);
        $sql .= $use_workaround ? ')' : '';
        return $sql;
    }


    protected function _executeSimpleCalculation($operation, $column_name, $column, $options) {
        $value = $this->_ActiveRecord->_db->selectValue($this->_constructCalculationSql($operation, $column_name, $options));
        return $this->_typeCastCalculatedValue($value, $column, $operation);
    }

    protected function _executeGroupedCalculation($operation, $column_name, $column, $options) {
        $group_field = $options['group'];
        $group_alias = $this->_getColumnAliasFor($group_field);
        $group_column = $this->_getColumnFor($group_field);
        $options = array_merge(array('group_field' => $group_field, 'group_alias' => $group_alias),$options);
        $sql = $this->_constructCalculationSql($operation, $column_name, $options);
        $calculated_data = $this->_ActiveRecord->_db->select($sql);
        $aggregate_alias = $this->_getColumnAliasFor($operation, $column_name);

        $all = array();
        foreach ($calculated_data as $row){
            $key = $this->_typeCastCalculatedValue($row[$group_alias], $group_column);
            $all[$key] = $this->_typeCastCalculatedValue($row[$aggregate_alias], $column, $operation);
        }
        return $all;
    }

    protected function _validateCalculationOptions($options = array()) {
        $invalid_options = array_diff(array_keys($options),$this->_calculation_options);
        if(!empty($invalid_options)){
            trigger_error(Ak::t('%options are not valid calculation options.', array('%options'=>join(', ',$invalid_options))).AkDebug::getFileAndNumberTextForError(1), E_USER_ERROR);
        }
    }

    /**
    * Converts a given key to the value that the database adapter returns as
    * as a usable column name.
    *   users.id #=> users_id
    *   sum(id) #=> sum_id
    *   count(distinct users.id) #=> count_distinct_users_id
    *   count(*) #=> count_all
    */
    protected function _getColumnAliasFor() {
        $args = func_get_args();
        $keys = strtolower(join(' ',(!empty($args) ? (is_array($args[0]) ? $args[0] : $args) : array())));
        return preg_replace(array('/\*/','/\W+/','/^ +/','/ +$/','/ +/'),array('all',' ','','','_'), $keys);
    }

    protected function _getColumnFor($field) {
        $field_name = ltrim(substr($field,strpos($field,'.')),'.');
        if(in_array($field_name,$this->_ActiveRecord->getColumnNames())){
            return $field_name;
        }
        return $field;
    }

    protected function _typeCastCalculatedValue($value, $column, $operation = null) {
        $operation = strtolower($operation);
        if($operation == 'count'){
            return intval($value);
        }elseif ($operation == 'avg'){
            return floatval($value);
        }else{
            return empty($column) ? $value : $this->_ActiveRecord->castAttributeFromDatabase($column, $value);
        }
    }


    protected function _constructCalculationSqlWithAssociations($sql, $options = array()) {
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

    protected function &_calculateBySqlWithAssociations($sql) {
        $objects = array();
        $results = $this->_db->execute ($sql,'find with associations');
        if (!$results){
            return $objects;
        }
        return $results;
    }
}
