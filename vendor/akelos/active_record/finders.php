<?php

class AkActiveRecordFinders extends AkActiveRecordExtenssion
{
    /**
    * Returns true if the given id represents the primary key of a record in the database, false otherwise. Example:
    *
    * $Person->exists(5);
    */
    public function exists($id) {
        return $this->find('first',array('conditions' => array($this->_ActiveRecord->getPrimaryKey().' = '.$id))) !== false;
    }

    /**
     * Find operates with three different retrieval approaches:
    * * Find by id: This can either be a specific id find(1), a list of ids find(1, 5, 6),
    *   or an array of ids find(array(5, 6, 10)). If no record can be found for all of the listed ids,
    *   then RecordNotFound will be raised.
    * * Find first: This will return the first record matched by the options used. These options
    *   can either be specific conditions or merely an order.
    *   If no record can matched, false is returned.
    * * Find all: This will return all the records matched by the options used. If no records are found, an empty array is returned.
    *
    * All approaches accepts an $option array as their last parameter. The options are:
    *
    * 'conditions' => An SQL fragment like "administrator = 1" or array("user_name = ?" => $username). See conditions in the intro.
    * 'order' => An SQL fragment like "created_at DESC, name".
    * 'limit' => An integer determining the limit on the number of rows that should be returned.
    * 'offset' => An integer determining the offset from where the rows should be fetched. So at 5, it would skip the first 4 rows.
    * 'joins' => An SQL fragment for additional joins like "LEFT JOIN comments ON comments.post_id = $id". (Rarely needed).
    * 'include' => Names associations that should be loaded alongside using LEFT OUTER JOINs. The symbols
    * named refer to already defined associations. See eager loading under Associations.
    *
    * Examples for find by id:
    * <code>
    *   $Person->find(1);       // returns the object for ID = 1
    *   $Person->find(1, 2, 6); // returns an array for objects with IDs in (1, 2, 6), Returns false if any of those IDs is not available
    *   $Person->find(array(7, 17)); // returns an array for objects with IDs in (7, 17)
    *   $Person->find(array(1));     // returns an array for objects the object with ID = 1
    *   $Person->find(1, array('conditions' => "administrator = 1", 'order' => "created_on DESC"));
    * </code>
    *
    * Examples for find first:
    * <code>
    *   $Person->find('first'); // returns the first object fetched by SELECT * FROM people
    *   $Person->find('first', array('conditions' => array("user_name = ':user_name'", ':user_name' => $user_name)));
    *   $Person->find('first', array('order' => "created_on DESC", 'offset' => 5));
    * </code>
    *
    * Examples for find all:
    * <code>
    *   $Person->find('all'); // returns an array of objects for all the rows fetched by SELECT * FROM people
    *   $Person->find(); // Same as $Person->find('all');
    *   $Person->find('all', array('conditions' => array("category IN (categories)", 'categories' => join(','$categories)), 'limit' => 50));
    *   $Person->find('all', array('offset' => 10, 'limit' => 10));
    *   $Person->find('all', array('include' => array('account', 'friends'));
    * </code>
    */
    public function &find() {
        $args = func_get_args();
        $options = $this->_extractOptionsFromArgs($args);
        list($fetch, $options) = $this->_extractConditionsFromArgs($args, $options);

        $this->_sanitizeConditionsVariables($options);

        switch ($fetch) {
            case 'first':
                return $this->_findInitial($options);

            case 'all':
                return $this->_findEvery($options);

            default:
                return $this->_findFromIds($args, $options);
        }
    }

    public function &findFirst() {
        $args = func_get_args();
        $result = call_user_func_array(array($this,'find'), array_merge(array('first'),$args));
        return $result;
    }

    public function &findAll() {
        $args = func_get_args();
        $result = call_user_func_array(array($this,'find'), array_merge(array('all'),$args));
        return $result;
    }


    /**
    * Works like find_all, but requires a complete SQL string. Examples:
    * $Post->findBySql("SELECT p.*, c.author FROM posts p, comments c WHERE p.id = c.post_id");
    * $Post->findBySql(array("SELECT * FROM posts WHERE author = ? AND created_on > ?", $author_id, $start_date));
    */
    public function &findBySql($sql, $limit = null, $offset = null, $bindings = null, $returns = 'default', $simulation_class = 'AkActiveRecordMock') {
        if ($limit || $offset){
            Ak::deprecateWarning("You're calling AR::findBySql with \$limit or \$offset parameters. This has been deprecated.");
            $this->_ActiveRecord->_db->addLimitAndOffset($sql, array('limit'=>$limit,'offset'=>$offset));
        }
        $objects = array();
        $records = $this->_ActiveRecord->_db->select ($sql,'selecting');
        foreach ($records as $record){
            if ($returns == 'default') {
                $objects[] = $this->instantiate($this->_ActiveRecord->getOnlyAvailableAttributes($record), false);
            } else if ($returns == 'simulated') {
                $objects[] = $this->_ActiveRecord->castAttributesFromDatabase($this->_ActiveRecord->getOnlyAvailableAttributes($record));
            } else if ($returns == 'array') {

                $objects[] = $this->_ActiveRecord->castAttributesFromDatabase($this->_ActiveRecord->getOnlyAvailableAttributes($record));
            }
        }
        if ($returns == 'simulated') {
            $false = false;
            $objects = $this->_generateStdClasses($simulation_class,$objects,$this->_ActiveRecord->getType(),$false,$false,array('__owner'=>array('pk'=>$this->_ActiveRecord->getPrimaryKey(),'class'=>$this->_ActiveRecord->getType())));
        }

        return $objects;
    }

    /**
    * This function pretends to emulate RoR finders until AkActiveRecord::addMethod becomes stable on future PHP versions.
    * @todo use PHP5 __call method for handling the magic finder methods like findFirstByUnsenameAndPassword('bermi','pass')
    */
    public function &findFirstBy() {
        $args = func_get_args();
        array_unshift($args,'first');
        $result = call_user_func_array(array($this,'findBy'), $args);
        return $result;
    }

    public function &findLastBy() {
        $args = func_get_args();
        $options = $this->_extractOptionsFromArgs($args);
        $options['order'] = $this->_quoteAttributeName($this->_ActiveRecord->getPrimaryKey()).' DESC';
        array_push($args, $options);
        $result = call_user_func_array(array($this,'findFirstBy'), $args);
        return $result;
    }

    public function &findAllBy() {
        $args = func_get_args();
        array_unshift($args,'all');
        $result = call_user_func_array(array($this,'findBy'), $args);
        return $result;
    }

    /**
    * This method allows you to use finders in a more flexible way like:
    *
    *   findBy('username AND password', $username, $password);
    *   findBy('age > ? AND name:contains', 18, 'Joe');
    *   findBy('is_active = true AND session_id', session_id());
    *
    */
    public function &findBy() {
        $args = func_get_args();
        $find_by_sql = array_shift($args);
        if($find_by_sql == 'all' || $find_by_sql == 'first'){
            $fetch = $find_by_sql;
            $find_by_sql = array_shift($args);
        }else{
            $fetch = 'all';
        }

        $options = $this->_extractOptionsFromArgs($args);

        $query_values = $args;
        $query_arguments_count = count($query_values);

        list($sql, $requested_args) = $this->_getFindBySqlAndColumns($find_by_sql, $query_values);

        if($query_arguments_count != count($requested_args)){
            trigger_error(Ak::t('Argument list did not match expected set. Requested arguments are:').join(', ',$requested_args), E_USER_ERROR);
            $false = false;
            return $false;
        }

        $true_bool_values = array(true,1,'true','True','TRUE','1','y','Y','yes','Yes','YES','s','Si','SI','V','v','T','t');

        foreach ($requested_args as $k=>$v){
            switch ($this->_ActiveRecord->getColumnType($v)) {
                case 'boolean':
                    $query_values[$k] = in_array($query_values[$k],$true_bool_values) ? true : false;
                    break;

                case 'date':
                case 'datetime':
                    $query_values[$k] = str_replace('/','-', $this->_ActiveRecord->castAttributeForDatabase($k,$query_values[$k],false));
                    break;

                default:
                    break;
            }
        }

        $conditions = array($sql);
        foreach ($query_values as $bind_value){
            $conditions[] = $bind_value;
        }
        /**
        * @todo merge_conditions
        */
        $options['conditions'] = $conditions;

        $result = call_user_func_array(array($this,'find'), array($fetch,$options));
        return $result;
    }

    public function &findOrCreateBy() {
        $args = func_get_args();
        $Item = call_user_func_array(array($this,'findFirstBy'), $args);
        if(!$Item){
            $attributes = array();

            list($sql, $columns) = $this->_getFindBySqlAndColumns(array_shift($args), $args);

            if(!empty($columns)){
                foreach ($columns as $column){
                    $attributes[$column] = array_shift($args);
                }
            }
            $Item = $this->_ActiveRecord->create($attributes);
            $Item->has_been_created = true;
        }else{
            $Item->has_been_created = false;
        }
        $Item->has_been_found = !$Item->has_been_created;
        return $Item;
    }


    /**
     *  Given a condition that uses bindings like "user = ?  AND created_at > ?" will return a
     * string replacing the "?" bindings with the column values for current Active Record
     *
     * @return string
     */
    public function getVariableSqlCondition($variable_condition) {
        $query_values = array();
        list($sql, $requested_columns) = $this->_getFindBySqlAndColumns($variable_condition, $query_values);
        $replacements = array();
        $sql = preg_replace('/((('.join($requested_columns,'|').') = \?) = \?)/','$2', $sql);
        foreach ($requested_columns as $attribute){
            $replacements[$attribute] = $this->_ActiveRecord->castAttributeForDatabase($attribute, $this->_ActiveRecord->get($attribute));
        }
        return trim(preg_replace('/('.join('|',array_keys($replacements)).')\s+([^\?]+)\s+\?/e', "isset(\$replacements['\\1']) ? '\\1 \\2 '.\$replacements['\\1']:'\\1 \\2 null'", $sql));
    }


    public function constructFinderSql($options, $select_from_prefix = 'default') {
        $sql = isset($options['select_prefix']) ? $options['select_prefix'] : ($select_from_prefix == 'default' ? 'SELECT '.(!empty($options['joins'])?$this->_ActiveRecord->getTableName().'.':'') .'* FROM '.$this->_ActiveRecord->getTableName() : $select_from_prefix);
        $sql .= !empty($options['joins']) ? ' '.$options['joins'] : '';

        $sql = $this->sanitizeConditions($sql, isset($options['conditions']) ? $options['conditions'] : array());

        // Create an alias for order
        if(empty($options['order']) && !empty($options['sort'])){
            $options['order'] = $options['sort'];
        }

        $sql .= !empty($options['group']) ? ' GROUP BY '.$options['group'] : '';
        $sql .= !empty($options['order']) ? ' ORDER BY '.$options['order'] : '';

        $this->_ActiveRecord->_db->addLimitAndOffset($sql,$options);

        return $sql;
    }


    /**
    * Adds a sanitized version of $conditions to the $sql string.
    */
    public function sanitizeConditions($sql = null, $conditions = null, $table_alias = null) {
        if (empty($sql)) {
            $concat = '';
        }
        if (is_string($conditions) && (preg_match('/^SELECT.*?WHERE/is',trim($conditions)))) {
            $concat = '';
            $sql = $conditions;
            $conditions = '';
        } else {
            $concat = 'WHERE';
        }
        $concat = empty($sql) ? '' : ' WHERE ';
        if (stristr($sql,' WHERE ')) $concat = ' AND ';
        if (empty($conditions) && $this->_ActiveRecord->getDatabaseType() == 'sqlite') $conditions = '1';  // sqlite HACK

        if($this->_ActiveRecord->getInheritanceColumn() !== false){
            $type_condition = $this->_ActiveRecord->typeCondition($table_alias);
            if (empty($sql)) {
                $sql .= !empty($type_condition) ? $concat.$type_condition : '';
                $concat = ' AND ';
                if (!empty($conditions)) {
                    $conditions = '('.$conditions.')';
                }
            } else {
                if (($wherePos=stripos($sql,'WHERE'))!==false) {
                    if (!empty($type_condition)) {
                        $oldConditions = trim(substr($sql,$wherePos+5));
                        $sql = substr($sql,0,$wherePos).' WHERE '.$type_condition.' AND ('.$oldConditions.')';
                        $concat = ' AND ';
                    }
                    if (!empty($conditions)) {
                        $conditions = '('.$conditions.')';
                    }
                } else {
                    if (!empty($type_condition)) {
                        $sql = $sql.' WHERE '.$type_condition.'';
                        $concat = ' AND ';
                    }
                    if (!empty($conditions)) {
                        $conditions = '('.$conditions.')';
                    }
                }
            }
        }

        if(!empty($conditions)){
            $sql  .= $concat.$conditions;
            $concat = ' AND ';
        }

        return $sql;
    }

    /**
    * Gets a sanitized version of the input array. Each element will be escaped
    */
    public function getSanitizedConditionsArray($conditions_array) {
        $result = array();
        foreach ($conditions_array as $k=>$v){
            $k = str_replace(':','',$k); // Used for Oracle type bindings
            if($this->_ActiveRecord->hasColumn($k)){
                $v = $this->_ActiveRecord->castAttributeForDatabase($k, $v);
                $result[$k] = $v;
            }
        }
        return $result;
    }


    /**
    * This functions is used to get the conditions from an AkRequest object
    */
    public function getConditions($conditions, $prefix = '', $model_name = null) {
        $model_name = isset($model_name) ? $model_name : $this->_ActiveRecord->getModelName();
        $model_conditions = !empty($conditions[$model_name]) ? $conditions[$model_name] : $conditions;
        if($this->_ActiveRecord->$model_name instanceof $model_name){
            $model_instance = $this->_ActiveRecord->$model_name;
        }else{
            $model_instance = $this;
        }
        $new_conditions = array();
        if(is_array($model_conditions)){
            foreach ($model_conditions as $col=>$value){
                if($model_instance->hasColumn($col)){
                    $new_conditions[$prefix.$col] = $value;
                }
            }
        }
        return $new_conditions;
    }



    /**
    * Finder methods must instantiate through this method to work with the single-table inheritance model and
    * eager loading associations.
    * That makes it possible to create objects of different types from the same table.
    */
    public function &instantiate($record, $set_as_new = true, $call_after_instantiate = true) {
        $inheritance_column = $this->_ActiveRecord->getInheritanceColumn();
        if(!empty($record[$inheritance_column])){
            $inheritance_column = $record[$inheritance_column];
            $inheritance_model_name = AkInflector::camelize($inheritance_column);
            @require_once(AkInflector::toModelFilename($inheritance_model_name));
            if(!class_exists($inheritance_model_name)){
                trigger_error($this->_ActiveRecord->t("The single-table inheritance mechanism failed to locate the subclass: '%class_name'. ".
                "This error is raised because the column '%column' is reserved for storing the class in case of inheritance. ".
                "Please rename this column if you didn't intend it to be used for storing the inheritance class ".
                "or overwrite #{self.to_s}.inheritance_column to use another column for that information.",
                array('%class_name'=>$inheritance_model_name, '%column'=>$this->_ActiveRecord->getInheritanceColumn())).Ak::getFileAndNumberTextForError(1),E_USER_ERROR);
            }
        }

        $model_name = isset($inheritance_model_name) ? $inheritance_model_name : $this->_ActiveRecord->getModelName();
        $object = new $model_name(array('init'=>false));
        $object->_newRecord = $set_as_new;
        $object->setConnection($this->_ActiveRecord->getConnection());
        $object->beforeInstantiate($record);
        $object->init(array('attributes', $record));

        if ($call_after_instantiate) {
            $object->afterInstantiate();
            $object->notifyObservers('afterInstantiate');
        }
        (AK_CLI && AK_ENVIRONMENT == 'development') ? $object ->toString() : null;

        return $object;
    }



    protected function _extractOptionsFromArgs(&$args) {
        $last_arg = count($args)-1;
        return isset($args[$last_arg]) && is_array($args[$last_arg]) && $this->_isOptionsHash($args[$last_arg]) ? array_pop($args) : array();
    }

    protected function _isOptionsHash($options) {
        if (isset($options[0])){
            return false;
        }
        $valid_keys = array('simulation_class','returns','load_acts','wrap','conditions', 'include', 'joins', 'limit', 'offset', 'group', 'order', 'sort', 'bind', 'select','select_prefix', 'readonly', 'load_associations', 'load_acts');
        return count($options) != count(array_diff(array_keys($options), $valid_keys));
    }

    protected function _extractConditionsFromArgs($args, $options) {
        if(empty($args)){
            $fetch = 'all';
        } else {
            $fetch = $args[0];
        }
        $num_args = count($args);

        // deprecated: acts like findFirstBySQL
        if ($num_args === 1 && !is_numeric($args[0]) && is_string($args[0]) && $args[0] != 'all' && $args[0] != 'first'){
            //  $Users->find("last_name = 'Williams'");    => find('first',"last_name = 'Williams'");
            Ak::deprecateWarning(array("AR::find('%sql') is ambiguous and therefore deprecated, use AR::find('first',%sql) instead", '%sql'=>$args[0]));
            $options = array('conditions'=> $args[0]);
            return array('first',$options);
        } //end

        // set fetch_mode to 'all' if none is given
        if (!is_numeric($fetch) && !is_array($fetch) && $fetch != 'all' && $fetch != 'first') {
            array_unshift($args, 'all');
            $num_args = count($args);
        }
        if ($num_args > 1) {
            if (is_string($args[1])){
                //  $Users->find(:fetch_mode,"first_name = ?",'Tim');
                $fetch = array_shift($args);
                $options = array_merge($options, array('conditions'=>$args));   //TODO: merge_conditions
            }elseif (is_array($args[1])) {
                //  $Users->find(:fetch_mode,array('first_name = ?,'Tim'));
                $fetch = array_shift($args);
                $options = array_merge($options, array('conditions'=>$args[0]));   //TODO: merge_conditions
            }
        }

        return array($fetch,$options);
    }

    protected function _sanitizeConditionsVariables(&$options) {
        if(!empty($options['conditions']) && is_array($options['conditions'])){
            if (isset($options['conditions'][0]) && strstr($options['conditions'][0], '?') && count($options['conditions']) > 1){
                //array('conditions' => array("name=?",$name))
                $pattern = array_shift($options['conditions']);
                $options['bind'] = array_values($options['conditions']);
                $options['conditions'] = $pattern;
            }elseif (isset($options['conditions'][0])){
                //array('conditions' => array("user_name = :user_name", ':user_name' => 'hilario')
                $pattern = array_shift($options['conditions']);
                $options['conditions'] = str_replace(array_keys($options['conditions']), array_values($this->_ActiveRecord->getSanitizedConditionsArray($options['conditions'])),$pattern);
            }else{
                //array('conditions' => array('user_name'=>'Hilario'))
                $options['conditions'] = join(' AND ',(array)$this->_ActiveRecord->getAttributesQuoted($options['conditions']));
            }
        }
        $this->_sanitizeConditionsCollections($options);
    }

    protected function _sanitizeConditionsCollections(&$options) {
        if(!empty($options['bind']) && is_array($options['bind']) && preg_match_all('/([a-zA-Z_]+)\s+IN\s+\(?\?\)?/i', $options['conditions'], $matches)){
            $i = 0;
            foreach($options['bind'] as $k => $v){
                if(isset($matches[1][$i]) && is_array($v)){
                    $value = join(', ', $this->_ActiveRecord->castAttributesForDatabase($matches[1][$i], $v));
                    $startpos=strpos($options['conditions'],$matches[0][$i]);
                    $endpos=$startpos+strlen($matches[0][$i]);
                    $options['conditions'] = substr($options['conditions'],0,$startpos).str_replace('?', $value, $matches[0][$i]).substr( $options['conditions'],$endpos);
                    unset($options['bind'][$k]);
                    $i++;
                }
            }
        }
    }

    protected function _getFindBySqlAndColumns($find_by_sql, &$query_values) {
        $sql = str_replace(array('(',')','||','|','&&','&','  '),array(' ( ',' ) ',' OR ',' OR ',' AND ',' AND ',' '), $find_by_sql);
        $operators = array('AND','and','(',')','&','&&','NOT','<>','OR','|','||');
        $pieces = explode(' ',$sql);
        $pieces = array_diff($pieces,array(' ',''));
        $params = array_diff($pieces,$operators);
        $operators = array_diff($pieces,$params);

        $new_sql = '';
        $parameter_count = 0;
        $requested_args = array();
        foreach ($pieces as $piece){
            if(in_array($piece,$params) && $this->_ActiveRecord->hasColumn($piece)){
                $new_sql .= $piece.' = ? ';
                $requested_args[$parameter_count] = $piece;
                $parameter_count++;
            }elseif (!in_array($piece,$operators)){

                if(strstr($piece,':')){
                    $_tmp_parts = explode(':',$piece);
                    if($this->_ActiveRecord->hasColumn($_tmp_parts[0])){
                        $query_values[$parameter_count] = isset($query_values[$parameter_count]) ? $query_values[$parameter_count] : $this->_ActiveRecord->get($_tmp_parts[0]);
                        switch (strtolower($_tmp_parts[1])) {
                            case 'like':
                            case '%like%':
                            case 'is':
                            case 'has':
                            case 'contains':
                                $query_values[$parameter_count] = '%'.$query_values[$parameter_count].'%';
                                $new_sql .= $_tmp_parts[0]." LIKE ? ";
                                break;
                            case 'like_left':
                            case 'like%':
                            case 'begins':
                            case 'begins_with':
                            case 'starts':
                            case 'starts_with':
                                $query_values[$parameter_count] = $query_values[$parameter_count].'%';
                                $new_sql .= $_tmp_parts[0]." LIKE ? ";
                                break;
                            case 'like_right':
                            case '%like':
                            case 'ends':
                            case 'ends_with':
                            case 'finishes':
                            case 'finishes_with':
                                $query_values[$parameter_count] = '%'.$query_values[$parameter_count];
                                $new_sql .= $this->_quoteAttributeName($_tmp_parts[0])." LIKE ? ";
                                break;
                            case 'in':
                                $values = join(', ', $this->_ActiveRecord->castAttributesForDatabase($_tmp_parts[0], $query_values[$parameter_count]));
                                if(!empty($values)){
                                    $new_sql .= $_tmp_parts[0].' IN ('.$values.') ';
                                }else{
                                    $new_sql = preg_replace('/(AND|OR) $/','', $new_sql);
                                }
                                unset($query_values[$parameter_count]);
                                break;
                            default:
                                $query_values[$parameter_count] = $query_values[$parameter_count];
                                $new_sql .= $_tmp_parts[0].' '.$_tmp_parts[1].' ? ';
                                break;
                        }
                        $requested_args[$parameter_count] = $_tmp_parts[0];
                        $parameter_count++;
                    }else {
                        $new_sql .= $_tmp_parts[0];
                    }
                }else{
                    $new_sql .= $piece.' ';
                }
            }else{
                $new_sql .= $piece.' ';
            }
        }

        return array($new_sql, $requested_args);
    }


    protected function &_findInitial($options) {
        // TODO: virtual_limit is a hack
        // actually we fetch_all and return only the first row
        $options = array_merge($options, array((!empty($options['include']) ?'virtual_limit':'limit')=>1));
        $result = $this->_findEvery($options);

        if(!empty($result) && is_array($result)){
            return $result[0];
        }else{
            $_result = false;
            return  $_result;
        }
    }

    protected function &_findEvery($options) {
        if((!empty($options['include']) && $this->_ActiveRecord->hasAssociations())){
            $result = $this->findWithAssociations($options);
        }else{
            $sql = $this->constructFinderSql($options);
            if (isset($options['wrap'])) {
                $sql = str_replace('{query}',$sql,$options['wrap']);
            }
            if(!empty($options['bind']) && is_array($options['bind']) && strstr($sql,'?')){
                $sql = array_merge(array($sql),$options['bind']);
            }
            if (!empty($options['returns']) && $options['returns']!='default') {
                $options['returns'] = in_array($options['returns'],array('simulated','default','array'))?$options['returns']:'default';
                $simulation_class = !empty($options['simulation_class']) && class_exists($options['simulation_class'])?$options['simulation_class']:'AkActiveRecordMock';
                $result = $this->findBySql($sql,null,null,null,$options['returns'],$simulation_class);
            } else {
                $result = $this->findBySql($sql);
            }
        }

        if(!empty($result) && is_array($result)){
            return $result;
        }else{
            $_result = false;
            return  $_result;
        }
    }

    protected function &_findFromIds($ids, $options) {
        $expects_array = is_array($ids[0]);

        $ids = array_map(array($this->_ActiveRecord, 'quotedId'),array_unique($expects_array ? (isset($ids[1]) ? array_merge($ids[0],$ids) : $ids[0]) : $ids));
        $num_ids = count($ids);

        //at this point $options['conditions'] can't be an array
        //$conditions = !empty($options['conditions']) ? ' AND '.$options['conditions'] : '';
        $conditions=!empty($options['conditions'])?$options['conditions']:'';
        switch ($num_ids){
            case 0 :
                trigger_error($this->_ActiveRecord->t('Couldn\'t find %object_name without an ID%conditions',array('%object_name'=>$this->_ActiveRecord->getModelName(),'%conditions'=>$conditions)).Ak::getFileAndNumberTextForError(1), E_USER_ERROR);
                break;

            case 1 :
                $table_name = !empty($options['include']) && $this->_ActiveRecord->hasAssociations() ? '__owner' : $this->_ActiveRecord->getTableName();

                if (!preg_match('/SELECT .* FROM/is', $conditions)) {
                    $options['conditions'] = $table_name.'.'.$this->_ActiveRecord->getPrimaryKey().' = '.$ids[0].(empty($conditions)?'':' AND '.$conditions);
                } else {
                    if (false!==($pos=stripos($conditions,' WHERE '))) {
                        $before_where = substr($conditions,0, $pos);
                        $after_where = substr($conditions, $pos+7);
                        $options['conditions'] = $before_where.' WHERE ('.$table_name.'.'.$this->_ActiveRecord->getPrimaryKey().' = '.$ids[0].') AND ('.$after_where.')';
                    } else {
                        $options['conditions'].=' WHERE '.$table_name.'.'.$this->_ActiveRecord->getPrimaryKey().' = '.$ids[0];
                    }
                }

                $result = $this->_findEvery($options);
                if (!$expects_array && $result !== false){
                    return $result[0];
                }
                return  $result;
                break;

            default:
                $without_conditions = empty($options['conditions']) ? true : false;
                $ids_condition = $this->_ActiveRecord->getPrimaryKey().' IN ('.join(', ', $this->_ActiveRecord->castAttributesForDatabase($this->_ActiveRecord->getPrimaryKey(), $ids)).')';
                if (!preg_match('/SELECT .* FROM/is', $conditions)) {
                    $options['conditions'] = $ids_condition.(empty($conditions)?'':' AND '.$conditions);
                } else {
                    if (false!==($pos=stripos($conditions,' WHERE '))) {
                        $before_where = substr($conditions,0, $pos);
                        $after_where = substr($conditions, $pos+7);
                        $options['conditions'] = $before_where.' WHERE ('.$ids_condition.') AND ('.$after_where.')';
                    } else {
                        $options['conditions'].=' WHERE '.$ids_condition;
                    }
                }

                $result = $this->_findEvery($options);
                if(is_array($result) && ($num_ids==1 && count($result) != $num_ids && $without_conditions)){
                    $result = false;
                }
                return $result;
                break;
        }
    }

    protected function _quoteColumnName($column_name) {
        return $this->_ActiveRecord->_db->nameQuote.$column_name.$this->_ActiveRecord->_db->nameQuote;
    }



    // FROM ASSOCIATED ACTIVE RECORD

    /**
     * $options['returns'] can be default, array, simulated
     *
     *     default    - default behaviour, instantiating ActiveRecord Objects
     *     array      - returning the result as a big array
     *     simulated  - returning the result as AkActiveRecordMock Objects
     *
     * @param array $options
     * @return array of ActiveRecord Objects
     */
    public function &findWithAssociations($options) {
        $result = false;
        $options ['include'] = Ak::toArray($options ['include']);

        $load_acts = isset($options['load_acts'])?$options['load_acts']:true;

        $config = array('__owner'=>array('class'=>$this->_ActiveRecord->getType(),'pk'=>$this->_ActiveRecord->getPrimaryKey(),'instance'=>$this->_ActiveRecord));

        $returns = isset($options['returns'])?$options['returns']:'default';

        $simulation_class = isset($options['simulation_class']) && class_exists($options['simulation_class'])?$options['simulation_class']:'AkActiveRecordMock';
        if (!in_array($returns,array('default','array','simulated'))) {
            $this->_ActiveRecord->log('option "returns" must be one of default,array,simulated');
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
        $parent_pk = $this->_ActiveRecord->getPrimaryKey();
        $available_associated_options = array ('bind'=> array (),'order' => array (), 'conditions' => array (), 'joins' => array (), 'selection' => array () );
        $replacements = array();
        foreach ( $included_associations as $association_id ) {
            $association_options = empty($included_association_options [$association_id]) ? array () : $included_association_options [$association_id];

            $handler_name = $this->_ActiveRecord->getCollectionHandlerName($association_id);
            $handler_name = empty($handler_name) ? $association_id : (in_array($handler_name, $included_associations) ? $association_id : $handler_name);
            $type =$this->_ActiveRecord->$handler_name->getType();
            $multi = false;

            if (in_array($type,array('hasMany','hasAndBelongsToMany'))) {
                $multi = true;
                $instance = $this->_ActiveRecord->$handler_name->getAssociatedModelInstance();
                $class = $instance->getType();
                $pk_name = $instance->getPrimaryKey();
                $table_name =$instance->getTableName();
            } else {
                $class = $this->_ActiveRecord->$handler_name->getAssociationOption('class_name');
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
            $config['__owner'][$handler_name] = array('class'=>$class,'association_id'=>$association_id,'pk'=>$pk_name,'instance'=>$instance);

            if(isset($association_options['conditions']) && is_array($association_options['conditions'])) {
                $true=true;
                $_conditions = array_shift($association_options['conditions']);
                if(empty($association_options['bind'])) {
                    $association_options['bind'] = array();
                }
                $association_options['bind'] = array_merge($association_options['bind'], $association_options['conditions']);
                $association_options['conditions'] = $_conditions;
            }

            $associated_options = $this->_ActiveRecord->$handler_name->getAssociatedFinderSqlOptionsForInclusionChain('owner[@'.$parent_pk.']','__owner',$association_options,$multi);

            $options ['order'] = empty($options ['order']) ? '' : $this->_ActiveRecord->addTableAliasesToAssociatedSql('__owner', $options ['order']);

            $options ['group'] = empty($options ['group']) ? '' : $this->_ActiveRecord->addTableAliasesToAssociatedSql('__owner', $options ['group']);



            $options ['conditions'] = empty($options ['conditions']) ? '' : $this->_ActiveRecord->addTableAliasesToAssociatedSql('__owner', $options ['conditions']);


            foreach(array_keys($associated_options) as $option) {
                if(isset($associated_options[$option]) && is_string($associated_options[$option]))$associated_options[$option]=trim($associated_options[$option]);
                if(!empty($associated_options[$option])) {
                    if($option=='bind') {
                        $available_associated_options[$option] = array_merge((array)$available_associated_options[$option],(array)$associated_options[$option]);
                    } else {
                        $available_associated_options[$option][]=$associated_options[$option];
                    }
                }
            }
            $replacements['/ ('.$this->_ActiveRecord->getTableName().')\./']=' __owner.';
            $replacements['/^_('.$association_id.')\./']='__owner__'.$handler_name.'.';
            $replacements['/ _('.$association_id.')\./'] = ' __owner__'.$handler_name.'.';
            $replacements['/^_('.$table_name.')\./']='__owner__'.$handler_name.'.';
            $replacements['/ _('.$table_name.')\./'] = ' __owner__'.$handler_name.'.';
            $replacements['/^('.$table_name.')\./']='__owner__'.$handler_name.'.';
            $replacements['/ ('.$table_name.')\./'] = ' __owner__'.$handler_name.'.';


            $this->_prepareIncludes(
            'owner[@'.$parent_pk.']',
            $multi,
            $this->_ActiveRecord,
            $available_associated_options,
            $handler_name,
            $handler_name,
            $association_id,
            $options,
            $association_options,
            $replacements,
            $config['__owner']);

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
                if(!empty($true)) {
                    //echo "<pre>";
                    //var_dump($option);
                    //var_dump($values);
                    //var_dump($associated_options);
                    //die;
                }
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
                    if(!isset($options [$option])) {
                        $options [$option]=array();
                    }
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



    public function &_findBySqlWithAssociations($sql, $virtual_limit = false, $load_acts = true, $returns = 'default', $simulation_class = 'AkActiveRecordMock', $config = array()) {
        $objects = array();
        $results = $this->_ActiveRecord->_db->execute ($sql,'find with associations ext');
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
    public function &_generateObjectGraphFromResultSet($results, $virtual_limit = false, $load_acts=true, $returns = 'default',$simulation_class='AkActiveRecordMock', $config = array()) {
        $return = array();
        $owner = array();
        $keys = array();
        $record_counter=0;
        while ($record = $results->FetchRow()) {
            $record_counter++;
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
                $available_attributes = $this->_ActiveRecord->getAvailableAttributes();
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
            $false = false;
            $return = $this->_generateStdClasses($simulation_class, $owner, $this->_ActiveRecord->getType(), $false, $false, $config);
        }
        return $return;
    }

    public function _reindexArray(&$array) {
        if (is_numeric(key($array))) {
            $array = array_values($array);
        }
        foreach($array as $key => $value) {
            if (is_array($value)) {
                $this->_reindexArray($array[$key]);
            }
        }
    }
    public function &_generateStdClasses($simulation_class,$owner, $class, $handler_name, &$parent, $config = array(), $config_key = '__owner') {
        $return = array();
        $singularize=false;
        $pk = isset($config[$config_key]['pk'])?$config[$config_key]['pk']:'id';
        if (!is_numeric(key($owner))) {
            $singularize =true;

            $key = isset($owner[$pk])?$owner[$pk]:0;
            $owner = array($key=>$owner);
        }
        if(is_array($owner)){
            foreach($owner as $id=>$data) {
                $id = isset($data[$pk])?$data[$pk]:$id;
                $obj = new $simulation_class($id, $class, $handler_name, $parent);
                if(is_array($data)){
                    foreach($data as $key => $value) {
                        if ($key{0}=='_') continue;
                        if ( is_scalar($value)) {
                            $obj->$key = $value;
                        } else if (is_array($value)) {
                            $assoc = isset($config[$config_key][$key]['association_id'])?$config[$config_key][$key]['association_id']:false;
                            if ($assoc) {
                                $obj->$assoc = $this->_generateStdClasses($simulation_class, $value, @$config[$config_key][$key]['class'], $key, $obj, @$config[$config_key], $key);
                                $obj->addAssociated($assoc, $key);
                            }
                        }
                    }
                }
                $return[] = $obj;
            }
        }
        $result = $singularize ? $return[0]: $return;
        return $result;
    }


    public function org_addToOwner(&$owner, $key, $value) {

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
    public function _addToOwner(&$owner, $key, $value, $returns, $config) {

        if(preg_match_all('/\[(.*?)\]/',$key,$matches)) {
            $count = count($matches[1]);
            $last = &$owner;
            for($idx=0;$idx<$count;$idx++) {
                $key = $matches[1][$idx];
                $association = $key;

                if (isset($config[$key])) {
                    $config = $config[$key];
                    if($returns=='array') {
                        $association = $config['association_id'];
                    }
                    //$this->_ActiveRecord->log('using association:'.$association);
                }
                if (!isset($last[$association])) {
                    $last[$association] = array();
                }
                $last = &$last[$association];

            }
            if($returns=='array') {
                $value = $config['instance']->castAttributeFromDatabase($association, $value);
            }
            $last = $value;
        }
        //$this->log('owner:'.var_export($owner,true));
    }

    protected function _prepareIncludes($prefix, $parent_is_plural, &$parent, &$available_associated_options, $handler_name, $parent_association_id, $association_id, &$options, &$association_options, &$replacements, &$config) {
        if (isset($association_options['include'])) {
            $association_options['include'] = Ak::toArray($association_options['include']);
            if (isset($parent->$handler_name) && ($parent->$handler_name instanceof AkBaseModel)) {
                $main_association_class_name = $parent->$handler_name->getModelName();
                $sub_association_object = new $main_association_class_name;
            } else if (isset($parent->$handler_name) && (($parent->$handler_name instanceof AkHasMany) || ($parent->$handler_name instanceof AkHasAndBelongsToMany))){
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

            if ($type == 'hasMany' || $type == 'hasAndBelongsToMany') {
                $instance =& $sub_association_object->$sub_handler_name->getAssociatedModelInstance();
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
            $config[$handler_name][$sub_handler_name] = array('association_id'=>$sub_association_id,'class'=>$class_name,'pk'=>$pk, 'instance'=>$instance);
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
                    $available_associated_options [$sub_associated_option] = array_merge((array)$available_associated_options [$sub_associated_option],Ak::toArray($newoption));
                }

            }
            if (!empty($sub_options)) {
                $this->_prepareIncludes(
                $prefix.'['.$handler_name.']'.($parent_is_plural?'[@'.$pk.']':''),
                $pluralize,
                $instance,
                $available_associated_options,
                $sub_handler_name,
                $parent_association_id.'__'.$sub_handler_name,
                $sub_association_id,
                $options['include'][$association_id],
                $association_options['include'][$idx],
                $replacements,$config[$handler_name]);
            }
        }
    }

    public function _setAssociations($assoc_name, $val, &$parent, $load_acts = true) {
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
            } else if (isset($parent->$assoc_name) && (($parent->$assoc_name instanceof AkHasMany) || ($parent->$assoc_name instanceof AkHasAndBelongsToMany))){
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
                $this->_ActiveRecord->log('Cannot find association:'.$assoc_name.' on '.$parent->getType());
                return;
            }

        } else {
            if (!$parent->$assoc_name) {
                $this->_ActiveRecord->log($parent->getType().'->'.$assoc_name.' does not have assoc');
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

            $available = $instance->castAttributesFromDatabase($available);
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


    /**
     * Used for generating custom selections for habtm, has_many and has_one queries
     */
    public function constructFinderSqlWithAssociations($options, $include_owner_as_selection = true) {
        $sql = 'SELECT ';
        $selection = '';
        $parent_pk = $this->_ActiveRecord->getPrimaryKey();
        $parenthesis = $this->_ActiveRecord->_db->type()=='mysql'?"'":'"';
        if($include_owner_as_selection){
            foreach (array_keys($this->_ActiveRecord->getColumns()) as $column_name){
                $selection .= '__owner.'.$column_name.' AS '.$parenthesis.'owner[@'.$parent_pk.']['.$column_name.']'.$parenthesis.', ';
            }
            $selection .= (isset($options['selection']) ? $options['selection'].' ' : '');
            $selection = trim($selection,', ').' '; // never used by the unit tests
        }else{
            // used only by HasOne::findAssociated
            $selection .= $options['selection'].'.* ';
        }
        $sql .= $selection;
        $sql .= 'FROM '.($include_owner_as_selection ? $this->_ActiveRecord->getTableName().' AS __owner ' : $options['selection'].' ');
        $sql .= (!empty($options['joins']) ? $options['joins'].' ' : '');

        $sql = empty($options['conditions']) ? $sql : $this->sanitizeConditions($sql, $options['conditions'], $include_owner_as_selection?'__owner':null);

        // Create an alias for order
        if(empty($options['order']) && !empty($options['sort'])){
            $options['order'] = $options['sort'];
        }
        $sql  .= !empty($options['group']) ? ' GROUP BY  '.$options['group'] : '';
        $sql  .= !empty($options['order']) ? ' ORDER BY  '.$options['order'] : '';

        $this->_ActiveRecord->_db->addLimitAndOffset($sql,$options);
        return $sql;
    }

    private function _quoteTableName($table_name){
        //return $table_name;
        return $this->_ActiveRecord->_db->quoteTableName($table_name);
    }

    private function _quoteAttributeName($attribute){
        //return $attribute;
        return $this->_ActiveRecord->_db->quoteColumnName($attribute);
    }

}
