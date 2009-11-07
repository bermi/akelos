<?php

require_once(dirname(__FILE__).'/../../fixtures/config/config.php');

require_once(AK_LIB_DIR.DS.'AkRouter.php');

class AkRouterTestCase extends  AkUnitTest 
{

    public $Router;
    public $url_prefix = '';

    public function __construct()
    {
        $this->Router = new AkRouter();

        $this->Router->_loadUrlRewriteSettings();
        $this->url_prefix = AK_URL_REWRITE_ENABLED ? '' : '/?ak=';

        $this->Router->connect('/topic/:id', array('controller' => 'topic', 'action'=>'view', 'id'=>COMPULSORY), array('id'=>'[0-9]+'));
        $this->Router->connect('/topic/:id/unread', array('controller' => 'topic','action'=>'unread','id'=>COMPULSORY), array('id'=>'[0-9]+'));

        $this->Router->connect('/lists/:action/:id/:option', array('controller'=>'todo','option'=>COMPULSORY));
        $this->Router->connect('/setup/*config_settings',array('controller'=>'setup'));
        $this->Router->connect('/redirect/:url',array('controller'=>'redirect'));
        $this->Router->connect('/regex/:text/:int',array('text'=>'/[A-Za-z]+/','int'=>'/[0-9]+/','controller'=>'regex'));
        $this->Router->connect('/customize/*options/:action',array('controller'=>'themes','options'=>3));
        $this->Router->connect('/blog/:action/:id',array('controller'=>'post','action'=>'list','id'=>OPTIONAL, 'requirements'=>array('id'=>'/\d{1,}/')));
        $this->Router->connect('/:year/:month/:day',
        array('controller' => 'articles','action' => 'view_headlines','year' => COMPULSORY,'month' => 'all','day' => OPTIONAL) ,
        array('year'=>'/(20){1}\d{2}/','month'=>'/((1)?\d{1,2}){2}/','day'=>'/(([1-3])?\d{1,2}){2}/'));
        $this->Router->connect('/:webpage', array('controller' => 'page', 'action' => 'view_page', 'webpage' => 'index'),array('webpage'=>'/(\w|_)+/'));
        $this->Router->connect('/', array('controller' => 'page', 'action' => 'view_page', 'webpage'=>'index'));
        $this->Router->connect('/:controller/:action/:id');
    }


    public function Test_connect()
    {
        $this->assertEqual(count($this->Router->getRoutes()) , 12,'Wrong number of routes loaded. We expected 12');
    }

    public function Test_toParams()
    {
        $input_value = '/lists/show/123/featured=1';
        $expected = array('controller'=>'todo','action'=>'show','id'=>123,'option'=>'featured=1');
        $this->assertEqual($this->Router->toParams($input_value),$expected);

        $input_value = '/lists/show/123';
        $expected = array('controller'=>'lists','action'=>'show','id'=>123);
        $this->assertEqual($this->Router->toParams($input_value),$expected);


        $input_value = '/redirect/'.urlencode('http://www.akelos.com/buscar_dominio');
        $expected = array('controller'=>'redirect','url'=>'http://www.akelos.com/buscar_dominio');
        $this->assertEqual($this->Router->toParams($input_value),$expected);

        $input_value = '/regex/abc/123';
        $expected = array('controller'=>'regex','text'=>'abc','int'=>'123');
        $this->assertEqual($this->Router->toParams($input_value),$expected);

        $input_value = '/regex/abc1/123';
        $not_expected = array('controller'=>'regex','text'=>'abc1','int'=>'123');
        $this->assertNotEqual($this->Router->toParams($input_value),$not_expected);

        $input_value = '/regex/abc/text';
        $not_expected = array('controller'=>'regex','text'=>'abc','int'=>'text');
        $this->assertNotEqual($this->Router->toParams($input_value),$not_expected);

        $input_value = '/contact_us';
        $expected = array('controller'=>'page','action'=>'view_page','webpage'=>'contact_us');
        $this->assertEqual($this->Router->toParams($input_value),$expected);

        $input_value = '/';
        $expected = array('controller'=>'page','action'=>'view_page','webpage'=>'index');
        $this->assertEqual($this->Router->toParams($input_value),$expected);

        $input_value = '';
        $expected = array('controller'=>'page','action'=>'view_page','webpage'=>'index');
        $this->assertEqual($this->Router->toParams($input_value),$expected);

        $input_value = '/blog/';
        $expected = array('controller'=>'post','action'=>'list','id'=>null);
        $this->assertEqual($this->Router->toParams($input_value),$expected);


        $input_value = '/blog/view';
        $expected = array('controller'=>'post','action'=>'view','id'=>null);
        $this->assertEqual($this->Router->toParams($input_value),$expected);

        $input_value = '/blog/view/10/';
        $expected = array('controller'=>'post','action'=>'view','id'=>'10');
        $this->assertEqual($this->Router->toParams($input_value),$expected);

        $input_value = '/blog/view/newest/';
        $expected = array('controller'=>'blog','action'=>'view','id'=>'newest');
        $this->assertEqual($this->Router->toParams($input_value),$expected);

        $input_value = '/2005/10/';
        $expected = array('controller' => 'articles','action' => 'view_headlines','year' => '2005','month' => '10', 'day' => null);
        $this->assertEqual($this->Router->toParams($input_value),$expected);

        $input_value = '/2006/';
        $expected = array('controller' => 'articles','action' => 'view_headlines','year' => '2006','month' => 'all', 'day' => null);
        $this->assertEqual($this->Router->toParams($input_value),$expected);

        $input_value = '/user/list/12';
        $expected = array('controller' => 'user','action' => 'list','id' => '12');
        $this->assertEqual($this->Router->toParams($input_value),$expected);

        $input_value = '/setup/themes/clone/12/';
        $expected = array('controller' => 'setup','config_settings' => array('themes','clone','12'));
        $this->assertEqual($this->Router->toParams($input_value),$expected);

        $input_value = '/customize/blue/css/sans_serif/clone/';
        $expected = array('controller' => 'themes','options' => array('blue','css','sans_serif'), 'action'=>'clone');
        $this->assertEqual($this->Router->toParams($input_value),$expected);

        $input_value = '/customize/blue/css/invalid/sans_serif/clone/';
        $not_expected = array('controller' => 'themes','options' => array('blue','css','invalid','sans_serif'), 'action'=>'clone');
        $this->assertNotEqual($this->Router->toParams($input_value),$not_expected);

    }


    public function Test_toUrl()
    {
        $input_value = array('controller'=>'page','action'=>'view_page','webpage'=>'index');
        $expected = '/';
        $this->assertEqual($this->Router->toUrl($input_value),$expected);

        $input_value = array('controller'=>'page','action'=>'view_page','webpage'=>'contact_us');
        $expected = $this->url_prefix.'/contact_us/';
        $this->assertEqual($this->Router->toUrl($input_value),$expected);


        $input_value = array('controller'=>'post','action'=>'list','id'=>null);
        $expected = $this->url_prefix.'/blog/';
        $this->assertEqual($this->Router->toUrl($input_value),$expected);

        $input_value = array('controller'=>'post','action'=>'view','id'=>null);
        $expected = $this->url_prefix.'/blog/view/';
        $this->assertEqual($this->Router->toUrl($input_value),$expected);

        $input_value = array('controller'=>'error','action'=>'database', 'id'=>null);
        $expected = $this->url_prefix.'/error/database/';
        $this->assertEqual($this->Router->toUrl($input_value),$expected);

        $input_value = array('controller'=>'post','action'=>'view','id'=>'10');
        $expected = $this->url_prefix.'/blog/view/10/';
        $this->assertEqual($this->Router->toUrl($input_value),$expected);

        $input_value = array('controller'=>'blog','action'=>'view','id'=>'newest');
        $expected = $this->url_prefix.'/blog/view/newest/';
        $this->assertEqual($this->Router->toUrl($input_value),$expected);

        $input_value = array('controller'=>'blog','action'=>'view','id'=>'newest','format'=>'printer_friendly');
        $expected = AK_URL_REWRITE_ENABLED ? '/blog/view/newest.printer_friendly' : '/?ak=/blog/view/newest.printer_friendly';
        $this->assertEqual($this->Router->toUrl($input_value),$expected);

        $input_value = array('controller' => 'articles','action' => 'view_headlines','year' => '2005','month' => '10', 'day' => null);
        $expected = $this->url_prefix.'/2005/10/';
        $this->assertEqual($this->Router->toUrl($input_value),$expected);

        $input_value = array('controller' => 'articles','action' => 'view_headlines','year' => '2006','month' => 'all', 'day' => null);
        $expected = $this->url_prefix. '/2006/';
        $this->assertEqual($this->Router->toUrl($input_value),$expected);

        $input_value = array('controller' => 'user','action' => 'list','id' => '12');
        $expected = $this->url_prefix.'/user/list/12/';
        $this->assertEqual($this->Router->toUrl($input_value),$expected);

        $input_value = array('controller' => 'setup','config_settings' => array('themes','clone','12'));
        $expected = $this->url_prefix.'/setup/themes/clone/12/';
        $this->assertEqual($this->Router->toUrl($input_value),$expected);

        $input_value = array('controller' => 'themes','options' => array('blue','css','sans_serif'), 'action'=>'clone');
        $expected = $this->url_prefix.'/customize/blue/css/sans_serif/clone/';
        $this->assertEqual($this->Router->toUrl($input_value),$expected);

    }

    public function test_url_with_optional_variables()
    {
        $input_value = array('controller'=>'topic','action'=>'view', 'id'=>4);
        $expected = $this->url_prefix.'/topic/4/';
        $this->assertEqual($this->Router->toUrl($input_value),$expected);

        $input_value = array('controller'=>'topic','action'=>'unread', 'id'=>4);
        $expected = $this->url_prefix.'/topic/4/unread/';
        $this->assertEqual($this->Router->toUrl($input_value),$expected);

    }
}

class DefaultRoutesTestCase extends  AkUnitTest 
{

    public $Router;
    public $url_prefix = '';

    public function __construct()
    {
        $this->Router = new AkRouter();

        $this->Router->_loadUrlRewriteSettings();
        $this->url_prefix = AK_URL_REWRITE_ENABLED ? '' : '/?ak=';

        $this->Router->connect('/:controller/:action/:id', array('controller' => 'page', 'action' => 'index'));
        $this->Router->connect('/', array('controller' => 'page', 'action' => 'index'));
    }

    public function Test_connect()
    {
        $this->assertEqual(count($this->Router->getRoutes()) , 2,'Wrong number of routes loaded. We expected 12');
    }

    public function Test_toUrl()
    {
        $input_value = array('controller'=>'page','action'=>'listing');
        $expected = '/page/listing/';
        $this->assertEqual($this->Router->toUrl($input_value),$expected);
    }

}

# Fixes issue 27 reported by Jacek Jedrzejewski
class UrlConstantsNamedAsUrlVariablesTestCase extends  AkUnitTest 
{

    public $Router;
    public $url_prefix = '';

    public function setup()
    {
        $this->Router = new AkRouter();
        $this->Router->_loadUrlRewriteSettings();
        $this->url_prefix = AK_URL_REWRITE_ENABLED ? '' : '/?ak=';
    }

    public function test_same_pieces_1()
    {
        $this->Router->connect('/foo/id/:id', array('controller'=>'some'), array('id'=>'[0-9]+'));
        $this->assertEqual($this->Router->toParams('/foo/id/1'), array('controller'=>'some', 'id'=>'1'));
    }

    public function test_same_pieces_4()
    {
        $this->Router->connect('/foo/bar/*bar', array('controller'=>'some'));
        $this->assertEqual($this->Router->toParams('/foo/bar/foobar'), array ('bar' => array ( 0 => 'foobar'), 'controller' => 'some'));
        $this->assertEqual($this->Router->toParams('/foo/bar/foobar/foobar2'), array('controller'=>'some', 'bar'=>array(0=>'foobar',1=>'foobar2')));
    }

    public function test_same_pieces_5()
    {
        $this->Router->connect('/foo/bar/*bar', array('controller'=>'some', 'bar'=>1));
        $this->assertEqual($this->Router->toParams('/foo/bar/foobar'), array('controller'=>'some', 'bar'=>array(0=>'foobar')));
    }

    public function test_same_pieces_6()
    {
        $this->Router->connect('/foo/:bar',	array('variable'=>'ok'));
        $this->Router->connect('/baz/:bar',	array('variable2'=>'ok', 'bar'=>COMPULSORY));
        $this->Router->connect('/:controller');

        $this->assertEqual($this->Router->toParams('/foo/baz'), array('variable'=>'ok','bar'=>'baz'));
        $this->assertEqual($this->Router->toParams('/abc'), array('controller'=>'abc'));
        $this->assertEqual($this->Router->toParams('/fooabc'), array('controller'=>'fooabc'));
        $this->assertEqual($this->Router->toParams('/baz/bar'), array('variable2'=>'ok','bar'=>'bar'));
        $this->assertEqual($this->Router->toParams('/bazabc'), array('controller'=>'bazabc'));
    }
}


class MiddleOptionalValuesWhenGeneratingUrlsTestCase extends  AkUnitTest 
{
    public $Router;
    public $url_prefix = '';

    public function setup()
    {
        $this->Router = new AkRouter();
        $this->Router->_loadUrlRewriteSettings();
        $this->url_prefix = AK_URL_REWRITE_ENABLED ? '' : '/?ak=';
    }

    public function test_middle_values()
    {
        $this->Router->connect('/news/feed/:type/:category',
        array('controller'=>'news','action'=>'feed','type'=>'atom','category'=>'all'));


        $input_value = array('controller'=>'news','action'=>'feed','type'=>'atom','category'=>'foobar');
        $expected = $this->url_prefix.'/news/feed/atom/foobar/';
        $this->assertEqual($this->Router->toUrl($input_value),$expected);

        $input_value = array('controller'=>'news','action'=>'feed');
        $expected = $this->url_prefix.'/news/feed/';
        $this->assertEqual($this->Router->toUrl($input_value),$expected);
    }
}



class RouterConflictsTestCase extends  AkUnitTest 
{

    public $Router;
    public $url_prefix = '';

    public function __construct()
    {
        $this->Router = new AkRouter();
        $this->Router->_loadUrlRewriteSettings();
        $this->url_prefix = AK_URL_REWRITE_ENABLED ? '' : '/?ak=';
        $this->Router->connect('/:controller/:action/:value');
    }

    public function test_should_allow_variables_with_slashes()
    {
        $params = array('controller'=>'page','action'=>'redirect', 'value'=>'http://akelos.org/download/');
        $url = '/page/redirect/http%3A%2F%2Fakelos.org%2Fdownload%2F/';
        $this->assertEqual($this->Router->toUrl($params), $url);
        $this->assertEqual($this->Router->toParams($this->url_prefix.$url), $params);
    }

    public function test_should_trigger_error_on_forbidden_router_variable()
    {
        $this->Router->connect('/:this');
        $this->assertErrorPattern('/reserved word this/');
    }
}

class RouterModulesTestCase extends  AkUnitTest 
{

    public $Router;
    public $url_prefix = '';

    public function __construct()
    {
        $this->Router = new AkRouter();
        $this->Router->_loadUrlRewriteSettings();
        $this->url_prefix = AK_URL_REWRITE_ENABLED ? '' : '/?ak=';
        $this->Router->connect('/module_alias/:controller/:action/:id', array('controller' => 'test', 'action' => 'index', 'module'=>'testing_module'));
    }

    public function test_should_set_underscored_module()
    {
        $params = array('controller'=>'test','action'=>'show', 'id'=> 123, 'module' => 'testing_module');
        $url = '/module_alias/test/show/123/';
        $this->assertEqual($this->Router->toUrl($params), $url);
        $this->assertEqual($this->Router->toParams($this->url_prefix.$url), $params);

        $params = array('controller'=>'other','module' => 'testing_module');
        $url = '/module_alias/other/';
        $this->assertEqual($this->Router->toUrl($params), $url);
        $this->assertEqual(array_diff($this->Router->toParams($this->url_prefix.$url), array('')), array('controller'=>'other','module' => 'testing_module', 'action' => 'index'));
    }
}

?>
