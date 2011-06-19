<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

class AkFileSystem
{
    static function dir($path, $options = array()) {
        $result = array();

        $path = rtrim($path, '/\\');
        $default_options = array(
        'files' => true,
        'dirs' => true,
        'recurse' => false,
        );

        $options = array_merge($default_options, $options);

        if(is_file($path)){
            $result = array($path);
        }elseif(is_dir($path)){
            if ($id_dir = opendir($path)){
                while (false !== ($file = readdir($id_dir))){
                    if ($file != "." && $file != ".." && $file != '.svn' && $file != '.git'){
                        if(!empty($options['files']) && !is_dir($path.DS.$file)){
                            $result[] = $file;
                        }elseif(!empty($options['dirs'])){
                            $result[][$file] = !empty($options['recurse']) ? self::dir($path.DS.$file, $options) : $file;
                        }
                    }
                }
                closedir($id_dir);
            }
        }

        return array_reverse($result);
    }


    static function file_put_contents($file_name, $content, $options = array()) {
        $default_options = array(
        'ftp' => defined('AK_UPLOAD_FILES_USING_FTP') && AK_UPLOAD_FILES_USING_FTP,
        'base_path' => self::getDefaultBasePath($file_name)
        );
        $options = array_merge($default_options, $options);

        $file_name = self::getRestrictedPath($file_name, $options);

        if($options['ftp']){
            if(!AkFtp::is_dir(dirname($file_name))){
                AkFtp::make_dir(dirname($file_name));
            }
            return AkFtp::put_contents($file_name, $content);
        }else{
            $base_path = self::getNormalizedBasePath($options);
            if(!is_dir(dirname($base_path.$file_name))){
                self::make_dir(dirname($base_path.$file_name), $options);
            }

            if(!$result = file_put_contents($base_path.$file_name, $content)){
                if(!empty($content)){
                    trigger_error(Ak::t("Could not write to file: %file_name. Please change file/dir permissions or enable FTP file handling on your Akelos application.", array('%file_name' => '"'.$base_path.$file_name.'"')),  E_USER_ERROR);
                }
            }
            return $result;
        }
    }


    static function file_get_contents($file_name, $options = array()) {
        $default_options = array(
        'ftp' => defined('AK_READ_FILES_USING_FTP') && AK_READ_FILES_USING_FTP,
        'base_path' => self::getDefaultBasePath($file_name)
        );
        $options = array_merge($default_options, $options);

        $file_name = self::getRestrictedPath($file_name, $options);

        if($options['ftp']){
            return AkFtp::get_contents($file_name);
        }else{
            $base_path = self::getNormalizedBasePath($options);
            if(!file_exists($base_path.$file_name)){
                if(empty($options['skip_raising_errors'])){
                    throw new Exception('File '.$base_path.$file_name.' not found.');
                }
                return;
            }
            return file_get_contents($base_path.$file_name);
        }
    }

    /**
     * @todo Optimize this code (dirty add-on to log command line interpreter results)
     */
    static function file_add_contents($file_name, $content, $options = array()) {
        $original_content = self::file_get_contents($file_name, array_merge($options, array('skip_raising_errors'=>true)));
        return self::file_put_contents($file_name, $original_content.$content, $options);
    }

    static function file_delete($file_name, $options = array()) {
        $default_options = array(
        'ftp' => defined('AK_DELETE_FILES_USING_FTP') && AK_DELETE_FILES_USING_FTP,
        'base_path' => self::getDefaultBasePath($file_name)
        );
        $options = array_merge($default_options, $options);

        $file_name = self::getRestrictedPath($file_name, $options);
        $base_path = self::getNormalizedBasePath($options);

        if($options['ftp']){
            return AkFtp::delete($file_name, true);
        }elseif (file_exists($base_path.$file_name)){
            return unlink($base_path.$file_name);
        }
        return false;
    }

    static function directory_delete($dir_name, $options = array()) {
        $default_options = array(
        'ftp' => defined('AK_DELETE_FILES_USING_FTP') && AK_DELETE_FILES_USING_FTP,
        'base_path' => self::getDefaultBasePath($dir_name)
        );
        $options = array_merge($default_options, $options);

        $sucess = true;
        $dir_name = self::getRestrictedPath($dir_name, $options);

        if(empty($dir_name)){
            return false;
        }

        if($options['ftp']){
            return AkFtp::delete($dir_name);
        }else{
            $base_path = self::getNormalizedBasePath($options);
            $items = glob($base_path.$dir_name."/*");
            $hidden_items = glob($base_path.$dir_name."/.*");
            $fs_items = $items || $hidden_items ? array_merge((array)$items, (array)$hidden_items) : false;
            if($fs_items){
                $items_to_delete = array('directories'=>array(), 'files'=>array());
                foreach($fs_items as $fs_item) {
                    if($fs_item[strlen($fs_item)-1] != '.'){
                        $items_to_delete[ (is_dir($fs_item) ? 'directories' : 'files') ][] = $fs_item;
                    }
                }
                foreach ($items_to_delete['files'] as $file){
                    self::file_delete($file, $options);
                }
                foreach ($items_to_delete['directories'] as $directory){
                    $sucess = $sucess ? self::directory_delete($directory, $options) : $sucess;
                }
            }
            return $sucess ? (is_dir($base_path.$dir_name) ? rmdir($base_path.$dir_name) : $sucess) : $sucess;
        }
    }

    static function make_dir($path, $options = array()) {

        $default_options = array(
        'ftp' => defined('AK_UPLOAD_FILES_USING_FTP') && AK_UPLOAD_FILES_USING_FTP,
        'base_path' => AkConfig::getDir('base')
        );

        $options = array_merge($default_options, $options);

        if(!is_dir($options['base_path']) && !self::make_dir($options['base_path'], array('base_path' => dirname($options['base_path'])))){
            trigger_error(Ak::t('Base path %path must exist in order to use it as base_path in self::make_dir()', array('%path' => $options['base_path'])), E_USER_ERROR);
        }

        $path = self::getRestrictedPath($path, $options);

        if($options['ftp']){
            return AkFtp::make_dir($path);
        }else{
            $base_path = self::getNormalizedBasePath($options);
            $path = rtrim($base_path.$path, DS);

            if (!file_exists($path)){
                self::make_dir(dirname($path), $options);
                return mkdir($path);
            }else{
                return true;
            }
        }
        return false;
    }

    static function rmdir_tree($directory) {
        $files = glob($directory.'*', GLOB_MARK);
        foreach($files as $file){
            if(substr($file, -1) == DS){
                self::rmdir_tree($file);
            } else{
                unlink($file);
            }
        }
        if (is_dir($directory)){
            rmdir($directory);
        }
    }

    /**
    * This static method will copy recursively all the files or directories from one
    * path within an Akelos application to another.
    *
    * It uses current installation settings, so it can perform copies via the filesystem or via FTP
    */
    static function copy($origin, $target, $options = array()) {
        $default_options = array(
        'ftp' => defined('AK_UPLOAD_FILES_USING_FTP') && AK_UPLOAD_FILES_USING_FTP,
        'base_path' => self::getDefaultBasePath($origin)
        );
        $options = array_merge($default_options, $options);

        $sucess = true;

        $origin = self::getRestrictedPath($origin, $options);
        $target = self::getRestrictedPath($target, $options);

        if(empty($origin) || empty($target)){
            return false;
        }

        $destination = str_replace($origin, $target, $origin);
        $base_path = self::getNormalizedBasePath($options);
        if(is_file($base_path.$origin)){
            return self::file_put_contents($base_path.$destination, self::file_get_contents($base_path.$origin, $options), $options);
        }
        self::make_dir($base_path.$destination, $options);
        if($fs_items = glob($base_path.$origin."/*")){
            $items_to_copy = array('directories'=>array(), 'files'=>array());
            foreach($fs_items as $fs_item) {
                $items_to_copy[ (is_dir($fs_item) ? 'directories' : 'files') ][] = $fs_item;
            }
            foreach ($items_to_copy['files'] as $file){
                $destination = str_replace($origin, $target, $file);
                $sucess = $sucess ? self::file_put_contents($destination, self::file_get_contents($file, $options), $options) : $sucess;
            }
            foreach ($items_to_copy['directories'] as $directory){
                $destination = str_replace($origin, $target, $directory);
                $sucess = $sucess ? self::copy($directory, $destination, $options) : $sucess;
            }
        }
        return $sucess;
    }

    static function move($origin, $target, $options = array())
    {
        $default_options = array(
         'ftp' => defined('AK_UPLOAD_FILES_USING_FTP') && AK_UPLOAD_FILES_USING_FTP,
         'base_path' => self::getDefaultBasePath($origin)
        );
        $options = array_merge($default_options, $options);

        if(empty($options['skip_restricting_origin'])){
            $origin = self::getRestrictedPath($origin, $options);
        }
        if(empty($options['skip_restricting_target'])){
            $target = self::getRestrictedPath($target, $options);
        }

        if(empty($origin) || empty($target)){
            return false;
        }

        self::make_dir(dirname($target), $options);

        if($options['ftp']){
            self::file_put_contents($target, self::file_get_contents($origin), $options);
            self::file_delete($origin, $options);
        }else{
            rename($origin, $target);
            self::file_delete($origin, $options);
        }
    }

    /**
     * Returns a path restricting it to a base location
     *
     * This is used by Akelos to prevent AkFileSystem methods
     * from writing out of the Akelos base directory for security reasons.
     */
    static function getRestrictedPath($path, $options = array()) {
        if(!empty($options['skip_path_restriction'])){
            return $path;
        }
        $default_options = array(
        'ftp' => false,
        'base_path' => self::getDefaultBasePath($path)
        );
        $options = array_merge($default_options, $options);

        $path = str_replace('..','', rtrim($path,'\\/. '));
        $path = trim(str_replace($options['base_path'], '', $path), DS);

        if($options['ftp']){
            $path = trim(str_replace(array(DS,'//'),array('/','/'), $path),'/');
        }

        return $path;
    }

    /**
     * Gets a normalized base path for a base_path in options
     */
    static function getNormalizedBasePath($options = array()) {
        if(!empty($options['skip_path_restriction'])){
            return '';
        }
        return (AK_WIN && empty($options['base_path']) ? '' : $options['base_path'].DS);
    }

    static function getDefaultBasePath($for_path = null){
        return strstr($for_path, AK_TMP_DIR) ?  AK_TMP_DIR : AkConfig::getDir(defined('AK_CORE_DIR')? 'core' : 'base');
    }

}