<?php

defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);

require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

class test_AkActiveRecord_i18n extends  AkUnitTest
{
    function test_start()
    {
        $this->installAndIncludeModels(array('Article'));
    }


    function test_multilingual_setting()
    {
        $Article = new Article();
        
        $Article->set('headline', array(
                'en'=>'New PHP Framework released', 
                'es'=>'Se ha liberado un nuevo Framework para PHP'));
                
        $Article->set('body', array(
                'en'=>'The Akelos Framework has been released...', 
                'es'=>'Un equipo de programadores españoles ha lanzado un entorno de desarrollo para PHP...'));
                
        $Article->set('excerpt_limit', array('en'=> 7, 'es'=> 3));
        
        $this->assertTrue($Article->save());
        
        $Article = $Article->find($Article->getId());
        $this->assertEqual($Article->get('en_headline'), 'New PHP Framework released');
        $this->assertEqual($Article->get('es_body'), 'Un equipo de programadores españoles ha lanzado un entorno de desarrollo para PHP...');
        $this->assertEqual($Article->get('en_excerpt_limit'), 7);

    }
    

    function test_multilingual_setting_by_reference()
    {
        $Article =& new Article();
        
        $Article->set('headline', array(
                'en'=>'New PHP Framework re-released', 
                'es'=>'Se ha re-liberado un nuevo Framework para PHP'));
                
        $Article->set('body', array(
                'en'=>'The Akelos Framework has been re-released...', 
                'es'=>'Un equipo de programadores españoles ha re-lanzado un entorno de desarrollo para PHP...'));
                
        $Article->set('excerpt_limit', array('en'=> 7, 'es'=> 3));
        
        $this->assertTrue($Article->save());
        
        $Article =& $Article->find($Article->getId());
        $this->assertEqual($Article->get('en_headline'), 'New PHP Framework re-released');
        $this->assertEqual($Article->get('es_body'), 'Un equipo de programadores españoles ha re-lanzado un entorno de desarrollo para PHP...');
        $this->assertEqual($Article->get('en_excerpt_limit'), 7);

    }
    
    function test_multilingual_getting_an_specific_locale()
    {
        $Article =& new Article();
        $this->assertTrue($Article =& $Article->findFirstBy('en_headline', 'New PHP Framework released'));
        
        $this->assertEqual($Article->get('excerpt_limit', 'en'), 7);
        $this->assertEqual($Article->get('excerpt_limit', 'es'), 3);
        $this->assertEqual($Article->getAttribute('excerpt_limit', 'en'), 7);
        $this->assertEqual($Article->getAttribute('excerpt_limit', 'es'), 3);
        

        $this->assertEqual($Article->get('headline', 'en'), 'New PHP Framework released');
        $this->assertEqual($Article->get('headline', 'es'), 'Se ha liberado un nuevo Framework para PHP');
        $this->assertEqual($Article->getAttribute('headline', 'en'), 'New PHP Framework released');
        $this->assertEqual($Article->getAttribute('headline', 'es'), 'Se ha liberado un nuevo Framework para PHP');
        $this->assertEqual($Article->get('headline'), 'New PHP Framework released');
        $this->assertEqual($Article->getAttribute('headline'), 'New PHP Framework released');
        
        $this->assertEqual($Article->getAttributeLocales('headline'), array('en'=>'New PHP Framework released', 'es'=>'Se ha liberado un nuevo Framework para PHP'));
        
        
    }
    
    function test_multilingual_setting_an_specific_locale()
    {
        $Article =& new Article();
        $Article->set('headline','Happiness on developers boost productivity', 'en');
        $Article->set('headline','La felicidad de los programadores mejora la productivdad', 'es');
        
        $this->assertEqual($Article->en_headline,'Happiness on developers boost productivity');
        $this->assertEqual($Article->es_headline,'La felicidad de los programadores mejora la productivdad');
        
    }
}

Ak::test('test_AkActiveRecord_i18n',true);

?>