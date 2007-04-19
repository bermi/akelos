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
* The Menu Helper makes it easier to create simple menus from controllers actions.
*/
class MenuHelper extends AkActionViewHelper
{

    /**
    * Returns a menu for all or several actions in all or several controllers.
    *
    * Let +menu_options+ defaults and this will generate a menu with all actions in all controllers.
    * Set +menu_options+ to an array with keys as controller name and values as actions names.
    *
    * <?php echo $menu_helper->menu_for_controllers(array('advertiser' => array('buy', 'partial_in_template'))); ?>
    * will generate something like :
    * <div id="menu">
    *  <ul>
    *   <li>
    *    <h2><a href="/advertiser/">Advertiser</a></h2>
    *    <ul>
    *     <li><a href="/advertiser/buy/">Buy</a></li>
    *     <li><a href="/advertiser/partial_in_template/">Partial in template</a></li>
    *    </ul>
    *   </li>
    *  </ul>
    * </div>
    *
    * +div_menu_id+: the id of the main div (default is "menu")
    * +current_class+: the class name of the current controller or the current action (default is "current")
    * +title_tag+: the tag that will contain the controller name link (default is "h2"). If it's empty, it won't be present
    */
    function menu_for_controllers($menu_options = array(), $div_menu_id = 'menu', $current_class = 'current', $title_tag = 'h2')
    {
        $menu_options = empty($menu_options) ? $this->_get_default_full_menu() : $menu_options;
        $menu = '';
        
        foreach ($menu_options as $controller => $actions){
            $controller_name = AkInflector::classify($controller);
            $current_controller_name = $this->_controller->getControllerName();
            $current_action_name = $this->_controller->Request->getAction();
            $controller_class_name = $controller_name.'Controller';
            $controller_human_name = AkInflector::humanize($controller);
            $controller_file_name = AkInflector::toControllerFilename($controller);
            if(file_exists($controller_file_name)){
                include_once($controller_file_name);
                if(class_exists($controller_class_name)){
                    $class = $current_controller_name == $controller_name ? array('class' => $current_class) : array();
                    $href = array('href' => $this->_controller->urlFor(array('controller' => $controller)));
                    if (empty($title_tag)) {
                        $_title_tag = 'a';
                        $content = $controller_human_name;
                        $options = array_merge($class, $href);
                    } else {
                        $content = TagHelper::content_tag('a', $controller_human_name, $href);
                        $options = $class;
                        $_title_tag = $title_tag;
                    }
                    $menu_header = TagHelper::content_tag($_title_tag, $content, $options);
                    $submenu = '';
                    foreach ((array)$actions as $action){
                        if($action[0] == '_'){
                            continue;
                        }
                        $submenu .= TagHelper::content_tag('li', TagHelper::content_tag('a', AkInflector::humanize($action), array('href' => $this->_controller->urlFor(array('controller' => $controller, 'action' => $action)))), $current_controller_name == $controller_name && $current_action_name == $action ? array('class' => $current_class) : array());
                    }
                    $menu .= !empty($submenu) ? TagHelper::content_tag('ul', TagHelper::content_tag('li', $menu_header.TagHelper::content_tag('ul', $submenu))) : '';
                }
            }
        }
        return TagHelper::content_tag('div', $menu, array('id' => $div_menu_id));
    }

    function _get_default_full_menu()
    {
        $controller_file_names = Ak::dir(AK_CONTROLLERS_DIR, array('files'=>false));
        krsort($controller_file_names);
        $menu_options = array();
        foreach ($controller_file_names as $controller_file_name=>$options){
            $controller_file_name = array_pop($options);
            $controller_name = str_replace('.php','',$controller_file_name);
            if(strstr($controller_file_name,'_controller.php') && file_exists(AK_CONTROLLERS_DIR.DS.$controller_file_name)){
                include_once(AK_CONTROLLERS_DIR.DS.$controller_file_name);
                $controller_class_name = AkInflector::classify($controller_name);
                $menu_options[str_replace('_controller','',$controller_name)] = $this->_get_this_class_methods($controller_class_name);
            }
            
        }
        return $menu_options;
    }

    function _get_this_class_methods($class)
    {
        $array1 = get_class_methods($class);
        if($parent_class = get_parent_class($class)){
            $array2 = get_class_methods($parent_class);
            $array3 = array_diff($array1, $array2);
        }else{
            $array3 = $array1;
        }
        
        $array3 = array_map('strtolower',(array)$array3);
        $array3 = array_diff($array3, array(strtolower($class), 'index', 'destroy', 'edit', 'show'));
        return($array3);
    }
}

?>