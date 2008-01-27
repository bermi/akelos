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
 * @component DbAdapter MySQL
 * @author Bermi Ferrer <bermi a.t akelos c.om> 2004 - 2007
 * @author Kaste 2007
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

class AkMysqlDbAdapter extends AkDbAdapter
{
    
    /**
     * @param array $database_settings
     * @return string
     */
    function _constructDsn($database_settings)
    {
        $dsn  = 'mysqlt://';
        $dsn .= $database_settings['user'].':'.$database_settings['password'];
        $dsn .= !empty($database_settings['host']) ? '@'.$database_settings['host'] : '@localhost';
        $dsn .= !empty($database_settings['port']) ? ':'.$database_settings['port'] : '';
        $dsn .= '/'.$database_settings['database_name'];
        if (empty($database_settings['options']) && !empty($database_settings['socket'])) $database_settings['options'] = 'socket='.urlencode($database_settings['socket']); 
        $dsn .= !empty($database_settings['options']) ? '?'.$database_settings['options'] : '';
        return $dsn;
    }
    
    function type()
    {
        return 'mysql';
    }

    function addLimitAndOffset(&$sql,$options)
    {
        if (isset($options['limit']) && $limit = $options['limit']){
            if (isset($options['offset']) && $offset = $options['offset'])
                $sql .= " LIMIT $offset, $limit";
            else
                $sql .= " LIMIT $limit";
        }
        return $sql;
    }
    
    /* SCHEMA */
    
    function renameColumn($table_name,$column_name,$new_name)
    {
        $column_details = $this->selectOne("SHOW COLUMNS FROM $table_name LIKE '$column_name'");
        if (!$column_details) {
            trigger_error(Ak::t("No such column '%column' in %table_name",array('%column'=>$column_name,'%table_name'=>$table_name)), E_USER_ERROR);
            return false;
        }
        $column_type_definition = $column_details['Type'];
        if ($column_details['Null']!=='YES') $column_type_definition .= ' not null';
        if (!empty($column_details['Default'])) $column_type_definition .= " default '".$column_details['Default']."'";
        return $this->execute("ALTER TABLE $table_name CHANGE COLUMN $column_name $new_name $column_type_definition");
    }
    
    function availableTables()
    {
        return $this->selectValues('SHOW TABLES');
    }
    
    /* QUOTING */ 
    
    function quote_string($value)
    {
        return "'".mysql_real_escape_string($value,$this->connection->_connectionID)."'";
    }
    
}
?>