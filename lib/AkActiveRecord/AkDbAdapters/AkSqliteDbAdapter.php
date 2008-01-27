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
 * @component DbAdapter SQLite
 * @author Bermi Ferrer <bermi a.t akelos c.om> 2004 - 2007
 * @author Kaste 2007
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

class AkSqliteDbAdapter extends AkDbAdapter
{
    
    /**
     * @param array $database_settings
     * @return string
     */
    function _constructDsn($database_settings)
    {
        $dsn  = $database_settings['type'].'://';
        $dsn .= urlencode($database_settings['database_file']).'/?persist';
        $dsn .= !empty($database_settings['options']) ? $database_settings['options'] : '';
        return $dsn;
    }
    
    function type()
    {
        return 'sqlite';
    }

    /* DATABASE STATEMENTS - CRUD */
    
    function incrementsPrimaryKeyAutomatically(){
        return false;
    }
    
    function getNextSequenceValueFor($table){
        $sequence_table = 'seq_'.$table;
        return $this->connection->GenID($sequence_table);
    }
    
    /* QUOTING */ 
    
    function quote_string($value)
    {
        return "'".sqlite_escape_string($value)."'";
    }
    
    
}
?>