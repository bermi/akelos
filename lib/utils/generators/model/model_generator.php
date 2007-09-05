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

class ModelGenerator extends  AkelosGenerator
{
    var $command_values = array('class_name');

    function _preloadPaths()
    {
        $this->class_name = AkInflector::camelize($this->class_name);
        $this->assignVarToTemplate('class_name', $this->class_name);
        $this->table_name = AkInflector::tableize($this->class_name);
        $this->underscored_model_name = AkInflector::underscore($this->class_name);
        $this->model_path = 'app'.DS.'models'.DS.$this->underscored_model_name.'.php';
        $this->installer_path = 'app'.DS.'installers'.DS.$this->underscored_model_name.'_installer.php';
    }

    function hasCollisions()
    {
        $this->_preloadPaths();

        $this->collisions = array();

        if(AkInflector::is_plural($this->class_name)){
            $this->collisions[] = Ak::t('%class_name should be a singular noun',array('%class_name'=>$this->class_name));
        }

        $files = array(
        AkInflector::toModelFilename($this->class_name),
        AK_TEST_DIR.DS.'unit'.DS.'app'.DS.'models'.DS.$this->underscored_model_name.'.php',
        AK_TEST_DIR.DS.'fixtures'.DS.$this->model_path,
        AK_TEST_DIR.DS.'fixtures'.DS.$this->installer_path
        );

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

        $this->class_name = AkInflector::camelize($this->class_name);

        $files = array(
        'model'=>AkInflector::toModelFilename($this->class_name),
        'unit_test'=>AK_TEST_DIR.DS.'unit'.DS.'app'.DS.'models'.DS.$this->underscored_model_name.'.php',
        'model_fixture.tpl'=>AK_TEST_DIR.DS.'fixtures'.DS.$this->model_path,
        'installer_fixture.tpl'=>AK_TEST_DIR.DS.'fixtures'.DS.$this->installer_path
        );

        $this->_template_vars = (array)$this;

        foreach ($files as $template=>$file_path){
            $this->save($file_path, $this->render($template));
        }

        $installer_path = AK_APP_DIR.DS.'installers'.DS.$this->underscored_model_name.'_installer.php';
        if(!file_exists($installer_path)){
            $this->save($installer_path, $this->render('installer'));
        }

        $unit_test_runner = AK_TEST_DIR.DS.'unit.php';
        if(!file_exists($unit_test_runner)){
            Ak::file_put_contents($unit_test_runner, file_get_contents(AK_FRAMEWORK_DIR.DS.'test'.DS.'app.php'));
        }

    }

    function cast()
    {
        $this->_template_vars['class_name'] = AkInflector::camelize($this->class_name);
    }
}

?>
