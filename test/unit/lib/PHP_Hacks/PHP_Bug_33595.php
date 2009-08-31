<?php

defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);
require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

require_once(AK_LIB_DIR.DS.'AkProfiler.php');

class PHP_Bug_33595_A extends AkObject{
    public $two = null;
    public $data = null;
}


class PHP_Bug_33595_B extends AkObject{
    public $one = null;
}


class PHP_Bug_33595_TestCase extends AkUnitTest
{

    public function test_should_increase_memory()
    {
        $this->log_memory(true);

        for($i = 0; $i<2; $i++) {
            $this->instantiate_grow_and_unset(false);
            $this->log_memory();
        }
        $bytes = $this->log_memory();
        $this->assertTrue($bytes > 100000, 'PHP_Bug_33595 not detected, 
        memory increase was '.$bytes.' bytes but should be higher than 100000 bytes'); 

    }

    public function test_should_not_increase_memory()
    {
        $this->log_memory(true);

        for($i = 0; $i<20; $i++) {
            $this->instantiate_grow_and_unset();
            $this->log_memory();
        }
        $bytes = $this->log_memory();
        $this->assertTrue($bytes < 500, 'PHP_Bug_33595 not fixed, 
        memory increase was '.$bytes.' bytes but should be lower than 500 bytes'); 
    }
    
    public function instantiate_grow_and_unset($use_free_memory_hack = true)
    {
        $One = new PHP_Bug_33595_A();
        $Two = new PHP_Bug_33595_B();

        $One->two =& $Two;
        $Two->one =& $One;

        $One->data = str_repeat('One',10000);
        $Two->data = str_repeat('Two',10000);

        if($use_free_memory_hack){
            $One->freeMemory();
            $Two->freeMemory();
        }

        unset($One);
        unset($Two);
    }

    public function log_memory($reset = false, $vervose = false)
    {
        ($reset || empty($this->initial)) && $this->initial = memory_get_usage();
        $this->current = memory_get_usage();
        $this->difference = $this->current - $this->initial;
        $this->difference && $vervose && Ak::trace(($this->difference/1048576).' MB increased');
        return $this->difference;
    }
}

?>