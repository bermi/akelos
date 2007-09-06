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
    function resetFrameworkDatabaseTables()
    {
        require_once(AK_APP_DIR.DS.'installers'.DS.'framework_installer.php');
        $installer = new FrameworkInstaller();
        $installer->uninstall();
        $installer->install();
        if(isset($_SESSION['__activeRecordColumnsSettingsCache'])){
            unset($_SESSION['__activeRecordColumnsSettingsCache']);
        }
    }

    function installAndIncludeModels($models = array())
    {
        $args = func_get_args();
        $models = !empty($args) ? (is_array($args[0]) ? $args[0] : (count($args) > 1 ? $args : Ak::toArray($args[0]))) : array();

        $default_options = array('instantiate' => true);
        $options = is_array($models[count($models)-1]) ? array_pop($models) : array();
        $options = array_merge($default_options, $options);

        foreach ($models as $model){
            require_once(AK_APP_DIR.DS.'installers'.DS.AkInflector::underscore($model).'_installer.php');
            require_once(AK_MODELS_DIR.DS.AkInflector::underscore($model).'.php');
            $installer_name = $model.'Installer';
            $installer = new $installer_name();
            $installer->uninstall();
            $installer->install();
            if(!empty($options['populate'])){
                $this->populateTables(AkInflector::tableize($model));
            }
            if(!empty($options['instantiate'])){
                $this->instantiateModel($model);
            }
        }
        if(isset($_SESSION['__activeRecordColumnsSettingsCache'])){
            unset($_SESSION['__activeRecordColumnsSettingsCache']);
        }
    }

    function populateTables()
    {
        $args = func_get_args();
        $tables = !empty($args) ? (is_array($args[0]) ? $args[0] : (count($args) > 1 ? $args : Ak::toArray($args))) : array();
        foreach ($tables as $table){
            $file = AK_TEST_DIR.DS.'fixtures'.DS.'data'.DS.Ak::sanitize_include($table).'.yaml';
            if(!file_exists($file)){
                continue;
            }
            $class_name = AkInflector::modulize($table);
            if($this->instantiateModel($class_name)){
                $items = Ak::convert('yaml','array',file_get_contents($file));
                foreach ($items as $item){
                    $this->{$class_name}->create($item);
                }
            }

        }
    }

    function instantiateModel($model_name)
    {
        if(empty($this->$model_name)){
            Ak::import($model_name);
            if(class_exists($model_name)){
                $this->$model_name =& new $model_name();
            }
        }
        return !empty($this->$model_name) && is_object($this->$model_name) && strtolower(get_class($this->$model_name)) == strtolower($model_name);
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