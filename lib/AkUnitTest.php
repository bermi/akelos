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
 * @subpackage Testing
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

require_once(AK_CONTRIB_DIR.DS.'simpletest'.DS.'unit_tester.php');
require_once(AK_CONTRIB_DIR.DS.'simpletest'.DS.'mock_objects.php');
require_once(AK_CONTRIB_DIR.DS.'simpletest'.DS.'reporter.php');
require_once(AK_CONTRIB_DIR.DS.'simpletest'.DS.'web_tester.php');
//require_once(AK_CONTRIB_DIR.DS.'simpletest'.DS.'code_coverage.php');

require_once(AK_LIB_DIR.DS.'AkActiveRecord.php');
require_once(AK_LIB_DIR.DS.'AkInstaller.php');
require_once(AK_APP_DIR.DS.'shared_model.php');

class AkUnitTest extends UnitTestCase
{
    var $module = '';
    var $insert_models_data = false;
    var $instantiate_models = false;
    
    function AkUnitTest($label = false) {
        parent::UnitTestCase($label);
        $this->_configure();
    }
    
    /**
     *    Gets a list of test names. Normally that will
     *    be all internal methods that start with the
     *    name "test". This method should be overridden
     *    if you want a different rule.
     *    @return array        List of test names.
     *    @access public
     */
    function getTests() {
        $methods = array();
        if (isset($this->skip) && $this->skip == true) {
            return $methods;
        }
        foreach (get_class_methods(get_class($this)) as $method) {
            if ($this->_isTest($method)) {
                $methods[] = $method;
            }
        }
        return $methods;
    }
    
    function _configure()
    {
        $this->skip = !$this->_checkIfEnabled();
        $this->_loadFixtures();
    }
    
    function _checkIfEnabled($file = null)
    {
        if ($file == null) {
            $file = isset($this->check_file)?$this->check_file:null;
        }
        if ($file!=null && file_exists($file)) {
            $val = file_get_contents($file);
            if ($val == '0') {
                return false;
            }
        }
        return true;
    }
    
    
    function _loadFixtures($loadFixture = null)
    {
        if (isset($this->fixtures)) {
            $this->fixtures = is_array($this->fixtures)?$this->fixtures:Ak::toArray($this->fixtures);
        } else {
            $this->fixtures = array();
        }
        
        foreach ($this->fixtures as $fixture) {
            $file = AK_TEST_DIR.DS.'fixtures'.DS.'data'.DS.$fixture.'.yaml';
            if(!file_exists($file)){
                continue;
            }
            if ($loadFixture!=null && $fixture!=$loadFixture) {
                continue;
            }
            $setAlias=false;
            if (!isset($this->$fixture)) {
                $this->$fixture = array();
                $setAlias=true;
                $this->{$fixture.'_set'}=true;
            } else if ($this->{$fixture.'_set'}) {
                $setAlias = true;
            }
            $class_name = AkInflector::classify($fixture);
            if($this->instantiateModel($class_name)){
                $contents = &Ak::getStaticVar('yaml_fixture_'.$file);
                if (!$contents) {
                    ob_start();
                    require_once($file);
                    $contents = ob_get_clean();
                    Ak::setStaticVar('yaml_fixture_'.$file, $contents);
                }
                $items = Ak::convert('yaml','array',$contents);
                foreach ($items as $alias=>$item){
                    $obj=&$this->{$class_name}->create($item);
                    if (isset($item['created_at'])) {
                        $obj->updateAttribute('created_at',$item['created_at']);
                    } else if (isset($item['created_on'])) {
                        $obj->updateAttribute('created_on',$item['created_on']);
                    }
                    if ($setAlias) {
                        $array=&$this->$fixture;
                        $array[$alias] = &$obj;
                        $this->$fixture = &$array;
                    }
                }
            }
        }
    }
    
    function resetFrameworkDatabaseTables()
    {
        require_once(AK_APP_DIR.DS.'installers'.DS.'framework_installer.php');
        $installer = new FrameworkInstaller();
        $installer->uninstall();
        $installer->install();
        AkDbSchemaCache::clearAll();
    }

    /**
     * Re-installs the table for a given Modelname and includes or even instantiates the Model.
     * Looks in test/fixtures/app/models for the models and in test/fixtures/app/installers for the appropriate installers.
     * If no class-file for Model is found, it generates a dumb one temporarily.
     * For quick and dirty guys, the table can be generated on the fly. see below.
     *  
     * examples:
     * installAndIncludeModels('Article');
     * installAndIncludeModels(array('Article','Comment'=>'id,body'));
     *
     * @param mixed $models
     */
    function installAndIncludeModels($models = array())
    {
        $args = func_get_args();
        $last_arg = count($args)-1;

        if (isset($args[$last_arg]) && is_array($args[$last_arg]) && (isset($args[$last_arg]['instantiate']) || isset($args[$last_arg]['populate']))){
            $options = array_pop($args);
        } else $options = array();
        $default_options = array('instantiate' => true);
        $options = array_merge($default_options, $options);

        $models = !empty($args) ? (is_array($args[0]) ? array_shift($args) : (count($args) > 1 ? $args : Ak::toArray($args[0]))) : array();

        foreach ($models as $key=>$value){                               // handle array('Tag','Article')   <= array
            $model = is_numeric($key) ? $value : $key;                   //  or    array('Tag'=>'id,name'); <= a hash!
            $table_definition = is_numeric($key) ? '' : $value;
            $this->_reinstallModel($model, $table_definition);
            $this->_includeOrGenerateModel($model);
            if($this->insert_models_data || !empty($options['populate'])){
                $this->populateTables(AkInflector::tableize($model));
            }
            if($this->instantiate_models || !empty($options['instantiate'])){
                $this->instantiateModel($model);
            }
        }
    }

    function _reinstallModel($model, $table_definition = '')
    {
        if (!$this->uninstallAndInstallMigration($model)){
            $table_name = AkInflector::tableize($model);
            if (empty($table_definition)) {
                trigger_error(Ak::t('Could not install the table %tablename for the model %modelname',array('%tablename'=>$table_name, '%modelname'=>$model)),E_USER_ERROR);
                return false;
            }
            $installer =& new AkInstaller();
            $installer->dropTable($table_name,array('sequence'=>true));
            $installer->createTable($table_name,$table_definition,array('timestamp'=>false));
            
        } else {
            $table_name = AkInflector::tableize($model);
        }
        if (isset($this->fixtures) && is_array($this->fixtures) && in_array($table_name,$this->fixtures)) {
            $this->_loadFixtures($table_name);
        }
    }

    function uninstallAndInstallMigration($installer_name)
    {
        if (file_exists(AK_APP_DIR.DS.'installers'.DS.AkInflector::underscore($installer_name).'_installer.php')){
            require_once(AK_APP_DIR.DS.'installers'.DS.AkInflector::underscore($installer_name).'_installer.php');
            $installer_class_name = $installer_name.'Installer';
            $Installer =& new $installer_class_name();
            $Installer->uninstall();
            $Installer->install();
            return true;
        }
        return false;
    }

    function _includeOrGenerateModel($model_name)
    {
        if (file_exists(AK_MODELS_DIR.DS.AkInflector::underscore($model_name).'.php')){
            require_once(AK_MODELS_DIR.DS.AkInflector::underscore($model_name).'.php');
        } else {
            if (class_exists($model_name)){
                return true;
            }
            $model_source_code = "class ".$model_name." extends ActiveRecord { ";
            if (!AK_PHP5) $model_source_code .= $this->__fix_for_PHP4($model_name);
            $model_source_code .= "}";
            $has_errors = @eval($model_source_code) === false;
            if ($has_errors) trigger_error(Ak::t('Could not declare the model %modelname.',array('%modelname'=>$model_name)),E_USER_ERROR);
        }
    }

    function __fix_for_PHP4($model_name)
    {
        $table_name = AkInflector::tableize($model_name);
        return "function $model_name()
    {
        \$this->setModelName('$model_name');
        \$attributes = (array)func_get_args();
        \$this->setTableName('$table_name');
        \$this->init(\$attributes);
    }";

    }

    function populateTables()
    {
        
        $args = func_get_args();
        $tables = !empty($args) ? (is_array($args[0]) ? $args[0] : (count($args) > 1 ? $args : Ak::toArray($args))) : array();
        foreach ($tables as $table){
            $file = AK_TEST_DIR.DS.'fixtures'.DS.'data'.DS.(empty($this->module)?'':$this->module.DS).Ak::sanitize_include($table).'.yaml';
            if(!file_exists($file)){
                continue;
            }
            $class_name = AkInflector::classify($table);
            if($this->instantiateModel($class_name)){
                $contents = &Ak::getStaticVar('yaml_fixture_'.$file);
                if (!$contents) {
                    ob_start();
                    require_once($file);
                    $contents = ob_get_clean();
                    Ak::setStaticVar('yaml_fixture_'.$file, $contents);
                }
                $items = Ak::convert('yaml','array',$contents);
                foreach ($items as $item){
                    
                    $obj=&$this->{$class_name}->create($item);
                    if (isset($item['created_at'])) {
                        $obj->updateAttribute('created_at',$item['created_at']);
                    } else if (isset($item['created_on'])) {
                        $obj->updateAttribute('created_on',$item['created_on']);
                    }
                }
            }
        }
    }

    function instantiateModel($model_name)
    {
        if(class_exists($model_name) || Ak::import($model_name)){
            $this->$model_name =& new $model_name();
        } else {
            trigger_error(Ak::t('Could not instantiate %modelname',array('%modelname'=>$model_name)),E_USER_ERROR);
        }
        return !empty($this->$model_name) && is_object($this->$model_name) && strtolower(get_class($this->$model_name)) == strtolower($model_name);
    }

    /**
     * Includes and instantiates given models
     */
    function includeAndInstatiateModels()
    {
        $args = func_get_args();
        $models = isset($args[1]) ? (array)$args : Ak::toArray($args[0]);
        foreach ($models as $model){
            $this->_includeOrGenerateModel($model);
            $this->instantiateModel($model);
        }
    }
}

/**
* Unit tester for your mailers.
*
* This tester will copy your application views from the app/views to test/fixtures/app/views 
* unless you implicitly set AkMailerTest::avoid_copying_views to true.
*/
class AkMailerTest extends AkUnitTest 
{
    function __construct()
    {
        empty($this->avoid_copying_views) && Ak::copy(AK_BASE_DIR.DS.'app'.DS.'views',AK_VIEWS_DIR);
    }
}

class AkWebTestCase extends WebTestCase
{
    function assertWantedText($text, $message = '%s')
    {
        $this->assertWantedPattern('/'.preg_quote($text).'/', $message);
    }

    /**
     * Asserts only if the whole response matches $text
     */
    function assertTextMatch($text, $message = '%s')
    {
        $this->assertWantedPattern('/^'.preg_quote($text).'$/', $message);
    }
}

?>