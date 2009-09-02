<?php

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
 * @package ActiveRecord
 * @subpackage Base
 * @component DbAdapter SQLite
 * @author Bermi Ferrer <bermi a.t bermilabs c.om>
 * @author Kaste
 * @author Arno Schneider <arno a.t bermilabs c.om>
 * @copyright Copyright (c) 2002-2009, The Akelos Team http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

class AkSqliteDbAdapter extends AkDbAdapter
{

    /**
     * @param array $database_settings
     * @return string
     */
    public function _constructDsn($database_settings)
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

?>