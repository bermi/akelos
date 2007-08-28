<?php

class AkActiveRecord_finders_TestCase extends  AkUnitTest
{
    function test_should_find_using_first_id_and_options()
    {
        $this->installAndIncludeModels(array('Tag'));
        
        $Tag =& new Tag();
        
        $One =& $Tag->create(array('name' => 'One'));
        $Two =& $Tag->create(array('name' => 'Two'));
        
        $Found =& $Tag->find('first', $Two->getId(), array('order'=>'name'));
        
        $this->assertEqual($Found->getId(), $Two->getId());
        
    }
}

?>
