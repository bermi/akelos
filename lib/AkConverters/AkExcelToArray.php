<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2006, Akelos Media, S.L.  & Bermi Ferrer Martinez |
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
 * @package ActiveSupport
 * @subpackage Converters
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

class AkExcelToArray
{
    function convert()
    {
        $this->handler->read($this->source_file);
        
        $result = array();
        for ($i = 1; $i <= $this->handler->sheets[0]['numRows']; $i++) {
            if($i === 1){
                @$col_names = $this->handler->sheets[0]['cells'][$i-1];
                foreach (range(1, $this->handler->sheets[0]['numCols']) as $column_number){
                    $col_names[$column_number-1] = empty($col_names[$column_number-1]) ? $column_number : trim($col_names[$column_number-1],"\t\n\r ");
                }
                continue;
            }

	        for ($j = 0; $j < $this->handler->sheets[0]['numCols']; $j++) {
                $result[$i-2][$col_names[$j]] = isset($this->handler->sheets[0]['cells'][$i-1][$j]) ? $this->handler->sheets[0]['cells'][$i-1][$j] : null;
	        }
        }
        $this->delete_source_file ? @Ak::file_delete($this->source_file) : null;
        return $result;
    }

    function init()
    {
        if(empty($this->handler)){
            require_once(AK_VENDOR_DIR.DS.'Excel'.DS.'reader.php');
            $this->handler = new Spreadsheet_Excel_Reader();
            $this->handler->setRowColOffset((empty($this->first_column) ? 0 : $this->first_column));
        }
        
        $this->tmp_name = Ak::randomString();
        if(empty($this->source_file)){
            $this->source_file = AK_TMP_DIR.DS.$this->tmp_name.'.xls';
            Ak::file_put_contents($this->source_file,$this->source);
            $this->delete_source_file = true;
            $this->keep_destination_file = empty($this->keep_destination_file) ? (empty($this->destination_file) ? false : true) : $this->keep_destination_file;
        }else{
            $this->delete_source_file = false;
            $this->keep_destination_file = true;
        }
    }
}

?>