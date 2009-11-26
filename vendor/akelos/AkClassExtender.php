<?php


defined('AK_CLASS_EXTENDER_ENABLE_CACHE') ? null : define('AK_CLASS_EXTENDER_ENABLE_CACHE', !AK_DEV_MODE);
/**
 * @ WARNING too experimental. This is a proof of concept. Do not use it for production.
 * 
 * The AkClassExtender provides the means for extending core Akelos Framework
 * functionality by chaining multiple objects and creating an extended 
 * composite class of the original.
 * 
 * It is a good practice maintaining the level of parents low when writing OO
 * code, but PHP classes are closed and do not allow runtime modification so
 * this method will allow others to write core extensions that modify the default
 * Akelos Framework behavior.
 * 
 * This technique requires that the participants of the class chain are not included yet
 * during execution, ass participants code will be joined, modified and cached for
 * including it as a single source file.
 */
class AkClassExtender
{
    var $_extended_classes;

    function extendClassWithSource($class_name_to_extend, $extension_path, $priority = 10)
    {
        $this->_extended_classes[$class_name_to_extend][$priority][] = $extension_path;
    }

    function _getExtensionFilePaths($class_name_to_extend)
    {
        $extension_files = array();
        if(!empty($this->_extended_classes[$class_name_to_extend])){
            $extensions = $this->_extended_classes[$class_name_to_extend];
            ksort($extensions);
            foreach ($extensions as $files){
                $extension_files = array_merge($extension_files, $files);
            }
        }
        return $extension_files;
    }

    function _getExtensionSourceAndChecksum($class_name_to_extend)
    {
        $file_contents = '';
        $file_paths = $this->_getExtensionFilePaths($class_name_to_extend);
        $checksum = md5(serialize($file_paths));
        if(!$this->_canIncludeMergedFile($class_name_to_extend, $checksum)){
            foreach ($file_paths as $file_path){
                if(is_file($file_path)){
                    $file_contents .= Ak::file_get_contents($file_path);
                }
            }
            return array($checksum, $file_contents);
        }else{
            return false;
        }
    }

    function extendClasses()
    {
        foreach (array_keys($this->_extended_classes) as $class_name_to_extend){
            $this->makeClassExtensible($class_name_to_extend);
        }
    }

    function makeClassExtensible($class_name_to_extend)
    {
        list($checksum, $source) = $this->_getExtensionSourceAndChecksum($class_name_to_extend);
        $merge_path = AK_TMP_DIR.DS.'.lib';
        if($source){
            if(preg_match_all('/[ \n\t]*([a-z]+)[ \n\t]*extends[ \n\t]*('.$class_name_to_extend.')[ \n\t]*[ \n\t]*{/i', $source, $matches)){
                $replacements = array();
                $extended_by = array();

                foreach ($matches[2] as $k => $class_to_extend){
                    if(empty($last_method) && class_exists($class_to_extend)){
                        $last_method = $class_to_extend;
                    }
                    if($class_to_extend == $last_method || !empty($extended_by[$class_to_extend]) && in_array($last_method,$extended_by[$class_to_extend])){
                        if(!class_exists($matches[1][$k])){
                            $replacements[trim($matches[0][$k],"\n\t {")] = $matches[1][$k].' extends '.$last_method;
                            $last_method = $matches[1][$k];
                            $extended_by[$class_to_extend][] = $last_method;
                        } else {
                            trigger_error(Ak::t('The class %class is already defined and can\'t be used for extending %parent_class', array('%class' => $matches[1][$k], '%parent_class' => $class_name_to_extend)), E_NOTICE);
                        }
                    }
                }
                $source = str_replace(array_keys($replacements), array_values($replacements), $source);
            }
            $source = "$source<?php class Extensible$class_name_to_extend extends $last_method{} ?>";
            if(md5($source) != @md5_file($merge_path.DS.'Extensible'.$class_name_to_extend.'.php')){
                Ak::file_put_contents($merge_path.DS.'Extensible'.$class_name_to_extend.'.php', $source);
                Ak::file_put_contents($merge_path.DS.'checksums'.DS.'Extensible'.$class_name_to_extend, $checksum);
            }
        }

        include_once($merge_path.DS.'Extensible'.$class_name_to_extend.'.php');
    }

    function _canIncludeMergedFile($class_name_to_extend, $checksum)
    {
        $merge_path = AK_TMP_DIR.DS.'.lib';
        if(AK_CLASS_EXTENDER_ENABLE_CACHE && file_exists($merge_path.DS.'Extensible'.$class_name_to_extend.'.php') &&
        Ak::file_get_contents($merge_path.DS.'checksums'.DS.'Extensible'.$class_name_to_extend) == $checksum){
            return true;
        }
        return false;
    }
}


?>