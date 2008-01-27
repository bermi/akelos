<?php 

class AkTestMember extends AkTestUser 
{ 
    function AkTestMember()
    {
        $this->setTableName("ak_test_members");
        $this->init(@(array)func_get_args());
    }
}

?>