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
 * @subpackage TemplateEngines
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

/**
 * The AkPhpCodeSanitizer ensures that Action View templates do not contain illegal function/variable calls
 * it is used by the AkPhpTemplateHander by default. If you want to stablish your own 
 * set of forbidden functionalities extend this class and set AK_PHP_CODE_SANITIZER_FOR_TEMPLATE_HANDLER 
 * with the name of your newly created class.
 */
class AkPhpCodeSanitizer
{
    var $Analyzer;
    var $_invalid = array();
    var $secure_active_record_method_calls = false;
    var $_errors = array();
    var $_options = array();
    var $_protedted_types = array('constructs','variables','member variables','functions','classes','methods');

    function clearErrors()
    {
        $this->_errors = array();
        $this->_invalid = array();
    }

    function setOptions($options)
    {
        $this->_options = $options;
    }

    function isCodeSecure($code =null, $raise_if_insecure = true)
    {
        $this->clearErrors();
        if(empty($code) && isset($this->_options['code'])){
            $code =& $this->_options['code'];
        }
        $this->AnalyzeCode($code);
        $this->secureVariables($code);
        $this->secureFunctions($code);
        $this->secureConstructs($code);
        $this->secureClasses($code);

        $this->secureProtectedTypes($code);

        if(!empty($this->_errors)){
            if($raise_if_insecure){
                $this->raiseError();
            }
            return false;
        }

        return true;
    }

    function raiseError($code = null)
    {
        $code = empty($code) ? @$this->_options['code'] : $code;
        if(AK_DEBUG){
            // We can't halt execution while testing and the error message is too large for trigger_error
            if(AK_ENVIRONMENT == 'testing'){
                trigger_error(join("\n", $this->getErrors()), E_USER_WARNING);
            }else{
                echo
                '<h1>'.Ak::t('Template %template_file security error', array('%template_file'=>@$this->_options['file_path'])).':</h1>'.
                "<ul><li>".join("</li>\n<li>",$this->getErrors())."</li></ul><hr />\n".
                '<h2>'.Ak::t('Showing template source from %file:',array('%file'=>$this->_options['file_path'])).'</h2>'.
                (isset($this->_options['file_path']) ? '<pre>'.htmlentities(Ak::file_get_contents($this->_options['file_path'])).'</pre><hr />':'').
                '<h2>'.Ak::t('Showing compiled template source:').'</h2>'.highlight_string($code,true);

                die();
            }
        }else{
            trigger_error(Ak::t('Template compilation error'),E_USER_ERROR);
        }
    }

    function getErrors()
    {
        return $this->_errors;
    }

    function _addDollarSymbol_(&$var)
    {
        if($var[0] != "$") {
            $var = "$".$var;
        }
    }

    function secureVariables($code)
    {
        $_forbidden['variables'] = empty($this->_options['forbidden_variables']) ?
        array_unique(array_merge(array_keys($GLOBALS), array_keys(get_defined_vars()))) :
        $this->_options['forbidden_variables'];

        array_map(array(&$this,'_addDollarSymbol_'), $_forbidden['variables']);

        $_used_vars = array_keys((array)$this->Analyzer->usedVariables);
        
        $this->lookForPrivateMemberVariables($this->Analyzer->usedMemberVariables);
        
        $this->_invalid['variables'] = array_diff($_used_vars, array_diff($_used_vars,array_merge($_forbidden['variables'], array_filter($_used_vars, array(&$this, 'isPrivateVar')))));
    }

    function secureFunctions($code = null)
    {
        if(!empty($this->Analyzer->createdFunctions)){
            $this->_errors[] = Ak::t('You can\'t create functions within templates');
        }
        $_used_functions = array_merge(array_keys((array)@$this->Analyzer->calledFunctions),array_keys((array)@$this->Analyzer->calledConstructs));

        $_forbidden['functions'] = array_merge($this->getForbiddenFunctions(),$this->_getFuntionsAsVariables($_used_functions));
        $this->_invalid['functions'] = array_diff($_used_functions, array_diff($_used_functions,$_forbidden['functions']));
    }

    function secureClasses($code = null)
    {
        if(!empty($this->Analyzer->createdClasses)){
            $this->_errors[] = Ak::t('You can\'t create classes within templates');
        }
        if(!empty($this->Analyzer->classesInstantiated)){
            $this->_errors[] = Ak::t('You can\'t instantiate classes within templates');
        }
        $this->_invalid['classes'] = array_diff(array_keys((array)$this->Analyzer->calledStaticMethods),(array)@$this->_options['classes']);

        $_class_calls = array_merge((array)$this->Analyzer->calledStaticMethods, (array)@$this->Analyzer->calledMethods);
        $forbidden_methods = array_merge($this->getForbiddenMethods(),(array)@$this->_options['forbidden_methods']);
        foreach ($_class_calls as $_class_name=>$_method_calls){
            foreach (array_keys($_method_calls) as $_method_call){
                if(empty($_method_call)){
                    continue;
                }
                $_method_name = $_class_name.($_class_name[0]=='$'?'->':'::').$_method_call;
                if($_method_call[0] === '_' || $_method_call[0] === '$' || in_array($_method_call, $forbidden_methods)){
                    $this->_invalid['methods'][] = $_method_name;
                }
            }
        }
    }

    function secureConstructs($code = null)
    {
        if(!empty($this->Analyzer->filesIncluded)){
            $this->_errors[] = Ak::t('You can\'t include files within templates using PHP include or require please use $this->render() instead');
        }
        $_used_constructs = array_keys((array)$this->Analyzer->calledConstructs);
        $this->_invalid['constructs'] = array_diff($_used_constructs, array_diff($_used_constructs, empty($this->_options['forbidden_constructs']) ? array('include','include_once','require','require_once') :
        $this->_options['forbidden_constructs']));
    }


    function secureProtectedTypes()
    {
        foreach ($this->_protedted_types as $_type){
            if(!empty($this->_invalid[$_type])){
                $this->_invalid[$_type] = array_diff($this->_invalid[$_type],(array)@$this->_options[$_type]);
            }
            if(!empty($this->_invalid[$_type])){
                array_unique($this->_invalid[$_type]);
                sort($this->_invalid[$_type]);
                $this->_errors[] = Ak::t('You can\'t use the following %type within templates:',array('%type'=>Ak::t($_type))).' '.join(', ',$this->_invalid[$_type]);
            }
        }
    }

    function isPrivateVar($var)
    {
        return preg_match('/^["\'${\.]*_/', $var);
    }
    
    function isPrivateDynamicVar($var)
    {
        if(preg_match('/^["\'{\.]*\$/', $var)){
            $var_name = trim($var, '{"\'.$');
            if(isset($GLOBALS[$var_name])){
                return $this->isPrivateVar($GLOBALS[$var_name]);
            }
            return true;
        }
        return false;
    }
    
    function lookForPrivateMemberVariables($var, $nested = false)
    {
        if(is_string($var) && $this->isPrivateVar($var)){
            $this->_invalid['member variables'][$var] = $var;
            return true;
        }elseif (is_array($var)){
            foreach (array_keys($var) as $k){
                if($this->isPrivateVar($k) || ($nested && $this->isPrivateDynamicVar($k))){
                    $this->_invalid['member variables'][$k] = $k;
                    return true;
                }elseif($this->lookForPrivateMemberVariables($var[$k], true)){
                    return true;
                }
            }
        }elseif (is_object($var)){
            return $this->lookForPrivateMemberVariables((array)$var, true);
        }
        return false;
    }

    function &getCodeAnalyzerInstance()
    {
        if(empty($this->Analyzer)){
            require_once(AK_CONTRIB_DIR.DS.'PHPCodeAnalyzer'.DS.'PHPCodeAnalyzer.php');
            $this->Analyzer =& new PHPCodeAnalyzer();
        }
        return $this->Analyzer;
    }

    function AnalyzeCode($code)
    {
        $this->Analyzer =& $this->getCodeAnalyzerInstance();
        $this->Analyzer->source = '?>'.$code.'<?php';
        $this->Analyzer->analyze();
        return $this->Analyzer;
    }

    function _getFuntionsAsVariables($functions_array)
    {
        $result = array();
        foreach ((array)$functions_array as $function){
            if(isset($function[0]) && $function[0] == '$'){
                $result[] = $function;
            }
        }
        return $result;
    }

    function getForbiddenMethods()
    {
        return !$this->secure_active_record_method_calls ? array() : array('init','setAccessibleAttributes','setProtectedAttributes','getConnection','setConnection','create','update','updateAttribute','updateAttributes','updateAll','delete','deleteAll','destroy','destroyAll','establishConnection','freeze','isFrozen','setInheritanceColumn','getColumnsWithRegexBoundaries','instantiate','getSubclasses','setAttribute','set','setAttributes','removeAttributesProtectedFromMassAssignment','cloneRecord','decrementAttribute','decrementAndSaveAttribute','incrementAttribute','incrementAndSaveAttribute','hasAttributesDefined','reload','save','createOrUpdate','toggleAttribute','toggleAttributeAndSave','setId','getAttributesBeforeTypeCast','getAttributeBeforeTypeCast','setPrimaryKey','resetColumnInformation','setSerializeAttribute','getSerializedAttributes','getAvailableCombinedAttributes','getAvailableAttributes','loadColumnsSettings','setColumnSettings','setAttributeByLocale','setAttributeLocales','initiateAttributeToNull','initiateColumnsToNull','getAkelosDataType','getClassForDatabaseTableMapping','setTableName','setDisplayField','debug','castAttributeForDatabase','castAttributeFromDatabase','isLockingEnabled','beforeCreate','beforeValidation','beforeValidationOnCreate','beforeValidationOnUpdate','beforeSave','beforeUpdate','afterUpdate','afterValidation','afterValidationOnCreate','afterValidationOnUpdate','afterCreate','afterDestroy','beforeDestroy','afterSave','transactionStart','transactionComplete','transactionFail','transactionHasFailed','validatesConfirmationOf','validatesAcceptanceOf','validatesAssociated','validatesPresenceOf','validatesLengthOf','validatesSizeOf','validatesUniquenessOf','validatesFormatOf','validatesInclusionOf','validatesExclusionOf','validatesNumericalityOf','validate','validateOnCreate','validateOnUpdate','notifyObservers','setObservableState','getObservableState','addObserver','getObservers','addErrorToBase','addError','addErrorOnEmpty','addErrorOnBlank','addErrorOnBoundaryBreaking','addErrorOnBoundryBreaking','clearErrors','actsAs','actsLike','dbug','sqlSelectOne','sqlSelectValue','sqlSelectValues','sqlSelectAll','sqlSelect');
    }

    function getForbiddenFunctions()
    {
        return array('__halt_compiler','aggregate','aggregate_methods','aggregate_methods_by_list','aggregate_methods_by_regexp','aggregate_properties','aggregate_properties_by_list','aggregate_properties_by_regexp','aggregation_info','apache_child_terminate','apache_get_modules','apache_get_version','apache_getenv','apache_lookup_uri','apache_note','apache_request_headers','apache_reset_timeout','apache_response_headers','apache_setenv','ascii2ebcdic','basename','call_user_func','call_user_func_array','call_user_method','call_user_method_array','chdir','chgrp','chmod','chown','chroot','class_exists','clearstatcache','closedir','closelog','com_addref','com_event_sink','com_get','com_invoke','com_invoke_ex','com_isenum','com_load','com_load_typelib','com_message_pump','com_print_typeinfo','com_propget','com_propput','com_propset','com_release','com_set','compact','connection_aborted','connection_status','connection_timeout','constant','copy','crc32','create_function','deaggregate','debug_backtrace','debug_zval_dump','define','define_syslog_variables','defined','delete','dio_close','dio_fcntl','dio_open','dio_read','dio_seek','dio_stat','dio_tcsetattr','dio_truncate','dio_write','dir','dirname','disk_free_space','disk_total_space','diskfreespace','dl','ebcdic2ascii','error_log','error_reporting','escapeshellarg','escapeshellcmd','eval','exec','extension_loaded','extract','fclose','feof','fflush','fgetc','fgetcsv','fgets','fgetss','file','file_exists','file_get_contents','file_put_contents','fileatime','filectime','filegroup','fileinode','filemtime','fileowner','fileperms','filesize','filetype','flock','flush','fmod','fnmatch','fopen','fpassthru','fputcsv','fputs','fread','fscanf','fseek','fsockopen','fstat','ftell','ftruncate','func_get_arg','func_get_args','func_num_args','function_exists','fwrite','gd_info','get_browser','get_cfg_var','get_class','get_class_methods','get_class_vars','get_current_user','get_declared_classes','get_defined_constants','get_defined_functions','get_defined_vars','get_extension_funcs','get_include_path','get_included_files','get_loaded_extensions','get_magic_quotes_gpc','get_magic_quotes_runtime','get_meta_tags','get_object_vars','get_parent_class','get_required_files','get_resource_type','getallheaders','getcwd','getenv','getmygid','getmyinode','getmypid','getmyuid','glob','gzclose','gzcompress','gzdeflate','gzencode','gzeof','gzfile','gzgetc','gzgets','gzgetss','gzinflate','gzopen','gzpassthru','gzputs','gzread','gzrewind','gzseek','gztell','gzuncompress','gzwrite','header','headers_sent','highlight_file','html_doc','html_doc_file','ignore_user_abort','import_request_variables','ini_alter','ini_get','ini_get_all','ini_restore','ini_set','is_dir','is_executable','is_file','is_link','is_readable','is_uploaded_file','is_writable','is_writeable','link','linkinfo','log','log10','lstat','magic_quotes_runtime','mail','md5_file','mkdir','move_uploaded_file','ob_clean','ob_end_clean','ob_end_flush','ob_flush','ob_get_clean','ob_get_contents','ob_get_flush','ob_get_length','ob_get_level','ob_get_status','ob_gzhandler','ob_implicit_flush','ob_list_handlers','ob_start','opendir','openlog','output_add_rewrite_var','output_reset_rewrite_vars','overload','pack','parse_ini_file','parse_str','parse_url','passthru','pathinfo','pclose','pfsockopen','php_check_syntax','php_ini_scanned_files','php_logo_guid','php_sapi_name','php_strip_whitespace','php_uname','phpcredits','phpinfo','phpversion','png2wbmp','popen','preg_replace_callback','proc_close','proc_get_status','proc_nice','proc_open','proc_terminate','putenv','readdir','readfile','readgzfile','readlink','realpath','register_shutdown_function','register_tick_function','rename','restore_error_handler','restore_include_path','rewind','rewinddir','rmdir','scandir','session_cache_expire','session_cache_limiter','session_commit','session_decode','session_destroy','session_encode','session_get_cookie_params','session_id','session_is_registered','session_module_name','session_name','session_regenerate_id','session_register','session_save_path','session_set_cookie_params','session_set_save_handler','session_start','session_unregister','session_unset','session_write_close','set_error_handler','set_file_buffer','set_include_path','set_magic_quotes_runtime','set_socket_blocking','set_time_limit','setcookie','setlocale','settype','shell_exec','shmop_close','shmop_delete','shmop_open','shmop_read','shmop_size','shmop_write','show_source','similar_text','sleep','socket_accept','socket_bind','socket_clear_error','socket_close','socket_connect','socket_create','socket_create_listen','socket_create_pair','socket_get_option','socket_get_status','socket_getopt','socket_getpeername','socket_getsockname','socket_iovec_add','socket_iovec_alloc','socket_iovec_delete','socket_iovec_fetch','socket_iovec_free','socket_iovec_set','socket_last_error','socket_listen','socket_read','socket_readv','socket_recv','socket_recvfrom','socket_select','socket_send','socket_sendmsg','socket_sendto','socket_set_block','socket_set_blocking','socket_set_nonblock','socket_set_option','socket_set_timeout','socket_setopt','socket_shutdown','socket_strerror','socket_write','socket_writev','stat','stream_context_create','stream_context_get_options','stream_context_set_option','stream_context_set_params','stream_filter_append','stream_filter_prepend','stream_get_meta_data','stream_register_wrapper','stream_select','stream_set_blocking','stream_set_timeout','stream_set_write_buffer','stream_wrapper_register','symlink','syslog','system','tempnam','time_nanosleep','time_sleep_until','tmpfile','token_get_all','token_name','touch','trigger_error','umask','uniqid','unlink','unpack','unregister_tick_function','user_error','usleep','virtual','zend_get_cfg_var','zend_loader_current_file','zend_loader_enabled','zend_loader_file_encoded','zend_loader_file_licensed','zend_loader_install_license','zend_logo_guid','zend_version');
    }
}


?>