<?php

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+

/**
 * @package ActiveRecord
 * @subpackage DatabaseAdapters
 * @author Bermi Ferrer <bermi a.t bermilabs c.om>
 * @author Kaste
 * @author Arno Schneider <arno a.t bermilabs c.om>
 * @copyright Copyright (c) 2002-2009, The Akelos Team http://www.akelos.org
 */

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

