<?php

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+

/**
 * @package ActionView
 * @subpackage TemplateEngines
 * @author Bermi Ferrer <bermi a.t bermilabs c.om>
 */

class AkPhpTemplateHandler
{
    public $_options = array();
    public $_AkActionView;
    public $_templateEngine = AK_DEFAULT_TEMPLATE_ENGINE;
    public $_codeSanitizerClass = AK_PHP_CODE_SANITIZER_FOR_TEMPLATE_HANDLER;

    public function __construct(&$AkActionView)
    {
        $this->_AkActionView = $AkActionView;
    }

    public function render(&$____code, $____local_assigns, $____file_path)
    {
        $this->_options['variables'] = $____local_assigns;
        $this->_options['code'] =& $____code;
        $this->_options['functions'] = array('');
        $this->_options['file_path'] = $____file_path;

        if($this->_templateNeedsCompilation()){
            if(!class_exists($this->_templateEngine)){
                require_once(AK_LIB_DIR.DS.'AkActionView'.DS.'TemplateEngines'.DS.$this->_templateEngine.'.php');
            }
            $____template_engine_name = $this->_templateEngine;

            $TemplateEngine = new $____template_engine_name();

            $TemplateEngine->init(array(
            'code' => $____code
            ));

            $____code = $TemplateEngine->toPhp();

            if($____code === false){
                if(AK_PRODUCTION_MODE){
                    trigger_error(join("\n",$TemplateEngine->getErrors()), E_USER_ERROR);
                    return false;
                }else{
                    trigger_error("Could not compile ".$this->_options['file_path']."\n\n".join("\n",$TemplateEngine->getErrors()), E_USER_ERROR);
                    echo highlight_string($TemplateEngine->getParsedCode(), true);
                    die();
                }
            }
            if(AK_TEMPLATE_SECURITY_CHECK && $this->_templateNeedsValidation()){
                if(!$this->_assertForValidTemplate()){
                    return false;
                }
            }
            $this->_saveCompiledTemplate();
        }

        (array)$____local_assigns;
        extract($____local_assigns, EXTR_SKIP);
        ob_start();

        include $this->_getCompiledTemplatePath();

        !empty($shared) ? $this->_AkActionView->addSharedAttributes($shared) : null;

        return  ob_get_clean();
    }


    public function _assertForValidTemplate()
    {
        static $CodeSanitizer;
        if(empty($CodeSanitizer)){
            $class = $this->_codeSanitizerClass;
            $CodeSanitizer = new $class();
        }
        $CodeSanitizer->setOptions($this->_options);
        return $CodeSanitizer->isCodeSecure();
    }

    public function _templateNeedsCompilation()
    {
        if(!file_exists($this->_getCompiledTemplatePath()) || AK_FORCE_TEMPLATE_COMPILATION){
            return true;
        }
        $tpl_time = @filemtime($this->_getTemplatePath());
        $compiled_tpl_time = filemtime($this->_getCompiledTemplatePath());
        if($tpl_time > $compiled_tpl_time){
            return true;
        }
        return false;
    }

    public function _templateNeedsValidation()
    {
        return true;
    }

    public function _getTemplateBasePath()
    {
        if(empty($this->_options['template_base_path'])){
            $template_file_name = $this->_getTemplateFilename();
            if(!empty($template_file_name)){
                $this->_options['template_base_path'] = rtrim(str_replace($template_file_name,'',$this->_options['file_path']),'\/');
                if(AK_COMPILED_VIEWS_DIR && !strstr($this->_options['template_base_path'], AK_TMP_DIR)){
                    $this->_options['template_base_path'] = str_replace(AkConfig::getDir('base'), AK_COMPILED_VIEWS_DIR, $this->_options['template_base_path']);
                }
            }else{
                $this->_options['template_base_path'] = AK_COMPILED_VIEWS_DIR;
            }
        }
        return $this->_options['template_base_path'];
    }


    public function _getTemplatePath()
    {
        return $this->_options['file_path'];
    }

    public function _getTemplateFilename()
    {
        $this->_options['template_filename'] = empty($this->_options['template_filename']) && preg_match('/[^\/^\\\]+$/',$this->_options['file_path'],$match) ? $match[0] : @$this->_options['template_filename'];
        return $this->_options['template_filename'];
    }

    public function _getCompiledTemplateBasePath()
    {
        if(empty($this->_options['compiled_template_base_path'])){
            $this->_options['compiled_template_base_path'] = $this->_getTemplateBasePath().DS.'compiled';
        }
        return $this->_options['compiled_template_base_path'];
    }

    public function _getCompiledTemplatePath()
    {
        if(empty($this->_options['compiled_file_name'])){
            $template_filename = $this->_getTemplateFilename();
            $this->_options['compiled_file_name'] =  $this->_getCompiledTemplateBasePath().DS.
            (empty($template_filename) ? 'tpl_'.md5($this->_options['code']) : $template_filename).'.'.
            $this->_getHelpersChecksum().'.php';
        }
        return $this->_options['compiled_file_name'];
    }

    public function _saveCompiledTemplate()
    {
        $options = array('base_path' => (AK_COMPILED_VIEWS_DIR ? AK_TMP_DIR : AkConfig::getDir('base')));
        if(defined('AK_UPLOAD_FILES_USING_FTP') && AK_UPLOAD_FILES_USING_FTP && !strstr($options['base_path'], AkConfig::getDir('base'))){
            $options['ftp'] = false;
        }
        Ak::file_put_contents($this->_getCompiledTemplatePath(), $this->_options['code'], $options);
    }


    public function _getHelpersChecksum()
    {
        if(!isset($this->_helpers_checksum)){
            $this->_helpers_checksum = md5(serialize(AkHelperLoader::getInstantiatedHelperNames()));
        }
        return $this->_helpers_checksum;
    }
}

