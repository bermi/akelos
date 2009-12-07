<?php

require_once(dirname(__FILE__).'/../config.php');

class HttpClient_TestCase extends ActiveResourceUnitTest
{
    public $url = '';
    public $verbs = array('get', 'post', 'put', 'delete');
    public $ht_access_path = '';
    public $original_ht_access = '';
    public $webserver_enabled = false;

    function __construct() {
        if(!$this->webserver_enabled = AkConfig::getOption('webserver_enabled', false)){
            return;
        }
        $this->url = AkConfig::getOption('testing_url').
        '/active_resource/public/index.php?ak=http_requests';
        parent::__construct();
    }

    public function skip(){
        $this->skipIf(!$this->webserver_enabled, '['.get_class($this).'] Web server not enabled.');
    }

    public function setup() {
        $this->Client = new AkHttpClient();
    }

    public function  test_get_verb() {
        $this->assertEqual($this->Client->get($this->url), 'Hello unit tester');
        $this->assertEqual($this->Client->get($this->url.'/verb'), 'get');
        $this->assertEqual(Ak::url_get_contents($this->url.'/verb'), 'get');
    }

    public function  test_post_verb() {
        $this->assertEqual($this->Client->post($this->url.'/verb'), 'post');
        $this->assertEqual(Ak::url_get_contents($this->url.'/verb', array('method'=>'post')), 'post');
    }

    public function  test_put_verb() {
        $this->assertEqual($this->Client->put($this->url.'/verb'), 'put');
        $this->assertEqual(Ak::url_get_contents($this->url.'/verb', array('method'=>'put')), 'put');
    }

    public function  test_delete_verb() {
        $this->assertEqual($this->Client->delete($this->url.'/verb'), 'delete');
        $this->assertEqual(Ak::url_get_contents($this->url.'/verb', array('method'=>'delete')), 'delete');
    }

    public function test_should_get_response_header() {
        $this->Client->get($this->url.'/test_header');
        $this->assertEqual($this->Client->getResponseHeader('x-test-header'), 'akelos');
    }

    public function test_should_get_response_code() {
        $this->Client->get($this->url.'/code/201');
        $this->assertEqual($this->Client->getResponseCode(), 201);
    }

    public function test_should_set_user_agent() {
        $this->assertEqual($this->Client->get($this->url.'/get_user_agent'), 'Akelos PHP Framework AkHttpClient (http://akelos.org)');
        $this->assertEqual(Ak::url_get_contents($this->url.'/get_user_agent'), 'Akelos PHP Framework AkHttpClient (http://akelos.org)');
        $this->assertEqual(Ak::url_get_contents($this->url.'/get_user_agent',
        array('header'=> array('user-agent'=>'Testing agent'))), 'Testing agent');
    }


    public function test_should_send_params() {
        $params = array('testing'=>array('user'=>'bermi','nested'=>array('one','two')));
        $expected = Ak::toJson($params['testing']);

        $query = http_build_query($params);

        foreach ($this->verbs as $verb){
            $this->assertEqual($this->Client->$verb($this->url.'/json/&'.$query), $expected, "$verb passing params via url");
            $this->assertEqual($this->Client->$verb($this->url.'/json', array('params'=>$params)), $expected, "$verb passing params via params option");
        }
    }

    public function test_should_accept_redirects() {
        $this->assertEqual(Ak::url_get_contents($this->url.'/redirect_1'), 3);
    }

    public function test_should_keep_cookies() {
        $this->assertEqual(Ak::url_get_contents($this->url.'/persisting_cookies', array('cookies' => false)), 1);
        $this->assertEqual(Ak::url_get_contents($this->url.'/persisting_cookies', array('cookies' => true)), 1);
        $this->assertEqual(Ak::url_get_contents($this->url.'/persisting_cookies', array('cookies' => true)), 2);
        $this->assertEqual(Ak::url_get_contents($this->url.'/persisting_cookies', array('cookies' => true)), 3);
        $this->assertEqual(Ak::url_get_contents($this->url.'/persisting_cookies'), 1);
        $this->assertEqual(Ak::url_get_contents($this->url.'/persisting_cookies', array('cookies' => false)), 1);
        $this->assertEqual(Ak::url_get_contents($this->url.'/persisting_cookies', array('cookies' => true)), 1);
    }
}

ak_test_case('HttpClient_TestCase');

