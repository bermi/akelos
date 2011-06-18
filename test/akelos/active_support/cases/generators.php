<?php

require_once(dirname(__FILE__).'/../config.php');

class ControllerGenerator_TestCase extends ActiveSupportUnitTest
{
    public function __construct(){
        parent::__construct();
        $this->webserver_enabled = AkConfig::getOption('webserver_enabled', false);
    }
    
    public function runGeneratorCommand($command){
        ob_start();
        $Generator = new AkelosGenerator();
        $Generator->setFileOptions(array('base_path'=>AK_FRAMEWORK_DIR));
        $Generator->runCommand($command);
        $result = ob_get_clean();
        //AK::trace($result);
        return $result;
    }

    public function skip(){
        $this->skipIf(!$this->webserver_enabled, '['.get_class($this).'] Web server not enabled');
    }

    public function test_should_generate_controller_and_auxiliary_files(){
        
        $files = array(
        AkConfig::getDir('controllers').DS.'credit_card_controller.php',
        AkConfig::getDir('helpers').DS.'credit_card_helper.php',
        AkConfig::getDir('test').DS.'functional'.DS.'controllers'.DS.'credit_card_controller_test.php',
        AkConfig::getDir('test').DS.'unit'.DS.'helpers'.DS.'credit_card_helper_test.php',

        AkConfig::getDir('views').DS.'credit_card'.DS.'open.html.tpl',
        AkConfig::getDir('views').DS.'credit_card'.DS.'debit.html.tpl',
        AkConfig::getDir('views').DS.'credit_card'.DS.'credit.html.tpl',
        AkConfig::getDir('views').DS.'credit_card'.DS.'close.html.tpl');

        clearstatcache();
        foreach ($files as $file){
            file_exists($file) && unlink($file);
            $this->assertFalse(file_exists($file));
        }

        AkFileSystem::file_put_contents(AkConfig::getDir('views').DS.'credit_card'.DS.'credit.html.tpl', 'foo', array('base_path'=>AK_FRAMEWORK_DIR));
        clearstatcache();

        $this->assertPattern('/collisions/', $this->runGeneratorCommand('controller CreditCard open debit credit close'));

        AkFileSystem::file_delete(AkConfig::getDir('views').DS.'credit_card'.DS.'credit.html.tpl', array('base_path'=>AK_FRAMEWORK_DIR));

        clearstatcache();
        foreach ($files as $file){
            $this->assertFalse(file_exists($file));
        }

        $this->assertPattern('/ files have been created/', $this->runGeneratorCommand('controller CreditCard open debit credit close'));

        clearstatcache();
        foreach ($files as $file){
            $this->assertTrue(file_exists($file));
            if(!file_exists($file)){
                AkDebug::trace($file);
            }
            @unlink($file);
        }
    }
}

ak_test_case('ControllerGenerator_TestCase');
