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
 * @subpackage Base
 * @component DbAdapter PostgreSQL
 * @author Bermi Ferrer <bermi a.t akelos c.om> 2004 - 2007
 * @author Kaste 2007
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

class AkPgsqlDbAdapter extends AkDbAdapter
{
    
    
    function type()
    {
        return 'postgre';
    }
    
    /* SCHEMA */
    
    function renameColumn($table_name,$column_name,$new_name)
    {
        return $this->execute("ALTER TABLE $table_name RENAME COLUMN $column_name TO $new_name");
    }
    
    /* META */
    
    function availableTables()
    {
        $schema_path = $this->selectValue('SHOW search_path');
        $schemas = "'".join("', '",split(',',$schema_path))."'";
        return $this->selectValues("SELECT tablename FROM pg_tables WHERE schemaname IN ($schemas)");
    }

    /* QUOTING */ 
    
    function quote_string($value)
    {
        if (AK_PHP5) {
            return "'".pg_escape_string($this->connection->_connectionID,$value)."'";
        } else
            return "'".pg_escape_string($value)."'";
    }
    
}
?>