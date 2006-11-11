<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2006, Akelos Media, S.L.  & Bermi Ferrer Martinez |
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
 * @package AkelosFramework
 * @subpackage Administration
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

require_once(AK_LIB_DIR.DS.'Ak.php');

class AkDbManager
{
    var $_db;

    function AkDbManager($db_connection = null)
    {
        if(empty($db_connection)){
            $this->_db =& Ak::db();
        }else {
            $this->_db =& $db_connection;
        }
    }

    function createTable($table_name, $table_fields, $table_options, $add_sequence_table = true, $table_index_fields = null)
    {
        if(!isset($this->_db)){
            $db =& Ak::db();
        }else {
            $db =& $this->_db;
        }
        
        $dict = NewDataDictionary($db);
        $sqlarray = $dict->CreateTableSQL($table_name, $table_fields, $table_options);
        $dict->ExecuteSQLArray($sqlarray);
        if(isset($table_index_fields)){
            $sqlarray = $dict->CreateIndexSQL('idx_'.$table_name, $table_name, $table_index_fields);
            $dict->ExecuteSQLArray($sqlarray);
        }
        if($add_sequence_table){
            $db->CreateSequence('seq_'.$table_name);
        }
    }
}




class AkDbSchema
{
    function loadFromDatabase(){}
    function loadFromDatabaseTable(){}
    function loadTableFromXml(){}
    function loadDatabaseFromXml(){}

    function updateDatabase(){}
    function updateDatabaseTable(){}

    function createDatabase(){}
    function createDatabaseTable(){}

    function saveTableSchemaAsXml(){}
    function saveDatabaseSchemaAsXml(){}
}

?>
