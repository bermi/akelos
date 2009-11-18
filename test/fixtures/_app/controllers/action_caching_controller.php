<?php

class ActionCachingController extends ApplicationController
{
    var $caches_action = array('index',
                               'redirected',
                               'forbidden',
                               'show'=>array('cache_path'=>'http://test.host/custom/show'),
                               'edit'=>array('cache_path'=>'http://test.host/edit'),
                               'skip'
                               );
    function _initActionCache()
    {
        if (!empty($this->params['id'])) {
            $this->caches_action['edit'] = array('cache_path'=>'http://test.host/'.$this->params['id'].';edit');
        }
    }
    
    function _initExtensions()
    {
        $this->_initActionCache();
        parent::_initExtensions();
    }
    function index()
    {
        
        $this->renderText($this->cache_this);
        
    }
    
    function redirected()
    {
        $this->redirectToAction('index');
    }
    function forbidden()
    {
        $this->renderText('Forbidden',403);
    }
    function skip()
    {
        $this->renderText('Hello<!--CACHE-SKIP-START-->
        
        You wont see me after the cache is rendered.
        
        <!--CACHE-SKIP-END-->');
    }
    function expire()
    {
        $this->expireAction(array('controller'=>'action_caching','action'=>'index','lang'=>Ak::lang()));
        $this->expireAction(array('controller'=>'action_caching','action'=>'skip'));
        $this->renderNothing(200);
    }
    
    function show()
    {
        $this->performActionWithoutFilters('index');
    }
    
    function edit()
    {
        $this->performActionWithoutFilters('index');
    }
    

}