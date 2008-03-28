<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2006, Akelos Media, S.L.  & Bermi Ferrer Martinez |
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
 * @package ActiveSupport
 * @subpackage Generators
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

defined('AK_GENERATORS_DIR') ? null : define('AK_GENERATORS_DIR', AK_LIB_DIR.DS.'utils'.DS.'generators');

class AkelosGenerator
{
    var $log = array();
    var $type = '';
    var $_template_vars = array();
    var $collisions = array();
    var $generators_dir = AK_GENERATORS_DIR;

    function runCommand($command)
    {
        $commands = $this->getOptionsFromCommand($command);
        $generator_name = AkInflector::underscore(isset($commands['generator']) ? $commands['generator'] : array_shift($commands));

        $available_generators = $this->_getAvailableGenerators();
        $generator_file_name = array_shift(array_keys($available_generators, $generator_name));

        if(empty($generator_file_name)){
            echo "\n   ".Ak::t("You must supply a valid generator as the first command.\n\n   Available generator are:");
            echo "\n\n   ".join("\n   ", $available_generators)."\n\n";
            defined('AK_CONSOLE_MODE') && AK_CONSOLE_MODE ? null : exit;
            return ;
        }

        if(include_once($generator_file_name)){

            $generator_class_name = AkInflector::camelize($generator_name.'_generator');
            $generator = new $generator_class_name();
            $generator->_generator_base_path = dirname($generator_file_name);

            if(count(array_diff($commands,array('help','-help','usage','-usage','h','-h','USAGE','-USAGE'))) != count($commands) || count($commands) == 0){
                if(empty($generator->command_values) && empty($commands)){
                    // generator without commands
                }else{
                    $generator->banner();
                    return;
                }
            }

            $generator->type = $generator_name;
            $generator->_identifyUnnamedCommands($commands);
            $generator->_assignVars($commands);
            $generator->cast();
            $generator->_generate();
        }else {
            echo "\n".Ak::t('Could not find %generator_name generator',array('%generator_name'=>$generator_name))."\n";
        }
    }

    function _assignVars($template_vars)
    {
        foreach ($template_vars as $key=>$value){
            $this->$key = $value;
        }
        $this->_template_vars = (array)$this;
    }

    function assignVarToTemplate($var_name, $value)
    {
        $this->_template_vars[$var_name] = $value;
    }

    function cast()
    {

    }

    function _identifyUnnamedCommands(&$commands)
    {
        $i = 0;
        $extra_commands = array();
        $unnamed_commands = array();
        foreach ($commands as $param=>$value){
            if($value[0] == '-'){
                $next_is_value_for = trim($value,'- ');
                $extra_commands[$next_is_value_for] = true;
                continue;
            }

            if(isset($next_is_value_for)){
                $extra_commands[$next_is_value_for] = trim($value,'- ');
                unset($next_is_value_for);
                continue;
            }

            if(is_numeric($param)){
                if(!empty($this->command_values[$i])){
                    $index =$this->command_values[$i];
                    if(substr($this->command_values[$i],0,7) == '(array)'){
                        $index =substr($this->command_values[$i],7);
                        $unnamed_commands[$index][] = $value;
                        $i--;
                    }else{
                        $unnamed_commands[$index] = $value;
                    }
                }
                $i++;
            }
        }
        $commands = array_merge($extra_commands, $unnamed_commands);
    }

    function render($template, $sintags_version = false)
    {
        $__file_path = $this->generators_dir.DS.$this->type.DS.($sintags_version?'sintags_':'').'templates'.DS.(strstr($template,'.') ? $template : $template.'.tpl');
        if(!file_exists($__file_path)){
            trigger_error(Ak::t('Template file %path not found.', array('%path'=>$__file_path)), E_USER_NOTICE);
        }
        extract($this->_template_vars);
        ob_start();
        include($__file_path);
        $result = ob_get_contents();
        ob_end_clean();

        return $result;
    }

    function save($file_path, $content)
    {
        $this->log[] = $file_path;
        Ak::file_put_contents($file_path, $content);
    }

    function printLog()
    {
        if(!empty($this->log)){
            echo "\n".Ak::t('The following files have been created:')."\n";
            echo join("\n",$this->log)."\n";
        }
        $this->log = array();
    }

    function _generate()
    {
        if(isset($this->_template_vars['force']) || !$this->hasCollisions()){
            $this->generate();
            $this->printLog();
        }else{
            echo "\n".Ak::t('There where collisions when attempting to generate the %type.',array('%type'=>$this->type))."\n";
            echo Ak::t('Please add force=true to the argument list in order to overwrite existing files.')."\n\n";

            echo join("\n",$this->collisions)."\n";
        }
    }

    function hasCollisions()
    {
        return false;
    }

    function getOptionsFromCommand($command)
    {
        $command = $this->_maskAmpersands($command);


        // Named params
        if(preg_match_all('/( ([A-Za-z0-9_-])+=)/',' '.$command,$result)){
            $command = str_replace($result[0],$this->_addAmpersands($result[0]),$command);
            if(preg_match_all('/( [A-Z-a-z0-9_-]+&)+/',' '.$command,$result)){
                $command = str_replace($result[0],$this->_addAmpersands($result[0]),$command);
            }
        }
        $command = join('&',array_diff(explode(' ',$command.' '),array('')));

        parse_str($command,$command_pieces);

        $command_pieces = array_map('stripslashes',$command_pieces);
        $command_pieces = array_map(array(&$this,'_unmaskAmpersands'),$command_pieces);

        $params = array();
        foreach ($command_pieces as $param=>$value){
            if(empty($value)){
                $params[] = $param;
            }else{
                $param = $param[0] == '-' ? substr($param,1) : $param;
                $params[$param] = trim($value,"\"\n\r\t");
            }
        }
        return $params;
    }

    function _addAmpersands($array)
    {
        $ret = array();
        foreach ($array as $arr){
            $ret[] = '&'.trim($arr);
        }
        return $ret;
    }

    function _maskAmpersands($str)
    {
        return str_replace('&','___AMP___',$str);
    }

    function _unmaskAmpersands($str)
    {
        return str_replace('___AMP___','&',$str);
    }

    function manifest($call_generate = true)
    {
        return $call_generate ? $this->generate() : null;
    }
    
    function generate()
    {
        return $this->manifest(false);
    }

    function banner()
    {
        $usage = @file_get_contents(@$this->_generator_base_path.DS.'USAGE');
        echo empty($usage) ? "\n".Ak::t('Could not locate usage file for this generator') : "\n".$usage."\n";
    }

    function _getAvailableGenerators()
    {
        return array_merge($this->_getGeneratorsInsidePath($this->generators_dir), $this->_getPluginGenerators());
    }

    function _getPluginGenerators()
    {
        $generators = array();
        defined('AK_PLUGINS_DIR') ? null : define('AK_PLUGINS_DIR', AK_APP_DIR.DS.'vendor'.DS.'plugins');
        foreach (Ak::dir(AK_PLUGINS_DIR,array('files'=>false,'dirs'=>true)) as $folder){
            $plugin_name = array_shift(array_keys($folder));
            $generators = array_merge($generators, $this->_getGeneratorsInsidePath(AK_PLUGINS_DIR.DS.$plugin_name.DS.'generators'));
        }
        return $generators;
    }

    function _getGeneratorsInsidePath($path)
    {
        $generators = array();
        if(is_dir($path)){
            foreach (Ak::dir($path,array('files'=>false,'dirs'=>true)) as $folder){
                $generator = array_shift(array_keys($folder));
                if(strstr($generator,'.php')){
                    continue;
                }
                $generators[$path.DS.$generator.DS.$generator.'_generator.php'] = $generator;
            }
        }
        return $generators;
    }
}

?>
