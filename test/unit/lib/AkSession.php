<?php

require_once(dirname(__FILE__).'/../../fixtures/config/config.php');

defined('AK_TEST_MEMCACHED_CHECKFILE') ? null: define('AK_TEST_MEMCACHED_CHECKFILE',AK_TEST_DIR.DS.DS.'unit'.DS.'config'.DS.'memcached');

class AkSession_TestCase extends  AkWebTestCase
{
    public $sessionLife = NULL;

    public function _checkIfEnabled($file = null)
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

    public function test_install_db_tables()
    {
        require_once(dirname(__FILE__).'/../../fixtures/app/installers/framework_installer.php');
        $installer = new FrameworkInstaller();
        $installer->uninstall();
        $installer->install();

    }

    public function setUp()
    {
        $this->_test_script = str_replace('/fixtures/public','',trim(AK_TESTING_URL,'/')).
        '/mocks/test_script_AkSession.php';
    }

    public function test_all_session_handlers()
    {
        $cacheHandlers = array('cache_lite'=>1,'akadodbcache'=>2);
        $memcacheEnabled = $this->_checkIfEnabled(AK_TEST_MEMCACHED_CHECKFILE);
        if ($memcacheEnabled) {
            $cacheHandlers['akmemcache'] = 3;
        }
        $unitTests = array('_Test_open','_Test_read_write','_Test_destroy', '_Test_gc');


        foreach ($cacheHandlers as $class=>$type) {
            foreach ($unitTests as $test) {
                $this->$test($type,$class);
            }
        }
    }

    public function _Test_open($type, $class)
    {
        $browser = $this->getBrowser();
        $browser = $this->getBrowser();
        $this->get("$this->_test_script?open_check=1&handler=".$type);
        $expected_session_id = $browser->getContentAsText();
        $this->get("$this->_test_script?open_check=1&handler=".$type);
        //$browser->getContentAsText();
        $this->assertText($expected_session_id,'Sessions are not working correctly');
    }

    public function _Test_read_write($type, $class)
    {
        $expected = 'test_value';
        $this->get("$this->_test_script?key=test_key&value=$expected&handler=".$type);
        $this->get("$this->_test_script?key=test_key&handler=".$type);
        $this->assertText($expected,'Session is not storing values on database correctly when calling '.
        $this->_test_script.'?key=test_key&handler='.$type);
    }

    public function _Test_destroy($type, $class)
    {
        $expected = 'value not found';
        $this->get("$this->_test_script?key=test_key&value=test_value&handler=".$type);
        $this->get("$this->_test_script?destroy_check=1&handler=".$type);
        $this->get("$this->_test_script?key=test_key&handler=".$type);
        $this->assertText($expected,'session_destroy(); is not working as expected');
    }

    public function _Test_gc($type, $class)
    {
        $expected = 'value not found';
        $copy = $this;
        $copy->get("$this->_test_script?key=test_key&value=test_value&expire=1&handler=".$type);
        sleep(3);
        $this->restart();
        $this->get("$this->_test_script?dumb_call_for_activating_gc&handler=".$type);

        $copy->get("$this->_test_script?key=test_key&handler=".$type);
        $this->assertText($expected,'Session garbage collection is not working correctly');
    }
}

ak_test_case('AkSession_TestCase');

