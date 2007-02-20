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


/**
* This class is for the rare cases where you need to programmatically make tags.
*/
class TagHelper
{
    /**
       * Examples:
       * <tt>$tag_helper->tag('br'); => <br /></tt>
       * <tt>$tag_helper->tag('input', array('type' => 'text')); => <input type="text" /></tt>
       */
    function tag($name, $options = null, $open = false)
    {
        return '<'.$name.(!empty($options) ? TagHelper::_tag_options($options) : '').($open ? '>' : ' />');
    }

    /**
       * Examples:
       * <tt>$tag_helper->content_tag("p", "Hello world!") => <p>Hello world!</p></tt>
       * <tt>$tag_helper->content_tag("div", content_tag("p", "Hello world!"), "class" => "strong") => </tt>
       *<tt><div class="strong"><p>Hello world!</p></div></tt>
       * */
    function content_tag($name, $content, $options = null)
    {
        return '<'.$name.(!empty($options) ? TagHelper::_tag_options($options) : '').'>'.$content.'</'.$name.'>';
    }

    /**
      * Returns a CDATA section for the given +content+.  CDATA sections
      * are used to escape blocks of text containing characters which would
      * otherwise be recognized as markup. CDATA sections begin with the string
      * <tt>&lt;![CDATA[</tt> and } with (and may not contain) the string 
      * <tt>]]></tt>. 
      */
    function cdata_section($content)
    {
        return '<![CDATA['.$content.']]>';
    }

    function _tag_options($options)
    {
        $formated_options = array();
        foreach (array_diff($options,array('')) as $key=>$value){
            if(!is_numeric($key) && !is_array($value) && !is_object($value)){
                $formated_options[$key] =  $key.'="'.htmlentities($value, ENT_COMPAT, Ak::locale('charset')).'"';
            }
        }
        ksort($formated_options);
        return empty($formated_options) ? '' : ' '.join(' ',$formated_options);
    }
}

?>
