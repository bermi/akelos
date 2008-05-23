<?php

defined('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION') ? null : define('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION', false);
defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);

require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

class AkActiveRecord_finders_TestCase extends  AkUnitTest
{

    function setup()
    {
        $this->installAndIncludeModels(array('Post', 'Tag', 'Comment'));
        $Installer = new AkInstaller();
        @$Installer->dropTable('posts_tags');
        @Ak::file_delete(AK_MODELS_DIR.DS.'post_tag.php');
    }

    function test_should_find_using_id_and_options()
    {
        $Tag =& new Tag();

        $One =& $Tag->create(array('name' => 'One'));
        $Two =& $Tag->create(array('name' => 'Two'));

        //find by id is always 'first'; API-change
        //$Found =& $Tag->find('first', $Two->getId(), array('order'=>'name'));
        $Found =& $Tag->find($Two->getId(), array('order'=>'name'));

        $this->assertEqual($Found->getId(), $Two->getId());

    }

    function test_should_not_return_duplicated_owners_when_including_multiple_associates()
    {
        $Post =& new Post(array('title' => 'The best PHP Framework is ...'));
        $Post->comment->create(array('name'=>'Comment 1'));
        $Post->comment->create(array('name'=>'Comment 2'));
        $Post->tag->create(array('name'=>'Tag 1'));
        $Post->tag->create(array('name'=>'Tag 2'));

        $this->assertTrue($Post->save());

        // on PostgreSQL we get an unordered comments-list
        $this->assertTrue($Post =& $Post->find($Post->getId(), array('include'=>array('comments', 'tags'))));
        $exptected = array('Comment 1','Comment 2');
        $this->assertTrue(in_array($Post->comments[0]->get('name'),$exptected));
        $this->assertTrue(in_array($Post->comments[1]->get('name'),$exptected));
        
        // so we could do this 
        $this->assertTrue($Post =& $Post->find($Post->getId(), array('include'=>array('comments', 'tags'),'order'=>'_comments.id ASC, _tags.id ASC')));
        $this->assertEqual(count($Post->comments), 2);
        $this->assertEqual($Post->comments[0]->get('name'), 'Comment 1');
        $this->assertEqual($Post->comments[1]->get('name'), 'Comment 2');

        $this->assertEqual(count($Post->tags), 2);
        $this->assertEqual($Post->tags[0]->get('name'), 'Tag 1');
        $this->assertEqual($Post->tags[1]->get('name'), 'Tag 2');

    }

    function test_should_parse_include_as_array()
    {
        $Post =& new Post(array('title' => 'PHP Frameworks'));
        $Post->comment->create(array('name'=>'Comment 1'));
        $Post->comment->create(array('name'=>'Comment 2'));
        $Post->tag->create(array('name'=>'Tag 1'));
        $Post->tag->create(array('name'=>'Tag 2'));

        $this->assertTrue($Post->save());
        
        $this->assertTrue($Post =& $this->Post->find($Post->getId(), array('include'=>'comments,tags','order'=>'_comments.id ASC, _tags.id ASC')));
        
        $this->assertEqual($Post->tags[0]->name, 'Tag 1');
        $this->assertEqual($Post->tags[1]->name, 'Tag 2');

        $this->assertEqual($Post->comments[0]->name, 'Comment 1');
        $this->assertEqual($Post->comments[1]->name, 'Comment 2');
    }


}

ak_test('AkActiveRecord_finders_TestCase',true);

?>
