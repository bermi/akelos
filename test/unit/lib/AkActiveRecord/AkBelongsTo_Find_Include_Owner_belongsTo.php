<?php

if(!defined('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION')){
    define('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION',false);
}

defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);
require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

class test_AkActiveRecord_belongsTo_Find_Include_Owner_belongsTo extends  AkUnitTest 
{
    /**/
    function test_start()
    {
        $this->installAndIncludeModels(array('Activity', 'Kid','Father'));
    }
/**
 * Need to test:
 * 
 * 1-1-1 (OK)
 * 1-n-m (OK)
 * 1-n-1 (OK)
 * n-m-1
 * n-m-n (OK)
 * n-n-1 
 * n-n-m 
 */
    /**
     * testing automatic loading of BelongsTo->Owner->BelongsTo
     * 
     * will load 2 association leves at once
     */
    function test_1_1_1()
    {
        $Activity =& new Activity(array('name'=>'Test'));
        $Child =& new Kid(array('name'=>'Johanna'));
        $Father =& new Father(array('name'=>'Daddy'));
        
        $Child->father->assign($Father);
        $Activity->kid->assign($Child);
        
        $Child->save();
        $Activity->save();
        
        $Test = &$Activity->findFirstBy('name','Test',array('include'=>array('kid'=>array('include'=>'father'))));
        $this->assertEqual($Test->name,'Test');
        $this->assertEqual($Test->kid->name,'Johanna');
        $this->assertEqual($Test->kid->father->name,'Daddy');

    }

    
    function test_1_n_1()
    {
        $this->installAndIncludeModels(array('Activity', 'Kid','Father'));
        $Activity1 =& new Activity(array('name'=>'Test1'));
        $Activity2 =& new Activity(array('name'=>'Test2'));
        $Child1 =& new Kid(array('name'=>'Johanna'));
        $Child2 =& new Kid(array('name'=>'John'));
        $Father =& new Father(array('name'=>'Daddy'));
        
        $Child1->father->assign($Father);
        $Child2->father->assign($Father);
        $Activity1->kid->assign($Child1);
        $Activity2->kid->assign($Child2);
        $Child1->save();
        $Child2->save();
        $Activity1->save();
        $Activity2->save();
        $Test = &$Father->findFirstBy('name','Daddy',array('include'=>array('kid'=>array('include'=>'activities'))));
        $this->assertEqual($Test->name,'Daddy');
        $this->assertEqual($Test->kids[0]->name,'Johanna');
        $this->assertEqual($Test->kids[1]->name,'John');
        $this->assertEqual($Test->kids[0]->activities[0]->name,'Test1');
        $this->assertEqual($Test->kids[1]->activities[0]->name,'Test2');
        //die;
    }
    
    function test_1_n_m()
    {
        $this->installAndIncludeModels(array('User', 'Post','Comment'));
        $User =& new User(array('name'=>'Arno','email'=>'arno@bermilabs.com'));
        $Post1 =& new Post(array('title'=>'Test1'));
        $Post2 =& new Post(array('title'=>'Test2'));
        $Comment1_1 =& new Comment(array('name'=>'Comment1_1'));
        $Comment1_2 =& new Comment(array('name'=>'Comment1_2'));
        $Comment2_1 =& new Comment(array('name'=>'Comment2_1'));
        $Comment2_2 =& new Comment(array('name'=>'Comment2_2'));
        
        
        $User->post->add($Post1);
        $User->post->add($Post2);
        $Post1->comment->add($Comment1_1);
        $Post1->comment->add($Comment1_2);
        $Post2->comment->add($Comment2_1);
        $Post2->comment->add($Comment2_2);
        $User->save();
        //$Post1->save();
        //$Post2->save();
        $Comment1_1->save();
        $Comment1_2->save();
        $Comment2_1->save();
        $Comment2_2->save();
        
        $Test = &$User->findFirstBy('name','Arno',array('include'=>array('posts'=>array('include'=>'comments'))));
        $this->assertEqual($Test->name,'Arno');
        $this->assertEqual($Test->posts[0]->title,'Test1');
        $this->assertEqual($Test->posts[1]->title,'Test2');
        $this->assertEqual($Test->posts[0]->comments[0]->name,'Comment1_1');
        $this->assertEqual($Test->posts[0]->comments[1]->name,'Comment1_2');
        $this->assertEqual($Test->posts[1]->comments[0]->name,'Comment2_1');
        $this->assertEqual($Test->posts[1]->comments[1]->name,'Comment2_2');
        
        /**
         * singular in "post", plural in "comments"
         */
        $Test = &$User->findFirstBy('name','Arno',array('include'=>array('post'=>array('include'=>'comments'))));
        $this->assertEqual($Test->name,'Arno');
        $this->assertEqual($Test->posts[0]->title,'Test1');
        $this->assertEqual($Test->posts[1]->title,'Test2');
        $this->assertEqual($Test->posts[0]->comments[0]->name,'Comment1_1');
        $this->assertEqual($Test->posts[0]->comments[1]->name,'Comment1_2');
        $this->assertEqual($Test->posts[1]->comments[0]->name,'Comment2_1');
        $this->assertEqual($Test->posts[1]->comments[1]->name,'Comment2_2');
        
        /**
         * plural in "posts", singular in "comment"
         */
        $Test = &$User->findFirstBy('name','Arno',array('include'=>array('posts'=>array('include'=>'comment'))));
        $this->assertEqual($Test->name,'Arno');
        $this->assertEqual($Test->posts[0]->title,'Test1');
        $this->assertEqual($Test->posts[1]->title,'Test2');
        $this->assertEqual($Test->posts[0]->comments[0]->name,'Comment1_1');
        $this->assertEqual($Test->posts[0]->comments[1]->name,'Comment1_2');
        $this->assertEqual($Test->posts[1]->comments[0]->name,'Comment2_1');
        $this->assertEqual($Test->posts[1]->comments[1]->name,'Comment2_2');
        
        /**
         * singular in "post", singular in "post"
         */
        $Test = &$User->findFirstBy('name','Arno',array('include'=>array('post'=>array('include'=>'comment'))));
        $this->assertEqual($Test->name,'Arno');
        $this->assertEqual($Test->posts[0]->title,'Test1');
        $this->assertEqual($Test->posts[1]->title,'Test2');
        $this->assertEqual($Test->posts[0]->comments[0]->name,'Comment1_1');
        $this->assertEqual($Test->posts[0]->comments[1]->name,'Comment1_2');
        $this->assertEqual($Test->posts[1]->comments[0]->name,'Comment2_1');
        $this->assertEqual($Test->posts[1]->comments[1]->name,'Comment2_2');
    }
    function test_n_m_n()
    {
        $this->installAndIncludeModels(array('User', 'Post','Comment'));
        $User1 =& new User(array('name'=>'Arno','email'=>'arno@bermilabs.com'));
        $User2 =& new User(array('name'=>'Arno','email'=>'arno2@bermilabs.com'));
        $Post1 =& new Post(array('title'=>'Test1'));
        $Post2 =& new Post(array('title'=>'Test2'));
        $Post3 =& new Post(array('title'=>'Test3'));
        $Comment1_1 =& new Comment(array('name'=>'Comment1_1'));
        $Comment1_2 =& new Comment(array('name'=>'Comment1_2'));
        $Comment2_1 =& new Comment(array('name'=>'Comment2_1'));
        $Comment2_2 =& new Comment(array('name'=>'Comment2_2'));
        $Comment3_1 =& new Comment(array('name'=>'Comment3_1'));
        $Comment3_2 =& new Comment(array('name'=>'Comment3_2'));
        
        $User1->post->add($Post1);
        $User1->post->add($Post2);
        $User2->post->add($Post3);
        $Post1->comment->add($Comment1_1);
        $Post1->comment->add($Comment1_2);
        $Post2->comment->add($Comment2_1);
        $Post2->comment->add($Comment2_2);
        $Post3->comment->add($Comment3_1);
        $Post3->comment->add($Comment3_2);
        $User1->save();
        $User2->save();
        //$Post1->save();
        //$Post2->save();
        $Comment1_1->save();
        $Comment1_2->save();
        $Comment2_1->save();
        $Comment2_2->save();
        $Comment3_1->save();
        $Comment3_2->save();
        $Test = &$User1->findAllBy('name','Arno',array('include'=>array('posts'=>array('include'=>'comments'))));
        
        $this->assertEqual($Test[0]->email,'arno@bermilabs.com');
        $this->assertEqual($Test[1]->email,'arno2@bermilabs.com');
        $this->assertEqual($Test[0]->posts[0]->title,'Test1');
        $this->assertEqual($Test[0]->posts[1]->title,'Test2');
        $this->assertEqual($Test[0]->posts[0]->comments[0]->name,'Comment1_1');
        $this->assertEqual($Test[0]->posts[0]->comments[1]->name,'Comment1_2');
        $this->assertEqual($Test[0]->posts[1]->comments[0]->name,'Comment2_1');
        $this->assertEqual($Test[0]->posts[1]->comments[1]->name,'Comment2_2');
        $this->assertEqual($Test[1]->posts[0]->title,'Test3');
        $this->assertEqual($Test[1]->posts[0]->comments[0]->name,'Comment3_1');
        $this->assertEqual($Test[1]->posts[0]->comments[1]->name,'Comment3_2');
       //die;
        //die;
    }
        /**/
}


ak_test('test_AkActiveRecord_belongsTo_Find_Include_Owner_belongsTo', true);


?>
