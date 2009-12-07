<?php

class DummyPostController extends ApplicationController
{
    public $finder_options = array('DummyPost'=>array('include'=>'dummy_comments', 'order'=>'_dummy_comments.id'));

    function comments() {
        $this->renderText(join('', Ak::collect($this->DummyPost->dummy_comments,'id','body')));
    }
}

