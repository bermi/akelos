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
 * Capture lets you extract parts of code into instance variables which
* can be used in other points of the template or even layout file.
*
* == Capturing a block into an instance variable
*
*   <? $capture_helper->begin (); ?>
*     [some html...]
*   <? $script = $capture_helper->end (); ?>
*  
*
* == Add javascript to header using content_for
*
* $capture_helper->content_for("name"); is a wrapper for capture which will store the 
* fragment in a instance variable similar to $content_for_layout.
*
* layout.tpl:
*
*   <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
*   <head>
*	    <title>layout with js</title>
*	    <script type="text/javascript">
*	    {content_for_script}
*   	</script>
*   </head>
*   <body>
*     {content_for_layout}
*   </body>
*   </html>
*
* view.tpl
*   
*   This page shows an alert box!
*
*   <? $capture_helper->begin ('script'); ?>
*     alert('hello world');
*   <? $capture_helper->end (); ?>
*
*   Normal view text
*/
class CaptureHelper
{
    var $_stack = array();
    /**
     * Capture allows you to extract a part of the template into an 
     * instance variable. You can use this instance variable anywhere
     * in your templates and even in your layout. 
     * 
     * Example:
     * 
     *   <? $capture_helper->begin(); ?>
     *     Welcome To my shiny new web page!
     *   <% $greeting = $capture_helper->end(); ?>      
     */
    function begin ($var_name = '')
    {
        ob_start();
        $this->_stack[] = $var_name;
    }

    function end()
    {
        $var_name = array_pop($this->_stack);
        $result = ob_get_clean();
        if(!empty($var_name)){
            $this->_addVarToView('content_for_'.$var_name, $result);
        }
        return $result;
    }

    function _addVarToView($var_name, $content)
    {
        $this->_controller->_viewVars[$var_name] = $content;
    }

    /**
    * Content_for will store the given block
    * in an instance variable for later use in another template
    * or in the layout. 
    * 
    * The name of the instance variable is content_for_<name> 
    * to stay consistent with $content_for_layout which is used 
    * by ActionView's layouts
    * 
    * Example:
    * 
    *   <? $capture_helper->content_for('header'); ?>
    *     alert('hello world');
    *   <? $capture_helper->end(); ?>
    *
    * You can use $content_for_header anywhere in your templates.
    *
    * NOTE: Beware that content_for is ignored in caches. So you shouldn't use it
    * for elements that are going to be fragment cached. 
    */
    function content_for($name)
    {
        $this->begin($name);
    }
}

?>
