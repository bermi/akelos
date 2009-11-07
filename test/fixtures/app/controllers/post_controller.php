<?php

class PostController extends ApplicationController  
{
    var $finder_options = array('Post'=>array('include'=>'comments','order'=>'_comments.id'));
    
    function comments()
    {
        $this->renderText(join('', Ak::collect($this->post->comments,'id','body')));
    }
}


?>