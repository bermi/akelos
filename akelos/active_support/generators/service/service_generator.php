<?php


/**
 * @package ActiveSupport
 * @subpackage Generators
 * @author Bermi Ferrer <bermi a.t bermilabs c.om>
 */

class ServiceGenerator extends  AkelosGenerator
{
    public
    $command_values = array('api_name'),
    $api_methods,
    $api_method_doc;

    public function _preloadPaths() {
        $this->api_name = AkInflector::camelize($this->api_name);
        $this->api_class_name = $this->api_name.'Api';

        $this->assignVarToTemplate('api_class_name', $this->api_class_name);

        $this->service_class_name = $this->api_name.'Service';
        $this->assignVarToTemplate('service_class_name', $this->service_class_name);

        $this->api_path = AkConfig::getDir('apis').DS.AkInflector::underscore($this->api_class_name).'.php';

        $this->underscored_service_name = AkInflector::underscore($this->api_name);
        $this->service_path = AkConfig::getDir('models').DS.$this->underscored_service_name.'_service.php';
    }

    public function hasCollisions() {
        $this->_preloadPaths();

        $this->collisions = array();

        $files = array(
        $this->service_path
        );
        foreach ($files as $file_name){
            if(file_exists($file_name)){
                $this->collisions[] = Ak::t('%file_name file already exists',array('%file_name'=>$file_name));
            }
        }
        return count($this->collisions) > 0;
    }

    public function _loadServiceStructureFromApi() {
        require_once($this->api_path);
        $Api = new $this->api_class_name;
        $api_methods = $Api->getApiMethods();
        $methods = array_keys($api_methods);
        foreach ($methods as $method_name){
            $this->api_methods[$method_name] = $this->_getFunctionParamsAsText($api_methods[$method_name]);
            $this->_addDocBlock($api_methods[$method_name]);
        }

        $this->assignVarToTemplate('api_methods', $this->api_methods);
        $this->assignVarToTemplate('api_method_doc', $this->api_method_doc);
    }

    public function _getFunctionParamsAsText($ApiMethod) {
        $params = array();
        foreach ($ApiMethod->expects as $k=>$param){
            $params[] = "\$param_".($k+1)."_as_".$param;
        }
        return join(", ", $params);
    }

    public function _addDocBlock($ApiMethod) {
        $this->api_method_doc[$ApiMethod->name] = !empty($ApiMethod->documentation)? "\n\t* ".$ApiMethod->documentation."\n\t*" : '';
        foreach (array('expects', 'returns') as $expects_or_returns){
            if(!empty($ApiMethod->{$expects_or_returns})){
                //$this->api_method_doc[$ApiMethod->name] .= "\n\t* ".ucfirst($expects_or_returns).":";
                foreach ($ApiMethod->{$expects_or_returns} as $k=>$type){
                    $this->api_method_doc[$ApiMethod->name] .= "\n\t*  ".(
                        $expects_or_returns == 'expects' ?
                        '@param param'.($k+1) : '@return '
                        )." $type";
                    if(!empty($ApiMethod->{$expects_or_returns.'_documentation'}[$k])){
                        $this->api_method_doc[$ApiMethod->name] .= ' '.$ApiMethod->{$expects_or_returns.'_documentation'}[$k];
                    }
                }
                $this->api_method_doc[$ApiMethod->name] .= "\n\t* ";
            }
        }
    }


    public function generate() {
        $this->_preloadPaths();

        $this->_loadServiceStructureFromApi();

        $files = array(
        'service'=> $this->service_path
        );

        foreach ($files as $template=>$file_path){
            $this->save($file_path, $this->render($template));
        }
    }
}
