<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

class AkRouterHelper
{
    private static $defined_functions = array();

    static function getDefinedFunctions() {
        return self::$defined_functions;    
    }

    static function generateHelperFunctionsFor($name,AkRoute $Route) {
        $names_array_as_string = var_export($Route->getNamesOfDynamicSegments(),true);
        $names_array_as_string = str_replace(array("\n","  "),'',$names_array_as_string);
        
        self::generateFunction($name,'url',$names_array_as_string,'',str_replace(array("\n","  "),'',var_export($Route->getDefaults(), true)));
        self::generateFunction($name,'path',$names_array_as_string,"'only_path'=>true", str_replace(array("\n","  "),'',var_export($Route->getDefaults(), true)));
    }

    /**
    * @todo Investigate if its possible to cache generated functions based on the mtime of the routes file.
    */
    private static function generateFunction($route_name,$function_suffix,$excluded_params_as_string,$additional_parameters='',$default_parameters = '') {
        $function_name = $route_name.'_'.$function_suffix;
        $parameters_function_name = $route_name.'_params';
        if (function_exists($function_name)) return;

        $additional_parameters ? $additional_parameters .= ',' : null;
        
        $code = <<<BANNER
function $function_name(\$params=array())
{
    \$url_writer = AkUrlWriter::getInstance();
    \$my_params = array(
        'use_named_route'=>'$route_name',
        $additional_parameters
        'skip_old_parameters_except'=>$excluded_params_as_string
    );
    \$params = array_merge(\$my_params,\$params);
    return \$url_writer->rewrite(\$params);    
}

BANNER;
        //echo $code;
        eval($code);
        self::$defined_functions[] = $function_name;
        
        if (function_exists($parameters_function_name)) return $code;
        
        $parameters_code = <<<BANNER
function $parameters_function_name(\$params=array())
{
    return array_merge($default_parameters,\$params);
}

BANNER;

        eval($parameters_code);
        self::$defined_functions[] = $parameters_function_name;
                
        return $code;
    }
}

