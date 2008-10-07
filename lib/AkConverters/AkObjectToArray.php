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

class AkObjectToArray
{
    function convert()
    {
        return $this->_walkObject($this->source);
    }
    function _convertNumeric($value)
    {
        if (is_string($value) && !ereg('\d',$value{0})) {
            return $value;
        } else if (is_null($value)) {
            return null;
        } else if (($int=intval($value))==$value+0) {
            return $int;
        } else if (($float = floatval($value)) == $value+0.0) {
            return $float;
        } else if (($double = doubleval($value)) == $value+0.0) {
            return $double;
        }
        return $value;
    }
    function _walkObject($obj)
    {
        $return = array();
        foreach($obj as $key=>$value) {
            if (is_object($value)) {
                $return[$key] = $this->_walkObject($value);
            } else {
                $return[$key] = is_numeric($value)?$this->_convertNumeric($value):$value;
            }
        }
        
        return $return;
    }
}

?>
