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
defined('AK_PHP_CODE_SANITIZER_FOR_TEMPLATE_HANDLER')? null : define('AK_PHP_CODE_SANITIZER_FOR_TEMPLATE_HANDLER', 'AkPhpCodeSanitizer');

class AkPhpTemplateHandler
{
    var $_options = array();
    var $_AkActionView;
    var $_templateEngine = AK_DEFAULT_TEMPLATE_ENGINE;
    var $_codeSanitizerClass = AK_PHP_CODE_SANITIZER_FOR_TEMPLATE_HANDLER;

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
            if(!class_exists($this->_templateEngine)){
                require_once(AK_LIB_DIR.DS.'AkActionView'.DS.'TemplateEngines'.DS.$this->_templateEngine.'.php');
            }
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
        extract($____local_assigns, EXTR_SKIP);
        ob_start();
        include($this->_getCompiledTemplatePath());

        !empty($shared) ? $this->_AkActionView->addSharedAttributes($shared) : null;

        return  ob_get_clean();
    }


    function _assertForValidTemplate()
    {
        static $CodeSanitizer;
        if(empty($CodeSanitizer)){
            if($this->_codeSanitizerClass == 'AkPhpCodeSanitizer'){
                require_once(AK_LIB_DIR.DS.'AkActionView'.DS.'AkPhpCodeSanitizer.php');
            }
            $class = $this->_codeSanitizerClass;
            $CodeSanitizer = new $class();
        }
        $CodeSanitizer->setOptions($this->_options);
        return $CodeSanitizer->isCodeSecure();
    }

    function _templateNeedsCompilation()
    {
        if(!file_exists($this->_getCompiledTemplatePath())){
            return true;
        }
        $tpl_time = @filemtime($this->_getTemplatePath());
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
            $template_file_name = $this->_getTemplateFilename();
            if(!empty($template_file_name)){
                $this->_options['template_base_path'] = rtrim(str_replace($template_file_name,'',$this->_options['file_path']),'\/');
                if(defined('AK_COMPILED_VIEWS_DIR') && !strstr($this->_options['template_base_path'], AK_TMP_DIR)){
                    $this->_options['template_base_path'] = str_replace(AK_BASE_DIR, AK_COMPILED_VIEWS_DIR, $this->_options['template_base_path']);
                }
            }else{
                $this->_options['template_base_path'] = AK_BASE_DIR.DS.'tmp';
            }
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
}


?>