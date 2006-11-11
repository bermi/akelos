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
class AkDBDesignerToAkelosDatabaseDesign
{
    var $_parser;
    var $_stack = array();
    var $_errors = array();
    var $db_schema = array();
    var $current_table;

    function AkDBDesignerToAkelosDatabaseDesign()
    {
        $this->_parser = xml_parser_create();
        xml_set_object($this->_parser, &$this);
        xml_set_element_handler($this->_parser, 'tagOpen', 'tagClose');
        xml_parser_set_option($this->_parser, XML_OPTION_CASE_FOLDING, false);
    }


    function addError($error)
    {
        $this->_errors[] = $error.' on line '.$this->getCurrentLine();
    }

    function getCurrentLine()
    {
        return xml_get_current_line_number($this->_parser) + $this->_startLine;
    }

    function hasErrors(&$xhtml)
    {
        return count($this->getErrors()) > 0;
    }

    function getErrors()
    {
        return array_unique($this->_errors);
    }

    function showErrors()
    {
        echo '<ul><li>'.join("</li>\n<li>", $this->getErrors()).'</li></ul>';
    }

    function convert()
    {
        if (!xml_parse($this->_parser, $this->source)) {
            $this->addError(Ak::t('DBDesigner file is not well-formed.').' '.xml_error_string(xml_get_error_code($this->_parser)));
        }
        
        foreach ($this->db_schema as $table=>$create_text){
            $this->db_schema[$table] = rtrim($create_text,", \n");
        }
        
        return $this->db_schema;
    }


    function tagOpen($parser, $tag, $attributes)
    {
        if(!empty($attributes['Tablename'])){
            $this->current_table = $attributes['Tablename'];
        }
        if(!empty($attributes['ColName']) && !empty($this->current_table)){
            $this->db_schema[$this->current_table] = empty($this->db_schema[$this->current_table]) ? '' : $this->db_schema[$this->current_table];
            $this->db_schema[$this->current_table] .= 
            $attributes['ColName'].' '.
            $this->getDataType($attributes['idDatatype']).$attributes['DatatypeParams'].
            (empty($attributes['PrimaryKey']) ? '' : ' primary').
            (empty($attributes['NotNull']) ? '' : ' not null').
            (empty($attributes['AutoInc']) ? '' : ' auto increment').
            (empty($attributes['DefaultValue']) ? '' : ' default='.$attributes['DefaultValue']).",\n";
        }
    }

    function getDataType($type)
    {
        (int)$type = $type;
        $dbdesigner_data_types = array(
        1 => 'integer',
        2 => 'integer',
        3 => 'integer',
        4 => 'integer',
        5 => 'integer',
        6 => 'integer',
        7 => 'float',
        8 => 'float',
        9 => 'float',
        10 => 'float',
        11 => 'float',
        12 => 'float',
        13 => 'float',

        14 => 'date',
        15 => 'datetime',
        16 => 'timestamp',
        17 => 'time',
        18 => 'integer',

        19 => 'string',
        20 => 'string',

        21 => 'boolean',
        22 => 'boolean',

        23 => 'binary',
        24 => 'binary',
        25 => 'binary',
        26 => 'binary',

        27 => 'text',
        28 => 'text',
        29 => 'text',
        30 => 'text');
        
        return empty($dbdesigner_data_types[$type]) ? 'string' : $dbdesigner_data_types[$type];

    }

    function tagClose($parser, $tag)
    {
    }
}


?>
