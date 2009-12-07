<?php

class AkTestMember extends AkTestUser
{
    public function __construct() {
        $this->setTableName("ak_test_members");
        parent::__construct(@(array)func_get_args());
    }
}

