<?php

class AkSqliteDbAdapter extends AkDbAdapter
{
    /**
     * @param array $database_settings
     * @return string
     */
    static function constructDsn($database_settings)
    {
        $dsn  = $database_settings['type'].'://';
        $dsn .= urlencode($database_settings['database_file']).'/?persist';
        $dsn .= !empty($database_settings['options']) ? $database_settings['options'] : '';

        return $dsn;
    }

    public function type()
    {
        return 'sqlite';
    }

    /* DATABASE STATEMENTS - CRUD */

    public function incrementsPrimaryKeyAutomatically()
    {
        return false;
    }

    public function getNextSequenceValueFor($table)
    {
        $sequence_table = 'seq_'.$table;
        return $this->connection->GenID($sequence_table);
    }

    /* QUOTING */

    public function quote_string($value)
    {
        return "'".sqlite_escape_string($value)."'";
    }
}

