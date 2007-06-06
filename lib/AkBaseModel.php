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
 * @subpackage Utils
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

ak_define('LOG_EVENTS', false);

require_once(AK_LIB_DIR.DS.'Ak.php');
require_once(AK_LIB_DIR.DS.'AkInflector.php');
require_once(AK_LIB_DIR.DS.'AkObject.php');

/**
 * This is the base class for all sort of models (Mailers or Active records)
 * It handles the naming conventions for models, as in PHP4 all methods appear lowercased
 * we to work around to find the real case of the methos to apply conventions.
 * 
 * See also <AkActiveRecord> and <AkActionMailer> as those are the ones you will usually inherit from
*
* @package AkelosFramework
* @author Bermi Ferrer <bermi a.t akelos c.om>
* @copyright Copyright (c) 2002-2005, Akelos Media, S.L. http://www.akelos.org
* @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
*/
class AkBaseModel extends AkObject 
{
    var $_modelName;
    
    /**
    * Returns current model name
    */
    function getModelName()
    {
        if(!isset($this->_modelName)){
            if(!$this->setModelName()){
                trigger_error(Ak::t('Unable to fetch current model name'),E_USER_NOTICE);
            }
        }
        return $this->_modelName;
    }

    /**
    * Sets current model name
    *
    * Use this option if the model name can't be guessed by the Active Record
    */
    function setModelName($model_name = null)
    {
        if(!empty($model_name)){
            $this->_modelName = $model_name;
        }else{
            $this->_modelName = $this->_getModelName(get_class($this));
        }
        return true;
    }

    /**
     * This method will nicely handle model names even  on 
     * PHP where class names are all lowercased
     */
    function _getModelName($class_name)
    {
        if(AK_PHP5){
            return $class_name;
        }
        $included_models = $this->_getIncludedModelNames();
        if(!in_array($class_name, $included_models)){
            $class_name = strtolower($class_name);
            foreach ($included_models as $included_model){
                if($class_name == strtolower($included_model)){
                    return $included_model;
                }
            }

            trigger_error(Ak::t('The Akelos Framework could not automatically configure your model name.'.
            ' This might be caused because your model file is not located on %path. Please call $this->setModelName("YourModelName");'.
            ' in your model constructor in order to make this work.',array('%path'=>AK_MODELS_DIR.DS)), E_USER_ERROR);
            return false;
        }
    }



    function getParentModelName()
    {
        if(!isset($this->_parentModelName)){
            if(!$this->setParentModelName()){
                return false;
            }
        }
        return $this->_parentModelName;
    }

    function setParentModelName($model_name = null)
    {
        $got_errors = false;
        if(!empty($model_name)){
            $this->_parentModelName = $model_name;
        }else{
            $class_name = AkInflector::camelize(get_parent_class($this));
            if(!AK_PHP5){
                $included_models = $this->_getIncludedModelNames();
                if(!in_array($class_name, $included_models)){
                    $class_name = strtolower($class_name);
                    foreach ($included_models as $included_model){
                        if($class_name == strtolower($included_model)){
                            $this->_parentModelName = $included_model;
                            return true;
                        }
                    }
                    $got_errors = true;
                }
            }
            if($got_errors || $class_name == 'AkActiveRecord'){
                trigger_error(Ak::t('The Akelos Framework could not automatically configure your model name.'.
                ' This might be caused because your model file is not located on %path. Please call $this->setParentModelName("YourParentModelName");'.
                ' in your model constructor in order to make this work.',array('%path'=>AK_MODELS_DIR.DS)), E_USER_ERROR);
                return false;
            }
            $this->_parentModelName = $class_name;
        }
        return true;
    }

    function _getIncludedModelNames()
    {
        $included_files = get_included_files();
        $models = array();
        foreach ($included_files as $file_name){
            if(strstr($file_name,AK_MODELS_DIR)){
                $models[] = AkInflector::camelize(str_replace(array(AK_MODELS_DIR.DS,'.php'),'',$file_name));
            }
        }
        return $models;
    }
    
    function _executeSql($sql, $trigger_error = true)
    {
        AK_LOG_EVENTS ? ($this->Logger->message($this->getModelName().' executing SQL: '.$sql)) : null;
        $result = $this->_db->Execute($sql);
        if(!$result && AK_DEBUG){
            AK_LOG_EVENTS ? ($this->Logger->error($this->getModelName().': '.$this->_db->ErrorMsg())) : null;
            $trigger_error ? trigger_error($this->_db->ErrorMsg(), E_USER_NOTICE) : false;
        }
        return $result;
    }
    
    function _startSqlBlockLog()
    {
        $this->__original_dbug = $this->_db->debug;
        $this->_db->debug = true;
        ob_start();
    }
    
    function _endSqlBlockLog()
    {
        $sql_debug = ob_get_clean();
        $this->Logger->message($this->Logger->formatText($this->getModelName(),'bold').' executing SQL: '.
        $this->Logger->formatText(preg_replace('/^\([a-z]+\): /','',trim(Ak::html_entity_decode(strip_tags($sql_debug)),"\n- ")),'blue'));
        if($this->__original_dbug){
            echo $sql_debug;
        }
        $this->_db->debug = $this->__original_dbug;
    }
}


?>