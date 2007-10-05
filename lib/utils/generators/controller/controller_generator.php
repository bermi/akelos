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
 * @subpackage Generators
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

class ControllerGenerator extends  AkelosGenerator
{
    var $command_values = array('class_name','(array)actions');
    var $scaffold = false;


    function _preloadPaths()
    {
        if(!empty($this->class_name_arg)){
            $this->class_name = $this->class_name_arg;
        }
        
        $this->class_name = $this->controller_name = $this->class_name_arg = str_replace('::', '/', AkInflector::camelize(preg_replace('/_?controller$/i','',$this->class_name)));
        
        $this->module_path = '';

        // Controller inside module
        if(strstr($this->class_name_arg,'/')){
            $module_parts = substr($this->class_name, 0, strrpos($this->class_name_arg, '/'));
            $this->module_path = join(DS, array_map(array('AkInflector','underscore'), strstr($module_parts, '/') ? explode('/', $module_parts) : array($module_parts))).DS;

            $this->controller_name = substr($this->class_name_arg, strrpos($this->class_name_arg, '/') + 1);
            $this->underscored_controller_name = $this->module_path.AkInflector::underscore($this->controller_name);
            $this->controller_path = 'controllers'.DS.$this->underscored_controller_name.'_controller.php';

            $this->class_name = str_replace('/', '_', $this->class_name_arg);
        }else{
            $this->underscored_controller_name = AkInflector::underscore($this->class_name);
            $this->controller_path = 'controllers'.DS.$this->underscored_controller_name.'_controller.php';
        }

        $this->assignVarToTemplate('class_name', $this->class_name);

    }

    function hasCollisions()
    {
        $this->collisions = array();
        $this->_preloadPaths();
        $this->actions = empty($this->actions) ? array() : (array)$this->actions;

        $files = array(
        AK_APP_DIR.DS.$this->controller_path,
        AK_TEST_DIR.DS.'functional'.DS.'app'.DS.$this->controller_path,
        AK_TEST_DIR.DS.'fixtures'.DS.'app'.DS.$this->controller_path,
        AK_TEST_DIR.DS.'fixtures'.DS.'app'.DS.'helpers'.DS.$this->underscored_controller_name."_helper.php",
        AK_HELPERS_DIR.DS.$this->underscored_controller_name."_helper.php"
        );
        
        foreach ($this->actions as $action){
            $files[] = AK_VIEWS_DIR.DS.$this->module_path.AkInflector::underscore($this->controller_name).DS.$action.'.tpl';
        }

        foreach ($files as $file_name){
            if(file_exists($file_name)){
                $this->collisions[] = Ak::t('%file_name file already exists',array('%file_name'=>$file_name));
            }
        }
        return count($this->collisions) > 0;
    }

    function generate()
    {
        $this->_preloadPaths();

        $this->save(AK_APP_DIR.DS.$this->controller_path, $this->render('controller'));
        $this->save(AK_HELPERS_DIR.DS.$this->underscored_controller_name."_helper.php", $this->render('helper'));
        $this->save(AK_TEST_DIR.DS.'functional'.DS.$this->controller_path, $this->render('functional_test'));
        $this->save(AK_TEST_DIR.DS.'fixtures'.DS.'app'.DS.$this->controller_path, $this->render('fixture'));
        $this->save(AK_TEST_DIR.DS.'fixtures'.DS.'app'.DS.'helpers'.DS.$this->underscored_controller_name."_helper.php", $this->render('helper_fixture'));

        @Ak::make_dir(AK_VIEWS_DIR.DS.$this->module_path.AkInflector::underscore($this->controller_name));

        foreach ($this->actions as $action){
            //$this->action = $action;
            $this->assignVarToTemplate('action',$action);
            $this->assignVarToTemplate('path','AK_VIEWS_DIR.DS.\''.$this->module_path.AkInflector::underscore($this->controller_name).'/'.$action.'.tpl\'');
            $this->save(AK_VIEWS_DIR.DS.$this->module_path.AkInflector::underscore($this->controller_name).DS.$action.'.tpl', $this->render('view'));
        }
    }
}

?>
