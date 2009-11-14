<?php

class PostController extends ApplicationController
{
    public $finder_options = array('Post'=>array('include'=>'comments', 'order'=>'_comments.id'));

    public $models = 'One,Two,Thumbnail';

    function comments()
    {
        $this->renderText(join('', Ak::collect($this->Post->comments,'id','body')));
    }
}

