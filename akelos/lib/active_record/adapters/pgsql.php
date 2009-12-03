<?php

class AkPgsqlDbAdapter extends AkDbAdapter
{

    public function type()
    {
        return 'postgre';
    }

    /* SCHEMA */

    public function renameColumn($table_name,$column_name,$new_name)
    {
        return $this->execute("ALTER TABLE $table_name RENAME COLUMN $column_name TO $new_name");
    }

    /* META */

    public function getAvailableTables($force_lookup = false)
    {
        $schema_path = $this->selectValue('SHOW search_path');
        $schemas = "'".join("', '", explode(',',$schema_path))."'";
        return $this->selectValues("SELECT tablename FROM pg_tables WHERE schemaname IN ($schemas)");
    }

    /* QUOTING */

    public function quote_string($value)
    {
        return "'".pg_escape_string($this->connection->_connectionID,$value)."'";
    }
}

