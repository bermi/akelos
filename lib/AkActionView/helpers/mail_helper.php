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
 * @subpackage Helpers
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

require_once(AK_LIB_DIR.DS.'AkActionView'.DS.'helpers'.DS.'text_helper.php');

class MailHelper
{
    function setController(&$controller)
    {
        $this->_controller =& $controller;
    }

   /**
   * Uses TextHelper::format to take the text and format it, indented two spaces for
   * each line, and wrapped at 72 columns.
   */
    function block_format($text)
    {
        $formatted = '';
        $paragraphs = split("(\n|\r){2,}",$text);
        foreach ((array)$paragraphs as $paragraph){
            $formatted .= TextHelper::format($paragraph, array('columns' => 72, 'first_indent' => 2, 'body_indent' => 2));
        }
        // Make list points stand on their own line
        return preg_replace("/[ ]*([*]+) ([^*]*)/"," $1 $2\n", preg_replace("/[ ]*([#]+) ([^#]*)/"," $1 $2\n",$formatted));
    }
    
}

?>