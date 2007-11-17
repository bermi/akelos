<?php

defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);
require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

class _AkActionController_model_instantiation extends AkWebTestCase
{
    function test_setup()
    {
        $TestSetup = new AkUnitTest();
        $TestSetup->installAndIncludeModels(array('Post','Comment','Tag'));
        $Post =& $TestSetup->Post->create(array('title'=>'One','body'=>'First post'));
        foreach (range(1,5) as $n){
            $Post->comment->add(new Comment(array('body' => AkInflector::ordinalize($n).' post')));
        }
        $Post->save();
        $Post->reload();
        $Post->comment->load();
        $this->assertEqual($Post->comment->count(), 5);
        $this->post_id = $Post->id;
    }
    
    function test_should_access_public_action()
    {
        $this->setMaximumRedirects(0);
        $this->get(AK_TESTING_URL.'/post/comments/'.$this->post_id);
        $this->assertResponse(200);
        $this->assertTextMatch('1st post2nd post3rd post4th post5th post');
    }
}

ak_test('_AkActionController_model_instantiation', true);

?>
