<?php

require_once(dirname(__FILE__).'/../config.php');

class RestfulRequests_TestCase extends ActionPackUnitTest
{
    public $webserver_enabled;

    public function __construct() {
        $this->webserver_enabled = AkConfig::getOption('webserver_enabled', false);
        parent::__construct();
        $this->_test_script = AkConfig::getOption('testing_url').
        '/action_pack/public/index.php?custom_routes=restful&ak=';
    }
    
    public function skip(){
        $this->skipIf(!$this->webserver_enabled, '['.get_class($this).'] Web server not enabled');
    }

    public function testIndexRespondsWithXmlContent() {
        
        $Http = new AkHttpClient();
        $options = array('header'=>array('accept'=>'text/xml'));
        $result = $Http->get($this->_test_script.'people',$options);
        $headers = $Http->getResponseHeaders();
        $this->assertEqual('application/xml',$headers['content-type']);
    }
    

    public function testPutPersonOnTheServerViaXml() {
        $person = '<person><name>Steve</name></person>';
        $Http = new AkHttpClient();
        $options = array('header'=>array(
            'content-type'=>'text/xml',
        ));

        $result = $Http->put($this->_test_script.'people/1', $options, $person);
        $this->assertEqual('Steve',$result);
    }
    
    public function testPutPersonOnTheServerViaWwwForm() {
        $person = array('person'=>array('name'=>'Steve'));
        $Http = new AkHttpClient();

        $options['params'] = $person;
        $result = $Http->put($this->_test_script.'people/1',$options);
        $this->assertEqual('Steve',$result);
    }


    public function testPostPersonOnTheServerViaXml() {
        $person = '<person><name>Steve</name></person>';
        $Http = new AkHttpClient();
        $options = array('header'=>array(
            'content-type'=>'text/xml',
        ));
        $result = $Http->post($this->_test_script.'people',$options,$person);
        $this->assertEqual('Steve',$result);
    }

    public function testPostPersonOnTheServerViaWwwForm() {
        $person = array('person'=>array('name'=>'Steve'));
        $Http = new AkHttpClient();

        $options['params'] = $person;
        $result = $Http->post($this->_test_script.'people', $options);
        $this->assertEqual('Steve',$result);
    }

    public function testFileUpload() {
        $Http = new AkHttpClient();
        $options['params'] = array('photo'=>array('title'=>'My Photo.'));
        $options['file'] = array('inputname'=>'photo','filename'=>__FILE__);
        $result = $Http->post($this->_test_script.'people/1/photo',$options);
        $this->assertEqual("My Photo.|".basename(__FILE__),$result);
    }

}

ak_test_case('RestfulRequests_TestCase');

