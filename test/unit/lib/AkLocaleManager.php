<?php

defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);
require_once(dirname(__FILE__).'/../../fixtures/config/config.php');

require_once(AK_LIB_DIR.DS.'AkLocaleManager.php');

class Test_of_AkLocaleManager_Class extends  AkUnitTest
{

    var $LocaleManager;

    function setUp()
    {
        $this->LocaleManager =& new AkLocaleManager();
    }

    function tearDown()
    {
        unset ($this->LocaleManager);
    }

    function Test_of__getAvailableLocales()
    {
        $available_locales = $this->LocaleManager->_getAvailableLocales();
        $this->assertTrue(is_array($available_locales['en']) && count($available_locales) > 0 ,'Locale en was not found on config/locales folder.');
    }

    function Test_of__parseLocaleConfigString()
    {

        $config_string = 'en';
        $expected = array('en'=>array('en'));
        $result = $this->LocaleManager->_parseLocaleConfigString($config_string);
        $this->assertEqual($expected, $result);

        $config_string = 'en,es ';
        $expected = array('en'=>array('en'),'es'=>array('es'));
        $result = $this->LocaleManager->_parseLocaleConfigString($config_string);
        $this->assertEqual($expected, $result);

        $config_string = 'en; es';
        $expected = array('en'=>array('en'),'es'=>array('es'));
        $result = $this->LocaleManager->_parseLocaleConfigString($config_string);
        $this->assertEqual($expected, $result);

        $config_string = 'en; es (spain)';
        $expected = array('en'=>array('en'),'es'=>array('es','spain'));
        $result = $this->LocaleManager->_parseLocaleConfigString($config_string);
        $this->assertEqual($expected, $result);

        $config_string = 'en; es (spain|espana)';
        $expected = array('en'=>array('en'),'es'=>array('es','spain', 'espana'));
        $result = $this->LocaleManager->_parseLocaleConfigString($config_string);
        $this->assertEqual($expected, $result);

        $config_string = 'es (spain)';
        $expected = array('es'=>array('es','spain'));
        $result = $this->LocaleManager->_parseLocaleConfigString($config_string);
        $this->assertEqual($expected, $result);

        $config_string = 'es (spain|espana)';
        $expected = array('es'=>array('es','spain', 'espana'));
        $result = $this->LocaleManager->_parseLocaleConfigString($config_string);
        $this->assertEqual($expected, $result);


    }



    function Test_of__getBrowserLanguage()
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = $this->LocaleManager->_browser_language = 'en-us,en,es-es;q=0.5;';
        
        unset($_SERVER['HTTP_USER_AGENT']);
        $this->LocaleManager->available_locales = array('en_us'=>'en_us','en'=>'en','es_es'=>'es_es');
        $expected = array_keys($this->LocaleManager->available_locales);
        $result = $this->LocaleManager->_getBrowserLanguage();
        $this->assertEqual($expected, $result);

        unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);
        unset($_SERVER['HTTP_USER_AGENT']);
        $this->LocaleManager->available_locales = array('en'=>'en');
        $expected = array_keys($this->LocaleManager->available_locales);
        $result = $this->LocaleManager->_getBrowserLanguage();
        $this->assertEqual($expected, $result);

        $this->LocaleManager->available_locales = array('en_us'=>'en_us');
        $expected = array_keys($this->LocaleManager->available_locales);
        $result = $this->LocaleManager->_getBrowserLanguage();
        $this->assertEqual($expected, $result);

        unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);
        $_SERVER['HTTP_USER_AGENT'] = $this->LocaleManager->_browser_language = 
        'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.7.10) Gecko/20050716 Firefox/1.0.6';
        $this->LocaleManager->available_locales = array('es'=>'es','en'=>'en','en_us'=>'en_us');
        $expected = array('en_us');
        $result = $this->LocaleManager->_getBrowserLanguage();
        $this->assertEqual($expected, $result);

    }


    function Test_of_getDefaultLanguageForUser()
    {
        unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);
        unset($_SERVER['HTTP_USER_AGENT']);
        $this->LocaleManager->available_locales = array('en_us'=>array('en_us'),'en'=>array('en'),'es_es'=>array('es_es'));
        $this->LocaleManager->browser_lang = $this->LocaleManager->_getBrowserLanguage();
        $result = $this->LocaleManager->getDefaultLanguageForUser();
        $expected = 'en_us';
        $this->assertEqual($expected, $result);

        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.7.10) Gecko/20050716 Firefox/1.0.6';
        $this->LocaleManager->available_locales = array('en'=>array('en'),'en_us'=>array('en_us'),'es_es'=>array('es_es'));
        $this->LocaleManager->browser_lang = $this->LocaleManager->_getBrowserLanguage();
        $result = $this->LocaleManager->getDefaultLanguageForUser();
        $expected = 'en_us';
        $this->assertEqual($expected, $result);

        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'es-es,en-us,en;q=0.5;';
        $this->LocaleManager->available_locales = array('en'=>array('en'),'en_us'=>array('en_us'),'es_es'=>array('es_es'));
        $this->LocaleManager->browser_lang = $this->LocaleManager->_getBrowserLanguage();
        $result = $this->LocaleManager->getDefaultLanguageForUser();
        $expected = 'es_es';
        $this->assertEqual($expected, $result);

        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-us,en;q=0.5;';
        $this->LocaleManager->available_locales = array('es_es'=>array('es_es'));
        $this->LocaleManager->browser_lang = $this->LocaleManager->_getBrowserLanguage();
        $result = $this->LocaleManager->getDefaultLanguageForUser();
        $expected = 'es_es';
        $this->assertEqual($expected, $result);

    }

    function Test_of__getDefaultLocale()
    {
        $this->LocaleManager->available_locales = array('es_es'=>array('es_es'));
        $result = $this->LocaleManager->_getDefaultLocale();
        $expected = 'es_es';
        $this->assertEqual($expected, $result);

        $this->LocaleManager->available_locales = array('es_es'=>'es_es');
        $result = $this->LocaleManager->_getDefaultLocale();
        $expected = 'es_es';
        $this->assertEqual($expected, $result);

        $this->LocaleManager->available_locales = array('es_es');
        $result = $this->LocaleManager->_getDefaultLocale();
        $expected = 'es_es';
        $this->assertEqual($expected, $result);

        $this->LocaleManager->available_locales = array('en'=>array('en'),'es_es'=>array('es_es'));
        $result = $this->LocaleManager->_getDefaultLocale();
        $expected = 'en';
        $this->assertEqual($expected, $result);
        $result = $this->LocaleManager->_getDefaultLocale();
        $expected = 'en';
        $this->assertEqual($expected, $result);

    }


    function Test_of_getLangFromUrl()
    {
        $Request = new AkObject();

        $Request->ak = 'en';
        $Request->_request['ak'] = 'en';
        $this->LocaleManager->available_locales = array('en'=>array('en'),'es_es'=>array('es_es','spain'));

        $expected = 'en';

        $result = $this->LocaleManager->getLangFromUrl($Request);
        $this->assertEqual($expected, $result);
        $this->assertEqual($Request->_request['ak'],'');
        $this->assertEqual($Request->ak,'');

        $Request->ak = '/en/';
        $Request->_request['ak'] = '/en/';

        $result = $this->LocaleManager->getLangFromUrl($Request);
        $this->assertEqual($expected, $result);
        $this->assertEqual($Request->_request['ak'],'');
        $this->assertEqual($Request->ak,'');

        $Request->ak = 'en/post';
        $Request->_request['ak'] = 'en/post';

        $result = $this->LocaleManager->getLangFromUrl($Request);
        $this->assertEqual($expected, $result);

        $this->assertEqual($Request->_request['ak'],'post');
        $this->assertEqual($Request->ak,'post');

        $Request->ak = 'fr/post';
        $Request->_request['ak'] = 'fr/post';
        unset($Request->lang);

        $result = $this->LocaleManager->getLangFromUrl($Request);
        $this->assertFalse($result);

        $this->assertEqual($Request->_request['ak'],'fr/post');
        $this->assertEqual($Request->ak,'fr/post');

        $Request->ak = 'post/es_es';
        $Request->_request['ak'] = 'post/es_es';
        unset($Request->lang);

        $result = $this->LocaleManager->getLangFromUrl($Request);
        $this->assertFalse($result);

        $this->assertEqual($Request->_request['ak'],'post/es_es');
        $this->assertEqual($Request->ak,'post/es_es');

        $Request->ak = 'es/post';
        $Request->_request['ak'] = 'es/post';
        unset($Request->lang);

        $result = $this->LocaleManager->getLangFromUrl($Request);
        $this->assertFalse($result);

        $this->assertEqual($Request->_request['ak'],'es/post');
        $this->assertEqual($Request->ak,'es/post');
        
        $Request->ak = 'spain/people';
        $Request->_request['ak'] = 'spain/people';
        unset($Request->lang);

        $result = $this->LocaleManager->getLangFromUrl($Request);
        $expected = 'spain';
        $this->assertEqual($result, $expected);

        $this->assertEqual($Request->_request['ak'],'people');
        $this->assertEqual($Request->ak,'people');
        
        //Falta devolver IDIOMA y no alias y cargar $request->lang

    }


    function Test_of_getLocaleFromAlias()
    {
        $this->LocaleManager->available_locales = $this->LocaleManager->_parseLocaleConfigString('es, en, fr (france)');
        $result = $this->LocaleManager->getLocaleFromAlias('france');
        $expected = 'fr';
        $this->assertEqual($result,$expected);
        
        $result = $this->LocaleManager->getLocaleFromAlias('spain');
        $this->assertFalse($result);
        
    }

}

ak_test('Test_of_AkLocaleManager_Class');


?>
