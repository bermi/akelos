<?php

class AkMongoDbAdapter extends AkDbAdapter
{
    public function _constructDsn($database_settings)
    {
        print_r($database_settings);
        
        die();
        $dsn  = 'mysqlt://';
        $dsn .= $database_settings['user'].':'.$database_settings['password'];
        $dsn .= !empty($database_settings['host']) ? '@'.$database_settings['host'] : '@localhost';
        $dsn .= !empty($database_settings['port']) ? ':'.$database_settings['port'] : '';
        $dsn .= '/'.$database_settings['database_name'];
        if (empty($database_settings['options']) && !empty($database_settings['socket'])) $database_settings['options'] = 'socket='.urlencode($database_settings['socket']);
        $dsn .= !empty($database_settings['options']) ? '?'.$database_settings['options'] : '';
        return $dsn;
    }

    public function getDictionary()
    {

    }

    public function type()
    {
        return 'mongo';
    }

    public function addLimitAndOffset(&$sql, $options)
    {
    }

    public function renameColumn($table_name,$column_name,$new_name)
    {
    }

    public function connect($die_on_error=true)
    {
    }


    public function execute($sql, $message = 'SQL')
    {
        if (is_array($sql)) {
            $sql_string = array_shift($sql);
            $bindings = $sql;
        } else {
            $sql_string = $sql;
        }

        if(isset($bindings)){

        }else{

        }
        return false;
    }

    public function incrementsPrimaryKeyAutomatically()
    {
        return true;
    }

    public function getLastInsertedId($table,$pk)
    {
        return false;
    }

    public function getAffectedRows()
    {
        return 0;
    }

    public function insert($sql,$id=null,$pk=null,$table=null,$message = '')
    {
        $result = $this->execute($sql,$message);
        if (!$result){
            return false;
        }
        return is_null($id) ? $this->getLastInsertedId($table,$pk) : $id;
    }

    public function update($sql,$message = '')
    {
        $result = $this->execute($sql,$message);
        return ($result) ? $this->getAffectedRows() : false;
    }

    public function delete($sql, $message = '')
    {
        $result = $this->execute($sql,$message);
        return ($result) ? $this->getAffectedRows() : false;
    }

    public function selectValue($sql)
    {
        $result = $this->selectOne($sql);
        return !is_null($result) ? array_shift($result) : null;
    }

    public function selectValues($sql)
    {
        $values = array();
        if($results = $this->select($sql)){
            foreach ($results as $result){
                $values[] = array_shift($result);
            }
        }
        return $values;
    }

    /**
     * Returns a record array of the first row with the column names as keys and column values
     * as values.
     */
    public function selectOne($sql)
    {
        $result = $this->select($sql);
        return  !is_null($result) ? array_shift($result) : null;
    }

    /**
     * alias for select
     */
    public function selectAll($sql)
    {
        return $this->select($sql);
    }

    public function select($sql, $message = '')
    {
        return array();
    }

    public function startTransaction()
    {
        return true;
    }

    public function stopTransaction()
    {
        return true;
    }

    public function failTransaction()
    {
        return false;
    }

    public function hasTransactionFailed()
    {
        return false;
    }

    public function availableTables($force_lookup = false)
    {
        return array();
    }

    public function tableExists($table_name)
    {
        return true;
    }

    public function getColumnDetails($table_name)
    {
        return array();
    }

    public function getIndexes($table_name)
    {
        return array();
    }

    public function quote_string($value)
    {
        return $value;
    }

    public function quote_datetime($value)
    {
        return $value;
    }

    public function quote_date($value)
    {
        return $value;
    }

    public function escape_blob($value)
    {
        return $value;
    }

    public function unescape_blob($value)
    {
        return $value;
    }
}

?>