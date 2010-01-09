<?php

require_once(dirname(__FILE__).'/../router.php');
require_once(dirname(__FILE__).'/../../lib/ideal_world.php');

class IdealWorldNamedRoutes_TestCase extends IdealWorldUnitTest
{
    public $Routes = array(
        'author' =>array('/author/:name',array('controller'=>'author','action'=>'show','name'=>COMPULSORY)),
        'default'=>array('/:controller/:action/:id',array('controller'=>COMPULSORY,'action'=>'index')),
        'root'   =>array('/',array('controller'=>'blog','action'=>'index'))
    );
    
    public function testDefaultRoute() {
        $url_writer = $this->withRequestTo('/user');
        $this->assertEqual('http://localhost/user/show/1',default_url(array('action'=>'show','id'=>'1')));
    }
    
    public function testChangeLanguage() {
        $url_writer = $this->withRequestTo('/en/user/show/1');
        $this->assertEqual('/es/user/show/1',default_path(array('overwrite_params'=>array('lang'=>'es'))));
    }
    
    public function testFromDefaultToAuthor() {
        $this->withRequestTo('/user');
        $this->assertEqual('http://localhost/author/mart',author_url(array('name'=>'mart')));
    }

    public function testFromAuthorToRoot() {
        $this->withRequestTo('/author/steve');
        $this->assertEqual('http://localhost/',root_url());
    }
    
    public function testFromRootToAuthorPath() {
        $this->withRequestTo('/');
        $this->assertEqual('/author/steve',author_path(array('name'=>'steve')));
    }
}

ak_test_case('IdealWorldNamedRoutes_TestCase');