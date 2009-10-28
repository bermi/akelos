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

    class AkArrayToCsv
    {
        public function convert()
        {
            $lines = array();
            foreach ($this->source as $v) {
                $lines[] = $this->_arr_to_csv_line($v);
            }
            return implode("\n", $lines);
        }

        private function _arr_to_csv_line($arr) {
            $line = array();
            foreach ($arr as $v) {
                $line[] = is_array($v) ? $this->_arr_to_csv_line($v) : '"' . str_replace('"', '""', $v) . '"';
            }
            return implode(",", $line);
        }
    }

?>
