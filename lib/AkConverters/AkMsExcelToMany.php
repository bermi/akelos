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
 * @subpackage Converters
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

class AkMsExcelToMany
{
    var $_file_type_codes = array('csv' => 6,'msdos' => 21,'xls'=>-4143,'rtf'=>6,'unicode'=>7,'doc'=>0,'html'=>8,'txt'=>4);

    function convert()
    {
        $excel = new COM('excel.application') or die('Unable to instantiate Excel');
        $excel->Visible = false;
        $excel->WorkBooks->Open($this->source_file);
        $excel->WorkBooks[1]->SaveAs($this->destination_file,$this->_file_type_codes[$this->convert_to]);
        $excel->Quit();
        unset($excel);

        $result = Ak::file_get_contents($this->destination_file);
        $this->delete_source_file ? @Ak::file_delete($this->source_file) : null;
        $this->keep_destination_file ? null : Ak::file_delete($this->destination_file);

        return $result;
    }

    function init()
    {
        $this->ext = empty($this->ext) ? 'xls' : strtolower(trim($this->ext,'.'));
        $this->tmp_name = Ak::randomString();
        if(empty($this->source_file)){
            $this->source_file = AK_CACHE_DIR.DS.$this->tmp_name.'.'.$this->ext;
            Ak::file_put_contents($this->source_file,$this->source);
            $this->delete_source_file = true;
            $this->keep_destination_file = empty($this->keep_destination_file) ? (empty($this->destination_file) ? false : true) : $this->keep_destination_file;
        }else{
            $this->delete_source_file = false;
            $this->keep_destination_file = true;
        }

        $this->convert_to = !empty($this->convert_to) && empty($this->_file_type_codes[$this->convert_to]) ? 'csv' : (empty($this->convert_to) ? 'csv' : $this->convert_to);
        $this->destination_file_name = empty($this->destination_file_name) ? $this->tmp_name.'.'.$this->convert_to : $this->destination_file_name.(strstr($this->destination_file_name,'.') ? '' : '.'.$this->convert_to);
        $this->destination_file = empty($this->destination_file) ? AK_CACHE_DIR.DS.$this->destination_file_name : $this->destination_file;
    }
}

?>