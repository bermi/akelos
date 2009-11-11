<?php

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
 * @package AkelosFramework
 * @subpackage Helpers
 * @author Bermi Ferrer <bermi a.t bermilabs c.om>
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

class MailHelper
{
    public function setController(&$controller)
    {
        $this->_controller = $controller;
    }

   /**
   * Uses TextHelper::format to take the text and format it, indented two spaces for
   * each line, and wrapped at 72 columns.
   */
    public function block_format($text)
    {
        $formatted = '';
        $paragraphs = preg_split("/(\n|\r){2,}/", $text);
        foreach ((array)$paragraphs as $paragraph){
            $formatted .= TextHelper::format($paragraph, array('columns' => 72, 'first_indent' => 2, 'body_indent' => 2));
        }
        // Make list points stand on their own line
        return preg_replace("/[ ]*([*]+) ([^*]*)/"," $1 $2\n", preg_replace("/[ ]*([#]+) ([^#]*)/"," $1 $2\n",$formatted));
    }
}
