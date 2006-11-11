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


class MenuHelper extends AkActionViewHelper
{

    function menu_for_controllers($menu_options = array())
    {
        $menu_options = empty($menu_options) ? $this->_get_default_full_menu() : $menu_options;
        $menu = '';
        foreach ($menu_options as $controller=>$actions){
            $controller_name = AkInflector::classify($controller);
            $current_controller_name = $this->_controller->getControllerName();
            $current_action_name = $this->_controller->Request->getAction();
            $controller_class_name = $controller_name.'Controller';
            $controller_human_name = AkInflector::humanize($controller);
            $controller_file_name = AkInflector::toControllerFilename($controller);

            if(file_exists($controller_file_name)){
                include_once($controller_file_name);
                if(class_exists($controller_class_name)){
                    $menu_header = TagHelper::content_tag('h2',TagHelper::content_tag('a', $controller_human_name, array('href'=>$this->_controller->urlFor(array('controller'=>$controller)))), array('class'=> ($current_controller_name == $controller_name ? 'current' : '')));
                    $submenu = '';
                    foreach ((array)$actions as $action){
                        if($action[0] == '_'){
                            continue;
                        }
                        $submenu .= TagHelper::content_tag('li',TagHelper::content_tag('a', AkInflector::humanize($action), array('href'=>$this->_controller->urlFor(array('controller'=>$controller,'action'=>$action)))), array('class'=> ($current_controller_name == $controller_name && $current_action_name == $action ? 'current' : '')));
                    }
                    $menu .= !empty($submenu) ? TagHelper::content_tag('ul', TagHelper::content_tag('li', $menu_header.TagHelper::content_tag('ul',$submenu))) : '';
                }
            }
        }
        return TagHelper::content_tag('div',$menu, array('id'=>'menu'));
    }

    function _get_default_full_menu()
    {
        $controller_file_names = Ak::dir(AK_CONTROLLERS_DIR, array('files'=>false));
        krsort($controller_file_names);
        $menu_options = array();
        foreach ($controller_file_names as $controller_file_name=>$options){
            $controller_file_name = array_pop($options);
            $controller_name = str_replace('.php','',$controller_file_name);
            
            if(file_exists(AK_CONTROLLERS_DIR.DS.$controller_file_name)){
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