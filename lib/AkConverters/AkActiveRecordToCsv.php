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
    * @author Pau Ramon <masylum a.t gmail c.om>
    * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
    * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
    */

    class AkActiveRecordToCsv
    {
        public function convert()
        {
            $attributes = $this->_setColumnNames();

            if(is_array($this->source)){
                foreach ($this->source as $item){
                    if($this->_isActiveRecord($item)){
                        array_push($attributes, $item->getAttributes());
                    }
                }
            }elseif ($this->_isActiveRecord($this->source)){
                array_push($attributes, $this->source->getAttributes());
            }

            return self::arr_to_csv($attributes);
        }

        public function _isActiveRecord(&$Candidate)
        {
            return is_object($Candidate) && method_exists($Candidate, 'getAttributes') && method_exists($Candidate, 'getId');
        }

        private function _setColumnNames(){
            if(is_array($this->source) && $this->_isActiveRecord($this->source[0])){
                return array($this->source[0]->getColumnNames());
            }elseif ($this->_isActiveRecord($this->source)){
                return array($this->source->getColumnNames());
            }
        }

        static public function arr_to_csv_line($arr) {
            $line = array();
            foreach ($arr as $v) {
                $line[] = is_array($v) ? self::arr_to_csv_line($v) : '"' . str_replace('"', '""', $v) . '"';
            }
            return implode(",", $line);
        }

        static public function arr_to_csv($arr) {
            $lines = array();
            foreach ($arr as $v) {
                $lines[] = self::arr_to_csv_line($v);
            }
            return implode("\n", $lines);
        }
    }

?>
