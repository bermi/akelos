<?php

require_once(dirname(__FILE__).'/../../fixtures/config/config.php');

require_once(AK_LIB_DIR.DS.'AkRequest.php');

class Test_of_AkRequest_Class extends  UnitTestCase
{
    var $test_request;
    var $test_request2;
    var $test_request3;
    var $test_request4;
    var $test_request5;

    var $_testRequestInstance;
    var $_original_values;

    function setUp()
    {
        $sess_request = isset($_SESSION['request']) ? $_SESSION['request'] : null;
        $this->_original_values = array($sess_request, $_COOKIE, $_POST, $_GET, $_REQUEST);

        $_SESSION['request'] = array(
        'session_param'=>'session',
        'ak'=>'/session_controller/session_action',
        'general_param'=>'session'
        );

        $_COOKIE = array(
        'cookie_param'=>'cookie',
        'session_param'=>'cookie',
        'general_param'=>'cookie'
        );

        $_POST = array(
        'post_param'=>'post',
        'cookie_param'=>'post',
        'session_param'=>'post',
        'general_param'=>'post'
        );

        $_GET = array(
        'get_param'=>'get',
        'ak'=>'/get_controller/get_action',
        'post_param'=>'get',
        'cookie_param'=>'get',
        'session_param'=>'get',
        'general_param'=>'get',
        'gpc_param'=>'Isn\\\'t it ironic'
        );

        $_REQUEST = array(
        'cmd_param'=>'cmd',
        'get_param'=>'cmd',
        'post_param'=>'cmd',
        'cookie_param'=>'cmd',
        'session_param'=>'cmd',
        'general_param'=>'cmd'
        );



        $this->_testRequestInstance =& new AkRequest();
        $this->_testRequestInstance->init();

    }

    function tearDown()
    {
        unset($this->_testRequestInstance);

        //We reset the original values
        $_SESSION['request'] = $this->_original_values[0];
        $_COOKIE = $this->_original_values[1];
        $_POST = $this->_original_values[2];
        $_GET = $this->_original_values[3];
        $_REQUEST = $this->_original_values[4];
    }


    function Test_mergeRequest()
    {
        $gpc_param = get_magic_quotes_gpc() ? "Isn't it ironic" : 'Isn\\\'t it ironic';

        $expected = array(
        'cmd_param'=>'cmd',
        'get_param'=>'get',
        'post_param'=>'post',
        'cookie_param'=>'cookie',
        'session_param'=>'session',
        'general_param'=>'session',
        'ak'=>'/session_controller/session_action',
        'gpc_param'=>$gpc_param,
        );

        $this->_testRequestInstance->_mergeRequest();

        $this->assertEqual($this->_testRequestInstance->_request,$expected,'Comparing request precedence');
    }


    function Test_parseAkRequestString()
    {
        $expected_values = array('user','list','100');

        $this->assertEqual($this->_testRequestInstance->_parseAkRequestString('/user/list/100'), $expected_values);
        $this->assertEqual($this->_testRequestInstance->_parseAkRequestString('/user/list/100/'), $expected_values);
        $this->assertEqual($this->_testRequestInstance->_parseAkRequestString('user/list/100/'), $expected_values);
        $this->assertEqual($this->_testRequestInstance->_parseAkRequestString('user/list/100'), $expected_values);

        $expected_keys = array('controller','action','id');
        $this->assertEqual($this->_testRequestInstance->_parseAkRequestString('/:controller/:action/:id','/:'), $expected_keys);
        $this->assertEqual($this->_testRequestInstance->_parseAkRequestString('/:controller/:action/:id/:','/:'), $expected_keys);
        $this->assertEqual($this->_testRequestInstance->_parseAkRequestString('controller/:action/:id/:','/:'), $expected_keys);
        $this->assertEqual($this->_testRequestInstance->_parseAkRequestString('controller/:action/:id','/:'), $expected_keys);
    }



    function test_for_getRemoteIp()
    {
        $Request = new AkRequest();

        $Request->env = array('HTTP_CLIENT_IP'=>'64.68.15.10');
        $this->assertEqual($Request->getRemoteIp(),'64.68.15.10');

        $Request->env = array('HTTP_X_FORWARDED_FOR'=>'64.68.15.11');
        $this->assertEqual($Request->getRemoteIp(),'64.68.15.11');

        $Request->env = array('HTTP_X_FORWARDED_FOR'=>'364.68.15.11');
        $this->assertEqual($Request->getRemoteIp(),'');

        $Request->env = array('HTTP_X_FORWARDED_FOR'=>'3e4f:123f:c12a:5566:888e:9975:aaff:2344');
        $this->assertEqual($Request->getRemoteIp(),'3e4f:123f:c12a:5566:888e:9975:aaff:2344');

        $Request->env = array('HTTP_X_FORWARDED_FOR'=>'3e4f:123f:c12a:5566:888e:9975:aafg:2344');
        $this->assertEqual($Request->getRemoteIp(),'');

        $Request->env = array('HTTP_X_FORWARDED_FOR'=>'364.68.15.11,64.68.15.11');
        $this->assertEqual($Request->getRemoteIp(),'64.68.15.11');

        $Request->env = array('REMOTE_ADDR'=>'64.68.15.11');
        $this->assertEqual($Request->getRemoteIp(),'64.68.15.11');
    }

    function test_for_getDomain()
    {
        $Request = new AkRequest();

        $env_backup = $Request->env;

        $Request->env['SERVER_NAME'] = 'localhost';
        $Request->env['SERVER_ADDR'] = '127.0.0.1';

        $this->assertEqual($Request->getDomain(), 'localhost');

        $Request->_host = 'www.dev.akelos.com';

        $this->assertEqual($Request->getDomain(),'akelos.com');
        $this->assertEqual($Request->getDomain(2),'dev.akelos.com');

        $Request->env = $env_backup;
    }

    function test_for_getSubDomains()
    {
        $Request = new AkRequest();

        $env_backup = $Request->env;

        $Request->_host = 'www.dev.akelos.com';

        $this->assertEqual($Request->getSubdomains(), array('www','dev'));
        $this->assertEqual($Request->getSubdomains(2),array('www'));

        $Request->env = $env_backup;
    }

    
    function test_should_normalize_single_level_file_uploads()
    {
        $Request = new AkRequest();
        $_FILES = array ( 
            'image0' => array ( 'name' => 'mod_rewrite_cheat_sheet.pdf', 'type' => 'application/pdf', 'tmp_name' => '/tmp/php0JvZ0p', 'error' => 0, 'size' => 332133 ), 
            'image1' => array ( 'name' => 'microformats_cheat_sheet.pdf', 'type' => 'application/pdf', 'tmp_name' => '/tmp/phpKry1xs', 'error' => 0, 'size' => 427735 ), 
            'image2' => array ( 'name' => '', 'type' => '', 'tmp_name' => '', 'error' => 4, 'size' => 0 ) );
        $normalized = array ( 
            'image0' => array ( 'name' => 'mod_rewrite_cheat_sheet.pdf', 'type' => 'application/pdf', 'tmp_name' => '/tmp/php0JvZ0p', 'error' => 0, 'size' => 332133 ), 
            'image1' => array ( 'name' => 'microformats_cheat_sheet.pdf', 'type' => 'application/pdf', 'tmp_name' => '/tmp/phpKry1xs', 'error' => 0, 'size' => 427735 ));
        
        $this->assertEqual($Request->_getNormalizedFilesArray(), $normalized);
    }

    function test_should_normalize_multi_level_but_flat_file_uploads()
    {
        $Request = new AkRequest();
        $_FILES = array ( 'image' => array ( 'name' => array ( 'file' => array ( 'a' => 'mod_rewrite_cheat_sheet.pdf', 'b' => 'microformats_cheat_sheet.pdf', 'c' => '', ), ), 'type' => array ( 'file' => array ( 'a' => 'application/pdf', 'b' => 'application/pdf', 'c' => '', ), ), 'tmp_name' => array ( 'file' => array ( 'a' => '/tmp/phporGMwx', 'b' => '/tmp/phpGycyd6', 'c' => '', ), ), 'error' => array ( 'file' => array ( 'a' => 0, 'b' => 0, 'c' => 4, ), ), 'size' => array ( 'file' => array ( 'a' => 332133, 'b' => 427735, 'c' => 0, ), ), ), );
        $normalized = array ( 'image' => array ( 'file' => array ( 'a' => array ( 'name' => 'mod_rewrite_cheat_sheet.pdf', 'tmp_name' => '/tmp/phporGMwx', 'size' => 332133, 'type' => 'application/pdf', 'error' => 0, ), 'b' => array ( 'name' => 'microformats_cheat_sheet.pdf', 'tmp_name' => '/tmp/phpGycyd6', 'size' => 427735, 'type' => 'application/pdf', 'error' => 0, ), ), ), );
        
        $this->assertEqual($Request->_getNormalizedFilesArray(), $normalized);
    }

    function test_should_normalize_multi_level_as_array_file_uploads()
    {
        $Request = new AkRequest();
        $_FILES = array ( 'image' => array ( 'name' => array ( 'file' => array ( 0 => 'mod_rewrite_cheat_sheet.pdf', 1 => 'microformats_cheat_sheet.pdf', 2 => '', ), ), 'type' => array ( 'file' => array ( 0 => 'application/pdf', 1 => 'application/pdf', 2 => '', ), ), 'tmp_name' => array ( 'file' => array ( 0 => '/tmp/phpoOcNXs', 1 => '/tmp/php4xVEbv', 2 => '', ), ), 'error' => array ( 'file' => array ( 0 => 0, 1 => 0, 2 => 4, ), ), 'size' => array ( 'file' => array ( 0 => 332133, 1 => 427735, 2 => 0, ), ), ), );
        $normalized = array ( 'image' => array ( 'file' => array ( 0 => array ( 'name' => 'mod_rewrite_cheat_sheet.pdf', 'tmp_name' => '/tmp/phpoOcNXs', 'size' => 332133, 'type' => 'application/pdf', 'error' => 0, ), 1 => array ( 'name' => 'microformats_cheat_sheet.pdf', 'tmp_name' => '/tmp/php4xVEbv', 'size' => 427735, 'type' => 'application/pdf', 'error' => 0, ), ), ), );
        
        $this->assertEqual($Request->_getNormalizedFilesArray(), $normalized);
    }

    function test_should_normalize_simple_level_as_array_file_uploads()
    {
        $Request = new AkRequest();
        $_FILES = array ( 'image' => array ( 'name' => array ( 0 => 'mod_rewrite_cheat_sheet.pdf', 1 => 'microformats_cheat_sheet.pdf', 2 => '', ), 'type' => array ( 0 => 'application/pdf', 1 => 'application/pdf', 2 => '', ), 'tmp_name' => array ( 0 => '/tmp/phpXpfUKA', 1 => '/tmp/phpkB6MnX', 2 => '', ), 'error' => array ( 0 => 0, 1 => 0, 2 => 4, ), 'size' => array ( 0 => 332133, 1 => 427735, 2 => 0, ), ), );
        $normalized = array ('image' => array (0 => array ('name' => 'mod_rewrite_cheat_sheet.pdf','tmp_name' => '/tmp/phpXpfUKA','size' => 332133,'type' => 'application/pdf','error' => 0),1 => array ('name' => 'microformats_cheat_sheet.pdf','tmp_name' => '/tmp/phpkB6MnX','size' => 427735,'type' => 'application/pdf','error' => 0)));
        $this->assertEqual($Request->_getNormalizedFilesArray(), $normalized);
    }

}

ak_test('Test_of_AkRequest_Class');

?>
