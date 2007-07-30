<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2006, Akelos Media, S.L.  & Bermi Ferrer Martinez |
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
 * @package ActionView
 * @subpackage Helpers
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */


require_once(AK_LIB_DIR.DS.'AkActionView'.DS.'AkActionViewHelper.php');

class FileUploadHelper extends AkActionViewHelper
{
    function _instantiateCacheHandler()
    {
        if(empty($this->Cache)){
            require_once(AK_LIB_DIR.DS.'AkCache.php');
            $this->Cache =& new AkCache();
            $this->Cache->init(3600*2);
        }
    }

    /**
     * Handles a gmail-like file upload.
     * 
     * Just add this code at the beigining of the form action receiver.
     * 
     *  if($this->file_upload_helper->handle_partial_upload('/tmp')){ // where /tmp is the temporary directory for uploaded files
     *      return;
     *  }
     *
     * You must add this javascript to your view:
     * 
     * <script src="/javascripts/file_uploader.js" type="text/javascript"></script>
     * <script type="text/javascript">
     *  window.onload = function(){
     *     FileUploader.start('form_id', {partial:true}); // Change "form_id" for the id you supplied to your form
     *  }
     * </script>
     * 
     * @param bool $send_json_response
     */
    function handle_partial_upload($temporary_directory = AK_TMP_DIR, $send_json_response = true)
    {
        $this->_instantiateCacheHandler();
        
        $this->_setTempDir($temporary_directory);

        // Perform some garbage collection
        $this->clean_persisted_files();

        // If we are uploading files from the special iframe we store the file on the cache for using it later
        if($this->_controller->Request->isPost() && !empty($this->_controller->params['__iframe_file_uploader_call_from'])){
            $uploaded_files = $this->_handle_partial_files($this->_controller->params);
            if($send_json_response){
                $this->_controller->layout = false;
                $this->_controller->renderText(Ak::toJson($uploaded_files));
            }else{
                $this->_controller->params = array_merge($this->_controller->params, $uploaded_files);
            }
            return true;


            // We are requesting the file for downloading
        }elseif (!$this->_controller->Request->isPost() && !empty($this->_controller->params['persistence_key'])){
            $this->_send_file($this->_controller->params['persistence_key']);
            return true;


            // We have "persisted_keys" into the post so lets look for them and populate the params with cached data
        }elseif ($this->_controller->Request->isPost() && !empty($this->_controller->params['persisted_keys'])){
            if(!empty($this->_controller->params['persisted_files'])){
                $files = $this->_get_persisted_files_params($this->_controller->params['persisted_files']);
                $this->_controller->params = array_merge_recursive($this->_controller->params, $files);
                $this->_clean_up_persisted_on_shutdown($this->_controller->params['persisted_keys']);
                unset($this->_controller->params['persisted_keys']);
                unset($this->_controller->params['persisted_files']);
            }
            return false;
        }else{
            return false;
        }
        return true;
    }

    function _get_persisted_files_params($params)
    {
        $result = array();
        foreach ($params as $name=>$details){
            if(is_string($details)){
                $result[$name] = $this->_get_file_details($details);
            }elseif(is_array($details)){
                $_nested = $this->_get_persisted_files_params($details);
                if(!empty($_nested)){
                    $result = array_merge(array($name=>$_nested), $result);
                }
            }
        }
        return $result;
    }

    function _get_file_details($key)
    {
        $key = preg_replace('/[^A-Z^a-z^0-9]/','',$key);
        $file = $this->get_persisted_file($key);
        if(!empty($file)){
            Ak::file_put_contents($this->_getTempDir().DS.'_file_uploader_file_'.$key, base64_decode($file['contents']), array('ftp'=>false));
            return array('tmp_name'=>$this->_getTempDir().DS.'_file_uploader_file_'.$key,'size'=>$file['size'], 'name'=>$file['name'],'type'=>$file['type'], 'error'=>0);
        }else{
            return false;
        }
    }

    function _getTempDir()
    {
        return $this->temp_dir;
    }

    function _setTempDir($temp_dir)
    {
        $temp_dir = rtrim($temp_dir,'/\\');
        $tmp_file = @tempnam($temp_dir,'testing');
        if($tmp_file && @unlink($tmp_file)){
            $this->temp_dir = $temp_dir;
        }else{
            trigger_error(Ak::t("You cant use the directory %dir for temporary storing files uploaded",array('%dir'=>$temp_dir)), E_USER_ERROR);
        }
    }

    function _handle_partial_files($params)
    {
        $result = array();
        foreach ($params as $name=>$details){
            if(is_array($details) && !empty($details['name']) &&  !empty($details['tmp_name']) &&  !empty($details['size'])){
                $details['persistence_key'] = md5($details['tmp_name']);
                $details['human_size'] = $this->_controller->number_helper->human_size($details['size']);
                $file = $this->Cache->get($details['persistence_key'], 'persistent_files');
                if (empty($file)) {
                    $this->Cache->save(serialize(array_merge($details,array('contents'=>base64_encode(file_get_contents($details['tmp_name']))))));
                }
                $result[$name] = $details;
            }elseif(is_array($details)){
                $_nested = $this->_handle_partial_files($details);
                if(!empty($_nested)){
                    $result = array_merge(array($name=>$_nested), $result);
                }
            }
        }
        return $result;
    }

    function get_persisted_file($persistence_key)
    {
        $file = $this->Cache->get($persistence_key, 'persistent_files');
        if (empty($file)) {
            return array();
        }
        return unserialize($file);
    }

    function delete_persisted_file($key)
    {
        $key = preg_replace('/[^A-Z^a-z^0-9]/','',$key);
        $this->Cache->remove($key, 'persistent_files');
    }

    function clean_persisted_files()
    {
        $this->Cache->clean('persistent_files', 'old');
    }

    function _send_file($key)
    {
        $key = preg_replace('/[^A-Z^a-z^0-9]/','',$key);
        $file = $this->get_persisted_file($key);
        if(!empty($file)){
            $send_method = $file['size'] >  1048576 ? 'sendDataAsStream' : 'sendData';
            $this->_controller->$send_method(base64_decode($file['contents']),array('length'=>$file['size'], 'filename'=>$file['name'],'type'=>$file['type']));
        }else{
            die('invalid file');
        }

    }

    function _clean_up_persisted_on_shutdown($keys = false)
    {
        static $key_cache;
        if($keys === false){
            foreach ($key_cache as $key){
                @unlink($this->_getTempDir().DS.'_file_uploader_file_'.$key);
                $this->delete_persisted_file($key);
            }
            return;
        }
        if(empty($key_cache)){
            register_shutdown_function(array(&$this,'_clean_up_persisted_on_shutdown'));
        }
        $key_cache = array_merge($key_cache, $keys);
    }
}

?>
