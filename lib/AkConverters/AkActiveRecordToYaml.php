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

class AkActiveRecordToYaml
{
    public function convert()
    {
        $attributes = array();
        if(is_array($this->source)){
            foreach (array_keys($this->source) as $k){
                if($this->_isActiveRecord($this->source[$k])){
                    $attributes[$this->source[$k]->getId()] = $this->source[$k]->getAttributes();
                }
            }
        }elseif ($this->_isActiveRecord($this->source)){
            $attributes[$this->source->getId()] = $this->source->getAttributes();
        }
        require_once(AK_VENDOR_DIR.DS.'TextParsers'.DS.'spyc.php');
        return Spyc::YAMLDump($attributes);
    }

    public function _isActiveRecord(&$Candidate)
    {
        return is_object($Candidate) && method_exists($Candidate, 'getAttributes') && method_exists($Candidate, 'getId');
    }
}

?>
