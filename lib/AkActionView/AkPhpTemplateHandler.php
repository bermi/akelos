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
 * @subpackage AkActionView
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

defined('AK_DEFAULT_TEMPLATE_ENGINE') ? null : define('AK_DEFAULT_TEMPLATE_ENGINE', 'AkSintags');
defined('AK_TEMPLATE_SECURITY_CHECK') ? null : define('AK_TEMPLATE_SECURITY_CHECK', true);

class AkPhpTemplateHandler
{
    var $_options = array();
    var $_AkActionView;
    var $_templateEngine = AK_DEFAULT_TEMPLATE_ENGINE;

    function AkPhpTemplateHandler(&$AkActionView)
    {
        $this->_AkActionView =& $AkActionView;
    }

    function render(&$____code, $____local_assigns, $____file_path)
    {
        $this->_options['variables'] = $____local_assigns;
        $this->_options['code'] =& $____code;
        $this->_options['functions'] = array('');
        $this->_options['file_path'] = $____file_path;

        if($this->_templateNeedsCompilation()){
            require_once(AK_LIB_DIR.DS.'AkActionView'.DS.'TemplateEngines'.DS.$this->_templateEngine.'.php');
            $____template_engine_name = $this->_templateEngine;

            $TemplateEngine =& new $____template_engine_name();

            $TemplateEngine->init(array(
            'code' => $____code,
            ));

            $____code = $TemplateEngine->toPhp();

            if($____code === false){
                trigger_error(join("\n",$TemplateEngine->getErrors()), E_USER_ERROR);
                return false;
            }
            if(AK_TEMPLATE_SECURITY_CHECK && $this->_templateNeedsValidation()){
                if(!$this->_assertForValidTemplate()){
                    return false;
                }
            }
            $this->_saveCompiledTemplate();
        }

        (array)$____local_assigns;
        /**
         * @todo Create a copy of the Active Recods with frozen enabled in order to avoid
         * model manipulation on views. If should also forbid the ussage of freeze functions.
         */
        extract($____local_assigns, EXTR_SKIP);
        ob_start();
        include($this->_getCompiledTemplatePath());

        !empty($shared) ? $this->_AkActionView->addSharedAttributes($shared) : null;

        return  ob_get_clean();
    }


    function _assertForValidTemplate()
    {
        $_invalid = array();
        $_errors = array();
        $_protedted_types = array('constructs','variables','functions','classes','methods');

        require_once(AK_CONTRIB_DIR.DS.'PHPCodeAnalyzer'.DS.'PHPCodeAnalyzer.php');
        $_analyzer = new PHPCodeAnalyzer();
        $_analyzer->source = '?>'.$this->_options['code'].'<?php';
        $_analyzer->analyze();

        //echo '<pre>'.print_r($_analyzer, true).'</pre>';


        if(strstr($this->_options['code'],'${')){
            $_errors[] = Ak::t('You can\'t use ${ within templates');
        }
        if(!empty($_analyzer->createdClasses)){
            $_errors[] = Ak::t('You can\'t create classes within templates');
        }
        if(!empty($_analyzer->createdFunctions)){
            $_errors[] = Ak::t('You can\'t create functions within templates');
        }
        if(!empty($_analyzer->filesIncluded)){
            $_errors[] = Ak::t('You can\'t include files within templates using PHP include or require please use $this->render() instead');
        }
        if(!empty($_analyzer->classesInstantiated)){
            $_errors[] = Ak::t('You can\'t instantiate classes within templates');
        }

        $_add_dollar_function = create_function('&$_var', 'if($_var[0] != "$") $_var = "$".$_var;');
        $_is_private_var = create_function('$_var', 'return $_var[1]==="_";');

        $_forbidden['variables'] = empty($this->_options['forbidden_variables']) ? array_unique(array_merge(array_keys($GLOBALS), array_keys(get_defined_vars()))) : $this->_options['forbidden_variables'];
        array_map($_add_dollar_function, $_forbidden['variables']);

        $_used_constructs = array_keys((array)$_analyzer->calledConstructs);
        $_invalid['constructs'] = array_diff($_used_constructs, array_diff($_used_constructs, empty($this->_options['forbidden_constructs']) ? array('include','include_once','require','require_once') : $this->_options['forbidden_constructs']));


        $_used_vars = array_keys((array)$_analyzer->usedVariables);
        $_invalid['variables'] = array_diff($_used_vars, array_diff($_used_vars,array_merge($_forbidden['variables'],array_filter($_used_vars, $_is_private_var))));

        $_used_functions = array_merge(array_keys((array)@$_analyzer->calledFunctions),array_keys((array)@$_analyzer->calledConstructs));

        $_forbidden['functions'] = array_merge($this->getForbiddenFunctions(),$this->_getFuntionsAsVariables($_used_functions));
        $_invalid['functions'] = array_diff($_used_functions, array_diff($_used_functions,$_forbidden['functions']));

        $_invalid['classes'] = array_diff(array_keys((array)$_analyzer->calledStaticMethods),(array)@$this->_options['classes']);

        $_class_calls = array_merge((array)$_analyzer->calledStaticMethods, (array)@$_analyzer->calledMethods);

        foreach ($_class_calls as $_class_name=>$_method_calls){
            foreach (array_keys($_method_calls) as $_method_call){
                if(empty($_method_call)){
                    continue;
                }
                $_method_name = $_class_name.($_class_name[0]=='$'?'->':'::').$_method_call;
                if($_method_call[0] === '_' || in_array($_method_name,(array)@$this->_options['forbidden_methods'])){
                    $_invalid['methods'][] = $_method_name;
                }
            }
        }

        foreach ($_protedted_types as $_type){

            if(!empty($_invalid[$_type])){
                $_invalid[$_type] = array_diff($_invalid[$_type],(array)@$this->_options[$_type]);
            }

            if(!empty($_invalid[$_type])){
                array_unique($_invalid[$_type]);
                sort($_invalid[$_type]);
                $_errors[] = Ak::t('You can\'t use the following %type within templates:',array('%type'=>Ak::t($_type))).' '.join(', ',$_invalid[$_type]);
            }
        }


        if(!empty($_errors)){
            if(AK_DEBUG){
                echo
                '<h1>'.Ak::t('Template %template_file security error', array('%template_file'=>$this->_options['file_path'])).':</h1>'.
                "<ul><li>".join("</li>\n<li>",$_errors)."</li></ul><hr />\n".
                '<h2>'.Ak::t('Showing template source from %file:',array('%file'=>$this->_options['file_path'])).'</h2><pre>'.
                htmlentities(Ak::file_get_contents($this->_options['file_path'])).'</pre><hr />'.
                '<h2>'.Ak::t('Showing compiled template source:').'</h2>'.highlight_string($this->_options['code'],true);

                //echo '<pre>'.print_r($_analyzer, true).'</pre>';
                die();
            }else{
                trigger_error(Ak::t('Template compilation error'),E_USER_ERROR);
                return false;
            }
        }

        return true;
    }


    function _templateNeedsCompilation()
    {
        if(!file_exists($this->_getCompiledTemplatePath())){
            return true;
        }
        $tpl_time = filemtime($this->_getTemplatePath());
        $compiled_tpl_time = filemtime($this->_getCompiledTemplatePath());
        if($tpl_time > $compiled_tpl_time){
            return true;
        }

        return false;
    }

    function _templateNeedsValidation()
    {
        return true;
    }



    function _getTemplateBasePath()
    {
        if(empty($this->_options['template_base_path'])){
            $this->_options['template_base_path'] = rtrim(str_replace($this->_getTemplateFilename(),'',$this->_options['file_path']),'\/');
        }

        return $this->_options['template_base_path'];
    }


    function _getTemplatePath()
    {
        return $this->_options['file_path'];
    }

    function _getTemplateFilename()
    {
        $this->_options['template_filename'] = empty($this->_options['template_filename']) && preg_match('/[^\/^\\\]+$/',$this->_options['file_path'],$match) ? $match[0] : @$this->_options['template_filename'];
        return $this->_options['template_filename'];
    }

    function _getCompiledTemplateBasePath()
    {
        if(empty($this->_options['compiled_template_base_path'])){
            $this->_options['compiled_template_base_path'] = $this->_getTemplateBasePath().DS.'compiled';
        }
        return $this->_options['compiled_template_base_path'];
    }

    function _getCompiledTemplatePath()
    {
        if(empty($this->_options['compiled_file_name'])){
            $template_filename = $this->_getTemplateFilename();
            $this->_options['compiled_file_name'] =  $this->_getCompiledTemplateBasePath().DS.
            (empty($template_filename) ? 'tpl_'.md5($this->_options['code']) : $template_filename).'.php';
        }
        return $this->_options['compiled_file_name'];
    }



    function _saveCompiledTemplate()
    {
        Ak::file_put_contents($this->_getCompiledTemplatePath(),$this->_options['code']);
    }

    function _getFuntionsAsVariables($functions_array)
    {
        $result = array();
        foreach ((array)$functions_array as $function){
            if($function[0] == '$'){
                $result[] = $function;
            }
        }
        return $result;
    }

    function getForbiddenFunctions()
    {
        return array('__halt_compiler','aggregate','aggregate_methods','aggregate_methods_by_list','aggregate_methods_by_regexp','aggregate_properties','aggregate_properties_by_list','aggregate_properties_by_regexp','aggregation_info','apache_child_terminate','apache_get_modules','apache_get_version','apache_getenv','apache_lookup_uri','apache_note','apache_request_headers','apache_reset_timeout','apache_response_headers','apache_setenv','ascii2ebcdic','basename','call_user_func','call_user_func_array','call_user_method','call_user_method_array','chdir','chgrp','chmod','chown','chroot','class_exists','clearstatcache','closedir','closelog','com_addref','com_event_sink','com_get','com_invoke','com_invoke_ex','com_isenum','com_load','com_load_typelib','com_message_pump','com_print_typeinfo','com_propget','com_propput','com_propset','com_release','com_set','compact','connection_aborted','connection_status','connection_timeout','constant','copy','crc32','create_function','deaggregate','debug_backtrace','debug_zval_dump','define','define_syslog_variables','defined','delete','dio_close','dio_fcntl','dio_open','dio_read','dio_seek','dio_stat','dio_tcsetattr','dio_truncate','dio_write','dir','dirname','disk_free_space','disk_total_space','diskfreespace','dl','ebcdic2ascii','error_log','error_reporting','escapeshellarg','escapeshellcmd','eval','exec','extension_loaded','extract','fclose','feof','fflush','fgetc','fgetcsv','fgets','fgetss','file','file_exists','file_get_contents','file_put_contents','fileatime','filectime','filegroup','fileinode','filemtime','fileowner','fileperms','filesize','filetype','flock','flush','fmod','fnmatch','fopen','fpassthru','fputcsv','fputs','fread','fscanf','fseek','fsockopen','fstat','ftell','ftruncate','func_get_arg','func_get_args','func_num_args','function_exists','fwrite','gd_info','get_browser','get_cfg_var','get_class','get_class_methods','get_class_vars','get_current_user','get_declared_classes','get_defined_constants','get_defined_functions','get_defined_vars','get_extension_funcs','get_include_path','get_included_files','get_loaded_extensions','get_magic_quotes_gpc','get_magic_quotes_runtime','get_meta_tags','get_object_vars','get_parent_class','get_required_files','get_resource_type','getallheaders','getcwd','getenv','getmygid','getmyinode','getmypid','getmyuid','glob','gzclose','gzcompress','gzdeflate','gzencode','gzeof','gzfile','gzgetc','gzgets','gzgetss','gzinflate','gzopen','gzpassthru','gzputs','gzread','gzrewind','gzseek','gztell','gzuncompress','gzwrite','header','headers_sent','highlight_file','html_doc','html_doc_file','ignore_user_abort','import_request_variables','ini_alter','ini_get','ini_get_all','ini_restore','ini_set','is_dir','is_executable','is_file','is_link','is_readable','is_uploaded_file','is_writable','is_writeable','link','linkinfo','log','log10','lstat','magic_quotes_runtime','mail','md5_file','mkdir','move_uploaded_file','ob_clean','ob_end_clean','ob_end_flush','ob_flush','ob_get_clean','ob_get_contents','ob_get_flush','ob_get_length','ob_get_level','ob_get_status','ob_gzhandler','ob_implicit_flush','ob_list_handlers','ob_start','opendir','openlog','output_add_rewrite_var','output_reset_rewrite_vars','overload','pack','parse_ini_file','parse_str','parse_url','passthru','pathinfo','pclose','pfsockopen','php_check_syntax','php_ini_scanned_files','php_logo_guid','php_sapi_name','php_strip_whitespace','php_uname','phpcredits','phpinfo','phpversion','png2wbmp','popen','preg_replace_callback','proc_close','proc_get_status','proc_nice','proc_open','proc_terminate','putenv','readdir','readfile','readgzfile','readlink','realpath','register_shutdown_function','register_tick_function','rename','restore_error_handler','restore_include_path','rewind','rewinddir','rmdir','scandir','session_cache_expire','session_cache_limiter','session_commit','session_decode','session_destroy','session_encode','session_get_cookie_params','session_id','session_is_registered','session_module_name','session_name','session_regenerate_id','session_register','session_save_path','session_set_cookie_params','session_set_save_handler','session_start','session_unregister','session_unset','session_write_close','set_error_handler','set_file_buffer','set_include_path','set_magic_quotes_runtime','set_socket_blocking','set_time_limit','setcookie','setlocale','settype','shell_exec','shmop_close','shmop_delete','shmop_open','shmop_read','shmop_size','shmop_write','show_source','similar_text','sleep','socket_accept','socket_bind','socket_clear_error','socket_close','socket_connect','socket_create','socket_create_listen','socket_create_pair','socket_get_option','socket_get_status','socket_getopt','socket_getpeername','socket_getsockname','socket_iovec_add','socket_iovec_alloc','socket_iovec_delete','socket_iovec_fetch','socket_iovec_free','socket_iovec_set','socket_last_error','socket_listen','socket_read','socket_readv','socket_recv','socket_recvfrom','socket_select','socket_send','socket_sendmsg','socket_sendto','socket_set_block','socket_set_blocking','socket_set_nonblock','socket_set_option','socket_set_timeout','socket_setopt','socket_shutdown','socket_strerror','socket_write','socket_writev','stat','stream_context_create','stream_context_get_options','stream_context_set_option','stream_context_set_params','stream_filter_append','stream_filter_prepend','stream_get_meta_data','stream_register_wrapper','stream_select','stream_set_blocking','stream_set_timeout','stream_set_write_buffer','stream_wrapper_register','symlink','syslog','system','tempnam','time_nanosleep','time_sleep_until','tmpfile','token_get_all','token_name','touch','trigger_error','umask','uniqid','unlink','unpack','unregister_tick_function','user_error','usleep','virtual','zend_get_cfg_var','zend_loader_current_file','zend_loader_enabled','zend_loader_file_encoded','zend_loader_file_licensed','zend_loader_install_license','zend_logo_guid','zend_version');
    }
}


?>