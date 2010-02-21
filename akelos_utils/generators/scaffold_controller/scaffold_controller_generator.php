<?php

class ScaffoldControllerGenerator extends AkelosGenerator
{
    public $command_values = array('class_name','(array)table_columns');
    public $module_path = '';

    public function generate() {
        $this->generateFromFilePaths();
        $this->_addRoute('$Map->resources(\'credit_cards\');');
        
    }
    
    public function _addRoute($route){
        $routes_path = AkConfig::getDir('config').DS.'routes.php';
        $routes = file_get_contents($routes_path);
        if(!strstr($routes, trim($route, ';) '))){
            file_put_contents($routes_path, str_replace("<?php\n", "<?php\n\n".$route."\n", $routes));
        }
    }

    public function getFilePaths(){
        $this->_setDefaults();
        $views_path = AkConfig::getDir('views').DS.$this->table_name;

        $files = array(
        $this->controller_file_name => 'controller',
        AkConfig::getDir('views').DS.'layouts'.DS.$this->table_name.'.tpl' => 'layout',
        );

        foreach (array('_form', 'add', 'edit','index', 'show') as $action){
            $files[$views_path.DS.$action.'.html.tpl'] = $action;
        }

        return $files;
    }

    private function _setDefaults() {
        $this->class_name             = AkInflector::camelize($this->class_name);
        $this->table_name             = AkInflector::tableize($this->class_name);
        $this->file_name              = AkInflector::underscore($this->class_name);
        $this->controller_class_name  = AkInflector::pluralize($this->class_name);
        $this->controller_file_name   = AkInflector::toControllerFilename($this->controller_class_name);
        $this->table_columns          = trim(join(' ', (array)@$this->table_columns));

        $this->assignVarToTemplate('attributes',            $this->_getModelAttributesForViews());
        $this->assignVarToTemplate('class_name',            $this->class_name);
        $this->assignVarToTemplate('table_name',            $this->table_name);
        $this->assignVarToTemplate('plural_name',           $this->table_name);
        $this->assignVarToTemplate('singular_name',         AkInflector::singularize($this->table_name));
        $this->assignVarToTemplate('file_name',             $this->file_name);
        $this->assignVarToTemplate('table_columns',         $this->table_columns);
        $this->assignVarToTemplate('controller_class_name', $this->controller_class_name);
    }

    private function _getModelAttributesForViews(){
        $attributes = array();
        $ModelInstance = Ak::get($this->class_name);
        if($ModelInstance instanceof $this->class_name){
            $table_name = $ModelInstance->getTableName();
            if(!empty($table_name)){
                $attributes = $ModelInstance->getContentColumns();
                unset(
                $attributes['updated_at'],
                $attributes['updated_on'],
                $attributes['created_at'],
                $attributes['created_on']
                );
            }
            $internationalized_columns = $ModelInstance->getInternationalizedColumns();
            foreach ($internationalized_columns as $column_name=>$languages){
                foreach ($languages as $lang){
                    $attributes[$column_name] = $attributes[$lang.'_'.$column_name];
                    $attributes[$column_name]['name'] = $column_name;
                    unset($attributes[$lang.'_'.$column_name]);
                }
            }
        }
        
        $helper_methods = array(
        'string'    => 'text_field',
        'text'      => 'text_area',
        'date'      => 'text_field',
        'datetime'  => 'text_field',
        );
        
        foreach ($attributes as $k => $v){
            $attributes[$k]['type'] = $helper_methods[$attributes[$k]['type']];
        }
        
        return $attributes;
    }
}

