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

class CloneGenerator extends  AkelosGenerator
{
    var $command_values = array('class_to_clone','class_name');

    function _setupCloner()
    {
        $this->clone_setup_done = true;
        $this->class_to_clone = AkInflector::underscore($this->class_to_clone);
        $this->class_name = AkInflector::underscore($this->class_name);

        $this->clone_replacements = array(
        
        AkInflector::camelize($this->class_to_clone).'Controller' => AkInflector::camelize($this->class_name).'Controller',
        
        AkInflector::humanize(AkInflector::pluralize($this->class_to_clone)) => AkInflector::humanize(AkInflector::pluralize($this->class_name)),
        AkInflector::titleize(AkInflector::pluralize($this->class_to_clone)) => AkInflector::titleize(AkInflector::pluralize($this->class_name)),

        AkInflector::humanize($this->class_to_clone) => AkInflector::humanize($this->class_name),
        AkInflector::titleize($this->class_to_clone) => AkInflector::titleize($this->class_name),
        
        AkInflector::camelize(AkInflector::pluralize($this->class_to_clone)) => AkInflector::camelize(AkInflector::pluralize($this->class_name)),
        AkInflector::pluralize($this->class_to_clone) => AkInflector::pluralize($this->class_name),
        
        AkInflector::camelize($this->class_to_clone) => AkInflector::camelize($this->class_name),
        $this->class_to_clone => $this->class_name,
        
        );


        //AK_VIEWS_DIR.DS.AkInflector::underscore($this->class_name).DS.$action.'.tpl'

        $this->files_to_clone = array(
        AkInflector::toModelFilename($this->class_to_clone) => AkInflector::toModelFilename($this->class_name),
        AK_TEST_DIR.DS.'unit'.DS.'test_'.$this->class_to_clone.'.php' => AK_TEST_DIR.DS.'unit'.DS.'test_'.$this->class_name.'.php',
        AK_TEST_DIR.DS.'fixtures'.DS.AkInflector::tableize($this->class_to_clone).'.yml' => AK_TEST_DIR.DS.'fixtures'.DS.AkInflector::tableize($this->class_name).'.yml',
        AkInflector::toControllerFilename($this->class_to_clone) => AkInflector::toControllerFilename($this->class_name),
        AK_TEST_DIR.DS.'functional'.DS.'test_'.AkInflector::camelize($this->class_to_clone.'_controller').'.php' => AK_TEST_DIR.DS.'functional'.DS.'test_'.AkInflector::camelize($this->class_name.'_controller').'.php',
        AK_HELPERS_DIR.DS.AkInflector::underscore("{$this->class_to_clone}_helper").'.php' => AK_HELPERS_DIR.DS.AkInflector::underscore("{$this->class_name}_helper").'.php'
        );

        foreach ($this->_getControllerViews() as $view_file){
            $this->files_to_clone[AK_VIEWS_DIR.DS.$this->class_to_clone.DS.$view_file.'.tpl'] = AK_VIEWS_DIR.DS.$this->class_name.DS.$view_file.'.tpl';
        }

        $this->files_to_clone[AK_VIEWS_DIR.DS.'layouts'.DS.$this->class_to_clone.'.tpl'] = AK_VIEWS_DIR.DS.'layouts'.DS.$this->class_name.'.tpl';
        
        foreach (Ak::dir(AK_APP_DIR.DS.'locales'.DS.$this->class_to_clone, array('dirs'=>false)) as $locale_file) {
            $this->files_to_clone[AK_APP_DIR.DS.'locales'.DS.$this->class_to_clone.DS.$locale_file] = AK_APP_DIR.DS.'locales'.DS.$this->class_name.DS.$locale_file;	
        }
    }

    function _getControllerViews()
    {
        $view_files = Ak::dir(AK_VIEWS_DIR.DS.$this->class_to_clone, array('dirs'=>false));
        foreach ($view_files as $k=>$view_file){
            $view_files[$k] = str_replace('.tpl','',$view_file);
        }
        return $view_files;
    }

    function hasCollisions()
    {
        $this->_setupCloner();
        
        $this->collisions = array();
        foreach ($this->files_to_clone as $origin=>$destination){
            if(file_exists($destination)){
                $this->collisions[] = Ak::t('%file_name file already exists',array('%file_name'=>$destination));
            }
        }
        return count($this->collisions) > 0;
    }

    function generate()
    {
        if (empty($this->clone_setup_done)) {
            $this->_setupCloner();	
        }
        foreach ($this->files_to_clone as $origin=>$destination){
            if(file_exists($origin)){
                $origin_code = Ak::file_get_contents($origin);
                $destination_code = str_replace(array_keys($this->clone_replacements), array_values($this->clone_replacements), $origin_code);
                $this->save($destination, $destination_code);
            }
        }
    }
}

?>
