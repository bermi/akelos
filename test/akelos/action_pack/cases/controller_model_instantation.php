<?php

require_once(dirname(__FILE__).'/../config.php');

class Controller_model_instantiation_TestCase extends AkWebTestCase
{
    public function test_setup()
    {
        $TestSetup = new AkUnitTest();
        $TestSetup->rebaseAppPaths();
        $TestSetup->installAndIncludeModels(array('Post','Comment'));
        $Post = $TestSetup->Post->create(array('title'=>'One','body'=>'First post'));
        foreach (range(1,5) as $n){
            $Post->comment->add(new Comment(array('body' => AkInflector::ordinalize($n).' post')));
        }
        $Post->save();
        $Post->reload();
        $Post->comment->load();
        $this->assertEqual($Post->comment->count(), 5);
        $this->post_id = $Post->id;

        if(!$this->webserver_enabled = AkConfig::getOption('webserver_enabled', false)){
            echo "Skipping Controller_model_instantiation_TestCase: Webserver no accesible at ".AkConfig::getOption('testing_url')."\n";
        }
        $this->_test_script = AkConfig::getOption('testing_url').
        '/action_pack/public/index.php?ak=';
    }

    public function __destruct()
    {
        $TestSetup = new AkUnitTest();
        $TestSetup->dropTables('all');
    }

    public function test_should_access_public_action()
    {
        if(!$this->webserver_enabled) return ;

        $this->setMaximumRedirects(0);
        $this->get($this->_test_script.'post/comments/'.$this->post_id);
        $this->assertResponse(200);
        $this->assertTextMatch('1st post2nd post3rd post4th post5th post');
    }
}

ak_test_case('Controller_model_instantiation_TestCase');

