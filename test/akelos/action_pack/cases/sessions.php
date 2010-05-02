<?php

require_once(dirname(__FILE__).'/../config.php');

class Sessions_TestCase extends AkWebTestCase
{
    public $sessionLife = null;
    public $webserver_enabled;

    public function __construct() {
        parent::__construct();
        $this->webserver_enabled = AkConfig::getOption('webserver_enabled', false);
        AkAdodbCache::install();
        AkDbSession::install();
        $this->_test_script = AkConfig::getOption('testing_url').
        '/action_pack/public/sessions.php';

        if($this->webserver_enabled){
            Ak::url_get_contents($this->_test_script.'?construct=1');
        }
    }

    public function __destruct() {
        if($this->webserver_enabled){
            Ak::url_get_contents($this->_test_script.'?destruct=1');
        }
    }

    public function skip(){
        $this->skipIf(!$this->webserver_enabled, '['.get_class($this).'] Web server not enabled');
    }

    public function test_all_session_handlers() {
        $cacheHandlers = array('cache_lite'=>1);

        if(!(Ak::db() instanceof AkSqliteDbAdapter)) {
            $cacheHandlers['akadodbcache'] = 2;
        }
        if (AkConfig::getOption('memcached_enabled', false)) {
            $cacheHandlers['akmemcache'] = 3;
        }
        $unitTests = array('_Test_open','_Test_read_write','_Test_destroy', '_Test_gc');


        foreach ($cacheHandlers as $class=>$type) {
            foreach ($unitTests as $test) {
                $this->$test($type,$class);
            }
        }
    }

    public function _Test_open($type, $class) {
        $browser = $this->getBrowser();
        $browser = $this->getBrowser();
        $this->get("$this->_test_script?open_check=1&handler=".$type);
        $expected_session_id = $browser->getContentAsText();
        $this->get("$this->_test_script?open_check=1&handler=".$type);
        //$browser->getContentAsText();
        $this->assertText($expected_session_id,'Sessions are not working correctly');
    }

    public function _Test_read_write($type, $class) {
        $expected = 'test_value';
        $this->get("$this->_test_script?key=test_key&value=$expected&handler=".$type);
        $this->get("$this->_test_script?key=test_key&handler=".$type);
        $this->assertText($expected,"($type) Session is not storing values on database correctly when calling ".
        $this->_test_script.'?key=test_key&handler='.$type);
    }

    public function _Test_destroy($type, $class) {
        $expected = 'value not found';
        $this->get("$this->_test_script?key=test_key&value=test_value&handler=".$type);
        $this->get("$this->_test_script?destroy_check=1&handler=".$type);
        $this->get("$this->_test_script?key=test_key&handler=".$type);
        $this->assertText($expected,'session_destroy(); is not working as expected');
    }

    public function _Test_gc($type, $class) {
        $expected = 'value not found';
        $copy = $this;
        $copy->get("$this->_test_script?key=test_key&value=test_value&expire=1&handler=".$type);
        sleep(1);
        $this->restart();
        $this->get("$this->_test_script?dumb_call_for_activating_gc&handler=".$type);

        $copy->get("$this->_test_script?key=test_key&handler=".$type);
        $this->assertText($expected,'Session garbage collection is not working correctly');
    }

    private function _checkIfEnabled($file = null) {
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

}

ak_test_case('Sessions_TestCase');
