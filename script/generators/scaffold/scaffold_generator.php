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
ak_define('ACTIVE_RECORD_VALIDATE_TABLE_NAMES', false);

class ScaffoldGenerator extends  AkelosGenerator
{
    var $command_values = array('model_name','controller_name','(array)actions');

    function cast()
    {
        $this->model_name = AkInflector::modulize($this->model_name);
        $this->model_file_path = AkInflector::toModelFilename($this->model_name);
        $this->controller_name = empty($this->controller_name) ? $this->model_name : (AkInflector::camelize($this->controller_name));
        $this->controller_file_path = AkInflector::toControllerFilename($this->controller_name);
        $this->controller_class_name = $this->controller_name.'Controller';
        $this->controller_human_name = AkInflector::humanize($this->controller_name);
        $this->helper_var_name = '$'.AkInflector::underscore($this->controller_name).'_helper';

        $this->singular_name = AkInflector::underscore($this->model_name);
        $this->plural_name = AkInflector::pluralize($this->singular_name);

        $this->files = array(
        'controller.php' => $this->controller_file_path,
        'functional_test.php' => AK_TEST_DIR.DS.'functional'.DS.'test_'.$this->controller_class_name.'.php',
        'helper.php' => AK_HELPERS_DIR.DS.trim($this->helper_var_name,'$').'.php',
        'layout' => AK_VIEWS_DIR.DS.'layouts'.DS.$this->singular_name.'.tpl',
        'style.css' => AK_PUBLIC_DIR.DS.'stylesheets'.DS.'scaffold.css',
        'view_add' => AK_VIEWS_DIR.DS.$this->singular_name.DS.'add.tpl',
        'view_destroy' => AK_VIEWS_DIR.DS.$this->singular_name.DS.'destroy.tpl',
        'view_edit' => AK_VIEWS_DIR.DS.$this->singular_name.DS.'edit.tpl',
        'view_listing' => AK_VIEWS_DIR.DS.$this->singular_name.DS.'listing.tpl',
        'view_show' => AK_VIEWS_DIR.DS.$this->singular_name.DS.'show.tpl',
        'form' => AK_VIEWS_DIR.DS.$this->singular_name.DS.'_form.tpl',
        );

        $this->user_actions = array();
        foreach ((array)@$this->actions as $action){
            $this->user_actions[$action] = AK_VIEWS_DIR.DS.$this->singular_name.DS.$action.'.tpl';
        }
    }

    function hasCollisions()
    {
        $this->collisions = array();
        foreach (array_merge(array_values($this->files),array_values($this->user_actions)) as $file_name){
            if(file_exists($file_name)){
                $this->collisions[] = Ak::t('%file_name file already exists',array('%file_name'=>$file_name));
            }
        }
        return count($this->collisions) > 0;
    }

    function generate()
    {
        //Generate models if they don't exist
        $model_files = array(
        'model'=>$this->model_file_path,
        'model_unit_test'=>AK_TEST_DIR.DS.'unit'.DS.'test_'.$this->singular_name.'.php',
        'model_fixtures.yml'=>AK_TEST_DIR.DS.'fixtures'.DS.$this->plural_name.'.yml'
        );

        foreach ($model_files as $template=>$file_path){
            if(!file_exists($file_path)){
                $this->save($file_path, $this->render($template));
            }
        }

        if(file_exists($this->model_file_path)){
            require_once(AK_APP_DIR.DS.'shared_model.php');
            require_once($this->model_file_path);
            if(class_exists($this->model_name)){
                $ModelInstance =& new $this->model_name;
                $table_name = $ModelInstance->getTableName();
                if(!empty($table_name)){
                    $this->content_columns = $ModelInstance->getContentColumns();
                    unset(
                    $this->content_columns['updated_at'],
                    $this->content_columns['updated_on'],
                    $this->content_columns['created_at'],
                    $this->content_columns['created_on']
                    );
                }
                $internationalized_columns = $ModelInstance->getInternationalizedColumns();
                foreach ($internationalized_columns as $column_name=>$languages){
                    foreach ($languages as $lang){
                        $this->content_columns[$column_name] = $this->content_columns[$lang.'_'.$column_name];
                        $this->content_columns[$column_name]['name'] = $column_name;
                        unset($this->content_columns[$lang.'_'.$column_name]);
                    }
                }


            }
        }

        $this->_template_vars = (array)$this;
        foreach ($this->files as $template=>$file_path){
            $this->save($file_path, $this->render($template));
        }
        foreach ($this->user_actions as $action=>$file_path){
            $this->assignVarToTemplate('action',$action);
            $this->save($file_path, $this->render('view'));
        }



    }
}

?>