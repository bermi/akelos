<?php

require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

class _AkActionController_locale_detection extends AkWebTestCase 
{

    public function test_request_LocaleDetectionController()
    {
        $this->setMaximumRedirects(0);
        $this->get(AK_TESTING_URL.'/locale_detection');
        $this->assertResponse(200);
        $this->assertTextMatch('Hello from LocaleDetectionController');

    }
    
    public function test_Language_header_detection()
    {
        $this->addHeader('Accept-Language: es,en-us,en;q=0.5');
        $this->get(AK_TESTING_URL.'/locale_detection/check_header');
        $this->assertTextMatch('es,en-us,en;q=0.5');
    }
    
    public function test_detect_default_language()
    {
        $this->addHeader('Accept-Language: es,en-us,en;q=0.5');
        $this->get(AK_TESTING_URL.'/locale_detection/get_language');
        $this->assertTextMatch('es'); 
    }
        
    public function test_session_are_working()
    {
        $this->get(AK_TESTING_URL.'/locale_detection/session/1234');
        $this->assertTextMatch('1234'); 
        
        $this->get(AK_TESTING_URL.'/locale_detection/session/');
        $this->assertTextMatch('1234'); 
    }
    
    public function test_session_are_fresh_on_new_request()
    {
        $this->get(AK_TESTING_URL.'/locale_detection/session/');
        $this->assertNoText('1234'); 
    }
    
    public function test_language_change()
    {
        $this->assertEqual( array('en','es'), Ak::langs() );
        
        $this->addHeader('Accept-Language: es,en-us,en;q=0.5');
        
        $this->get(AK_TESTING_URL.'/locale_detection/get_language');
        $this->assertTextMatch('es');
        
        $this->get(AK_TESTING_URL.'/locale_detection/get_param/?param=message&message=Hello');
        $this->assertTextMatch('Hello');
        
        $this->get(AK_TESTING_URL.'/locale_detection/get_param/?param=lang&lang=en');
        $this->assertTextMatch('en');
        
        $this->get(AK_TESTING_URL.'/locale_detection/get_language/?lang=en');
        $this->assertTextMatch('en');

        $this->get(AK_TESTING_URL.'/locale_detection/get_language');
        $this->assertTextMatch('en');
        
        $this->get(AK_TESTING_URL.'/locale_detection/get_language/?lang=invalid');
        $this->assertTextMatch('en');
        
    }
    
    public function test_language_change_on_ak()
    {
        $this->assertEqual( array('en','es'), Ak::langs() );
        
        $this->addHeader('Accept-Language: es,en-us,en;q=0.5');
        
        $this->get(AK_TESTING_URL.'/locale_detection/get_language');
        $this->assertTextMatch('es');
        
        $this->get(AK_TESTING_URL.'/en/locale_detection/get_language/');
        $this->assertTextMatch('en');

        $this->get(AK_TESTING_URL.'/locale_detection/get_language');
        $this->assertTextMatch('en');
    }    
}

ak_test('_AkActionController_locale_detection');

?>
