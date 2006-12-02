<?php

defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);
require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');


require_once(AK_LIB_DIR.DS.'AkActionWebservice'.DS.'AkActionWebserviceApi.php');
require_once(AK_APIS_DIR.DS.'todo_api.php');


class AkActionWebServiceApiTests extends  UnitTestCase
{

    function test_web_service_api()
    {
        $TodoApi =& new TodoApi();

        // hasApiMethod
        $this->assertTrue($TodoApi->hasApiMethod('create list'));
        $this->assertFalse($TodoApi->hasApiMethod('call the queen'));
        $this->assertFalse($TodoApi->hasApiMethod('CreateList'));
        
        // hasPublicApiMethod
        $this->assertTrue($TodoApi->hasPublicApiMethod('CreateList'));
        $this->assertFalse($TodoApi->hasPublicApiMethod('create list'));
        $this->assertFalse($TodoApi->hasPublicApiMethod('CallAlice'));

        // getPublicApiMethodName
        $this->assertEqual($TodoApi->getPublicApiMethodName('call ali') , 'CallAli');
        $TodoApi->inflect_names = false;
        $this->assertEqual($TodoApi->getPublicApiMethodName('call ali') , 'call ali');
        $TodoApi->inflect_names = true;
        
        // getApiMethodName
        $this->assertEqual($TodoApi->getApiMethodName('CreateList'), 'create list');
        
        $api_methods =& $TodoApi->getApiMethods();
        $methods = array_keys($api_methods);
                
        foreach ($methods as $method_name){
            $this->assertEqual(strtolower(get_class($api_methods[$method_name])), 'akactionwebservicemethod');
            
            $this->assertReference($api_methods[$method_name], $TodoApi->getPublicApiMethodInstance($TodoApi->getPublicApiMethodName($method_name)));
            $this->assertReference($api_methods[$method_name], $TodoApi->getApiMethodInstance($method_name));
            
        }
        
        $this->assertFalse($TodoApi->getDefaultApiMethodInstance());
        
        $TodoApi->default_api_method = $method_name;
        $TodoApi->default_api_method_instance =& $api_methods[$method_name];
        $this->assertReference($api_methods[$method_name], $TodoApi->getDefaultApiMethodInstance());
        
        $TodoApi->default_api_method = $methods[0];
        $TodoApi->default_api_method_instance = false;
        $ApiInstance = $TodoApi->getDefaultApiMethodInstance();
        $this->assertEqual($api_methods[$TodoApi->default_api_method]->name, $ApiInstance->name);
        
        
        $this->assertEqual($TodoApi->_getApiPublicMethodNames(), array_map(array($TodoApi, 'getPublicApiMethodName'), $methods));
        
        //echo "<pre>".print_r($TodoApi,true)."</pre>";
    }
}


Ak::test('AkActionWebServiceApiTests',true);

?>
