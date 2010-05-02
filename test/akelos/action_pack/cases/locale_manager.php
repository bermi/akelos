<?php

require_once(dirname(__FILE__).'/../config.php');

class LocaleManager_TestCase extends ActionPackUnitTest
{
    public $LocaleManager;
    public $original_locales = array();

    public function __construct() {
        parent::__construct();
        foreach (glob(AkConfig::getDir('config').'/locales/*.php') as $file){
            $this->original_locales[$file] = file_get_contents($file);
        }
    }

    public function __destruct() {
        parent::__destruct();
        foreach ($this->original_locales as $file => $content){
            file_put_contents($file, $content);
        }
    }

    public function setUp() {
        $this->LocaleManager = new AkLocaleManager();
    }

    public function tearDown() {
        unset ($this->LocaleManager);
    }

    public function test_should_get_available_locales() {
        $available_locales = $this->LocaleManager->getAvailableLocales();
        $this->assertTrue(is_array($available_locales['en']) && count($available_locales) > 0 ,'Locale en was not found on config/locales folder.');
    }

    public function test_should_parse_locale_strings() {

        $config_string = 'en';
        $expected = array('en'=>array('en'));
        $result = $this->LocaleManager->parseLocaleConfigString($config_string);
        $this->assertEqual($expected, $result);

        $config_string = 'en,es ';
        $expected = array('en'=>array('en'),'es'=>array('es'));
        $result = $this->LocaleManager->parseLocaleConfigString($config_string);
        $this->assertEqual($expected, $result);

        $config_string = 'en; es';
        $expected = array('en'=>array('en'),'es'=>array('es'));
        $result = $this->LocaleManager->parseLocaleConfigString($config_string);
        $this->assertEqual($expected, $result);

        $config_string = 'en; es (spain)';
        $expected = array('en'=>array('en'),'es'=>array('es','spain'));
        $result = $this->LocaleManager->parseLocaleConfigString($config_string);
        $this->assertEqual($expected, $result);

        $config_string = 'en; es (spain|espana)';
        $expected = array('en'=>array('en'),'es'=>array('es','spain', 'espana'));
        $result = $this->LocaleManager->parseLocaleConfigString($config_string);
        $this->assertEqual($expected, $result);

        $config_string = 'es (spain)';
        $expected = array('es'=>array('es','spain'));
        $result = $this->LocaleManager->parseLocaleConfigString($config_string);
        $this->assertEqual($expected, $result);

        $config_string = 'es (spain|espana)';
        $expected = array('es'=>array('es','spain', 'espana'));
        $result = $this->LocaleManager->parseLocaleConfigString($config_string);
        $this->assertEqual($expected, $result);


    }

    public function test_should_get_browser_language() {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = $this->LocaleManager->_browser_language = 'en-us,en,es-es;q=0.5;';

        $this->LocaleManager->available_locales = array('en_us'=>'en_us','en'=>'en','es_es'=>'es_es');
        $expected = array_keys($this->LocaleManager->available_locales);
        $result = $this->LocaleManager->getBrowserLanguages();
        $this->assertEqual($expected, $result);

        unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);
        $this->LocaleManager->available_locales = array('en'=>'en');
        $expected = array_keys($this->LocaleManager->available_locales);
        $result = $this->LocaleManager->getBrowserLanguages();
        $this->assertEqual($expected, $result);

        $this->LocaleManager->available_locales = array('en_us'=>'en_us');
        $expected = array_keys($this->LocaleManager->available_locales);
        $result = $this->LocaleManager->getBrowserLanguages();
        $this->assertEqual($expected, $result);

    }

    public function test_should_get_default_language_for_user() {
        unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);
        $this->LocaleManager->available_locales = array('en_us'=>array('en_us'),'en'=>array('en'),'es_es'=>array('es_es'));
        $this->LocaleManager->browser_lang = $this->LocaleManager->getBrowserLanguages();
        $result = $this->LocaleManager->getDefaultLanguageForUser();
        $expected = 'en_us';
        $this->assertEqual($expected, $result);

        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'es-es,en-us,en;q=0.5;';
        $this->LocaleManager->available_locales = array('en'=>array('en'),'en_us'=>array('en_us'),'es_es'=>array('es_es'));
        $this->LocaleManager->browser_lang = $this->LocaleManager->getBrowserLanguages();
        $result = $this->LocaleManager->getDefaultLanguageForUser();
        $expected = 'es_es';
        $this->assertEqual($expected, $result);

        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-us,en;q=0.5;';
        $this->LocaleManager->available_locales = array('es_es'=>array('es_es'));
        $this->LocaleManager->browser_lang = $this->LocaleManager->getBrowserLanguages();
        $result = $this->LocaleManager->getDefaultLanguageForUser();
        $expected = 'es_es';
        $this->assertEqual($expected, $result);

    }

    public function test_should_get_default_locale() {
        $this->LocaleManager->available_locales = array('es_es'=>array('es_es'));
        $result = $this->LocaleManager->getDefaultLocale();
        $expected = 'es_es';
        $this->assertEqual($expected, $result);

        $this->LocaleManager->available_locales = array('es_es'=>'es_es');
        $result = $this->LocaleManager->getDefaultLocale();
        $expected = 'es_es';
        $this->assertEqual($expected, $result);

        $this->LocaleManager->available_locales = array('es_es');
        $result = $this->LocaleManager->getDefaultLocale();
        $expected = 'es_es';
        $this->assertEqual($expected, $result);

        $this->LocaleManager->available_locales = array('en'=>array('en'),'es_es'=>array('es_es'));
        $result = $this->LocaleManager->getDefaultLocale();
        $expected = 'en';
        $this->assertEqual($expected, $result);
        $result = $this->LocaleManager->getDefaultLocale();
        $expected = 'en';
        $this->assertEqual($expected, $result);

    }

    public function test_should_get_language_from_url() {
        $Request = new stdClass();

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
    }

    public function test_should_get_locale_from_alias() {
        $this->LocaleManager->available_locales = $this->LocaleManager->parseLocaleConfigString('es, en, fr (france)');
        $result = $this->LocaleManager->getLocaleFromAlias('france');
        $expected = 'fr';
        $this->assertEqual($result,$expected);

        $result = $this->LocaleManager->getLocaleFromAlias('spain');
        $this->assertFalse($result);
    }

    public function test_locale_setting_getting_deleting_methods() {
        !defined('AK_TEST_TRANSLATIONS') ? define('AK_TEST_TRANSLATIONS',true):null;
        $translation_key = Ak::randomString(8);
        $namespace = Ak::randomString(8);
        $translation=Ak::t($translation_key,null,$namespace);
        $this->assertEqual($translation_key,$translation);

        $locale_files = AkLocaleManager::updateLocaleFiles();
        $dictionary = AkLocaleManager::getDictionary(AK_FRAMEWORK_LANGUAGE,$namespace);
        $this->assertEqual(array($translation_key=>$translation_key),$dictionary);

        $dictionary[$translation_key] = 'Spanish';
        AkLocaleManager::setDictionary($dictionary,'es',$namespace);
        $dictionary = AkLocaleManager::getDictionary('es',$namespace);
        $this->assertEqual(array($translation_key=>'Spanish'),$dictionary);

        Ak::t('dummy',null,$namespace);
        $locale_files = array_merge($locale_files, AkLocaleManager::updateLocaleFiles());
        $dictionary = AkLocaleManager::getDictionary(AK_FRAMEWORK_LANGUAGE,$namespace);
        $this->assertEqual(array($translation_key=>$translation_key,'dummy'=>'dummy'),$dictionary);

        $this->assertTrue(AkLocaleManager::deleteDictionary(AK_FRAMEWORK_LANGUAGE,$namespace));
        $this->assertEqual(array(),AkLocaleManager::getDictionary(AK_FRAMEWORK_LANGUAGE,$namespace));
        foreach ($locale_files as $locale_file){
            AkFileSystem::file_delete($locale_file);
        }
    }

    public function test_framework_config_locale_update() {
        $langs=Ak::langs();
        $translation_key=Ak::randomString(8);
        $this->assertEqual(Ak::t($translation_key),$translation_key);
        $locale_files = AkLocaleManager::updateLocaleFiles();
        list($locales,$core_dictionary) = AkLocaleManager::getCoreDictionary(AK_FRAMEWORK_LANGUAGE);
        $this->assertTrue(isset($core_dictionary[$translation_key]));

        foreach($langs as $lang) {
            list($locales,$core_dictionary) = AkLocaleManager::getCoreDictionary($lang);
            $this->assertTrue(isset($core_dictionary[$translation_key]));
        }
        foreach ($locale_files as $locale_file){
            AkFileSystem::file_delete($locale_file);
        }
    }

}

ak_test_case('LocaleManager_TestCase');

