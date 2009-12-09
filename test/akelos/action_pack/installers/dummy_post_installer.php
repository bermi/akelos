<?php

class DummyPostInstaller extends AkInstaller
{
    public function install($version = null, $options = array()) {
        $this->createTable('dummy_posts', 'id, title, body, hip_factor int, comments_count, posted_on, expires_at');
        $this->createTable('dummy_comments', 'id,name,body,dummy_post_id,created_at');
        $DummyPost = new DummyPost();
        $Post = $DummyPost->create(array('title'=>'One','body'=>'First post'));
        foreach (range(1,5) as $n){
            $Post->dummy_comment->add(new DummyComment(array('body' => AkInflector::ordinalize($n).' post')));
        }
        $Post->save();
    }

    public function uninstall($version = null, $options = array()) {
        $this->dropTable('dummy_posts', array('sequence'=>true));
        $this->dropTable('dummy_comments');
    }
}

